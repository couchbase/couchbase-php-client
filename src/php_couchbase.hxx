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

#ifndef PHP_COUCHBASE_H_
#define PHP_COUCHBASE_H_

#include <zend_modules.h>

#define STRINGIFY(x) #x
#define TOSTRING(x) STRINGIFY(x)

#ifdef COUCHBASE_ABI_VERSION
    #define PHP_COUCHBASE_EXTENSION_NAME "couchbase" "_" TOSTRING(COUCHBASE_ABI_VERSION)
#else
    #define PHP_COUCHBASE_EXTENSION_NAME "couchbase"
#endif

#define PHP_COUCHBASE_VERSION "4.2.7"

#ifdef __cplusplus
extern "C" {
#endif
extern zend_module_entry couchbase_module_entry;
#ifdef __cplusplus
};
#endif

#endif
