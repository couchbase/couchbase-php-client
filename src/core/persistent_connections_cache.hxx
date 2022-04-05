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

#include "connection_handle.hxx"

#include <Zend/zend_API.h>

namespace couchbase::php
{

void
set_persistent_connection_destructor_id(int id);

[[nodiscard]] int
get_persistent_connection_destructor_id();

[[nodiscard]] std::pair<zend_resource*, core_error_info>
create_persistent_connection(zend_string* connection_hash, zend_string* connection_string, zval* options);

void
destroy_persistent_connection(zend_resource* res);

[[nodiscard]] int
check_persistent_connection(zval* zv);
} // namespace couchbase::php