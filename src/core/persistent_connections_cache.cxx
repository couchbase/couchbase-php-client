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

#include "common.hxx"
#include "connection_handle.hxx"

namespace couchbase::php
{

int persistent_connection_destructor_id{ 0 };

void
destroy_persistent_connection(zend_resource* res)
{
}

int
check_persistent_connection(zval* zv)
{
    zend_resource* le = Z_RES_P(zv);
    auto now = std::chrono::steady_clock::now();

    connection_handle* connection;

    if (le->type == persistent_connection_destructor_id) {
        connection = static_cast<connection_handle*>(le->ptr);

        if (COUCHBASE_G(persistent_timeout) != -1 && connection->is_expired(now)) {
            /* connection has timed out */
            return ZEND_HASH_APPLY_REMOVE;
        }
    }
    return ZEND_HASH_APPLY_KEEP;
}
} // namespace couchbase::php