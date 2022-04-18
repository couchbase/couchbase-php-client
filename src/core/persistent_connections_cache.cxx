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

#include "core.hxx"

#include "common.hxx"
#include "connection_handle.hxx"

namespace couchbase::php
{

static int persistent_connection_destructor_id_{ 0 };

COUCHBASE_API
void
set_persistent_connection_destructor_id(int id)
{
    persistent_connection_destructor_id_ = id;
}

COUCHBASE_API
int
get_persistent_connection_destructor_id()
{
    return persistent_connection_destructor_id_;
}

COUCHBASE_API
int
check_persistent_connection(zval* zv)
{
    zend_resource* res = Z_RES_P(zv);
    auto now = std::chrono::steady_clock::now();

    if (res->type == persistent_connection_destructor_id_) {
        auto const* connection = static_cast<connection_handle*>(res->ptr);
        if (COUCHBASE_G(persistent_timeout) != -1 && connection->is_expired(now)) {
            /* connection has timed out */
            return ZEND_HASH_APPLY_REMOVE;
        }
    }
    return ZEND_HASH_APPLY_KEEP;
}

COUCHBASE_API
std::pair<zend_resource*, core_error_info>
create_persistent_connection(zend_string* connection_hash, zend_string* connection_string, zval* options)
{
    connection_handle* handle = nullptr;
    bool found = false;

    if (zval* entry = zend_hash_find(&EG(persistent_list), connection_hash); entry != nullptr) {
        zend_resource* res = Z_RES_P(entry);
        found = true;
        if (res->type == persistent_connection_destructor_id_) {
            handle = static_cast<connection_handle*>(res->ptr);
        }
    }
    if (handle != nullptr) {
        return { zend_register_resource(handle, persistent_connection_destructor_id_), {} };
    }
    if (found) {
        /* found something, which is not our resource */
        zend_hash_del(&EG(persistent_list), connection_hash);
    }

    if (COUCHBASE_G(max_persistent) != -1 && COUCHBASE_G(num_persistent) >= COUCHBASE_G(max_persistent)) {
        /* try to find an idle connection and kill it */
        zend_hash_apply(&EG(persistent_list), check_persistent_connection);
    }

    auto now = std::chrono::steady_clock::now();
    auto idle_expire_at = COUCHBASE_G(persistent_timeout) > 0 ? now + std::chrono::milliseconds(COUCHBASE_G(persistent_timeout)) : now;
    core_error_info rc;
    std::tie(handle, rc) = create_connection_handle(connection_string, options, idle_expire_at);
    if (rc.ec) {
        return { nullptr, rc };
    }

    if (rc = handle->open(); rc.ec) {
        delete handle;
        return { nullptr, rc };
    }
    zend_register_persistent_resource_ex(connection_hash, handle, persistent_connection_destructor_id_);
    ++COUCHBASE_G(num_persistent);
    return { zend_register_resource(handle, persistent_connection_destructor_id_), {} };
}

COUCHBASE_API
void
destroy_persistent_connection(zend_resource* res)
{
    if (res->type == persistent_connection_destructor_id_ && res->ptr != nullptr) {
        auto* handle = static_cast<connection_handle*>(res->ptr);
        delete handle;
        res->ptr = nullptr;
        --COUCHBASE_G(num_persistent);
    }
}

} // namespace couchbase::php
