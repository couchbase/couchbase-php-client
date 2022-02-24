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

#include "config.h"

#include "php_couchbase.hxx"

#include "core/version.hxx"

#include <php.h>

#include <ext/standard/info.h>

PHP_MINIT_FUNCTION(couchbase)
{
    (void)type;
    (void)module_number;
    return SUCCESS;
}

PHP_MSHUTDOWN_FUNCTION(couchbase)
{
    (void)type;
    (void)module_number;
    return SUCCESS;
}

PHP_FUNCTION(version)
{
    if (zend_parse_parameters_none_throw() == FAILURE) {
        RETURN_NULL();
    }
    zval version = couchbase::php::core_version();

    RETURN_ZVAL(&version, 1, 0);
}

static PHP_MINFO_FUNCTION(couchbase)
{
    php_info_print_table_start();
    php_info_print_table_row(2, "couchbase", "enabled");
    php_info_print_table_row(2, "extension version", PHP_COUCHBASE_VERSION);
    php_info_print_table_end();
    DISPLAY_INI_ENTRIES();
}

ZEND_BEGIN_ARG_INFO_EX(ai_CouchbaseExtension_version, 0, 0, 0)
ZEND_END_ARG_INFO()

// clang-format off
static zend_function_entry couchbase_functions[] = {
    ZEND_NS_FE("Couchbase\\Extension", version, ai_CouchbaseExtension_version)

    PHP_FE_END
};

static zend_module_dep php_couchbase_deps[] = {
    ZEND_MOD_REQUIRED("json")
    ZEND_MOD_END
};

zend_module_entry couchbase_module_entry = {
    STANDARD_MODULE_HEADER_EX,
    NULL,
    php_couchbase_deps,
    PHP_COUCHBASE_EXTENSION_NAME,
    couchbase_functions,
    PHP_MINIT(couchbase),
    PHP_MSHUTDOWN(couchbase),
    NULL,
    NULL,
    PHP_MINFO(couchbase),
    PHP_COUCHBASE_VERSION,
    STANDARD_MODULE_PROPERTIES
};

// clang-format on

#ifdef COMPILE_DL_COUCHBASE
#ifdef ZTS
ZEND_TSRMLS_CACHE_DEFINE();
#endif
ZEND_GET_MODULE(couchbase)
#endif
