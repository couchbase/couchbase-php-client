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

#pragma once

#include "api_visibility.hxx"

#include "connection_handle.hxx"

#include <Zend/zend_API.h>

#include <chrono>
#include <functional>

namespace couchbase::php
{

COUCHBASE_API void
set_persistent_connection_destructor_id(int id);

COUCHBASE_API int
get_persistent_connection_destructor_id();

COUCHBASE_API std::pair<zend_resource*, core_error_info>
create_persistent_connection(zend_string* connection_hash,
                             zend_string* connection_string,
                             zval* options);

COUCHBASE_API void
destroy_persistent_connection(zend_resource* res);

COUCHBASE_API void
detach_persistent_connection(zend_resource* res);

COUCHBASE_API void
sweep_detached_connections();

COUCHBASE_API void
forget_busy_connections();

COUCHBASE_API void
wait_for_teardowns_of_freeable_connections(std::chrono::system_clock::time_point cutoff);

/* runs destroy on a separate thread that wait_for_teardowns() waits for */
COUCHBASE_API void
destroy_in_background(std::function<void()> destroy);

COUCHBASE_API void
wait_for_teardowns();

COUCHBASE_API int
check_persistent_connection(zval* zv, void* cutoff_ptr);

COUCHBASE_API
core_error_info
notify_fork(const zend_string* fork_event);
} // namespace couchbase::php
