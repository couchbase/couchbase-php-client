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

#ifdef HAVE_CONFIG_H
#include "config.h"
#endif

#include "api_visibility.hxx"

#include "core_error_info.hxx"
#include "php_7_api_layer.hxx"

#include <Zend/zend_API.h>

ZEND_BEGIN_MODULE_GLOBALS(couchbase)
const char* log_level{};
zend_long max_persistent{};     /* maximum number of persistent connections per process */
zend_long num_persistent{};     /* number of existing persistent connections */
zend_long persistent_timeout{}; /* time period after which idle persistent connection is considered expired */
ZEND_END_MODULE_GLOBALS(couchbase)

COUCHBASE_API
ZEND_EXTERN_MODULE_GLOBALS(couchbase)
#define COUCHBASE_G(v) ZEND_MODULE_GLOBALS_ACCESSOR(couchbase, v)

namespace couchbase::php
{
COUCHBASE_API void
initialize_exceptions(const zend_function_entry* exception_functions);

COUCHBASE_API zend_class_entry*
couchbase_exception();

COUCHBASE_API zend_class_entry*
map_error_to_exception(const core_error_info& info);

COUCHBASE_API void
error_context_to_zval(const core_error_info& info, zval* return_value);

COUCHBASE_API void
create_exception(zval* return_value, const couchbase::php::core_error_info& error_info);
} // namespace couchbase::php
