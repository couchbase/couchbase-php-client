/**
 * Copyright 2016-Present Couchbase, Inc.
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *     http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */

#include "wrapper.hxx"

#include "common.hxx"
#include "connection_handle.hxx"
#include "transactions_resource.hxx"

#include <core/logger/logger.hxx>
#include <couchbase/error_codes.hxx>
#include <couchbase/fork_event.hxx>

#include <spdlog/fmt/bundled/chrono.h>

#include <vector>

namespace couchbase::php
{

namespace
{
int persistent_connection_destructor_id_{ 0 };

/* the persistent list is per thread, and so are the detached entries in it */
thread_local std::vector<zend_resource*> detached_connections_{};

auto
detached_key(const zend_resource* res) -> std::string
{
  return fmt::format("couchbase_detached_connection:{}", static_cast<const void*>(res));
}
} // namespace

COUCHBASE_API
void
set_persistent_connection_destructor_id(int id)
{
  persistent_connection_destructor_id_ = id;
}

COUCHBASE_API
auto
get_persistent_connection_destructor_id() -> int
{
  return persistent_connection_destructor_id_;
}

COUCHBASE_API
auto
check_persistent_connection(zval* zv, void* expire_ptr) -> int
{
  zend_resource* res = Z_RES_P(zv);

  if (res->type != persistent_connection_destructor_id_) {
    return ZEND_HASH_APPLY_KEEP;
  }

  const auto* handle = static_cast<connection_handle*>(res->ptr);
  if (handle == nullptr || handle->detached()) {
    /* freed by sweep_detached_connections() */
    return ZEND_HASH_APPLY_KEEP;
  }

  if (!*static_cast<const bool*>(expire_ptr)) {
    return ZEND_HASH_APPLY_KEEP;
  }

  auto now = std::chrono::system_clock::now();
  if (handle->is_expired(now)) {
    if (GC_REFCOUNT(res) == 0 && !handle->has_dependents()) {
      /* connection has timed out */
      return ZEND_HASH_APPLY_REMOVE;
    }

    const std::string connection_string = handle->connection_string();
    const std::string connection_hash = handle->connection_hash();
    const auto expires_at = handle->expires_at();
    CB_LOG_DEBUG("persistent connection expired, but the application still uses it: handle={}, "
                 "connection_hash={}, connection_string=\"{}\", expires_at=\"{}\" ({}), "
                 "destructor_id={}, refcount={}, num_persistent={}",
                 static_cast<const void*>(handle),
                 connection_hash,
                 connection_string,
                 expires_at,
                 (expires_at - now),
                 res->type,
                 GC_REFCOUNT(res),
                 COUCHBASE_G(num_persistent));
  }
  return ZEND_HASH_APPLY_KEEP;
}

/*
 * The last PHP value releasing a persistent resource does not destroy it, so without this a
 * detached connection would keep its sockets open until request shutdown.
 */
COUCHBASE_API
void
sweep_detached_connections()
{
  for (auto it = detached_connections_.begin(); it != detached_connections_.end();) {
    if (const auto* handle = static_cast<connection_handle*>((*it)->ptr);
        GC_REFCOUNT(*it) > 0 || (handle != nullptr && handle->has_dependents())) {
      ++it;
      continue;
    }
    auto key = detached_key(*it);
    it = detached_connections_.erase(it);
    zend_hash_str_del(&EG(persistent_list), key.data(), key.size());
  }
}

COUCHBASE_API
auto
create_persistent_connection(zend_string* connection_hash,
                             zend_string* connection_string,
                             zval* options) -> std::pair<zend_resource*, core_error_info>
{
  sweep_detached_connections();

  connection_handle* handle = nullptr;
  zend_resource* res = nullptr;
  bool found = false;

  if (zval* entry = zend_hash_find(&EG(persistent_list), connection_hash); entry != nullptr) {
    found = true;
    res = Z_RES_P(entry);
    if (res->type == persistent_connection_destructor_id_) {
      handle = static_cast<connection_handle*>(res->ptr);
    }
  }
  auto now = std::chrono::system_clock::now();
  auto expires_at = COUCHBASE_G(persistent_timeout) > 0
                      ? now + std::chrono::milliseconds(COUCHBASE_G(persistent_timeout))
                      : now;
  if (handle != nullptr) {
    handle->expires_at(expires_at);
    auto old_refcount = GC_REFCOUNT(res);
    GC_ADDREF(res);
    CB_LOG_DEBUG(
      "persistent connection hit: handle={}, connection_hash={}, connection_string=\"{}\", "
      "expires_at=\"{}\" ({}), destructor_id={}, refcount={}->{}",
      static_cast<const void*>(handle),
      ZSTR_VAL(connection_hash),
      ZSTR_VAL(connection_string),
      expires_at,
      (expires_at - now),
      res->type,
      old_refcount,
      GC_REFCOUNT(res));
    return { res, {} };
  }
  if (found) {
    /* found something, which is not our resource */
    CB_LOG_DEBUG("persistent connection hit, but handle=nullptr: connection_hash={}, "
                 "connection_string=\"{}\", refcount={}, destructor_id={} (!= {})",
                 ZSTR_VAL(connection_hash),
                 ZSTR_VAL(connection_string),
                 GC_REFCOUNT(res),
                 res->type,
                 persistent_connection_destructor_id_);
    zend_hash_del(&EG(persistent_list), connection_hash);
  }

  if (COUCHBASE_G(persistent_timeout) >= 0 && COUCHBASE_G(max_persistent) >= 0 &&
      COUCHBASE_G(num_persistent) >= COUCHBASE_G(max_persistent)) {
    /* try to find an idle connection and kill it */
    CB_LOG_DEBUG(
      "cleanup idle connections. max_persistent({}) != -1, num_persistent({}) >= max_persistent",
      COUCHBASE_G(max_persistent),
      COUCHBASE_G(num_persistent));
  } else {
    CB_LOG_DEBUG("don't cleanup idle connections. couchbase.persistent_timeout={}, "
                 "couchbase.max_persistent={}, num_persistent={}",
                 COUCHBASE_G(persistent_timeout),
                 COUCHBASE_G(max_persistent),
                 COUCHBASE_G(num_persistent));
  }

  core_error_info rc;
  std::tie(handle, rc) =
    create_connection_handle(connection_string, connection_hash, options, expires_at);
  if (rc.ec) {
    CB_LOG_DEBUG("persistent connection miss, failed to create new connection: rc={} ({}), "
                 "connection_hash={}, connection_string=\"{}\", "
                 "destructor_id={}",
                 rc.ec.message(),
                 rc.message,
                 ZSTR_VAL(connection_hash),
                 ZSTR_VAL(connection_string),
                 persistent_connection_destructor_id_);
    return { nullptr, rc };
  }

  if (rc = handle->open(); rc.ec) {
    CB_LOG_DEBUG("persistent connection miss, failed to open new connection: rc={} ({}), "
                 "connection_hash={}, connection_string=\"{}\", "
                 "destructor_id={}",
                 rc.ec.message(),
                 rc.message,
                 ZSTR_VAL(connection_hash),
                 ZSTR_VAL(connection_string),
                 persistent_connection_destructor_id_);
    delete handle;
    return { nullptr, rc };
  }
  res = zend_register_persistent_resource_ex(
    zend_string_dup(connection_hash, true), handle, persistent_connection_destructor_id_);
  auto current_persistent = ++COUCHBASE_G(num_persistent);
  CB_LOG_DEBUG("persistent connection miss, created new connection: handle={}, connection_hash={}, "
               "connection_string=\"{}\", expires_at=\"{}\" ({}), destructor_id={}, refcount={}, "
               "num_persistent={}",
               static_cast<const void*>(handle),
               ZSTR_VAL(connection_hash),
               ZSTR_VAL(connection_string),
               expires_at,
               (expires_at - now),
               res->type,
               GC_REFCOUNT(res),
               current_persistent);
  return { res, {} };
}

COUCHBASE_API
void
destroy_persistent_connection(zend_resource* res)
{
  if (res->type == persistent_connection_destructor_id_ && res->ptr != nullptr) {
    auto* handle = static_cast<connection_handle*>(res->ptr);
    const std::string connection_string = handle->connection_string();
    const std::string connection_hash = handle->connection_hash();
    const auto expires_at = handle->expires_at();
    auto now = std::chrono::system_clock::now();
    delete handle;
    res->ptr = nullptr;
    auto current_persistent = --COUCHBASE_G(num_persistent);
    CB_LOG_DEBUG(
      "persistent connection destroyed: handle={}, connection_hash={}, connection_string=\"{}\", "
      "expires_at=\"{}\" ({}), destructor_id={}, refcount={}, num_persistent={}",
      static_cast<const void*>(handle),
      connection_hash,
      connection_string,
      expires_at,
      (expires_at - now),
      res->type,
      GC_REFCOUNT(res),
      current_persistent);
  }
}

/*
 * Moves the entry of res in EG(persistent_list) to a key no lookup produces. Deleting the old
 * bucket must not run plist_entry_destructor: it frees res, which PHP values may still reference.
 * The new key carries the flag zend_register_persistent_resource_ex() sets on its keys; an engine
 * built with ZEND_RC_DEBUG asserts on reference counting a persistent key without it during a
 * request. GC_MAKE_PERSISTENT_LOCAL would expand to nothing here, as this file is built without
 * that macro.
 */
COUCHBASE_API
void
detach_persistent_connection(zend_resource* res)
{
  sweep_detached_connections();

  auto key = detached_key(res);
  zval entry;
  ZVAL_UNDEF(&entry);

  Bucket* bucket = nullptr;
  ZEND_HASH_FOREACH_BUCKET(&EG(persistent_list), bucket)
  {
    if (Z_TYPE(bucket->val) == IS_RESOURCE && Z_RES(bucket->val) == res) {
      if (bucket->key != nullptr &&
          std::string_view(ZSTR_VAL(bucket->key), ZSTR_LEN(bucket->key)) == key) {
        return;
      }
      ZVAL_COPY_VALUE(&entry, &bucket->val);
      auto* destructor = EG(persistent_list).pDestructor;
      EG(persistent_list).pDestructor = nullptr;
      zend_hash_del_bucket(&EG(persistent_list), bucket);
      EG(persistent_list).pDestructor = destructor;
      break;
    }
  }
  ZEND_HASH_FOREACH_END();

  if (Z_ISUNDEF(entry)) {
    return;
  }

  zend_string* persistent_key = zend_string_init(key.data(), key.size(), true);
  GC_ADD_FLAGS(persistent_key, GC_PERSISTENT_LOCAL);
  zend_hash_update(&EG(persistent_list), persistent_key, &entry);
  zend_string_release_ex(persistent_key, true);
  detached_connections_.push_back(res);

  if (auto* handle = static_cast<connection_handle*>(res->ptr); handle != nullptr) {
    handle->detached(true);
  }
}

COUCHBASE_API
void
close_persistent_connection(zend_resource* res)
{
  detach_persistent_connection(res);
  if (auto* handle = static_cast<connection_handle*>(res->ptr); handle != nullptr) {
    handle->close();
  }
}

namespace
{
auto
notify_transaction(zval* zv, void* event_ptr) -> int
{
  if (event_ptr == nullptr) {
    return ZEND_HASH_APPLY_KEEP;
  }

  zend_resource* res = Z_RES_P(zv);
  const fork_event event = *(static_cast<fork_event*>(event_ptr));

  if (res->type == get_transactions_destructor_id()) {
    const auto* transaction = static_cast<transactions_resource*>(res->ptr);
    transaction->notify_fork(event);
  }
  return ZEND_HASH_APPLY_KEEP;
}

auto
notify_connection(zval* zv, void* event_ptr) -> int
{
  if (event_ptr == nullptr) {
    return ZEND_HASH_APPLY_KEEP;
  }

  zend_resource* res = Z_RES_P(zv);
  const fork_event event = *(static_cast<fork_event*>(event_ptr));

  if (res->type == persistent_connection_destructor_id_ && res->ptr != nullptr) {
    const auto* connection = static_cast<connection_handle*>(res->ptr);
    /* a closed handle still runs its io_context, which must be quiesced and restarted too */
    connection->notify_fork(event);
  }
  return ZEND_HASH_APPLY_KEEP;
}

auto
get_fork_event(const zend_string* fork_event_str)
  -> std::pair<core_error_info, std::optional<couchbase::fork_event>>
{
  if (fork_event_str == nullptr || ZSTR_VAL(fork_event_str) == nullptr ||
      ZSTR_LEN(fork_event_str) == 0) {
    return { { errc::common::invalid_argument,
               ERROR_LOCATION,
               "expected non-empty string for forkEvent argument" },
             {} };
  }

  if (zend_binary_strcmp(
        ZSTR_VAL(fork_event_str), ZSTR_LEN(fork_event_str), ZEND_STRL("prepare")) == 0) {
    return { {}, couchbase::fork_event::prepare };
  }
  if (zend_binary_strcmp(ZSTR_VAL(fork_event_str), ZSTR_LEN(fork_event_str), ZEND_STRL("parent")) ==
      0) {
    return { {}, couchbase::fork_event::parent };
  }
  if (zend_binary_strcmp(ZSTR_VAL(fork_event_str), ZSTR_LEN(fork_event_str), ZEND_STRL("child")) ==
      0) {
    return { {}, couchbase::fork_event::child };
  }
  return { { errc::common::invalid_argument,
             ERROR_LOCATION,
             fmt::format("unknown forkEvent: {}",
                         std::string_view(ZSTR_VAL(fork_event_str), ZSTR_LEN(fork_event_str))) },
           {} };
}
} // namespace

COUCHBASE_API
auto
notify_fork(const zend_string* fork_event) -> core_error_info
{
  auto [e, event] = get_fork_event(fork_event);
  if (e.ec) {
    return e;
  }

  /* transactions must be first to stop */
  if (event == fork_event::prepare) {
    zend_hash_apply_with_argument(&EG(persistent_list), notify_transaction, &event);
  }

  zend_hash_apply_with_argument(&EG(persistent_list), notify_connection, &event);

  /* transactions must be last to start */
  if (event != fork_event::prepare) {
    zend_hash_apply_with_argument(&EG(persistent_list), notify_transaction, &event);
  }

  return {};
}
} // namespace couchbase::php
