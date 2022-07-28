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

#include <php_version.h>

#if PHP_VERSION_ID < 80000

#define couchbase_update_property_string(scope, object, name, value)                                                                       \
    zend_update_property_string((scope), (object), ZEND_STRL(name), (value))
#define couchbase_update_property_long(scope, object, name, value) zend_update_property_long((scope), (object), ZEND_STRL(name), (value))
#define couchbase_update_property(scope, object, name, value) zend_update_property((scope), (object), ZEND_STRL(name), (value))

#define couchbase_read_property(scope, object, name, silent, rv) zend_read_property((scope), (object), ZEND_STRL(name), (silent), (rv))

#define RETURN_THROWS()                                                                                                                    \
    do {                                                                                                                                   \
        ZEND_ASSERT(EG(exception));                                                                                                        \
        (void)return_value;                                                                                                                \
        return;                                                                                                                            \
    } while (0)

#define Z_PARAM_ARRAY_OR_NULL(dest) Z_PARAM_ARRAY_EX(dest, 1, 0)

#else // PHP_VERSION_ID >= 80000

#define couchbase_update_property_string(scope, object, name, value)                                                                       \
    zend_update_property_string((scope), Z_OBJ_P(object), ZEND_STRL(name), (value))
#define couchbase_update_property_long(scope, object, name, value)                                                                         \
    zend_update_property_long((scope), Z_OBJ_P(object), ZEND_STRL(name), (value))
#define couchbase_update_property(scope, object, name, value) zend_update_property((scope), Z_OBJ_P(object), ZEND_STRL(name), (value))

#define couchbase_read_property(scope, object, name, silent, rv)                                                                           \
    zend_read_property((scope), Z_OBJ_P(object), ZEND_STRL(name), (silent), (rv))

#endif // PHP_VERSION_ID