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

#include "core/common.hxx"

#include "php_couchbase.hxx"

#include "core/persistent_connections_cache.hxx"
#include "core/version.hxx"

#include <php.h>

#include <ext/standard/info.h>

ZEND_DECLARE_MODULE_GLOBALS(couchbase)

ZEND_RSRC_DTOR_FUNC(couchbase_destroy_persistent_connection)
{
    couchbase::php::destroy_persistent_connection(res);
}

PHP_RSHUTDOWN_FUNCTION(couchbase)
{
    /* Check persistent connections and do the necessary actions if needed. */
    zend_hash_apply(&EG(persistent_list), couchbase::php::check_persistent_connection);

    return SUCCESS;
}

PHP_MINIT_FUNCTION(couchbase)
{
    (void)type;

    couchbase::php::persistent_connection_destructor_id =
      zend_register_list_destructors_ex(NULL, couchbase_destroy_persistent_connection, "couchbase_persistent_connection", module_number);

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

PHP_INI_BEGIN()
    STD_PHP_INI_ENTRY("couchbase.max_persistent", "-1", PHP_INI_SYSTEM, OnUpdateLong, max_persistent, zend_couchbase_globals, couchbase_globals)
    STD_PHP_INI_ENTRY("couchbase.persistent_timeout", "-1", PHP_INI_SYSTEM, OnUpdateLong, persistent_timeout, zend_couchbase_globals, couchbase_globals)
PHP_INI_END()
// clang-format on

zend_module_entry couchbase_module_entry = {
    STANDARD_MODULE_HEADER_EX,
    NULL,
    php_couchbase_deps,
    PHP_COUCHBASE_EXTENSION_NAME,
    couchbase_functions,      /* extension function list */
    PHP_MINIT(couchbase),     /* extension-wide startup function */
    PHP_MSHUTDOWN(couchbase), /* extension-wide shutdown function */
    NULL,                     /* per-request startup function */
    PHP_RSHUTDOWN(couchbase), /* per-request shutdown function */
    PHP_MINFO(couchbase),     /* information function */
    PHP_COUCHBASE_VERSION,
    PHP_MODULE_GLOBALS(couchbase), /* globals descriptor */
    NULL,                          /* globals ctor */
    NULL,                          /* globals dtor */
    NULL,                          /* post deactivate */
    STANDARD_MODULE_PROPERTIES_EX,
};

#ifdef COMPILE_DL_COUCHBASE
#ifdef ZTS
ZEND_TSRMLS_CACHE_DEFINE();
#endif
ZEND_GET_MODULE(couchbase)
#endif
