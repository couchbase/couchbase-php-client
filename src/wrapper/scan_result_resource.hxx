/**
 * Copyright 2022-Present Couchbase, Inc.
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
#include <memory>
#include <string>
#include <system_error>

namespace couchbase
{
namespace core
{
class scan_result;
} // namespace core
} // namespace couchbase

namespace couchbase::php
{
class scan_result_resource
{
  public:
    COUCHBASE_API
    scan_result_resource(connection_handle* connection, const couchbase::core::scan_result& scan_result);

    COUCHBASE_API
    core_error_info next_item(zval* return_value);

  private:
    class impl;

    std::shared_ptr<impl> impl_;
};

COUCHBASE_API std::pair<zend_resource*, core_error_info>
create_scan_result_resource(connection_handle* connection,
                            const zend_string* bucket,
                            const zend_string* scope,
                            const zend_string* collection,
                            const zval* scan_type,
                            const zval* options);

COUCHBASE_API void
destroy_scan_result_resource(zend_resource* res);

COUCHBASE_API void
set_scan_result_destructor_id(int id);

COUCHBASE_API int
get_scan_result_destructor_id();
} // namespace couchbase::php
