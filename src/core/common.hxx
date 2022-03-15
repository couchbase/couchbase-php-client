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

#include "core_error_info.hxx"

#include <Zend/zend_API.h>

ZEND_BEGIN_MODULE_GLOBALS(couchbase)
zend_long max_persistent{};     /* maximum number of persistent connections per process */
zend_long num_persistent{};     /* number of existing persistent connections */
zend_long persistent_timeout{}; /* time period after which idle persistent connection is considered expired */
ZEND_END_MODULE_GLOBALS(couchbase)

ZEND_EXTERN_MODULE_GLOBALS(couchbase)

#ifdef ZTS
#define COUCHBASE_G(v) TSRMG(couchbase_globals_id, zend_couchbase_globals*, v)
#else
#define COUCHBASE_G(v) (couchbase_globals.v)
#endif
