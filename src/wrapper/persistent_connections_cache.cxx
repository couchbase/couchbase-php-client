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

#include <algorithm>
#include <condition_variable>
#include <cstddef>
#include <cstdint>
#include <cstdlib>
#include <functional>
#include <mutex>
#include <thread>
#include <utility>
#include <vector>

namespace couchbase::php
{

namespace
{
int persistent_connection_destructor_id_{ 0 };

/* the persistent list is per thread, and so are the detached entries in it */
thread_local std::vector<zend_resource*> detached_connections_{};
/* number of entries the last sweep left in detached_connections_, all of them in use */
thread_local std::size_t detached_after_sweep_{ 0 };
/* the entry sweep_detached_connections_if_grown() checks next */
thread_local std::size_t detached_cursor_{ 0 };
/* num_persistent after a scan for the limit that found every connection in use, otherwise 0 */
thread_local zend_long busy_at_limit_{ 0 };
/* the bucket of EG(persistent_list) that free_idle_connections_from_cursor() checks next */
thread_local std::uint32_t persistent_list_cursor_{ 0 };
/* detached connections seen in use in a row by free_idle_connections_from_cursor() */
thread_local std::size_t detached_seen_busy_{ 0 };

struct teardowns {
  std::mutex mutex{};
  std::condition_variable finished{};
  std::size_t pending{ 0 };
};
/* shared with the teardown threads, which can outlive the PHP thread that started them */
thread_local std::shared_ptr<teardowns> teardowns_{ std::make_shared<teardowns>() };

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
check_persistent_connection(zval* zv, void* cutoff_ptr) -> int
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

  const auto now = *static_cast<const std::chrono::system_clock::time_point*>(cutoff_ptr);
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

namespace
{
/*
 * Frees the entry at index i when nothing uses it, and returns whether it did. The order of the
 * list does not matter, so the freed entry is replaced by the last one.
 */
auto
free_detached_connection_if_unused(std::size_t i) -> bool
{
  zend_resource* res = detached_connections_[i];
  if (const auto* handle = static_cast<connection_handle*>(res->ptr);
      GC_REFCOUNT(res) > 0 || (handle != nullptr && handle->has_dependents())) {
    return false;
  }
  detached_connections_[i] = detached_connections_.back();
  detached_connections_.pop_back();
  auto key = detached_key(res);
  zend_hash_str_del(&EG(persistent_list), key.data(), key.size());
  return true;
}
} // namespace

/*
 * Waits for the native teardowns that keep a connection the pass after a request would free:
 * detached, or expired with couchbase.persistent_timeout enabled. Teardowns on connections that
 * stay cached go on in the background.
 */
COUCHBASE_API
void
wait_for_teardowns_of_freeable_connections(std::chrono::system_clock::time_point cutoff)
{
  const auto held = [](const zend_resource* res) {
    const auto* handle = static_cast<connection_handle*>(res->ptr);
    return handle != nullptr && GC_REFCOUNT(res) == 0 && handle->has_dependents();
  };
  std::vector<const connection_handle*> waited{};
  for (const auto* res : detached_connections_) {
    if (held(res)) {
      waited.push_back(static_cast<const connection_handle*>(res->ptr));
    }
  }
  if (COUCHBASE_G(persistent_timeout) >= 0) {
    zval* entry = nullptr;
    ZEND_HASH_FOREACH_VAL(&EG(persistent_list), entry)
    {
      if (Z_TYPE_P(entry) != IS_RESOURCE ||
          Z_RES_P(entry)->type != persistent_connection_destructor_id_) {
        continue;
      }
      const zend_resource* res = Z_RES_P(entry);
      const auto* handle = static_cast<connection_handle*>(res->ptr);
      if (handle != nullptr && !handle->detached() && handle->is_expired(cutoff) && held(res)) {
        waited.push_back(handle);
      }
    }
    ZEND_HASH_FOREACH_END();
  }
  if (waited.empty()) {
    return;
  }
  std::unique_lock lock(teardowns_->mutex);
  teardowns_->finished.wait(lock, [&waited] {
    return std::none_of(waited.begin(), waited.end(), [](const connection_handle* handle) {
      return handle->has_dependents();
    });
  });
}

/* request shutdown releases the connections, so the next miss at the limit scans them all again */
COUCHBASE_API
void
forget_busy_connections()
{
  busy_at_limit_ = 0;
  detached_seen_busy_ = 0;
}

/*
 * Frees the detached connections nothing uses. Releasing the last PHP value of a persistent
 * resource does not destroy it.
 */
COUCHBASE_API
void
sweep_detached_connections()
{
  for (std::size_t i = 0; i < detached_connections_.size();) {
    if (!free_detached_connection_if_unused(i)) {
      ++i;
    }
  }
  detached_after_sweep_ = detached_connections_.size();
}

namespace
{
/*
 * Sweeps once the list has doubled since the last sweep, and otherwise checks one entry in turn,
 * so a connection released after the last sweep is still freed during the request. Detached
 * connections still in use thus cost O(1) amortized per lookup and per detach.
 */
void
sweep_detached_connections_if_grown()
{
  if (detached_connections_.size() > 2 * detached_after_sweep_) {
    sweep_detached_connections();
    return;
  }
  if (detached_connections_.empty()) {
    return;
  }
  detached_cursor_ %= detached_connections_.size();
  if (!free_detached_connection_if_unused(detached_cursor_)) {
    ++detached_cursor_;
  }
}
} // namespace

COUCHBASE_API
void
destroy_in_background(std::function<void()> destroy)
{
  {
    const std::scoped_lock lock(teardowns_->mutex);
    ++teardowns_->pending;
  }
  /* lets tests keep a teardown in progress */
  static const auto delay = std::chrono::milliseconds([] {
    const char* value = std::getenv("COUCHBASE_TEST_TEARDOWN_DELAY_MS");
    return value == nullptr ? 0 : std::atol(value);
  }());
  std::thread([destroy = std::move(destroy), state = teardowns_]() {
    if (delay.count() > 0) {
      std::this_thread::sleep_for(delay);
    }
    destroy();
    const std::scoped_lock lock(state->mutex);
    --state->pending;
    state->finished.notify_all();
  }).detach();
}

COUCHBASE_API
void
wait_for_teardowns()
{
  std::unique_lock lock(teardowns_->mutex);
  teardowns_->finished.wait(lock, [] {
    return teardowns_->pending == 0;
  });
}

namespace
{
/* an attached connection that no PHP value, transaction or scan uses */
auto
is_idle_connection(const zend_resource* res) -> bool
{
  if (res->type != persistent_connection_destructor_id_) {
    return false;
  }
  const auto* handle = static_cast<connection_handle*>(res->ptr);
  return handle != nullptr && !handle->detached() && GC_REFCOUNT(res) == 0 &&
         !handle->has_dependents();
}

/*
 * Frees idle connections bucket by bucket from the cursor until a new one fits under
 * couchbase.max_persistent or a bucket holds none, so a call costs O(1) plus the connections it
 * frees. The cursor is a position in the bucket array, so a rehash only changes the order in which
 * the buckets are visited.
 */
void
free_idle_connections_from_cursor()
{
  /*
   * Detached connections go first, from their own cursor. Attached ones are freed only once every
   * detached connection has been seen in use since one was last freed, one check per call.
   */
  while (!detached_connections_.empty() &&
         COUCHBASE_G(num_persistent) >= COUCHBASE_G(max_persistent)) {
    detached_cursor_ %= detached_connections_.size();
    if (free_detached_connection_if_unused(detached_cursor_)) {
      detached_seen_busy_ = 0;
      continue;
    }
    ++detached_cursor_;
    if (++detached_seen_busy_ < detached_connections_.size()) {
      return;
    }
    break;
  }
  HashTable* list = &EG(persistent_list);
  if (HT_IS_PACKED(list)) {
    return;
  }
  while (list->nNumUsed > 0 && COUCHBASE_G(num_persistent) >= COUCHBASE_G(max_persistent)) {
    persistent_list_cursor_ %= list->nNumUsed;
    Bucket* bucket = list->arData + persistent_list_cursor_++;
    if (bucket->key == nullptr || Z_TYPE(bucket->val) != IS_RESOURCE ||
        !is_idle_connection(Z_RES(bucket->val))) {
      return;
    }
    zend_hash_del_bucket(list, bucket);
  }
}

/*
 * Frees the least recently used idle connections until a new one fits under
 * couchbase.max_persistent. A connection that PHP values or native dependents still use is never
 * freed, so the limit can be exceeded while all of them are in use.
 */
void
make_room_for_connection()
{
  if (COUCHBASE_G(max_persistent) < 0 ||
      COUCHBASE_G(num_persistent) < COUCHBASE_G(max_persistent)) {
    busy_at_limit_ = 0;
    return;
  }
  /*
   * Every connection was in use at the last scan. Until their number has doubled, a miss frees
   * the connections released since from a rotating position instead of scanning them all, so a
   * run of misses over the limit costs O(1) amortized each, and least recently used order applies
   * to full scans only. Request shutdown clears this, as it releases the connections.
   */
  if (busy_at_limit_ > 0 && COUCHBASE_G(num_persistent) < 2 * busy_at_limit_) {
    free_idle_connections_from_cursor();
    if (COUCHBASE_G(num_persistent) < COUCHBASE_G(max_persistent)) {
      busy_at_limit_ = 0;
    }
    return;
  }
  busy_at_limit_ = 0;
  /* unused detached connections count toward the limit, and go before any attached one */
  sweep_detached_connections();
  detached_seen_busy_ = 0;
  if (COUCHBASE_G(num_persistent) < COUCHBASE_G(max_persistent)) {
    return;
  }

  /*
   * expires_at is refreshed on every cache hit, so the earliest one was used least recently. The
   * scan is O(N) per miss, against a bootstrap that each miss pays anyway. Deleting a bucket does
   * not move the others, so the collected pointers stay valid.
   */
  HashTable* list = &EG(persistent_list);
  std::vector<std::pair<std::chrono::system_clock::time_point, Bucket*>> idle{};
  for (uint32_t i = 0; !HT_IS_PACKED(list) && i < list->nNumUsed; ++i) {
    Bucket* bucket = list->arData + i;
    if (bucket->key == nullptr || Z_TYPE(bucket->val) != IS_RESOURCE ||
        !is_idle_connection(Z_RES(bucket->val))) {
      continue;
    }
    const auto* handle = static_cast<connection_handle*>(Z_RES(bucket->val)->ptr);
    idle.emplace_back(handle->expires_at(), bucket);
  }

  /* selects only as many of the least recently used as must go, in O(N) */
  const auto needed = std::min(
    idle.size(),
    static_cast<std::size_t>(COUCHBASE_G(num_persistent) - COUCHBASE_G(max_persistent) + 1));
  std::nth_element(idle.begin(), idle.begin() + static_cast<std::ptrdiff_t>(needed), idle.end());
  idle.resize(needed);
  for (const auto& [expires_at, bucket] : idle) {
    CB_LOG_DEBUG("evict idle persistent connection: connection_hash={}, expires_at=\"{}\", "
                 "max_persistent={}, num_persistent={}",
                 std::string_view(ZSTR_VAL(bucket->key), ZSTR_LEN(bucket->key)),
                 expires_at,
                 COUCHBASE_G(max_persistent),
                 COUCHBASE_G(num_persistent));
    zend_hash_del_bucket(list, bucket);
  }
  if (COUCHBASE_G(num_persistent) >= COUCHBASE_G(max_persistent)) {
    busy_at_limit_ = COUCHBASE_G(num_persistent);
  }
}
} // namespace

COUCHBASE_API
auto
create_persistent_connection(zend_string* connection_hash,
                             zend_string* connection_string,
                             zval* options) -> std::pair<zend_resource*, core_error_info>
{
  sweep_detached_connections_if_grown();

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
  /* only after open() succeeds, so a failed attempt does not cost an idle connection */
  make_room_for_connection();
  /* bootstrap can outlast couchbase.persistent_timeout, so the idle time starts when it ends */
  now = std::chrono::system_clock::now();
  expires_at = COUCHBASE_G(persistent_timeout) > 0
                 ? now + std::chrono::milliseconds(COUCHBASE_G(persistent_timeout))
                 : now;
  handle->expires_at(expires_at);
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
    /* only shutdown frees a connection with dependents, whose teardown still uses its cluster */
    if (handle->has_dependents()) {
      wait_for_teardowns();
    }
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
  sweep_detached_connections_if_grown();

  auto* handle = static_cast<connection_handle*>(res->ptr);
  if (handle == nullptr || handle->detached()) {
    return;
  }
  /* an attached connection is registered under its connection hash */
  const auto& hash = handle->connection_hash();
  const zval* registered = zend_hash_str_find(&EG(persistent_list), hash.data(), hash.size());
  if (registered == nullptr || Z_TYPE_P(registered) != IS_RESOURCE || Z_RES_P(registered) != res) {
    return;
  }
  zval entry;
  ZVAL_COPY_VALUE(&entry, registered);
  auto* destructor = EG(persistent_list).pDestructor;
  EG(persistent_list).pDestructor = nullptr;
  zend_hash_str_del(&EG(persistent_list), hash.data(), hash.size());
  EG(persistent_list).pDestructor = destructor;

  auto key = detached_key(res);
  zend_string* persistent_key = zend_string_init(key.data(), key.size(), true);
  GC_ADD_FLAGS(persistent_key, GC_PERSISTENT_LOCAL);
  zend_hash_update(&EG(persistent_list), persistent_key, &entry);
  zend_string_release_ex(persistent_key, true);
  detached_connections_.push_back(res);
  handle->detached(true);
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

  if (res->type == persistent_connection_destructor_id_) {
    const auto* connection = static_cast<connection_handle*>(res->ptr);
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
    /* the child inherits the dependency tokens of teardown threads, but not the threads */
    wait_for_teardowns();
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
