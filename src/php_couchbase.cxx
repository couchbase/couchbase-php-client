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

#include "wrapper/common.hxx"
#include "wrapper/logger.hxx"
#include "wrapper/persistent_connections_cache.hxx"
#include "wrapper/scan_result_resource.hxx"
#include "wrapper/transaction_context_resource.hxx"
#include "wrapper/transactions_resource.hxx"
#include "wrapper/version.hxx"

#include "php_couchbase.hxx"

#include <php.h>

#include <Zend/zend_exceptions.h>
#include <ext/standard/info.h>

#include <sstream>

ZEND_RSRC_DTOR_FUNC(couchbase_destroy_persistent_connection)
{
    couchbase::php::destroy_persistent_connection(res);
}

ZEND_RSRC_DTOR_FUNC(couchbase_destroy_transactions)
{
    couchbase::php::destroy_transactions_resource(res);
}

ZEND_RSRC_DTOR_FUNC(couchbase_destroy_transaction_context)
{
    couchbase::php::destroy_transaction_context_resource(res);
}

ZEND_RSRC_DTOR_FUNC(couchbase_destroy_core_scan_result)
{
    couchbase::php::destroy_scan_result_resource(res);
}

PHP_RSHUTDOWN_FUNCTION(couchbase)
{
    /* Check persistent connections and do the necessary actions if needed. */
    zend_hash_apply(&EG(persistent_list), couchbase::php::check_persistent_connection);

    couchbase::php::flush_logger();
    return SUCCESS;
}

ZEND_BEGIN_ARG_WITH_RETURN_TYPE_INFO(ai_Exception_getContext, IS_ARRAY, 0)
ZEND_END_ARG_INFO()

ZEND_BEGIN_ARG_INFO_EX(ai_Exception___construct, 0, 0, 0)
ZEND_ARG_TYPE_INFO_WITH_DEFAULT_VALUE(0, message, IS_STRING, 0, "\"\"")
ZEND_ARG_TYPE_INFO_WITH_DEFAULT_VALUE(0, code, IS_LONG, 0, "0")
ZEND_ARG_OBJ_INFO_WITH_DEFAULT_VALUE(0, previous, Throwable, 1, "null")
ZEND_ARG_TYPE_INFO_WITH_DEFAULT_VALUE(0, context, IS_ARRAY, 1, "null")
ZEND_END_ARG_INFO()

PHP_METHOD(CouchbaseException, getContext)
{
    if (zend_parse_parameters_none_throw() == FAILURE) {
        return;
    }

    zval *prop, rv;
    prop = couchbase_read_property(couchbase::php::couchbase_exception(), getThis(), "context", 0, &rv);
    ZVAL_COPY_DEREF(return_value, prop);
}

PHP_METHOD(CouchbaseException, __construct)
{
    zend_string* message = NULL;
    zend_long code = 0;
    zval tmp, *object, *previous = NULL, *context = NULL;

    if (zend_parse_parameters(ZEND_NUM_ARGS(), "|SlO!a", &message, &code, &previous, zend_ce_throwable, &context) == FAILURE) {
        RETURN_THROWS();
    }

    object = ZEND_THIS;

    if (message) {
        ZVAL_STR_COPY(&tmp, message);
        zend_update_property_ex(zend_ce_exception, Z_OBJ_P(object), ZSTR_KNOWN(ZEND_STR_MESSAGE), &tmp);
        zval_ptr_dtor(&tmp);
    }

    if (code) {
        ZVAL_LONG(&tmp, code);
        zend_update_property_ex(zend_ce_exception, Z_OBJ_P(object), ZSTR_KNOWN(ZEND_STR_CODE), &tmp);
    }

    if (previous) {
        zend_update_property_ex(zend_ce_exception, Z_OBJ_P(object), ZSTR_KNOWN(ZEND_STR_PREVIOUS), previous);
    }

    if (context) {
        zend_string* property_context_name = zend_string_init(ZEND_STRL("context"), 1);
        zend_update_property_ex(couchbase::php::couchbase_exception(), Z_OBJ_P(object), property_context_name, context);
        zend_string_release(property_context_name);
    }
}

PHP_RINIT_FUNCTION(couchbase)
{
    if (!COUCHBASE_G(initialized)) {
        couchbase::php::initialize_logger();
        COUCHBASE_G(initialized) = 1;
    }
    return SUCCESS;
}

// clang-format off
static const zend_function_entry exception_functions[] = {
        PHP_ME(CouchbaseException, getContext, ai_Exception_getContext, ZEND_ACC_PUBLIC)
        PHP_ME(CouchbaseException, __construct, ai_Exception___construct, ZEND_ACC_PUBLIC)
        PHP_FE_END
};

PHP_INI_BEGIN()
STD_PHP_INI_ENTRY("couchbase.max_persistent", "-1", PHP_INI_SYSTEM, OnUpdateLong, max_persistent, zend_couchbase_globals, couchbase_globals)
STD_PHP_INI_ENTRY("couchbase.persistent_timeout", "-1", PHP_INI_SYSTEM, OnUpdateLong, persistent_timeout, zend_couchbase_globals, couchbase_globals)
STD_PHP_INI_ENTRY("couchbase.log_level", "", PHP_INI_ALL, OnUpdateString, log_level, zend_couchbase_globals, couchbase_globals)
/* use php_error() for logging */
STD_PHP_INI_ENTRY("couchbase.log_php_log_err", "1", PHP_INI_SYSTEM, OnUpdateBool, log_php_log_err, zend_couchbase_globals, couchbase_globals)
STD_PHP_INI_ENTRY("couchbase.log_stderr", "0", PHP_INI_SYSTEM, OnUpdateBool, log_stderr, zend_couchbase_globals, couchbase_globals)
/* write logs to given file (does not override couchbase.log_use_php_error) */
STD_PHP_INI_ENTRY("couchbase.log_path", "", PHP_INI_SYSTEM, OnUpdateString, log_path, zend_couchbase_globals, couchbase_globals)
PHP_INI_END()
// clang-format on

PHP_MINIT_FUNCTION(couchbase)
{
    (void)type;
    REGISTER_INI_ENTRIES();

    couchbase::php::initialize_exceptions(exception_functions);

    couchbase::php::set_persistent_connection_destructor_id(zend_register_list_destructors_ex(
      nullptr, couchbase_destroy_persistent_connection, "couchbase_persistent_connection", module_number));
    couchbase::php::set_transactions_destructor_id(
      zend_register_list_destructors_ex(couchbase_destroy_transactions, nullptr, "couchbase_transactions", module_number));
    couchbase::php::set_transaction_context_destructor_id(
      zend_register_list_destructors_ex(couchbase_destroy_transaction_context, nullptr, "couchbase_transaction_context", module_number));
    couchbase::php::set_scan_result_destructor_id(
      zend_register_list_destructors_ex(couchbase_destroy_core_scan_result, nullptr, "couchbase_scan_result", module_number));

    return SUCCESS;
}

struct logger_flusher {
    logger_flusher() = default;
    ~logger_flusher()
    {
        couchbase::php::flush_logger();
    }
};

static void
couchbase_throw_exception(const couchbase::php::core_error_info& error_info)
{
    if (!error_info.ec) {
        return; // success
    }

    zval ex;
    couchbase::php::create_exception(&ex, error_info);
    zend_throw_exception_object(&ex);
}

PHP_MSHUTDOWN_FUNCTION(couchbase)
{
    couchbase::php::shutdown_logger();

    (void)type;
    (void)module_number;
    return SUCCESS;
}

PHP_FUNCTION(version)
{
    if (zend_parse_parameters_none_throw() == FAILURE) {
        RETURN_NULL();
    }
    couchbase::php::core_version(return_value);
}

PHP_FUNCTION(notifyFork)
{
    zend_string* fork_event = nullptr;

    ZEND_PARSE_PARAMETERS_START(1, 1)
    Z_PARAM_STR(fork_event)
    ZEND_PARSE_PARAMETERS_END();

    if (auto e = couchbase::php::notify_fork(fork_event); e.ec) {
        couchbase_throw_exception(e);
        RETURN_THROWS();
    }

    RETURN_NULL();
}

PHP_FUNCTION(createConnection)
{
    zend_string* connection_hash = nullptr;
    zend_string* connection_string = nullptr;
    zval* options = nullptr;

    ZEND_PARSE_PARAMETERS_START(3, 3)
    Z_PARAM_STR(connection_hash)
    Z_PARAM_STR(connection_string)
    Z_PARAM_ARRAY(options)
    ZEND_PARSE_PARAMETERS_END();

    logger_flusher guard;

    auto [resource, e] = couchbase::php::create_persistent_connection(connection_hash, connection_string, options);
    if (e.ec) {
        couchbase_throw_exception(e);
        RETURN_THROWS();
    }

    RETURN_RES(resource);
}

static inline couchbase::php::connection_handle*
fetch_couchbase_connection_from_resource(zval* resource)
{
    return static_cast<couchbase::php::connection_handle*>(
      zend_fetch_resource(Z_RES_P(resource), "couchbase_persistent_connection", couchbase::php::get_persistent_connection_destructor_id()));
}

PHP_FUNCTION(clusterVersion)
{
    zval* connection = nullptr;
    zend_string* name = nullptr;
    zval* options = nullptr;

    ZEND_PARSE_PARAMETERS_START(2, 2)
    Z_PARAM_RESOURCE(connection)
    Z_PARAM_STR(name)
    ZEND_PARSE_PARAMETERS_END();

    logger_flusher guard;

    auto* handle = fetch_couchbase_connection_from_resource(connection);
    if (handle == nullptr) {
        RETURN_THROWS();
    }

    auto version = handle->cluster_version(name);
    if (version.empty()) {
        RETURN_NULL();
    }
    RETURN_STRINGL(version.data(), version.size());
}

PHP_FUNCTION(replicasConfiguredForBucket)
{
    zval* connection = nullptr;
    zend_string* name = nullptr;
    zval* options = nullptr;

    ZEND_PARSE_PARAMETERS_START(2, 2)
    Z_PARAM_RESOURCE(connection)
    Z_PARAM_STR(name)
    ZEND_PARSE_PARAMETERS_END();

    logger_flusher guard;

    auto* handle = fetch_couchbase_connection_from_resource(connection);
    if (handle == nullptr) {
        RETURN_THROWS();
    }

    if (handle->replicas_configured_for_bucket(name)) {
        RETURN_TRUE;
    }
    RETURN_FALSE;
}

PHP_FUNCTION(openBucket)
{
    zval* connection = nullptr;
    zend_string* name = nullptr;
    zval* options = nullptr;

    ZEND_PARSE_PARAMETERS_START(2, 2)
    Z_PARAM_RESOURCE(connection)
    Z_PARAM_STR(name)
    ZEND_PARSE_PARAMETERS_END();

    logger_flusher guard;

    auto* handle = fetch_couchbase_connection_from_resource(connection);
    if (handle == nullptr) {
        RETURN_THROWS();
    }

    if (auto e = handle->bucket_open(name); e.ec) {
        couchbase_throw_exception(e);
        RETURN_THROWS();
    }
}

PHP_FUNCTION(closeBucket)
{
    zval* connection = nullptr;
    zend_string* name = nullptr;
    zval* options = nullptr;

    ZEND_PARSE_PARAMETERS_START(2, 2)
    Z_PARAM_RESOURCE(connection)
    Z_PARAM_STR(name)
    ZEND_PARSE_PARAMETERS_END();

    logger_flusher guard;

    auto* handle = fetch_couchbase_connection_from_resource(connection);
    if (handle == nullptr) {
        RETURN_THROWS();
    }

    if (auto e = handle->bucket_close(name); e.ec) {
        couchbase_throw_exception(e);
        RETURN_THROWS();
    }
}

PHP_FUNCTION(documentUpsert)
{
    zval* connection = nullptr;
    zend_string* bucket = nullptr;
    zend_string* scope = nullptr;
    zend_string* collection = nullptr;
    zend_string* id = nullptr;
    zend_string* value = nullptr;
    zend_long flags = 0;
    zval* options = nullptr;

    ZEND_PARSE_PARAMETERS_START(7, 8)
    Z_PARAM_RESOURCE(connection)
    Z_PARAM_STR(bucket)
    Z_PARAM_STR(scope)
    Z_PARAM_STR(collection)
    Z_PARAM_STR(id)
    Z_PARAM_STR(value)
    Z_PARAM_LONG(flags)
    Z_PARAM_OPTIONAL
    Z_PARAM_ARRAY_OR_NULL(options)
    ZEND_PARSE_PARAMETERS_END();

    logger_flusher guard;

    auto* handle = fetch_couchbase_connection_from_resource(connection);
    if (handle == nullptr) {
        RETURN_THROWS();
    }

    if (auto e = handle->document_upsert(return_value, bucket, scope, collection, id, value, flags, options); e.ec) {
        couchbase_throw_exception(e);
        RETURN_THROWS();
    }
}

PHP_FUNCTION(documentInsert)
{
    zval* connection = nullptr;
    zend_string* bucket = nullptr;
    zend_string* scope = nullptr;
    zend_string* collection = nullptr;
    zend_string* id = nullptr;
    zend_string* value = nullptr;
    zend_long flags = 0;
    zval* options = nullptr;

    ZEND_PARSE_PARAMETERS_START(7, 8)
    Z_PARAM_RESOURCE(connection)
    Z_PARAM_STR(bucket)
    Z_PARAM_STR(scope)
    Z_PARAM_STR(collection)
    Z_PARAM_STR(id)
    Z_PARAM_STR(value)
    Z_PARAM_LONG(flags)
    Z_PARAM_OPTIONAL
    Z_PARAM_ARRAY_OR_NULL(options)
    ZEND_PARSE_PARAMETERS_END();

    logger_flusher guard;

    auto* handle = fetch_couchbase_connection_from_resource(connection);
    if (handle == nullptr) {
        RETURN_THROWS();
    }

    if (auto e = handle->document_insert(return_value, bucket, scope, collection, id, value, flags, options); e.ec) {
        couchbase_throw_exception(e);
        RETURN_THROWS();
    }
}

PHP_FUNCTION(documentReplace)
{
    zval* connection = nullptr;
    zend_string* bucket = nullptr;
    zend_string* scope = nullptr;
    zend_string* collection = nullptr;
    zend_string* id = nullptr;
    zend_string* value = nullptr;
    zend_long flags = 0;
    zval* options = nullptr;

    ZEND_PARSE_PARAMETERS_START(7, 8)
    Z_PARAM_RESOURCE(connection)
    Z_PARAM_STR(bucket)
    Z_PARAM_STR(scope)
    Z_PARAM_STR(collection)
    Z_PARAM_STR(id)
    Z_PARAM_STR(value)
    Z_PARAM_LONG(flags)
    Z_PARAM_OPTIONAL
    Z_PARAM_ARRAY_OR_NULL(options)
    ZEND_PARSE_PARAMETERS_END();

    logger_flusher guard;

    auto* handle = fetch_couchbase_connection_from_resource(connection);
    if (handle == nullptr) {
        RETURN_THROWS();
    }

    if (auto e = handle->document_replace(return_value, bucket, scope, collection, id, value, flags, options); e.ec) {
        couchbase_throw_exception(e);
        RETURN_THROWS();
    }
}

PHP_FUNCTION(documentAppend)
{
    zval* connection = nullptr;
    zend_string* bucket = nullptr;
    zend_string* scope = nullptr;
    zend_string* collection = nullptr;
    zend_string* id = nullptr;
    zend_string* value = nullptr;
    zval* options = nullptr;

    ZEND_PARSE_PARAMETERS_START(6, 7)
    Z_PARAM_RESOURCE(connection)
    Z_PARAM_STR(bucket)
    Z_PARAM_STR(scope)
    Z_PARAM_STR(collection)
    Z_PARAM_STR(id)
    Z_PARAM_STR(value)
    Z_PARAM_OPTIONAL
    Z_PARAM_ARRAY_OR_NULL(options)
    ZEND_PARSE_PARAMETERS_END();

    logger_flusher guard;

    auto* handle = fetch_couchbase_connection_from_resource(connection);
    if (handle == nullptr) {
        RETURN_THROWS();
    }

    if (auto e = handle->document_append(return_value, bucket, scope, collection, id, value, options); e.ec) {
        couchbase_throw_exception(e);
        RETURN_THROWS();
    }
}

PHP_FUNCTION(documentPrepend)
{
    zval* connection = nullptr;
    zend_string* bucket = nullptr;
    zend_string* scope = nullptr;
    zend_string* collection = nullptr;
    zend_string* id = nullptr;
    zend_string* value = nullptr;
    zval* options = nullptr;

    ZEND_PARSE_PARAMETERS_START(6, 7)
    Z_PARAM_RESOURCE(connection)
    Z_PARAM_STR(bucket)
    Z_PARAM_STR(scope)
    Z_PARAM_STR(collection)
    Z_PARAM_STR(id)
    Z_PARAM_STR(value)
    Z_PARAM_OPTIONAL
    Z_PARAM_ARRAY_OR_NULL(options)
    ZEND_PARSE_PARAMETERS_END();

    logger_flusher guard;

    auto* handle = fetch_couchbase_connection_from_resource(connection);
    if (handle == nullptr) {
        RETURN_THROWS();
    }

    if (auto e = handle->document_prepend(return_value, bucket, scope, collection, id, value, options); e.ec) {
        couchbase_throw_exception(e);
        RETURN_THROWS();
    }
}

PHP_FUNCTION(documentIncrement)
{
    zval* connection = nullptr;
    zend_string* bucket = nullptr;
    zend_string* scope = nullptr;
    zend_string* collection = nullptr;
    zend_string* id = nullptr;
    zval* options = nullptr;

    ZEND_PARSE_PARAMETERS_START(5, 6)
    Z_PARAM_RESOURCE(connection)
    Z_PARAM_STR(bucket)
    Z_PARAM_STR(scope)
    Z_PARAM_STR(collection)
    Z_PARAM_STR(id)
    Z_PARAM_OPTIONAL
    Z_PARAM_ARRAY_OR_NULL(options)
    ZEND_PARSE_PARAMETERS_END();

    logger_flusher guard;

    auto* handle = fetch_couchbase_connection_from_resource(connection);
    if (handle == nullptr) {
        RETURN_THROWS();
    }

    if (auto e = handle->document_increment(return_value, bucket, scope, collection, id, options); e.ec) {
        couchbase_throw_exception(e);
        RETURN_THROWS();
    }
}

PHP_FUNCTION(documentDecrement)
{
    zval* connection = nullptr;
    zend_string* bucket = nullptr;
    zend_string* scope = nullptr;
    zend_string* collection = nullptr;
    zend_string* id = nullptr;
    zval* options = nullptr;

    ZEND_PARSE_PARAMETERS_START(5, 6)
    Z_PARAM_RESOURCE(connection)
    Z_PARAM_STR(bucket)
    Z_PARAM_STR(scope)
    Z_PARAM_STR(collection)
    Z_PARAM_STR(id)
    Z_PARAM_OPTIONAL
    Z_PARAM_ARRAY_OR_NULL(options)
    ZEND_PARSE_PARAMETERS_END();

    logger_flusher guard;

    auto* handle = fetch_couchbase_connection_from_resource(connection);
    if (handle == nullptr) {
        RETURN_THROWS();
    }

    if (auto e = handle->document_decrement(return_value, bucket, scope, collection, id, options); e.ec) {
        couchbase_throw_exception(e);
        RETURN_THROWS();
    }
}

PHP_FUNCTION(documentGet)
{
    zval* connection = nullptr;
    zend_string* bucket = nullptr;
    zend_string* scope = nullptr;
    zend_string* collection = nullptr;
    zend_string* id = nullptr;
    zval* options = nullptr;

    ZEND_PARSE_PARAMETERS_START(5, 6)
    Z_PARAM_RESOURCE(connection)
    Z_PARAM_STR(bucket)
    Z_PARAM_STR(scope)
    Z_PARAM_STR(collection)
    Z_PARAM_STR(id)
    Z_PARAM_OPTIONAL
    Z_PARAM_ARRAY_OR_NULL(options)
    ZEND_PARSE_PARAMETERS_END();

    logger_flusher guard;

    auto* handle = fetch_couchbase_connection_from_resource(connection);
    if (handle == nullptr) {
        RETURN_THROWS();
    }

    if (auto e = handle->document_get(return_value, bucket, scope, collection, id, options); e.ec) {
        couchbase_throw_exception(e);
        RETURN_THROWS();
    }
}

PHP_FUNCTION(documentGetAnyReplica)
{
    zval* connection = nullptr;
    zend_string* bucket = nullptr;
    zend_string* scope = nullptr;
    zend_string* collection = nullptr;
    zend_string* id = nullptr;
    zval* options = nullptr;

    ZEND_PARSE_PARAMETERS_START(5, 6)
    Z_PARAM_RESOURCE(connection)
    Z_PARAM_STR(bucket)
    Z_PARAM_STR(scope)
    Z_PARAM_STR(collection)
    Z_PARAM_STR(id)
    Z_PARAM_OPTIONAL
    Z_PARAM_ARRAY_OR_NULL(options)
    ZEND_PARSE_PARAMETERS_END();

    logger_flusher guard;

    auto* handle = fetch_couchbase_connection_from_resource(connection);
    if (handle == nullptr) {
        RETURN_THROWS();
    }

    if (auto e = handle->document_get_any_replica(return_value, bucket, scope, collection, id, options); e.ec) {
        couchbase_throw_exception(e);
        RETURN_THROWS();
    }
}

PHP_FUNCTION(documentGetAllReplicas)
{
    zval* connection = nullptr;
    zend_string* bucket = nullptr;
    zend_string* scope = nullptr;
    zend_string* collection = nullptr;
    zend_string* id = nullptr;
    zval* options = nullptr;

    ZEND_PARSE_PARAMETERS_START(5, 6)
    Z_PARAM_RESOURCE(connection)
    Z_PARAM_STR(bucket)
    Z_PARAM_STR(scope)
    Z_PARAM_STR(collection)
    Z_PARAM_STR(id)
    Z_PARAM_OPTIONAL
    Z_PARAM_ARRAY_OR_NULL(options)
    ZEND_PARSE_PARAMETERS_END();

    logger_flusher guard;

    auto* handle = fetch_couchbase_connection_from_resource(connection);
    if (handle == nullptr) {
        RETURN_THROWS();
    }

    if (auto e = handle->document_get_all_replicas(return_value, bucket, scope, collection, id, options); e.ec) {
        couchbase_throw_exception(e);
        RETURN_THROWS();
    }
}

PHP_FUNCTION(documentGetAndLock)
{
    zval* connection = nullptr;
    zend_string* bucket = nullptr;
    zend_string* scope = nullptr;
    zend_string* collection = nullptr;
    zend_string* id = nullptr;
    zend_long lock_time;
    zval* options = nullptr;

    ZEND_PARSE_PARAMETERS_START(6, 7)
    Z_PARAM_RESOURCE(connection)
    Z_PARAM_STR(bucket)
    Z_PARAM_STR(scope)
    Z_PARAM_STR(collection)
    Z_PARAM_STR(id)
    Z_PARAM_LONG(lock_time)
    Z_PARAM_OPTIONAL
    Z_PARAM_ARRAY_OR_NULL(options)
    ZEND_PARSE_PARAMETERS_END();

    logger_flusher guard;

    auto* handle = fetch_couchbase_connection_from_resource(connection);
    if (handle == nullptr) {
        RETURN_THROWS();
    }

    if (auto e = handle->document_get_and_lock(return_value, bucket, scope, collection, id, lock_time, options); e.ec) {
        couchbase_throw_exception(e);
        RETURN_THROWS();
    }
}

PHP_FUNCTION(documentUnlock)
{
    zval* connection = nullptr;
    zend_string* bucket = nullptr;
    zend_string* scope = nullptr;
    zend_string* collection = nullptr;
    zend_string* id = nullptr;
    zend_string* cas = nullptr;
    zval* options = nullptr;

    ZEND_PARSE_PARAMETERS_START(6, 7)
    Z_PARAM_RESOURCE(connection)
    Z_PARAM_STR(bucket)
    Z_PARAM_STR(scope)
    Z_PARAM_STR(collection)
    Z_PARAM_STR(id)
    Z_PARAM_STR(cas)
    Z_PARAM_OPTIONAL
    Z_PARAM_ARRAY_OR_NULL(options)
    ZEND_PARSE_PARAMETERS_END();

    logger_flusher guard;

    auto* handle = fetch_couchbase_connection_from_resource(connection);
    if (handle == nullptr) {
        RETURN_THROWS();
    }

    if (auto e = handle->document_unlock(return_value, bucket, scope, collection, id, cas, options); e.ec) {
        couchbase_throw_exception(e);
        RETURN_THROWS();
    }
}

PHP_FUNCTION(documentRemove)
{
    zval* connection = nullptr;
    zend_string* bucket = nullptr;
    zend_string* scope = nullptr;
    zend_string* collection = nullptr;
    zend_string* id = nullptr;
    zval* options = nullptr;

    ZEND_PARSE_PARAMETERS_START(5, 6)
    Z_PARAM_RESOURCE(connection)
    Z_PARAM_STR(bucket)
    Z_PARAM_STR(scope)
    Z_PARAM_STR(collection)
    Z_PARAM_STR(id)
    Z_PARAM_OPTIONAL
    Z_PARAM_ARRAY_OR_NULL(options)
    ZEND_PARSE_PARAMETERS_END();

    logger_flusher guard;

    auto* handle = fetch_couchbase_connection_from_resource(connection);
    if (handle == nullptr) {
        RETURN_THROWS();
    }

    if (auto e = handle->document_remove(return_value, bucket, scope, collection, id, options); e.ec) {
        couchbase_throw_exception(e);
        RETURN_THROWS();
    }
}

PHP_FUNCTION(documentGetAndTouch)
{
    zval* connection = nullptr;
    zend_string* bucket = nullptr;
    zend_string* scope = nullptr;
    zend_string* collection = nullptr;
    zend_string* id = nullptr;
    zend_long expiry;
    zval* options = nullptr;

    ZEND_PARSE_PARAMETERS_START(6, 7)
    Z_PARAM_RESOURCE(connection)
    Z_PARAM_STR(bucket)
    Z_PARAM_STR(scope)
    Z_PARAM_STR(collection)
    Z_PARAM_STR(id)
    Z_PARAM_LONG(expiry)
    Z_PARAM_OPTIONAL
    Z_PARAM_ARRAY_OR_NULL(options)
    ZEND_PARSE_PARAMETERS_END();

    logger_flusher guard;

    auto* handle = fetch_couchbase_connection_from_resource(connection);
    if (handle == nullptr) {
        RETURN_THROWS();
    }

    if (auto e = handle->document_get_and_touch(return_value, bucket, scope, collection, id, expiry, options); e.ec) {
        couchbase_throw_exception(e);
        RETURN_THROWS();
    }
}

PHP_FUNCTION(documentTouch)
{
    zval* connection = nullptr;
    zend_string* bucket = nullptr;
    zend_string* scope = nullptr;
    zend_string* collection = nullptr;
    zend_string* id = nullptr;
    zend_long expiry;
    zval* options = nullptr;

    ZEND_PARSE_PARAMETERS_START(6, 7)
    Z_PARAM_RESOURCE(connection)
    Z_PARAM_STR(bucket)
    Z_PARAM_STR(scope)
    Z_PARAM_STR(collection)
    Z_PARAM_STR(id)
    Z_PARAM_LONG(expiry)
    Z_PARAM_OPTIONAL
    Z_PARAM_ARRAY_OR_NULL(options)
    ZEND_PARSE_PARAMETERS_END();

    logger_flusher guard;

    auto* handle = fetch_couchbase_connection_from_resource(connection);
    if (handle == nullptr) {
        RETURN_THROWS();
    }

    if (auto e = handle->document_touch(return_value, bucket, scope, collection, id, expiry, options); e.ec) {
        couchbase_throw_exception(e);
        RETURN_THROWS();
    }
}

PHP_FUNCTION(documentExists)
{
    zval* connection = nullptr;
    zend_string* bucket = nullptr;
    zend_string* scope = nullptr;
    zend_string* collection = nullptr;
    zend_string* id = nullptr;
    zval* options = nullptr;

    ZEND_PARSE_PARAMETERS_START(5, 6)
    Z_PARAM_RESOURCE(connection)
    Z_PARAM_STR(bucket)
    Z_PARAM_STR(scope)
    Z_PARAM_STR(collection)
    Z_PARAM_STR(id)
    Z_PARAM_OPTIONAL
    Z_PARAM_ARRAY_OR_NULL(options)
    ZEND_PARSE_PARAMETERS_END();

    logger_flusher guard;

    auto* handle = fetch_couchbase_connection_from_resource(connection);
    if (handle == nullptr) {
        RETURN_THROWS();
    }

    if (auto e = handle->document_exists(return_value, bucket, scope, collection, id, options); e.ec) {
        couchbase_throw_exception(e);
        RETURN_THROWS();
    }
}

PHP_FUNCTION(documentMutateIn)
{
    zval* connection = nullptr;
    zend_string* bucket = nullptr;
    zend_string* scope = nullptr;
    zend_string* collection = nullptr;
    zend_string* id = nullptr;
    zval* specs = nullptr;
    zval* options = nullptr;

    ZEND_PARSE_PARAMETERS_START(6, 7)
    Z_PARAM_RESOURCE(connection)
    Z_PARAM_STR(bucket)
    Z_PARAM_STR(scope)
    Z_PARAM_STR(collection)
    Z_PARAM_STR(id)
    Z_PARAM_ARRAY(specs)
    Z_PARAM_OPTIONAL
    Z_PARAM_ARRAY_OR_NULL(options)
    ZEND_PARSE_PARAMETERS_END();

    logger_flusher guard;

    auto* handle = fetch_couchbase_connection_from_resource(connection);
    if (handle == nullptr) {
        RETURN_THROWS();
    }

    if (auto e = handle->document_mutate_in(return_value, bucket, scope, collection, id, specs, options); e.ec) {
        couchbase_throw_exception(e);
        RETURN_THROWS();
    }
}

PHP_FUNCTION(documentLookupIn)
{
    zval* connection = nullptr;
    zend_string* bucket = nullptr;
    zend_string* scope = nullptr;
    zend_string* collection = nullptr;
    zend_string* id = nullptr;
    zval* specs = nullptr;
    zval* options = nullptr;

    ZEND_PARSE_PARAMETERS_START(6, 7)
    Z_PARAM_RESOURCE(connection)
    Z_PARAM_STR(bucket)
    Z_PARAM_STR(scope)
    Z_PARAM_STR(collection)
    Z_PARAM_STR(id)
    Z_PARAM_ARRAY(specs)
    Z_PARAM_OPTIONAL
    Z_PARAM_ARRAY_OR_NULL(options)
    ZEND_PARSE_PARAMETERS_END();

    logger_flusher guard;

    auto* handle = fetch_couchbase_connection_from_resource(connection);
    if (handle == nullptr) {
        RETURN_THROWS();
    }

    if (auto e = handle->document_lookup_in(return_value, bucket, scope, collection, id, specs, options); e.ec) {
        couchbase_throw_exception(e);
        RETURN_THROWS();
    }
}

PHP_FUNCTION(documentLookupInAnyReplica)
{
    zval* connection = nullptr;
    zend_string* bucket = nullptr;
    zend_string* scope = nullptr;
    zend_string* collection = nullptr;
    zend_string* id = nullptr;
    zval* specs = nullptr;
    zval* options = nullptr;

    ZEND_PARSE_PARAMETERS_START(6, 7)
    Z_PARAM_RESOURCE(connection)
    Z_PARAM_STR(bucket)
    Z_PARAM_STR(scope)
    Z_PARAM_STR(collection)
    Z_PARAM_STR(id)
    Z_PARAM_ARRAY(specs)
    Z_PARAM_OPTIONAL
    Z_PARAM_ARRAY_OR_NULL(options)
    ZEND_PARSE_PARAMETERS_END();

    logger_flusher guard;

    auto* handle = fetch_couchbase_connection_from_resource(connection);
    if (handle == nullptr) {
        RETURN_THROWS();
    }

    if (auto e = handle->document_lookup_in_any_replica(return_value, bucket, scope, collection, id, specs, options); e.ec) {
        couchbase_throw_exception(e);
        RETURN_THROWS();
    }
}

PHP_FUNCTION(documentLookupInAllReplicas)
{
    zval* connection = nullptr;
    zend_string* bucket = nullptr;
    zend_string* scope = nullptr;
    zend_string* collection = nullptr;
    zend_string* id = nullptr;
    zval* specs = nullptr;
    zval* options = nullptr;

    ZEND_PARSE_PARAMETERS_START(6, 7)
    Z_PARAM_RESOURCE(connection)
    Z_PARAM_STR(bucket)
    Z_PARAM_STR(scope)
    Z_PARAM_STR(collection)
    Z_PARAM_STR(id)
    Z_PARAM_ARRAY(specs)
    Z_PARAM_OPTIONAL
    Z_PARAM_ARRAY_OR_NULL(options)
    ZEND_PARSE_PARAMETERS_END();

    logger_flusher guard;

    auto* handle = fetch_couchbase_connection_from_resource(connection);
    if (handle == nullptr) {
        RETURN_THROWS();
    }

    if (auto e = handle->document_lookup_in_all_replicas(return_value, bucket, scope, collection, id, specs, options); e.ec) {
        couchbase_throw_exception(e);
        RETURN_THROWS();
    }
}

static inline couchbase::php::scan_result_resource*
fetch_couchbase_scan_result_from_resource(zval* resource)
{
    return static_cast<couchbase::php::scan_result_resource*>(
      zend_fetch_resource(Z_RES_P(resource), "couchbase_scan_result", couchbase::php::get_scan_result_destructor_id()));
}

PHP_FUNCTION(createDocumentScanResult)
{
    zval* connection = nullptr;
    zend_string* bucket = nullptr;
    zend_string* scope = nullptr;
    zend_string* collection = nullptr;
    zval* scan_type = nullptr;
    zval* options = nullptr;

    ZEND_PARSE_PARAMETERS_START(5, 6)
    Z_PARAM_RESOURCE(connection)
    Z_PARAM_STR(bucket)
    Z_PARAM_STR(scope)
    Z_PARAM_STR(collection)
    Z_PARAM_ARRAY(scan_type)
    Z_PARAM_OPTIONAL
    Z_PARAM_ARRAY_OR_NULL(options)
    ZEND_PARSE_PARAMETERS_END();

    logger_flusher guard;

    auto* handle = fetch_couchbase_connection_from_resource(connection);
    if (handle == nullptr) {
        RETURN_THROWS();
    }

    auto [resource, e] = couchbase::php::create_scan_result_resource(handle, bucket, scope, collection, scan_type, options);
    if (e.ec) {
        couchbase_throw_exception(e);
        RETURN_THROWS();
    }
    RETURN_RES(resource);
}

PHP_FUNCTION(documentScanNextItem)
{
    zval* scan_result = nullptr;

    ZEND_PARSE_PARAMETERS_START(1, 1)
    Z_PARAM_RESOURCE(scan_result)
    ZEND_PARSE_PARAMETERS_END();

    logger_flusher guard;

    auto* scan_res = fetch_couchbase_scan_result_from_resource(scan_result);
    if (scan_res == nullptr) {
        RETURN_THROWS();
    }
    if (auto e = scan_res->next_item(return_value); e.ec) {

        couchbase_throw_exception(e);
        RETURN_THROWS();
    }
}

PHP_FUNCTION(documentGetMulti)
{
    zval* connection = nullptr;
    zend_string* bucket = nullptr;
    zend_string* scope = nullptr;
    zend_string* collection = nullptr;
    zval* ids = nullptr;
    zval* options = nullptr;

    ZEND_PARSE_PARAMETERS_START(5, 6)
    Z_PARAM_RESOURCE(connection)
    Z_PARAM_STR(bucket)
    Z_PARAM_STR(scope)
    Z_PARAM_STR(collection)
    Z_PARAM_ARRAY(ids)
    Z_PARAM_OPTIONAL
    Z_PARAM_ARRAY_OR_NULL(options)
    ZEND_PARSE_PARAMETERS_END();

    logger_flusher guard;

    auto* handle = fetch_couchbase_connection_from_resource(connection);
    if (handle == nullptr) {
        RETURN_THROWS();
    }

    if (auto e = handle->document_get_multi(return_value, bucket, scope, collection, ids, options); e.ec) {
        couchbase_throw_exception(e);
        RETURN_THROWS();
    }
}

PHP_FUNCTION(documentRemoveMulti)
{
    zval* connection = nullptr;
    zend_string* bucket = nullptr;
    zend_string* scope = nullptr;
    zend_string* collection = nullptr;
    zval* entries = nullptr;
    zval* options = nullptr;

    ZEND_PARSE_PARAMETERS_START(5, 6)
    Z_PARAM_RESOURCE(connection)
    Z_PARAM_STR(bucket)
    Z_PARAM_STR(scope)
    Z_PARAM_STR(collection)
    Z_PARAM_ARRAY(entries)
    Z_PARAM_OPTIONAL
    Z_PARAM_ARRAY_OR_NULL(options)
    ZEND_PARSE_PARAMETERS_END();

    logger_flusher guard;

    auto* handle = fetch_couchbase_connection_from_resource(connection);
    if (handle == nullptr) {
        RETURN_THROWS();
    }

    if (auto e = handle->document_remove_multi(return_value, bucket, scope, collection, entries, options); e.ec) {
        couchbase_throw_exception(e);
        RETURN_THROWS();
    }
}

PHP_FUNCTION(documentUpsertMulti)
{
    zval* connection = nullptr;
    zend_string* bucket = nullptr;
    zend_string* scope = nullptr;
    zend_string* collection = nullptr;
    zval* entries = nullptr;
    zval* options = nullptr;

    ZEND_PARSE_PARAMETERS_START(5, 6)
    Z_PARAM_RESOURCE(connection)
    Z_PARAM_STR(bucket)
    Z_PARAM_STR(scope)
    Z_PARAM_STR(collection)
    Z_PARAM_ARRAY(entries)
    Z_PARAM_OPTIONAL
    Z_PARAM_ARRAY_OR_NULL(options)
    ZEND_PARSE_PARAMETERS_END();

    logger_flusher guard;

    auto* handle = fetch_couchbase_connection_from_resource(connection);
    if (handle == nullptr) {
        RETURN_THROWS();
    }

    if (auto e = handle->document_upsert_multi(return_value, bucket, scope, collection, entries, options); e.ec) {
        couchbase_throw_exception(e);
        RETURN_THROWS();
    }
}

PHP_FUNCTION(query)
{
    zval* connection = nullptr;
    zend_string* statement = nullptr;
    zval* options = nullptr;

    ZEND_PARSE_PARAMETERS_START(2, 3)
    Z_PARAM_RESOURCE(connection)
    Z_PARAM_STR(statement)
    Z_PARAM_OPTIONAL
    Z_PARAM_ARRAY_OR_NULL(options)
    ZEND_PARSE_PARAMETERS_END();

    logger_flusher guard;

    auto* handle = fetch_couchbase_connection_from_resource(connection);
    if (handle == nullptr) {
        RETURN_THROWS();
    }
    if (auto e = handle->query(return_value, statement, options); e.ec) {
        couchbase_throw_exception(e);
        RETURN_THROWS();
    }
}

PHP_FUNCTION(analyticsQuery)
{
    zval* connection = nullptr;
    zend_string* statement = nullptr;
    zval* options = nullptr;

    ZEND_PARSE_PARAMETERS_START(2, 3)
    Z_PARAM_RESOURCE(connection)
    Z_PARAM_STR(statement)
    Z_PARAM_OPTIONAL
    Z_PARAM_ARRAY_OR_NULL(options)
    ZEND_PARSE_PARAMETERS_END();

    logger_flusher guard;

    auto* handle = fetch_couchbase_connection_from_resource(connection);
    if (handle == nullptr) {
        RETURN_THROWS();
    }

    if (auto e = handle->analytics_query(return_value, statement, options); e.ec) {
        couchbase_throw_exception(e);
        RETURN_THROWS();
    }
}

PHP_FUNCTION(viewQuery)
{
    zval* connection = nullptr;
    zend_string* bucket_name = nullptr;
    zend_string* design_document_name = nullptr;
    zend_string* view_name = nullptr;
    zend_long name_space = 0;
    zval* options = nullptr;

    ZEND_PARSE_PARAMETERS_START(5, 6)
    Z_PARAM_RESOURCE(connection)
    Z_PARAM_STR(bucket_name)
    Z_PARAM_STR(design_document_name)
    Z_PARAM_STR(view_name)
    Z_PARAM_LONG(name_space)
    Z_PARAM_OPTIONAL
    Z_PARAM_ARRAY_OR_NULL(options)
    ZEND_PARSE_PARAMETERS_END();

    logger_flusher guard;

    auto* handle = fetch_couchbase_connection_from_resource(connection);
    if (handle == nullptr) {
        RETURN_THROWS();
    }

    if (auto e = handle->view_query(return_value, bucket_name, design_document_name, view_name, name_space, options); e.ec) {
        couchbase_throw_exception(e);
        RETURN_THROWS();
    }
}

PHP_FUNCTION(searchQuery)
{
    zval* connection = nullptr;
    zend_string* index_name = nullptr;
    zend_string* query = nullptr;
    zval* options = nullptr;

    ZEND_PARSE_PARAMETERS_START(3, 4)
    Z_PARAM_RESOURCE(connection)
    Z_PARAM_STR(index_name)
    Z_PARAM_STR(query)
    Z_PARAM_OPTIONAL
    Z_PARAM_ARRAY_OR_NULL(options)
    ZEND_PARSE_PARAMETERS_END();

    logger_flusher guard;

    auto* handle = fetch_couchbase_connection_from_resource(connection);
    if (handle == nullptr) {
        RETURN_THROWS();
    }
    if (auto e = handle->search_query(return_value, index_name, query, options); e.ec) {
        couchbase_throw_exception(e);
        RETURN_THROWS();
    }
}

PHP_FUNCTION(vectorSearch)
{
    zval* connection = nullptr;
    zend_string* index_name = nullptr;
    zend_string* query = nullptr;
    zend_string* vector_search = nullptr;
    zval* options = nullptr;
    zval* vector_options = nullptr;

    ZEND_PARSE_PARAMETERS_START(4, 6)
    Z_PARAM_RESOURCE(connection)
    Z_PARAM_STR(index_name)
    Z_PARAM_STR(query)
    Z_PARAM_STR(vector_search)
    Z_PARAM_OPTIONAL
    Z_PARAM_ARRAY_OR_NULL(options)
    Z_PARAM_ARRAY_OR_NULL(vector_options)
    ZEND_PARSE_PARAMETERS_END();

    logger_flusher guard;

    auto* handle = fetch_couchbase_connection_from_resource(connection);
    if (handle == nullptr) {
        RETURN_THROWS();
    }
    if (auto e = handle->search(return_value, index_name, query, options, vector_search, vector_options); e.ec) {
        couchbase_throw_exception(e);
        RETURN_THROWS();
    }
}

PHP_FUNCTION(ping)
{
    zval* connection = nullptr;
    zval* options = nullptr;

    ZEND_PARSE_PARAMETERS_START(1, 2)
    Z_PARAM_RESOURCE(connection)
    Z_PARAM_OPTIONAL
    Z_PARAM_ARRAY_OR_NULL(options)
    ZEND_PARSE_PARAMETERS_END();

    logger_flusher guard;

    auto* handle = fetch_couchbase_connection_from_resource(connection);
    if (handle == nullptr) {
        RETURN_THROWS();
    }

    if (auto e = handle->ping(return_value, options); e.ec) {
        couchbase_throw_exception(e);
        RETURN_THROWS();
    }
}

PHP_FUNCTION(diagnostics)
{
    zval* connection = nullptr;
    zend_string* reportId = nullptr;
    zval* options = nullptr;

    ZEND_PARSE_PARAMETERS_START(2, 3)
    Z_PARAM_RESOURCE(connection)
    Z_PARAM_STR(reportId)
    Z_PARAM_OPTIONAL
    Z_PARAM_ARRAY_OR_NULL(options)
    ZEND_PARSE_PARAMETERS_END();

    logger_flusher guard;

    auto* handle = fetch_couchbase_connection_from_resource(connection);
    if (handle == nullptr) {
        RETURN_THROWS();
    }

    if (auto e = handle->diagnostics(return_value, reportId, options); e.ec) {
        couchbase_throw_exception(e);
        RETURN_THROWS();
    }
}

PHP_FUNCTION(searchIndexGet)
{
    zval* connection = nullptr;
    zend_string* index_name;
    zval* options = nullptr;

    ZEND_PARSE_PARAMETERS_START(2, 3)
    Z_PARAM_RESOURCE(connection)
    Z_PARAM_STR(index_name)
    Z_PARAM_OPTIONAL
    Z_PARAM_ARRAY_OR_NULL(options)
    ZEND_PARSE_PARAMETERS_END();

    logger_flusher guard;

    auto* handle = fetch_couchbase_connection_from_resource(connection);
    if (handle == nullptr) {
        RETURN_THROWS();
    }
    if (auto e = handle->search_index_get(return_value, index_name, options); e.ec) {
        couchbase_throw_exception(e);
        RETURN_THROWS();
    }
}

PHP_FUNCTION(searchIndexGetAll)
{
    zval* connection = nullptr;
    zval* options = nullptr;

    ZEND_PARSE_PARAMETERS_START(1, 2)
    Z_PARAM_RESOURCE(connection)
    Z_PARAM_OPTIONAL
    Z_PARAM_ARRAY_OR_NULL(options)
    ZEND_PARSE_PARAMETERS_END();

    logger_flusher guard;

    auto* handle = fetch_couchbase_connection_from_resource(connection);
    if (handle == nullptr) {
        RETURN_THROWS();
    }
    if (auto e = handle->search_index_get_all(return_value, options); e.ec) {
        couchbase_throw_exception(e);
        RETURN_THROWS();
    }
}

PHP_FUNCTION(searchIndexUpsert)
{
    zval* connection = nullptr;
    zval* index = nullptr;
    zval* options = nullptr;

    ZEND_PARSE_PARAMETERS_START(2, 3)
    Z_PARAM_RESOURCE(connection)
    Z_PARAM_ARRAY(index)
    Z_PARAM_OPTIONAL
    Z_PARAM_ARRAY_OR_NULL(options)
    ZEND_PARSE_PARAMETERS_END();

    logger_flusher guard;

    auto* handle = fetch_couchbase_connection_from_resource(connection);
    if (handle == nullptr) {
        RETURN_THROWS();
    }

    if (auto e = handle->search_index_upsert(return_value, index, options); e.ec) {
        couchbase_throw_exception(e);
        RETURN_THROWS();
    }
}

PHP_FUNCTION(searchIndexDrop)
{
    zval* connection = nullptr;
    zend_string* index_name = nullptr;
    zval* options = nullptr;

    ZEND_PARSE_PARAMETERS_START(2, 3)
    Z_PARAM_RESOURCE(connection)
    Z_PARAM_STR(index_name)
    Z_PARAM_OPTIONAL
    Z_PARAM_ARRAY_OR_NULL(options)
    ZEND_PARSE_PARAMETERS_END();

    logger_flusher guard;

    auto* handle = fetch_couchbase_connection_from_resource(connection);
    if (handle == nullptr) {
        RETURN_THROWS();
    }

    if (auto e = handle->search_index_drop(return_value, index_name, options); e.ec) {
        couchbase_throw_exception(e);
        RETURN_THROWS();
    }
}

PHP_FUNCTION(searchIndexGetDocumentsCount)
{
    zval* connection = nullptr;
    zend_string* index_name = nullptr;
    zval* options = nullptr;

    ZEND_PARSE_PARAMETERS_START(2, 3)
    Z_PARAM_RESOURCE(connection)
    Z_PARAM_STR(index_name)
    Z_PARAM_OPTIONAL
    Z_PARAM_ARRAY_OR_NULL(options)
    ZEND_PARSE_PARAMETERS_END();

    logger_flusher guard;

    auto* handle = fetch_couchbase_connection_from_resource(connection);
    if (handle == nullptr) {
        RETURN_THROWS();
    }

    if (auto e = handle->search_index_get_documents_count(return_value, index_name, options); e.ec) {
        couchbase_throw_exception(e);
        RETURN_THROWS();
    }
}

PHP_FUNCTION(searchIndexIngestPause)
{
    zval* connection = nullptr;
    zend_string* index_name = nullptr;
    zval* options = nullptr;

    ZEND_PARSE_PARAMETERS_START(2, 3)
    Z_PARAM_RESOURCE(connection)
    Z_PARAM_STR(index_name)
    Z_PARAM_OPTIONAL
    Z_PARAM_ARRAY_OR_NULL(options);
    ZEND_PARSE_PARAMETERS_END();

    logger_flusher guard;

    auto* handle = fetch_couchbase_connection_from_resource(connection);
    if (handle == nullptr) {
        RETURN_THROWS();
    }

    if (auto e = handle->search_index_control_ingest(return_value, index_name, true, options); e.ec) {
        couchbase_throw_exception(e);
        RETURN_THROWS();
    }
}

PHP_FUNCTION(searchIndexIngestResume)
{
    zval* connection = nullptr;
    zend_string* index_name = nullptr;
    zval* options = nullptr;

    ZEND_PARSE_PARAMETERS_START(2, 3)
    Z_PARAM_RESOURCE(connection)
    Z_PARAM_STR(index_name)
    Z_PARAM_OPTIONAL
    Z_PARAM_ARRAY_OR_NULL(options);
    ZEND_PARSE_PARAMETERS_END();

    logger_flusher guard;

    auto* handle = fetch_couchbase_connection_from_resource(connection);
    if (handle == nullptr) {
        RETURN_THROWS();
    }

    if (auto e = handle->search_index_control_ingest(return_value, index_name, false, options); e.ec) {
        couchbase_throw_exception(e);
        RETURN_THROWS();
    }
}

PHP_FUNCTION(searchIndexQueryingAllow)
{
    zval* connection = nullptr;
    zend_string* index_name = nullptr;
    zval* options = nullptr;

    ZEND_PARSE_PARAMETERS_START(2, 3)
    Z_PARAM_RESOURCE(connection)
    Z_PARAM_STR(index_name)
    Z_PARAM_OPTIONAL
    Z_PARAM_ARRAY_OR_NULL(options);
    ZEND_PARSE_PARAMETERS_END();

    logger_flusher guard;

    auto* handle = fetch_couchbase_connection_from_resource(connection);
    if (handle == nullptr) {
        RETURN_THROWS();
    }

    if (auto e = handle->search_index_control_query(return_value, index_name, true, options); e.ec) {
        couchbase_throw_exception(e);
        RETURN_THROWS();
    }
}

PHP_FUNCTION(searchIndexQueryingDisallow)
{
    zval* connection = nullptr;
    zend_string* index_name = nullptr;
    zval* options = nullptr;

    ZEND_PARSE_PARAMETERS_START(2, 3)
    Z_PARAM_RESOURCE(connection)
    Z_PARAM_STR(index_name)
    Z_PARAM_OPTIONAL
    Z_PARAM_ARRAY_OR_NULL(options);
    ZEND_PARSE_PARAMETERS_END();

    logger_flusher guard;

    auto* handle = fetch_couchbase_connection_from_resource(connection);
    if (handle == nullptr) {
        RETURN_THROWS();
    }

    if (auto e = handle->search_index_control_query(return_value, index_name, false, options); e.ec) {
        couchbase_throw_exception(e);
        RETURN_THROWS();
    }
}

PHP_FUNCTION(searchIndexPlanFreeze)
{
    zval* connection = nullptr;
    zend_string* index_name = nullptr;
    zval* options = nullptr;

    ZEND_PARSE_PARAMETERS_START(2, 3)
    Z_PARAM_RESOURCE(connection)
    Z_PARAM_STR(index_name)
    Z_PARAM_OPTIONAL
    Z_PARAM_ARRAY_OR_NULL(options);
    ZEND_PARSE_PARAMETERS_END();

    logger_flusher guard;

    auto* handle = fetch_couchbase_connection_from_resource(connection);
    if (handle == nullptr) {
        RETURN_THROWS();
    }

    if (auto e = handle->search_index_control_plan_freeze(return_value, index_name, true, options); e.ec) {
        couchbase_throw_exception(e);
        RETURN_THROWS();
    }
}

PHP_FUNCTION(searchIndexPlanUnfreeze)
{
    zval* connection = nullptr;
    zend_string* index_name = nullptr;
    zval* options = nullptr;

    ZEND_PARSE_PARAMETERS_START(2, 3)
    Z_PARAM_RESOURCE(connection)
    Z_PARAM_STR(index_name)
    Z_PARAM_OPTIONAL
    Z_PARAM_ARRAY_OR_NULL(options);
    ZEND_PARSE_PARAMETERS_END();

    logger_flusher guard;

    auto* handle = fetch_couchbase_connection_from_resource(connection);
    if (handle == nullptr) {
        RETURN_THROWS();
    }

    if (auto e = handle->search_index_control_plan_freeze(return_value, index_name, false, options); e.ec) {
        couchbase_throw_exception(e);
        RETURN_THROWS();
    }
}

PHP_FUNCTION(searchIndexDocumentAnalyze)
{
    zval* connection = nullptr;
    zend_string* index_name = nullptr;
    zend_string* document = nullptr;
    zval* options = nullptr;

    ZEND_PARSE_PARAMETERS_START(3, 4)
    Z_PARAM_RESOURCE(connection)
    Z_PARAM_STR(index_name)
    Z_PARAM_STR(document)
    Z_PARAM_OPTIONAL
    Z_PARAM_ARRAY_OR_NULL(options);
    ZEND_PARSE_PARAMETERS_END();

    logger_flusher guard;

    auto* handle = fetch_couchbase_connection_from_resource(connection);
    if (handle == nullptr) {
        RETURN_THROWS();
    }

    if (auto e = handle->search_index_analyze_document(return_value, index_name, document, options); e.ec) {
        couchbase_throw_exception(e);
        RETURN_THROWS();
    }
}

PHP_FUNCTION(scopeSearchIndexGet)
{
    zval* connection = nullptr;
    zend_string* bucket_name = nullptr;
    zend_string* scope_name = nullptr;
    zend_string* index_name = nullptr;
    zval* options = nullptr;

    ZEND_PARSE_PARAMETERS_START(4, 5)
    Z_PARAM_RESOURCE(connection)
    Z_PARAM_STR(bucket_name)
    Z_PARAM_STR(scope_name)
    Z_PARAM_STR(index_name)
    Z_PARAM_OPTIONAL
    Z_PARAM_ARRAY_OR_NULL(options)
    ZEND_PARSE_PARAMETERS_END();

    logger_flusher guard;

    auto* handle = fetch_couchbase_connection_from_resource(connection);
    if (handle == nullptr) {
        RETURN_THROWS();
    }
    if (auto e = handle->scope_search_index_get(return_value, bucket_name, scope_name, index_name, options); e.ec) {
        couchbase_throw_exception(e);
        RETURN_THROWS();
    }
}

PHP_FUNCTION(scopeSearchIndexGetAll)
{
    zval* connection = nullptr;
    zend_string* bucket_name = nullptr;
    zend_string* scope_name = nullptr;
    zval* options = nullptr;

    ZEND_PARSE_PARAMETERS_START(3, 4)
    Z_PARAM_RESOURCE(connection)
    Z_PARAM_STR(bucket_name)
    Z_PARAM_STR(scope_name)
    Z_PARAM_OPTIONAL
    Z_PARAM_ARRAY_OR_NULL(options)
    ZEND_PARSE_PARAMETERS_END();

    logger_flusher guard;

    auto* handle = fetch_couchbase_connection_from_resource(connection);
    if (handle == nullptr) {
        RETURN_THROWS();
    }
    if (auto e = handle->scope_search_index_get_all(return_value, bucket_name, scope_name, options); e.ec) {
        couchbase_throw_exception(e);
        RETURN_THROWS();
    }
}

PHP_FUNCTION(scopeSearchIndexUpsert)
{
    zval* connection = nullptr;
    zend_string* bucket_name = nullptr;
    zend_string* scope_name = nullptr;
    zval* index = nullptr;
    zval* options = nullptr;

    ZEND_PARSE_PARAMETERS_START(4, 5)
    Z_PARAM_RESOURCE(connection)
    Z_PARAM_STR(bucket_name)
    Z_PARAM_STR(scope_name)
    Z_PARAM_ARRAY(index)
    Z_PARAM_OPTIONAL
    Z_PARAM_ARRAY_OR_NULL(options)
    ZEND_PARSE_PARAMETERS_END();

    logger_flusher guard;

    auto* handle = fetch_couchbase_connection_from_resource(connection);
    if (handle == nullptr) {
        RETURN_THROWS();
    }

    if (auto e = handle->scope_search_index_upsert(return_value, bucket_name, scope_name, index, options); e.ec) {
        couchbase_throw_exception(e);
        RETURN_THROWS();
    }
}

PHP_FUNCTION(scopeSearchIndexDrop)
{
    zval* connection = nullptr;
    zend_string* bucket_name = nullptr;
    zend_string* scope_name = nullptr;
    zend_string* index_name = nullptr;
    zval* options = nullptr;

    ZEND_PARSE_PARAMETERS_START(4, 5)
    Z_PARAM_RESOURCE(connection)
    Z_PARAM_STR(bucket_name)
    Z_PARAM_STR(scope_name)
    Z_PARAM_STR(index_name)
    Z_PARAM_OPTIONAL
    Z_PARAM_ARRAY_OR_NULL(options)
    ZEND_PARSE_PARAMETERS_END();

    logger_flusher guard;

    auto* handle = fetch_couchbase_connection_from_resource(connection);
    if (handle == nullptr) {
        RETURN_THROWS();
    }

    if (auto e = handle->scope_search_index_drop(return_value, bucket_name, scope_name, index_name, options); e.ec) {
        couchbase_throw_exception(e);
        RETURN_THROWS();
    }
}

PHP_FUNCTION(scopeSearchIndexGetDocumentsCount)
{
    zval* connection = nullptr;
    zend_string* bucket_name = nullptr;
    zend_string* scope_name = nullptr;
    zend_string* index_name = nullptr;
    zval* options = nullptr;

    ZEND_PARSE_PARAMETERS_START(4, 5)
    Z_PARAM_RESOURCE(connection)
    Z_PARAM_STR(bucket_name)
    Z_PARAM_STR(scope_name)
    Z_PARAM_STR(index_name)
    Z_PARAM_OPTIONAL
    Z_PARAM_ARRAY_OR_NULL(options)
    ZEND_PARSE_PARAMETERS_END();

    logger_flusher guard;

    auto* handle = fetch_couchbase_connection_from_resource(connection);
    if (handle == nullptr) {
        RETURN_THROWS();
    }

    if (auto e = handle->scope_search_index_get_documents_count(return_value, bucket_name, scope_name, index_name, options); e.ec) {
        couchbase_throw_exception(e);
        RETURN_THROWS();
    }
}

PHP_FUNCTION(scopeSearchIndexIngestPause)
{
    zval* connection = nullptr;
    zend_string* bucket_name = nullptr;
    zend_string* scope_name = nullptr;
    zend_string* index_name = nullptr;
    zval* options = nullptr;

    ZEND_PARSE_PARAMETERS_START(4, 5)
    Z_PARAM_RESOURCE(connection)
    Z_PARAM_STR(bucket_name)
    Z_PARAM_STR(scope_name)
    Z_PARAM_STR(index_name)
    Z_PARAM_OPTIONAL
    Z_PARAM_ARRAY_OR_NULL(options);
    ZEND_PARSE_PARAMETERS_END();

    logger_flusher guard;

    auto* handle = fetch_couchbase_connection_from_resource(connection);
    if (handle == nullptr) {
        RETURN_THROWS();
    }

    if (auto e = handle->scope_search_index_control_ingest(return_value, bucket_name, scope_name, index_name, true, options); e.ec) {
        couchbase_throw_exception(e);
        RETURN_THROWS();
    }
}

PHP_FUNCTION(scopeSearchIndexIngestResume)
{
    zval* connection = nullptr;
    zend_string* bucket_name = nullptr;
    zend_string* scope_name = nullptr;
    zend_string* index_name = nullptr;
    zval* options = nullptr;

    ZEND_PARSE_PARAMETERS_START(4, 5)
    Z_PARAM_RESOURCE(connection)
    Z_PARAM_STR(bucket_name)
    Z_PARAM_STR(scope_name)
    Z_PARAM_STR(index_name)
    Z_PARAM_OPTIONAL
    Z_PARAM_ARRAY_OR_NULL(options);
    ZEND_PARSE_PARAMETERS_END();

    logger_flusher guard;

    auto* handle = fetch_couchbase_connection_from_resource(connection);
    if (handle == nullptr) {
        RETURN_THROWS();
    }

    if (auto e = handle->scope_search_index_control_ingest(return_value, bucket_name, scope_name, index_name, false, options); e.ec) {
        couchbase_throw_exception(e);
        RETURN_THROWS();
    }
}

PHP_FUNCTION(scopeSearchIndexQueryingAllow)
{
    zval* connection = nullptr;
    zend_string* bucket_name = nullptr;
    zend_string* scope_name = nullptr;
    zend_string* index_name = nullptr;
    zval* options = nullptr;

    ZEND_PARSE_PARAMETERS_START(4, 5)
    Z_PARAM_RESOURCE(connection)
    Z_PARAM_STR(bucket_name)
    Z_PARAM_STR(scope_name)
    Z_PARAM_STR(index_name)
    Z_PARAM_OPTIONAL
    Z_PARAM_ARRAY_OR_NULL(options);
    ZEND_PARSE_PARAMETERS_END();

    logger_flusher guard;

    auto* handle = fetch_couchbase_connection_from_resource(connection);
    if (handle == nullptr) {
        RETURN_THROWS();
    }

    if (auto e = handle->scope_search_index_control_query(return_value, bucket_name, scope_name, index_name, true, options); e.ec) {
        couchbase_throw_exception(e);
        RETURN_THROWS();
    }
}

PHP_FUNCTION(scopeSearchIndexQueryingDisallow)
{
    zval* connection = nullptr;
    zend_string* bucket_name = nullptr;
    zend_string* scope_name = nullptr;
    zend_string* index_name = nullptr;
    zval* options = nullptr;

    ZEND_PARSE_PARAMETERS_START(4, 5)
    Z_PARAM_RESOURCE(connection)
    Z_PARAM_STR(bucket_name)
    Z_PARAM_STR(scope_name)
    Z_PARAM_STR(index_name)
    Z_PARAM_OPTIONAL
    Z_PARAM_ARRAY_OR_NULL(options);
    ZEND_PARSE_PARAMETERS_END();

    logger_flusher guard;

    auto* handle = fetch_couchbase_connection_from_resource(connection);
    if (handle == nullptr) {
        RETURN_THROWS();
    }

    if (auto e = handle->scope_search_index_control_query(return_value, bucket_name, scope_name, index_name, false, options); e.ec) {
        couchbase_throw_exception(e);
        RETURN_THROWS();
    }
}

PHP_FUNCTION(scopeSearchIndexPlanFreeze)
{
    zval* connection = nullptr;
    zend_string* bucket_name = nullptr;
    zend_string* scope_name = nullptr;
    zend_string* index_name = nullptr;
    zval* options = nullptr;

    ZEND_PARSE_PARAMETERS_START(4, 5)
    Z_PARAM_RESOURCE(connection)
    Z_PARAM_STR(bucket_name)
    Z_PARAM_STR(scope_name)
    Z_PARAM_STR(index_name)
    Z_PARAM_OPTIONAL
    Z_PARAM_ARRAY_OR_NULL(options);
    ZEND_PARSE_PARAMETERS_END();

    logger_flusher guard;

    auto* handle = fetch_couchbase_connection_from_resource(connection);
    if (handle == nullptr) {
        RETURN_THROWS();
    }

    if (auto e = handle->scope_search_index_control_plan_freeze(return_value, bucket_name, scope_name, index_name, true, options); e.ec) {
        couchbase_throw_exception(e);
        RETURN_THROWS();
    }
}

PHP_FUNCTION(scopeSearchIndexPlanUnfreeze)
{
    zval* connection = nullptr;
    zend_string* bucket_name = nullptr;
    zend_string* scope_name = nullptr;
    zend_string* index_name = nullptr;
    zval* options = nullptr;

    ZEND_PARSE_PARAMETERS_START(4, 5)
    Z_PARAM_RESOURCE(connection)
    Z_PARAM_STR(bucket_name)
    Z_PARAM_STR(scope_name)
    Z_PARAM_STR(index_name)
    Z_PARAM_OPTIONAL
    Z_PARAM_ARRAY_OR_NULL(options);
    ZEND_PARSE_PARAMETERS_END();

    logger_flusher guard;

    auto* handle = fetch_couchbase_connection_from_resource(connection);
    if (handle == nullptr) {
        RETURN_THROWS();
    }

    if (auto e = handle->scope_search_index_control_plan_freeze(return_value, bucket_name, scope_name, index_name, false, options); e.ec) {
        couchbase_throw_exception(e);
        RETURN_THROWS();
    }
}

PHP_FUNCTION(scopeSearchIndexDocumentAnalyze)
{
    zval* connection = nullptr;
    zend_string* bucket_name = nullptr;
    zend_string* scope_name = nullptr;
    zend_string* index_name = nullptr;
    zend_string* document = nullptr;
    zval* options = nullptr;

    ZEND_PARSE_PARAMETERS_START(5, 6)
    Z_PARAM_RESOURCE(connection)
    Z_PARAM_STR(bucket_name)
    Z_PARAM_STR(scope_name)
    Z_PARAM_STR(index_name)
    Z_PARAM_STR(document)
    Z_PARAM_OPTIONAL
    Z_PARAM_ARRAY_OR_NULL(options);
    ZEND_PARSE_PARAMETERS_END();

    logger_flusher guard;

    auto* handle = fetch_couchbase_connection_from_resource(connection);
    if (handle == nullptr) {
        RETURN_THROWS();
    }

    if (auto e = handle->scope_search_index_analyze_document(return_value, bucket_name, scope_name, index_name, document, options); e.ec) {
        couchbase_throw_exception(e);
        RETURN_THROWS();
    }
}

PHP_FUNCTION(viewIndexUpsert)
{
    zval* connection = nullptr;
    zend_string* bucket_name = nullptr;
    zval* index = nullptr;
    zend_long name_space = 0;
    zval* options = nullptr;

    ZEND_PARSE_PARAMETERS_START(4, 5)
    Z_PARAM_RESOURCE(connection)
    Z_PARAM_STR(bucket_name)
    Z_PARAM_ARRAY(index)
    Z_PARAM_LONG(name_space)
    Z_PARAM_OPTIONAL
    Z_PARAM_ARRAY_OR_NULL(options)
    ZEND_PARSE_PARAMETERS_END();

    logger_flusher guard;

    auto* handle = fetch_couchbase_connection_from_resource(connection);
    if (handle == nullptr) {
        RETURN_THROWS();
    }
    if (auto e = handle->view_index_upsert(return_value, bucket_name, index, name_space, options); e.ec) {
        couchbase_throw_exception(e);
        RETURN_THROWS();
    }
}

PHP_FUNCTION(bucketCreate)
{
    zval* connection = nullptr;
    zval* bucket_settings = nullptr;
    zval* options = nullptr;

    ZEND_PARSE_PARAMETERS_START(2, 3)
    Z_PARAM_RESOURCE(connection)
    Z_PARAM_ARRAY(bucket_settings)
    Z_PARAM_OPTIONAL
    Z_PARAM_ARRAY_OR_NULL(options)
    ZEND_PARSE_PARAMETERS_END();

    logger_flusher guard;

    auto* handle = fetch_couchbase_connection_from_resource(connection);
    if (handle == nullptr) {
        RETURN_THROWS();
    }
    if (auto e = handle->bucket_create(return_value, bucket_settings, options); e.ec) {
        couchbase_throw_exception(e);
        RETURN_THROWS();
    }
}

PHP_FUNCTION(bucketGet)
{
    zval* connection = nullptr;
    zend_string* name = nullptr;
    zval* options = nullptr;

    ZEND_PARSE_PARAMETERS_START(2, 3)
    Z_PARAM_RESOURCE(connection)
    Z_PARAM_STR(name)
    Z_PARAM_OPTIONAL
    Z_PARAM_ARRAY_OR_NULL(options)
    ZEND_PARSE_PARAMETERS_END();

    logger_flusher guard;

    auto* handle = fetch_couchbase_connection_from_resource(connection);
    if (handle == nullptr) {
        RETURN_THROWS();
    }
    if (auto e = handle->bucket_get(return_value, name, options); e.ec) {
        couchbase_throw_exception(e);
        RETURN_THROWS();
    }
}

PHP_FUNCTION(bucketGetAll)
{
    zval* connection = nullptr;
    zval* options = nullptr;

    ZEND_PARSE_PARAMETERS_START(1, 2)
    Z_PARAM_RESOURCE(connection)
    Z_PARAM_OPTIONAL
    Z_PARAM_ARRAY_OR_NULL(options)
    ZEND_PARSE_PARAMETERS_END();

    auto* handle = fetch_couchbase_connection_from_resource(connection);
    if (handle == nullptr) {
        RETURN_THROWS();
    }
    if (auto e = handle->bucket_get_all(return_value, options); e.ec) {
        couchbase_throw_exception(e);
        RETURN_THROWS();
    }
}

PHP_FUNCTION(bucketDrop)
{
    zval* connection = nullptr;
    zend_string* name = nullptr;
    zval* options = nullptr;

    ZEND_PARSE_PARAMETERS_START(2, 3)
    Z_PARAM_RESOURCE(connection)
    Z_PARAM_STR(name)
    Z_PARAM_OPTIONAL
    Z_PARAM_ARRAY_OR_NULL(options)
    ZEND_PARSE_PARAMETERS_END();

    logger_flusher guard;

    auto* handle = fetch_couchbase_connection_from_resource(connection);
    if (handle == nullptr) {
        RETURN_THROWS();
    }
    if (auto e = handle->bucket_drop(return_value, name, options); e.ec) {
        couchbase_throw_exception(e);
        RETURN_THROWS();
    }
}

PHP_FUNCTION(bucketFlush)
{
    zval* connection = nullptr;
    zend_string* name = nullptr;
    zval* options = nullptr;

    ZEND_PARSE_PARAMETERS_START(2, 3)
    Z_PARAM_RESOURCE(connection)
    Z_PARAM_STR(name)
    Z_PARAM_OPTIONAL
    Z_PARAM_ARRAY_OR_NULL(options)
    ZEND_PARSE_PARAMETERS_END();

    logger_flusher guard;

    auto* handle = fetch_couchbase_connection_from_resource(connection);
    if (handle == nullptr) {
        RETURN_THROWS();
    }
    if (auto e = handle->bucket_flush(return_value, name, options); e.ec) {
        couchbase_throw_exception(e);
        RETURN_THROWS();
    }
}

PHP_FUNCTION(bucketUpdate)
{
    zval* connection = nullptr;
    zval* bucket_settings = nullptr;
    zval* options = nullptr;

    ZEND_PARSE_PARAMETERS_START(2, 3)
    Z_PARAM_RESOURCE(connection)
    Z_PARAM_ARRAY(bucket_settings)
    Z_PARAM_OPTIONAL
    Z_PARAM_ARRAY_OR_NULL(options)
    ZEND_PARSE_PARAMETERS_END();

    logger_flusher guard;

    auto* handle = fetch_couchbase_connection_from_resource(connection);
    if (handle == nullptr) {
        RETURN_THROWS();
    }
    if (auto e = handle->bucket_update(return_value, bucket_settings, options); e.ec) {
        couchbase_throw_exception(e);
        RETURN_THROWS();
    }
}

PHP_FUNCTION(scopeGetAll)
{
    zval* connection = nullptr;
    zend_string* name = nullptr;
    zval* options = nullptr;

    ZEND_PARSE_PARAMETERS_START(2, 3)
    Z_PARAM_RESOURCE(connection)
    Z_PARAM_STR(name)
    Z_PARAM_OPTIONAL
    Z_PARAM_ARRAY_OR_NULL(options)
    ZEND_PARSE_PARAMETERS_END();

    logger_flusher guard;

    auto* handle = fetch_couchbase_connection_from_resource(connection);
    if (handle == nullptr) {
        RETURN_THROWS();
    }

    if (auto e = handle->scope_get_all(return_value, name, options); e.ec) {
        couchbase_throw_exception(e);
        RETURN_THROWS();
    }
}

PHP_FUNCTION(scopeCreate)
{
    zval* connection = nullptr;
    zend_string* bucket_name = nullptr;
    zend_string* scope_name = nullptr;
    zval* options = nullptr;

    ZEND_PARSE_PARAMETERS_START(3, 4)
    Z_PARAM_RESOURCE(connection)
    Z_PARAM_STR(bucket_name)
    Z_PARAM_STR(scope_name)
    Z_PARAM_OPTIONAL
    Z_PARAM_ARRAY_OR_NULL(options)
    ZEND_PARSE_PARAMETERS_END();

    logger_flusher guard;

    auto* handle = fetch_couchbase_connection_from_resource(connection);
    if (handle == nullptr) {
        RETURN_THROWS();
    }

    if (auto e = handle->scope_create(return_value, bucket_name, scope_name, options); e.ec) {
        couchbase_throw_exception(e);
        RETURN_THROWS();
    }
}

PHP_FUNCTION(scopeDrop)
{
    zval* connection = nullptr;
    zend_string* bucket_name = nullptr;
    zend_string* scope_name = nullptr;
    zval* options = nullptr;

    ZEND_PARSE_PARAMETERS_START(3, 4)
    Z_PARAM_RESOURCE(connection)
    Z_PARAM_STR(bucket_name)
    Z_PARAM_STR(scope_name)
    Z_PARAM_OPTIONAL
    Z_PARAM_ARRAY_OR_NULL(options)
    ZEND_PARSE_PARAMETERS_END();

    logger_flusher guard;

    auto* handle = fetch_couchbase_connection_from_resource(connection);
    if (handle == nullptr) {
        RETURN_THROWS();
    }

    if (auto e = handle->scope_drop(return_value, bucket_name, scope_name, options); e.ec) {
        couchbase_throw_exception(e);
        RETURN_THROWS();
    }
}

PHP_FUNCTION(collectionCreate)
{
    zval* connection = nullptr;
    zend_string* bucket_name = nullptr;
    zend_string* scope_name = nullptr;
    zend_string* collection_name = nullptr;
    zval* settings = nullptr;
    zval* options = nullptr;

    ZEND_PARSE_PARAMETERS_START(4, 6)
    Z_PARAM_RESOURCE(connection)
    Z_PARAM_STR(bucket_name)
    Z_PARAM_STR(scope_name)
    Z_PARAM_STR(collection_name)
    Z_PARAM_OPTIONAL
    Z_PARAM_ARRAY_OR_NULL(settings)
    Z_PARAM_ARRAY_OR_NULL(options)
    ZEND_PARSE_PARAMETERS_END();

    logger_flusher guard;

    auto* handle = fetch_couchbase_connection_from_resource(connection);
    if (handle == nullptr) {
        RETURN_THROWS();
    }

    if (auto e = handle->collection_create(return_value, bucket_name, scope_name, collection_name, settings, options); e.ec) {
        couchbase_throw_exception(e);
        RETURN_THROWS();
    }
}

PHP_FUNCTION(collectionDrop)
{
    zval* connection = nullptr;
    zend_string* bucket_name = nullptr;
    zend_string* scope_name = nullptr;
    zend_string* collection_name = nullptr;
    zval* options = nullptr;

    ZEND_PARSE_PARAMETERS_START(4, 5)
    Z_PARAM_RESOURCE(connection)
    Z_PARAM_STR(bucket_name)
    Z_PARAM_STR(scope_name)
    Z_PARAM_STR(collection_name)
    Z_PARAM_OPTIONAL
    Z_PARAM_ARRAY_OR_NULL(options)
    ZEND_PARSE_PARAMETERS_END();

    logger_flusher guard;

    auto* handle = fetch_couchbase_connection_from_resource(connection);
    if (handle == nullptr) {
        RETURN_THROWS();
    }

    if (auto e = handle->collection_drop(return_value, bucket_name, scope_name, collection_name, options); e.ec) {
        couchbase_throw_exception(e);
        RETURN_THROWS();
    }
}

PHP_FUNCTION(collectionUpdate)
{
    zval* connection = nullptr;
    zend_string* bucket_name = nullptr;
    zend_string* scope_name = nullptr;
    zend_string* collection_name = nullptr;
    zval* settings = nullptr;
    zval* options = nullptr;

    ZEND_PARSE_PARAMETERS_START(5, 6)
    Z_PARAM_RESOURCE(connection)
    Z_PARAM_STR(bucket_name)
    Z_PARAM_STR(scope_name)
    Z_PARAM_STR(collection_name)
    Z_PARAM_ARRAY(settings)
    Z_PARAM_OPTIONAL
    Z_PARAM_ARRAY_OR_NULL(options)
    ZEND_PARSE_PARAMETERS_END();

    logger_flusher guard;

    auto* handle = fetch_couchbase_connection_from_resource(connection);
    if (handle == nullptr) {
        RETURN_THROWS();
    }

    if (auto e = handle->collection_update(return_value, bucket_name, scope_name, collection_name, settings, options); e.ec) {
        couchbase_throw_exception(e);
        RETURN_THROWS();
    }
}

static inline couchbase::php::transactions_resource*
fetch_couchbase_transactions_from_resource(zval* resource)
{
    return static_cast<couchbase::php::transactions_resource*>(
      zend_fetch_resource(Z_RES_P(resource), "couchbase_transactions", couchbase::php::get_transactions_destructor_id()));
}

PHP_FUNCTION(createTransactions)
{
    zval* connection = nullptr;
    zval* configuration = nullptr;

    ZEND_PARSE_PARAMETERS_START(1, 2)
    Z_PARAM_RESOURCE(connection)
    Z_PARAM_OPTIONAL
    Z_PARAM_ARRAY_OR_NULL(configuration)
    ZEND_PARSE_PARAMETERS_END();

    logger_flusher guard;

    auto* handle = fetch_couchbase_connection_from_resource(connection);
    if (handle == nullptr) {
        RETURN_THROWS();
    }
    auto [resource, e] = couchbase::php::create_transactions_resource(handle, configuration);
    if (e.ec) {
        couchbase_throw_exception(e);
        RETURN_THROWS();
    }
    RETURN_RES(resource);
}

static inline couchbase::php::transaction_context_resource*
fetch_couchbase_transaction_context_from_resource(zval* resource)
{
    return static_cast<couchbase::php::transaction_context_resource*>(
      zend_fetch_resource(Z_RES_P(resource), "couchbase_transaction_context", couchbase::php::get_transaction_context_destructor_id()));
}

PHP_FUNCTION(createTransactionContext)
{
    zval* transactions = nullptr;
    zval* configuration = nullptr;

    ZEND_PARSE_PARAMETERS_START(1, 2)
    Z_PARAM_RESOURCE(transactions)
    Z_PARAM_OPTIONAL
    Z_PARAM_ARRAY_OR_NULL(configuration)
    ZEND_PARSE_PARAMETERS_END();

    logger_flusher guard;

    auto* handle = fetch_couchbase_transactions_from_resource(transactions);
    if (handle == nullptr) {
        RETURN_THROWS();
    }
    auto [resource, e] = couchbase::php::create_transaction_context_resource(handle, configuration);
    if (e.ec) {
        couchbase_throw_exception(e);
        RETURN_THROWS();
    }
    RETURN_RES(resource);
}

PHP_FUNCTION(transactionNewAttempt)
{
    zval* transaction = nullptr;

    ZEND_PARSE_PARAMETERS_START(1, 1)
    Z_PARAM_RESOURCE(transaction)
    ZEND_PARSE_PARAMETERS_END();

    logger_flusher guard;

    auto* context = fetch_couchbase_transaction_context_from_resource(transaction);
    if (context == nullptr) {
        RETURN_THROWS();
    }
    if (auto e = context->new_attempt(); e.ec) {
        couchbase_throw_exception(e);
        RETURN_THROWS();
    }
    RETURN_NULL();
}

PHP_FUNCTION(transactionCommit)
{
    zval* transaction = nullptr;

    ZEND_PARSE_PARAMETERS_START(1, 1)
    Z_PARAM_RESOURCE(transaction)
    ZEND_PARSE_PARAMETERS_END();

    logger_flusher guard;

    auto* context = fetch_couchbase_transaction_context_from_resource(transaction);
    if (context == nullptr) {
        RETURN_THROWS();
    }
    if (auto e = context->commit(return_value); e.ec) {
        couchbase_throw_exception(e);
        RETURN_THROWS();
    }
}

PHP_FUNCTION(transactionRollback)
{
    zval* transaction = nullptr;

    ZEND_PARSE_PARAMETERS_START(1, 1)
    Z_PARAM_RESOURCE(transaction)
    ZEND_PARSE_PARAMETERS_END();

    logger_flusher guard;

    auto* context = fetch_couchbase_transaction_context_from_resource(transaction);
    if (context == nullptr) {
        RETURN_THROWS();
    }
    if (auto e = context->rollback(); e.ec) {
        couchbase_throw_exception(e);
        RETURN_THROWS();
    }
    RETURN_NULL();
}

PHP_FUNCTION(transactionGet)
{
    zval* transaction = nullptr;
    zend_string* bucket = nullptr;
    zend_string* scope = nullptr;
    zend_string* collection = nullptr;
    zend_string* id = nullptr;

    ZEND_PARSE_PARAMETERS_START(5, 5)
    Z_PARAM_RESOURCE(transaction)
    Z_PARAM_STR(bucket)
    Z_PARAM_STR(scope)
    Z_PARAM_STR(collection)
    Z_PARAM_STR(id)
    ZEND_PARSE_PARAMETERS_END();

    logger_flusher guard;

    auto* context = fetch_couchbase_transaction_context_from_resource(transaction);
    if (context == nullptr) {
        RETURN_THROWS();
    }
    if (auto e = context->get(return_value, bucket, scope, collection, id); e.ec) {
        couchbase_throw_exception(e);
        RETURN_THROWS();
    }
}

PHP_FUNCTION(transactionInsert)
{
    zval* transaction = nullptr;
    zend_string* bucket = nullptr;
    zend_string* scope = nullptr;
    zend_string* collection = nullptr;
    zend_string* id = nullptr;
    zend_string* value = nullptr;

    ZEND_PARSE_PARAMETERS_START(6, 6)
    Z_PARAM_RESOURCE(transaction)
    Z_PARAM_STR(bucket)
    Z_PARAM_STR(scope)
    Z_PARAM_STR(collection)
    Z_PARAM_STR(id)
    Z_PARAM_STR(value)
    ZEND_PARSE_PARAMETERS_END();

    logger_flusher guard;

    auto* context = fetch_couchbase_transaction_context_from_resource(transaction);
    if (context == nullptr) {
        RETURN_THROWS();
    }
    if (auto e = context->insert(return_value, bucket, scope, collection, id, value); e.ec) {
        couchbase_throw_exception(e);
        RETURN_THROWS();
    }
}

PHP_FUNCTION(transactionReplace)
{
    zval* transaction = nullptr;
    zval* document = nullptr;
    zend_string* value = nullptr;

    ZEND_PARSE_PARAMETERS_START(3, 3)
    Z_PARAM_RESOURCE(transaction)
    Z_PARAM_ARRAY(document)
    Z_PARAM_STR(value)
    ZEND_PARSE_PARAMETERS_END();

    logger_flusher guard;

    auto* context = fetch_couchbase_transaction_context_from_resource(transaction);
    if (context == nullptr) {
        RETURN_THROWS();
    }
    if (auto e = context->replace(return_value, document, value); e.ec) {
        couchbase_throw_exception(e);
        RETURN_THROWS();
    }
}

PHP_FUNCTION(transactionRemove)
{
    zval* transaction = nullptr;
    zval* document = nullptr;
    zend_string* value = nullptr;

    ZEND_PARSE_PARAMETERS_START(2, 2)
    Z_PARAM_RESOURCE(transaction)
    Z_PARAM_ARRAY(document)
    ZEND_PARSE_PARAMETERS_END();

    logger_flusher guard;

    auto* context = fetch_couchbase_transaction_context_from_resource(transaction);
    if (context == nullptr) {
        RETURN_THROWS();
    }
    if (auto e = context->remove(document); e.ec) {
        couchbase_throw_exception(e);
        RETURN_THROWS();
    }
}

PHP_FUNCTION(transactionQuery)
{
    zval* transaction = nullptr;
    zend_string* statement = nullptr;
    zval* options = nullptr;

    ZEND_PARSE_PARAMETERS_START(2, 3)
    Z_PARAM_RESOURCE(transaction)
    Z_PARAM_STR(statement)
    Z_PARAM_OPTIONAL
    Z_PARAM_ARRAY_OR_NULL(options)
    ZEND_PARSE_PARAMETERS_END();

    logger_flusher guard;

    auto* context = fetch_couchbase_transaction_context_from_resource(transaction);
    if (context == nullptr) {
        RETURN_THROWS();
    }
    if (auto e = context->query(return_value, statement, options); e.ec) {
        couchbase_throw_exception(e);
        RETURN_THROWS();
    }
}

PHP_FUNCTION(userUpsert)
{
    zval* connection = nullptr;
    zval* user = nullptr;
    zval* options = nullptr;

    ZEND_PARSE_PARAMETERS_START(2, 3)
    Z_PARAM_RESOURCE(connection)
    Z_PARAM_ARRAY(user)
    Z_PARAM_OPTIONAL
    Z_PARAM_ARRAY_OR_NULL(options)
    ZEND_PARSE_PARAMETERS_END();

    logger_flusher guard;

    auto* handle = fetch_couchbase_connection_from_resource(connection);
    if (handle == nullptr) {
        RETURN_THROWS();
    }
    if (auto e = handle->user_upsert(return_value, user, options); e.ec) {
        couchbase_throw_exception(e);
        RETURN_THROWS();
    }
}

PHP_FUNCTION(userGet)
{
    zval* connection = nullptr;
    zend_string* name = nullptr;
    zval* options = nullptr;

    ZEND_PARSE_PARAMETERS_START(2, 3)
    Z_PARAM_RESOURCE(connection)
    Z_PARAM_STR(name)
    Z_PARAM_OPTIONAL
    Z_PARAM_ARRAY_OR_NULL(options)
    ZEND_PARSE_PARAMETERS_END();

    logger_flusher guard;

    auto* handle = fetch_couchbase_connection_from_resource(connection);
    if (handle == nullptr) {
        RETURN_THROWS();
    }
    if (auto e = handle->user_get(return_value, name, options); e.ec) {
        couchbase_throw_exception(e);
        RETURN_THROWS();
    }
}

PHP_FUNCTION(userGetAll)
{
    zval* connection = nullptr;
    zval* options = nullptr;

    ZEND_PARSE_PARAMETERS_START(1, 2)
    Z_PARAM_RESOURCE(connection)
    Z_PARAM_OPTIONAL
    Z_PARAM_ARRAY_OR_NULL(options)
    ZEND_PARSE_PARAMETERS_END();

    logger_flusher guard;

    auto* handle = fetch_couchbase_connection_from_resource(connection);
    if (handle == nullptr) {
        RETURN_THROWS();
    }
    if (auto e = handle->user_get_all(return_value, options); e.ec) {
        couchbase_throw_exception(e);
        RETURN_THROWS();
    }
}

PHP_FUNCTION(userDrop)
{
    zval* connection = nullptr;
    zend_string* name = nullptr;
    zval* options = nullptr;

    ZEND_PARSE_PARAMETERS_START(2, 3)
    Z_PARAM_RESOURCE(connection)
    Z_PARAM_STR(name)
    Z_PARAM_OPTIONAL
    Z_PARAM_ARRAY_OR_NULL(options)
    ZEND_PARSE_PARAMETERS_END();

    logger_flusher guard;

    auto* handle = fetch_couchbase_connection_from_resource(connection);
    if (handle == nullptr) {
        RETURN_THROWS();
    }
    if (auto e = handle->user_drop(return_value, name, options); e.ec) {
        couchbase_throw_exception(e);
        RETURN_THROWS();
    }
}

PHP_FUNCTION(passwordChange)
{
    zval* connection = nullptr;
    zend_string* new_password = nullptr;
    zval* options = nullptr;

    ZEND_PARSE_PARAMETERS_START(2, 3)
    Z_PARAM_RESOURCE(connection)
    Z_PARAM_STR(new_password)
    Z_PARAM_OPTIONAL
    Z_PARAM_ARRAY_OR_NULL(options)
    ZEND_PARSE_PARAMETERS_END();

    logger_flusher guard;

    auto* handle = fetch_couchbase_connection_from_resource(connection);
    if (handle == nullptr) {
        RETURN_THROWS();
    }
    if (auto e = handle->change_password(return_value, new_password, options); e.ec) {
        couchbase_throw_exception(e);
        RETURN_THROWS();
    }
}

PHP_FUNCTION(groupUpsert)
{
    zval* connection = nullptr;
    zval* group = nullptr;
    zval* options = nullptr;

    ZEND_PARSE_PARAMETERS_START(2, 3)
    Z_PARAM_RESOURCE(connection)
    Z_PARAM_ARRAY(group)
    Z_PARAM_OPTIONAL
    Z_PARAM_ARRAY_OR_NULL(options)
    ZEND_PARSE_PARAMETERS_END();

    logger_flusher guard;

    auto* handle = fetch_couchbase_connection_from_resource(connection);
    if (handle == nullptr) {
        RETURN_THROWS();
    }
    if (auto e = handle->group_upsert(return_value, group, options); e.ec) {
        couchbase_throw_exception(e);
        RETURN_THROWS();
    }
}

PHP_FUNCTION(groupGet)
{
    zval* connection = nullptr;
    zend_string* name = nullptr;
    zval* options = nullptr;

    ZEND_PARSE_PARAMETERS_START(2, 3)
    Z_PARAM_RESOURCE(connection)
    Z_PARAM_STR(name)
    Z_PARAM_OPTIONAL
    Z_PARAM_ARRAY_OR_NULL(options)
    ZEND_PARSE_PARAMETERS_END();

    logger_flusher guard;

    auto* handle = fetch_couchbase_connection_from_resource(connection);
    if (handle == nullptr) {
        RETURN_THROWS();
    }
    if (auto e = handle->group_get(return_value, name, options); e.ec) {
        couchbase_throw_exception(e);
        RETURN_THROWS();
    }
}

PHP_FUNCTION(groupGetAll)
{
    zval* connection = nullptr;
    zval* options = nullptr;

    ZEND_PARSE_PARAMETERS_START(1, 2)
    Z_PARAM_RESOURCE(connection)
    Z_PARAM_OPTIONAL
    Z_PARAM_ARRAY_OR_NULL(options)
    ZEND_PARSE_PARAMETERS_END();

    logger_flusher guard;

    auto* handle = fetch_couchbase_connection_from_resource(connection);
    if (handle == nullptr) {
        RETURN_THROWS();
    }
    if (auto e = handle->group_get_all(return_value, options); e.ec) {
        couchbase_throw_exception(e);
        RETURN_THROWS();
    }
}

PHP_FUNCTION(groupDrop)
{
    zval* connection = nullptr;
    zend_string* name = nullptr;
    zval* options = nullptr;

    ZEND_PARSE_PARAMETERS_START(2, 3)
    Z_PARAM_RESOURCE(connection)
    Z_PARAM_STR(name)
    Z_PARAM_OPTIONAL
    Z_PARAM_ARRAY_OR_NULL(options)
    ZEND_PARSE_PARAMETERS_END();

    logger_flusher guard;

    auto* handle = fetch_couchbase_connection_from_resource(connection);
    if (handle == nullptr) {
        RETURN_THROWS();
    }
    if (auto e = handle->group_drop(return_value, name, options); e.ec) {
        couchbase_throw_exception(e);
        RETURN_THROWS();
    }
}

PHP_FUNCTION(roleGetAll)
{
    zval* connection = nullptr;
    zval* options = nullptr;

    ZEND_PARSE_PARAMETERS_START(1, 2)
    Z_PARAM_RESOURCE(connection)
    Z_PARAM_OPTIONAL
    Z_PARAM_ARRAY_OR_NULL(options)
    ZEND_PARSE_PARAMETERS_END();

    logger_flusher guard;

    auto* handle = fetch_couchbase_connection_from_resource(connection);
    if (handle == nullptr) {
        RETURN_THROWS();
    }
    if (auto e = handle->role_get_all(return_value, options); e.ec) {
        couchbase_throw_exception(e);
        RETURN_THROWS();
    }
}

PHP_FUNCTION(queryIndexGetAll)
{
    zval* connection = nullptr;
    zend_string* bucket_name = nullptr;
    zval* options = nullptr;

    ZEND_PARSE_PARAMETERS_START(2, 3)
    Z_PARAM_RESOURCE(connection)
    Z_PARAM_STR(bucket_name)
    Z_PARAM_OPTIONAL
    Z_PARAM_ARRAY_OR_NULL(options)
    ZEND_PARSE_PARAMETERS_END();

    logger_flusher guard;

    auto* handle = fetch_couchbase_connection_from_resource(connection);
    if (handle == nullptr) {
        RETURN_THROWS();
    }
    if (auto e = handle->query_index_get_all(return_value, bucket_name, options); e.ec) {
        couchbase_throw_exception(e);
        RETURN_THROWS();
    }
}

PHP_FUNCTION(queryIndexCreate)
{
    zval* connection = nullptr;
    zend_string* bucket_name = nullptr;
    zend_string* index_name = nullptr;
    zval* keys = nullptr;
    zval* options = nullptr;

    ZEND_PARSE_PARAMETERS_START(4, 5)
    Z_PARAM_RESOURCE(connection)
    Z_PARAM_STR(bucket_name)
    Z_PARAM_STR(index_name)
    Z_PARAM_ARRAY(keys)
    Z_PARAM_OPTIONAL
    Z_PARAM_ARRAY_OR_NULL(options)
    ZEND_PARSE_PARAMETERS_END();

    logger_flusher guard;

    auto* handle = fetch_couchbase_connection_from_resource(connection);
    if (handle == nullptr) {
        RETURN_THROWS();
    }
    if (auto e = handle->query_index_create(bucket_name, index_name, keys, options); e.ec) {
        couchbase_throw_exception(e);
        RETURN_THROWS();
    }
    RETURN_NULL();
}

PHP_FUNCTION(queryIndexCreatePrimary)
{
    zval* connection = nullptr;
    zend_string* bucket_name = nullptr;
    zval* options = nullptr;

    ZEND_PARSE_PARAMETERS_START(2, 3)
    Z_PARAM_RESOURCE(connection)
    Z_PARAM_STR(bucket_name)
    Z_PARAM_OPTIONAL
    Z_PARAM_ARRAY_OR_NULL(options)
    ZEND_PARSE_PARAMETERS_END();

    logger_flusher guard;

    auto* handle = fetch_couchbase_connection_from_resource(connection);
    if (handle == nullptr) {
        RETURN_THROWS();
    }
    if (auto e = handle->query_index_create_primary(bucket_name, options); e.ec) {
        couchbase_throw_exception(e);
        RETURN_THROWS();
    }
    RETURN_NULL();
}

PHP_FUNCTION(queryIndexDrop)
{
    zval* connection = nullptr;
    zend_string* bucket_name = nullptr;
    zend_string* index_name = nullptr;
    zval* options = nullptr;

    ZEND_PARSE_PARAMETERS_START(3, 4)
    Z_PARAM_RESOURCE(connection)
    Z_PARAM_STR(bucket_name)
    Z_PARAM_STR(index_name)
    Z_PARAM_OPTIONAL
    Z_PARAM_ARRAY_OR_NULL(options)
    ZEND_PARSE_PARAMETERS_END();

    logger_flusher guard;

    auto* handle = fetch_couchbase_connection_from_resource(connection);
    if (handle == nullptr) {
        RETURN_THROWS();
    }
    if (auto e = handle->query_index_drop(bucket_name, index_name, options); e.ec) {
        couchbase_throw_exception(e);
        RETURN_THROWS();
    }
    RETURN_NULL();
}

PHP_FUNCTION(queryIndexDropPrimary)
{
    zval* connection = nullptr;
    zend_string* bucket_name = nullptr;
    zend_string* index_name = nullptr;
    zval* options = nullptr;

    ZEND_PARSE_PARAMETERS_START(2, 3)
    Z_PARAM_RESOURCE(connection)
    Z_PARAM_STR(bucket_name)
    Z_PARAM_OPTIONAL
    Z_PARAM_ARRAY_OR_NULL(options)
    ZEND_PARSE_PARAMETERS_END();

    logger_flusher guard;

    auto* handle = fetch_couchbase_connection_from_resource(connection);
    if (handle == nullptr) {
        RETURN_THROWS();
    }
    if (auto e = handle->query_index_drop_primary(bucket_name, options); e.ec) {
        couchbase_throw_exception(e);
        RETURN_THROWS();
    }
    RETURN_NULL();
}

PHP_FUNCTION(queryIndexBuildDeferred)
{
    zval* connection = nullptr;
    zend_string* bucket_name = nullptr;
    zval* options = nullptr;

    ZEND_PARSE_PARAMETERS_START(2, 3)
    Z_PARAM_RESOURCE(connection)
    Z_PARAM_STR(bucket_name)
    Z_PARAM_OPTIONAL
    Z_PARAM_ARRAY_OR_NULL(options)
    ZEND_PARSE_PARAMETERS_END();

    logger_flusher guard;

    auto* handle = fetch_couchbase_connection_from_resource(connection);
    if (handle == nullptr) {
        RETURN_THROWS();
    }
    if (auto e = handle->query_index_build_deferred(return_value, bucket_name, options); e.ec) {
        couchbase_throw_exception(e);
        RETURN_THROWS();
    }
}

PHP_FUNCTION(collectionQueryIndexGetAll)
{
    zval* connection = nullptr;
    zend_string* bucket_name = nullptr;
    zend_string* scope_name = nullptr;
    zend_string* collection_name = nullptr;
    zval* options = nullptr;

    ZEND_PARSE_PARAMETERS_START(4, 5)
    Z_PARAM_RESOURCE(connection)
    Z_PARAM_STR(bucket_name)
    Z_PARAM_STR(scope_name)
    Z_PARAM_STR(collection_name)
    Z_PARAM_OPTIONAL
    Z_PARAM_ARRAY_OR_NULL(options)
    ZEND_PARSE_PARAMETERS_END();

    logger_flusher guard;

    auto* handle = fetch_couchbase_connection_from_resource(connection);
    if (handle == nullptr) {
        RETURN_THROWS();
    }
    if (auto e = handle->collection_query_index_get_all(return_value, bucket_name, scope_name, collection_name, options); e.ec) {
        couchbase_throw_exception(e);
        RETURN_THROWS();
    }
}

PHP_FUNCTION(collectionQueryIndexCreate)
{
    zval* connection = nullptr;
    zend_string* bucket_name = nullptr;
    zend_string* scope_name = nullptr;
    zend_string* collection_name = nullptr;
    zend_string* index_name = nullptr;
    zval* keys = nullptr;
    zval* options = nullptr;

    ZEND_PARSE_PARAMETERS_START(6, 7)
    Z_PARAM_RESOURCE(connection)
    Z_PARAM_STR(bucket_name)
    Z_PARAM_STR(scope_name)
    Z_PARAM_STR(collection_name)
    Z_PARAM_STR(index_name)
    Z_PARAM_ARRAY(keys)
    Z_PARAM_OPTIONAL
    Z_PARAM_ARRAY_OR_NULL(options)
    ZEND_PARSE_PARAMETERS_END();

    logger_flusher guard;

    auto* handle = fetch_couchbase_connection_from_resource(connection);
    if (handle == nullptr) {
        RETURN_THROWS();
    }
    if (auto e = handle->collection_query_index_create(bucket_name, scope_name, collection_name, index_name, keys, options); e.ec) {
        couchbase_throw_exception(e);
        RETURN_THROWS();
    }
    RETURN_NULL();
}

PHP_FUNCTION(collectionQueryIndexCreatePrimary)
{
    zval* connection = nullptr;
    zend_string* bucket_name = nullptr;
    zend_string* scope_name = nullptr;
    zend_string* collection_name = nullptr;
    zval* options = nullptr;

    ZEND_PARSE_PARAMETERS_START(4, 5)
    Z_PARAM_RESOURCE(connection)
    Z_PARAM_STR(bucket_name)
    Z_PARAM_STR(scope_name)
    Z_PARAM_STR(collection_name)
    Z_PARAM_OPTIONAL
    Z_PARAM_ARRAY_OR_NULL(options)
    ZEND_PARSE_PARAMETERS_END();

    logger_flusher guard;

    auto* handle = fetch_couchbase_connection_from_resource(connection);
    if (handle == nullptr) {
        RETURN_THROWS();
    }
    if (auto e = handle->collection_query_index_create_primary(bucket_name, scope_name, collection_name, options); e.ec) {
        couchbase_throw_exception(e);
        RETURN_THROWS();
    }
    RETURN_NULL();
}

PHP_FUNCTION(collectionQueryIndexDrop)
{
    zval* connection = nullptr;
    zend_string* bucket_name = nullptr;
    zend_string* scope_name = nullptr;
    zend_string* collection_name = nullptr;
    zend_string* index_name = nullptr;
    zval* options = nullptr;

    ZEND_PARSE_PARAMETERS_START(5, 6)
    Z_PARAM_RESOURCE(connection)
    Z_PARAM_STR(bucket_name)
    Z_PARAM_STR(scope_name)
    Z_PARAM_STR(collection_name)
    Z_PARAM_STR(index_name)
    Z_PARAM_OPTIONAL
    Z_PARAM_ARRAY_OR_NULL(options)
    ZEND_PARSE_PARAMETERS_END();

    logger_flusher guard;

    auto* handle = fetch_couchbase_connection_from_resource(connection);
    if (handle == nullptr) {
        RETURN_THROWS();
    }
    if (auto e = handle->collection_query_index_drop(bucket_name, scope_name, collection_name, index_name, options); e.ec) {
        couchbase_throw_exception(e);
        RETURN_THROWS();
    }
    RETURN_NULL();
}

PHP_FUNCTION(collectionQueryIndexDropPrimary)
{
    zval* connection = nullptr;
    zend_string* bucket_name = nullptr;
    zend_string* scope_name = nullptr;
    zend_string* collection_name = nullptr;
    zend_string* index_name = nullptr;
    zval* options = nullptr;

    ZEND_PARSE_PARAMETERS_START(4, 5)
    Z_PARAM_RESOURCE(connection)
    Z_PARAM_STR(bucket_name)
    Z_PARAM_STR(scope_name)
    Z_PARAM_STR(collection_name)
    Z_PARAM_OPTIONAL
    Z_PARAM_ARRAY_OR_NULL(options)
    ZEND_PARSE_PARAMETERS_END();

    logger_flusher guard;

    auto* handle = fetch_couchbase_connection_from_resource(connection);
    if (handle == nullptr) {
        RETURN_THROWS();
    }
    if (auto e = handle->collection_query_index_drop_primary(bucket_name, scope_name, collection_name, options); e.ec) {
        couchbase_throw_exception(e);
        RETURN_THROWS();
    }
    RETURN_NULL();
}

PHP_FUNCTION(collectionQueryIndexBuildDeferred)
{
    zval* connection = nullptr;
    zend_string* bucket_name = nullptr;
    zend_string* scope_name = nullptr;
    zend_string* collection_name = nullptr;
    zval* options = nullptr;

    ZEND_PARSE_PARAMETERS_START(4, 5)
    Z_PARAM_RESOURCE(connection)
    Z_PARAM_STR(bucket_name)
    Z_PARAM_STR(scope_name)
    Z_PARAM_STR(collection_name)
    Z_PARAM_OPTIONAL
    Z_PARAM_ARRAY_OR_NULL(options)
    ZEND_PARSE_PARAMETERS_END();

    logger_flusher guard;

    auto* handle = fetch_couchbase_connection_from_resource(connection);
    if (handle == nullptr) {
        RETURN_THROWS();
    }
    if (auto e = handle->collection_query_index_build_deferred(return_value, bucket_name, scope_name, collection_name, options); e.ec) {
        couchbase_throw_exception(e);
        RETURN_THROWS();
    }
}

static PHP_MINFO_FUNCTION(couchbase)
{
    php_info_print_table_start();
    php_info_print_table_row(2, "couchbase", "enabled");
    php_info_print_table_row(2, "couchbase_extension_version", PHP_COUCHBASE_VERSION);
    php_info_print_table_row(2, "couchbase_extension_revision", couchbase::php::extension_revision());
    php_info_print_table_row(2, "couchbase_client_revision", couchbase::php::cxx_client_revision());
    php_info_print_table_end();
    DISPLAY_INI_ENTRIES();
}

ZEND_BEGIN_ARG_INFO_EX(ai_CouchbaseExtension_version, 0, 0, 0)
ZEND_END_ARG_INFO()

ZEND_BEGIN_ARG_INFO_EX(ai_CouchbaseExtension_notifyFork, 0, 0, 1)
ZEND_ARG_TYPE_INFO(0, forkEvent, IS_STRING, 0)
ZEND_END_ARG_INFO()

ZEND_BEGIN_ARG_INFO_EX(ai_CouchbaseExtension_clusterVersion, 0, 0, 2)
ZEND_ARG_INFO(0, connection)
ZEND_ARG_TYPE_INFO(0, bucketName, IS_STRING, 0)
ZEND_END_ARG_INFO()

ZEND_BEGIN_ARG_INFO_EX(ai_CouchbaseExtension_replicasConfiguredForBucket, 0, 0, 2)
ZEND_ARG_INFO(0, connection)
ZEND_ARG_TYPE_INFO(0, bucketName, IS_STRING, 0)
ZEND_END_ARG_INFO()

ZEND_BEGIN_ARG_INFO_EX(ai_CouchbaseExtension_createConnection, 0, 0, 3)
ZEND_ARG_TYPE_INFO(0, connectionHash, IS_STRING, 0)
ZEND_ARG_TYPE_INFO(0, connectionString, IS_STRING, 0)
ZEND_ARG_TYPE_INFO(0, options, IS_ARRAY, 0)
ZEND_END_ARG_INFO()

ZEND_BEGIN_ARG_INFO_EX(ai_CouchbaseExtension_openBucket, 0, 0, 2)
ZEND_ARG_INFO(0, connection)
ZEND_ARG_TYPE_INFO(0, bucketName, IS_STRING, 0)
ZEND_END_ARG_INFO()

ZEND_BEGIN_ARG_INFO_EX(ai_CouchbaseExtension_closeBucket, 0, 0, 2)
ZEND_ARG_INFO(0, connection)
ZEND_ARG_TYPE_INFO(0, bucketName, IS_STRING, 0)
ZEND_END_ARG_INFO()

ZEND_BEGIN_ARG_INFO_EX(ai_CouchbaseExtension_documentUpsert, 0, 0, 7)
ZEND_ARG_INFO(0, connection)
ZEND_ARG_TYPE_INFO(0, bucket, IS_STRING, 0)
ZEND_ARG_TYPE_INFO(0, scope, IS_STRING, 0)
ZEND_ARG_TYPE_INFO(0, collection, IS_STRING, 0)
ZEND_ARG_TYPE_INFO(0, id, IS_STRING, 0)
ZEND_ARG_TYPE_INFO(0, value, IS_STRING, 0)
ZEND_ARG_TYPE_INFO(0, flags, IS_LONG, 0)
ZEND_ARG_TYPE_INFO(0, options, IS_ARRAY, 1)
ZEND_END_ARG_INFO()

ZEND_BEGIN_ARG_INFO_EX(ai_CouchbaseExtension_documentInsert, 0, 0, 7)
ZEND_ARG_INFO(0, connection)
ZEND_ARG_TYPE_INFO(0, bucket, IS_STRING, 0)
ZEND_ARG_TYPE_INFO(0, scope, IS_STRING, 0)
ZEND_ARG_TYPE_INFO(0, collection, IS_STRING, 0)
ZEND_ARG_TYPE_INFO(0, id, IS_STRING, 0)
ZEND_ARG_TYPE_INFO(0, value, IS_STRING, 0)
ZEND_ARG_TYPE_INFO(0, flags, IS_LONG, 0)
ZEND_ARG_TYPE_INFO(0, options, IS_ARRAY, 1)
ZEND_END_ARG_INFO()

ZEND_BEGIN_ARG_INFO_EX(ai_CouchbaseExtension_documentReplace, 0, 0, 7)
ZEND_ARG_INFO(0, connection)
ZEND_ARG_TYPE_INFO(0, bucket, IS_STRING, 0)
ZEND_ARG_TYPE_INFO(0, scope, IS_STRING, 0)
ZEND_ARG_TYPE_INFO(0, collection, IS_STRING, 0)
ZEND_ARG_TYPE_INFO(0, id, IS_STRING, 0)
ZEND_ARG_TYPE_INFO(0, value, IS_STRING, 0)
ZEND_ARG_TYPE_INFO(0, flags, IS_LONG, 0)
ZEND_ARG_TYPE_INFO(0, options, IS_ARRAY, 1)
ZEND_END_ARG_INFO()

ZEND_BEGIN_ARG_INFO_EX(ai_CouchbaseExtension_documentAppend, 0, 0, 6)
ZEND_ARG_INFO(0, connection)
ZEND_ARG_TYPE_INFO(0, bucket, IS_STRING, 0)
ZEND_ARG_TYPE_INFO(0, scope, IS_STRING, 0)
ZEND_ARG_TYPE_INFO(0, collection, IS_STRING, 0)
ZEND_ARG_TYPE_INFO(0, id, IS_STRING, 0)
ZEND_ARG_TYPE_INFO(0, value, IS_STRING, 0)
ZEND_ARG_TYPE_INFO(0, options, IS_ARRAY, 1)
ZEND_END_ARG_INFO()

ZEND_BEGIN_ARG_INFO_EX(ai_CouchbaseExtension_documentPrepend, 0, 0, 6)
ZEND_ARG_INFO(0, connection)
ZEND_ARG_TYPE_INFO(0, bucket, IS_STRING, 0)
ZEND_ARG_TYPE_INFO(0, scope, IS_STRING, 0)
ZEND_ARG_TYPE_INFO(0, collection, IS_STRING, 0)
ZEND_ARG_TYPE_INFO(0, id, IS_STRING, 0)
ZEND_ARG_TYPE_INFO(0, value, IS_STRING, 0)
ZEND_ARG_TYPE_INFO(0, options, IS_ARRAY, 1)
ZEND_END_ARG_INFO()

ZEND_BEGIN_ARG_INFO_EX(ai_CouchbaseExtension_documentIncrement, 0, 0, 5)
ZEND_ARG_INFO(0, connection)
ZEND_ARG_TYPE_INFO(0, bucket, IS_STRING, 0)
ZEND_ARG_TYPE_INFO(0, scope, IS_STRING, 0)
ZEND_ARG_TYPE_INFO(0, collection, IS_STRING, 0)
ZEND_ARG_TYPE_INFO(0, id, IS_STRING, 0)
ZEND_ARG_TYPE_INFO(0, options, IS_ARRAY, 1)
ZEND_END_ARG_INFO()

ZEND_BEGIN_ARG_INFO_EX(ai_CouchbaseExtension_documentDecrement, 0, 0, 5)
ZEND_ARG_INFO(0, connection)
ZEND_ARG_TYPE_INFO(0, bucket, IS_STRING, 0)
ZEND_ARG_TYPE_INFO(0, scope, IS_STRING, 0)
ZEND_ARG_TYPE_INFO(0, collection, IS_STRING, 0)
ZEND_ARG_TYPE_INFO(0, id, IS_STRING, 0)
ZEND_ARG_TYPE_INFO(0, options, IS_ARRAY, 1)
ZEND_END_ARG_INFO()

ZEND_BEGIN_ARG_INFO_EX(ai_CouchbaseExtension_documentGet, 0, 0, 5)
ZEND_ARG_INFO(0, connection)
ZEND_ARG_TYPE_INFO(0, bucket, IS_STRING, 0)
ZEND_ARG_TYPE_INFO(0, scope, IS_STRING, 0)
ZEND_ARG_TYPE_INFO(0, collection, IS_STRING, 0)
ZEND_ARG_TYPE_INFO(0, id, IS_STRING, 0)
ZEND_ARG_TYPE_INFO(0, options, IS_ARRAY, 1)
ZEND_END_ARG_INFO()

ZEND_BEGIN_ARG_INFO_EX(ai_CouchbaseExtension_documentGetAnyReplica, 0, 0, 5)
ZEND_ARG_INFO(0, connection)
ZEND_ARG_TYPE_INFO(0, bucket, IS_STRING, 0)
ZEND_ARG_TYPE_INFO(0, scope, IS_STRING, 0)
ZEND_ARG_TYPE_INFO(0, collection, IS_STRING, 0)
ZEND_ARG_TYPE_INFO(0, id, IS_STRING, 0)
ZEND_ARG_TYPE_INFO(0, options, IS_ARRAY, 1)
ZEND_END_ARG_INFO()

ZEND_BEGIN_ARG_INFO_EX(ai_CouchbaseExtension_documentGetAllReplicas, 0, 0, 5)
ZEND_ARG_INFO(0, connection)
ZEND_ARG_TYPE_INFO(0, bucket, IS_STRING, 0)
ZEND_ARG_TYPE_INFO(0, scope, IS_STRING, 0)
ZEND_ARG_TYPE_INFO(0, collection, IS_STRING, 0)
ZEND_ARG_TYPE_INFO(0, id, IS_STRING, 0)
ZEND_ARG_TYPE_INFO(0, options, IS_ARRAY, 1)
ZEND_END_ARG_INFO()

ZEND_BEGIN_ARG_INFO_EX(ai_CouchbaseExtension_documentGetAndLock, 0, 0, 6)
ZEND_ARG_INFO(0, connection)
ZEND_ARG_TYPE_INFO(0, bucket, IS_STRING, 0)
ZEND_ARG_TYPE_INFO(0, scope, IS_STRING, 0)
ZEND_ARG_TYPE_INFO(0, collection, IS_STRING, 0)
ZEND_ARG_TYPE_INFO(0, id, IS_STRING, 0)
ZEND_ARG_TYPE_INFO(0, lockTimeSeconds, IS_LONG, 0)
ZEND_ARG_TYPE_INFO(0, options, IS_ARRAY, 1)
ZEND_END_ARG_INFO()

ZEND_BEGIN_ARG_INFO_EX(ai_CouchbaseExtension_documentGetAndTouch, 0, 0, 6)
ZEND_ARG_INFO(0, connection)
ZEND_ARG_TYPE_INFO(0, bucket, IS_STRING, 0)
ZEND_ARG_TYPE_INFO(0, scope, IS_STRING, 0)
ZEND_ARG_TYPE_INFO(0, collection, IS_STRING, 0)
ZEND_ARG_TYPE_INFO(0, id, IS_STRING, 0)
ZEND_ARG_TYPE_INFO(0, expirySeconds, IS_LONG, 0)
ZEND_ARG_TYPE_INFO(0, options, IS_ARRAY, 1)
ZEND_END_ARG_INFO()

ZEND_BEGIN_ARG_INFO_EX(ai_CouchbaseExtension_documentUnlock, 0, 0, 6)
ZEND_ARG_INFO(0, connection)
ZEND_ARG_TYPE_INFO(0, bucket, IS_STRING, 0)
ZEND_ARG_TYPE_INFO(0, scope, IS_STRING, 0)
ZEND_ARG_TYPE_INFO(0, collection, IS_STRING, 0)
ZEND_ARG_TYPE_INFO(0, id, IS_STRING, 0)
ZEND_ARG_TYPE_INFO(0, cas, IS_STRING, 0)
ZEND_ARG_TYPE_INFO(0, options, IS_ARRAY, 1)
ZEND_END_ARG_INFO()

ZEND_BEGIN_ARG_INFO_EX(ai_CouchbaseExtension_documentRemove, 0, 0, 5)
ZEND_ARG_INFO(0, connection)
ZEND_ARG_TYPE_INFO(0, bucket, IS_STRING, 0)
ZEND_ARG_TYPE_INFO(0, scope, IS_STRING, 0)
ZEND_ARG_TYPE_INFO(0, collection, IS_STRING, 0)
ZEND_ARG_TYPE_INFO(0, id, IS_STRING, 0)
ZEND_ARG_TYPE_INFO(0, options, IS_ARRAY, 1)
ZEND_END_ARG_INFO()

ZEND_BEGIN_ARG_INFO_EX(ai_CouchbaseExtension_documentTouch, 0, 0, 6)
ZEND_ARG_INFO(0, connection)
ZEND_ARG_TYPE_INFO(0, bucket, IS_STRING, 0)
ZEND_ARG_TYPE_INFO(0, scope, IS_STRING, 0)
ZEND_ARG_TYPE_INFO(0, collection, IS_STRING, 0)
ZEND_ARG_TYPE_INFO(0, id, IS_STRING, 0)
ZEND_ARG_TYPE_INFO(0, expirySeconds, IS_LONG, 0)
ZEND_ARG_TYPE_INFO(0, options, IS_ARRAY, 1)
ZEND_END_ARG_INFO()

ZEND_BEGIN_ARG_INFO_EX(ai_CouchbaseExtension_documentExists, 0, 0, 5)
ZEND_ARG_INFO(0, connection)
ZEND_ARG_TYPE_INFO(0, bucket, IS_STRING, 0)
ZEND_ARG_TYPE_INFO(0, scope, IS_STRING, 0)
ZEND_ARG_TYPE_INFO(0, collection, IS_STRING, 0)
ZEND_ARG_TYPE_INFO(0, id, IS_STRING, 0)
ZEND_ARG_TYPE_INFO(0, options, IS_ARRAY, 1)
ZEND_END_ARG_INFO()

ZEND_BEGIN_ARG_INFO_EX(ai_CouchbaseExtension_documentMutateIn, 0, 0, 6)
ZEND_ARG_INFO(0, connection)
ZEND_ARG_TYPE_INFO(0, bucket, IS_STRING, 0)
ZEND_ARG_TYPE_INFO(0, scope, IS_STRING, 0)
ZEND_ARG_TYPE_INFO(0, collection, IS_STRING, 0)
ZEND_ARG_TYPE_INFO(0, id, IS_STRING, 0)
ZEND_ARG_TYPE_INFO(0, specs, IS_ARRAY, 0)
ZEND_ARG_TYPE_INFO(0, options, IS_ARRAY, 1)
ZEND_END_ARG_INFO()

ZEND_BEGIN_ARG_INFO_EX(ai_CouchbaseExtension_documentLookupIn, 0, 0, 6)
ZEND_ARG_INFO(0, connection)
ZEND_ARG_TYPE_INFO(0, bucket, IS_STRING, 0)
ZEND_ARG_TYPE_INFO(0, scope, IS_STRING, 0)
ZEND_ARG_TYPE_INFO(0, collection, IS_STRING, 0)
ZEND_ARG_TYPE_INFO(0, id, IS_STRING, 0)
ZEND_ARG_TYPE_INFO(0, specs, IS_ARRAY, 0)
ZEND_ARG_TYPE_INFO(0, options, IS_ARRAY, 1)
ZEND_END_ARG_INFO()

ZEND_BEGIN_ARG_INFO_EX(ai_CouchbaseExtension_documentLookupInAnyReplica, 0, 0, 6)
ZEND_ARG_INFO(0, connection)
ZEND_ARG_TYPE_INFO(0, bucket, IS_STRING, 0)
ZEND_ARG_TYPE_INFO(0, scope, IS_STRING, 0)
ZEND_ARG_TYPE_INFO(0, collection, IS_STRING, 0)
ZEND_ARG_TYPE_INFO(0, id, IS_STRING, 0)
ZEND_ARG_TYPE_INFO(0, specs, IS_ARRAY, 0)
ZEND_ARG_TYPE_INFO(0, options, IS_ARRAY, 1)
ZEND_END_ARG_INFO()

ZEND_BEGIN_ARG_INFO_EX(ai_CouchbaseExtension_documentLookupInAllReplicas, 0, 0, 6)
ZEND_ARG_INFO(0, connection)
ZEND_ARG_TYPE_INFO(0, bucket, IS_STRING, 0)
ZEND_ARG_TYPE_INFO(0, scope, IS_STRING, 0)
ZEND_ARG_TYPE_INFO(0, collection, IS_STRING, 0)
ZEND_ARG_TYPE_INFO(0, id, IS_STRING, 0)
ZEND_ARG_TYPE_INFO(0, specs, IS_ARRAY, 0)
ZEND_ARG_TYPE_INFO(0, options, IS_ARRAY, 1)
ZEND_END_ARG_INFO()

ZEND_BEGIN_ARG_WITH_RETURN_TYPE_INFO_EX(ai_CouchbaseExtension_createDocumentScanResult, 0, 0, IS_RESOURCE, 1)
ZEND_ARG_INFO(0, connection)
ZEND_ARG_TYPE_INFO(0, bucket, IS_STRING, 0)
ZEND_ARG_TYPE_INFO(0, scope, IS_STRING, 0)
ZEND_ARG_TYPE_INFO(0, collection, IS_STRING, 0)
ZEND_ARG_TYPE_INFO(0, scan_type, IS_ARRAY, 0)
ZEND_ARG_TYPE_INFO(0, options, IS_ARRAY, 1)
ZEND_END_ARG_INFO()

ZEND_BEGIN_ARG_INFO_EX(ai_CouchbaseExtension_documentScanNextItem, 0, 0, 1)
ZEND_ARG_INFO(0, scan_result)
ZEND_END_ARG_INFO()

ZEND_BEGIN_ARG_INFO_EX(ai_CouchbaseExtension_documentGetMulti, 0, 0, 5)
ZEND_ARG_INFO(0, connection)
ZEND_ARG_TYPE_INFO(0, bucket, IS_STRING, 0)
ZEND_ARG_TYPE_INFO(0, scope, IS_STRING, 0)
ZEND_ARG_TYPE_INFO(0, collection, IS_STRING, 0)
ZEND_ARG_TYPE_INFO(0, ids, IS_ARRAY, 0)
ZEND_ARG_TYPE_INFO(0, options, IS_ARRAY, 1)
ZEND_END_ARG_INFO()

ZEND_BEGIN_ARG_INFO_EX(ai_CouchbaseExtension_documentRemoveMulti, 0, 0, 5)
ZEND_ARG_INFO(0, connection)
ZEND_ARG_TYPE_INFO(0, bucket, IS_STRING, 0)
ZEND_ARG_TYPE_INFO(0, scope, IS_STRING, 0)
ZEND_ARG_TYPE_INFO(0, collection, IS_STRING, 0)
ZEND_ARG_TYPE_INFO(0, entries, IS_ARRAY, 0)
ZEND_ARG_TYPE_INFO(0, options, IS_ARRAY, 1)
ZEND_END_ARG_INFO()

ZEND_BEGIN_ARG_INFO_EX(ai_CouchbaseExtension_documentUpsertMulti, 0, 0, 5)
ZEND_ARG_INFO(0, connection)
ZEND_ARG_TYPE_INFO(0, bucket, IS_STRING, 0)
ZEND_ARG_TYPE_INFO(0, scope, IS_STRING, 0)
ZEND_ARG_TYPE_INFO(0, collection, IS_STRING, 0)
ZEND_ARG_TYPE_INFO(0, entries, IS_ARRAY, 0)
ZEND_ARG_TYPE_INFO(0, options, IS_ARRAY, 1)
ZEND_END_ARG_INFO()

ZEND_BEGIN_ARG_INFO_EX(ai_CouchbaseExtension_query, 0, 0, 2)
ZEND_ARG_INFO(0, connection)
ZEND_ARG_TYPE_INFO(0, statement, IS_STRING, 0)
ZEND_ARG_TYPE_INFO(0, options, IS_ARRAY, 1)
ZEND_END_ARG_INFO()

ZEND_BEGIN_ARG_INFO_EX(ai_CouchbaseExtension_analyticsQuery, 0, 0, 2)
ZEND_ARG_INFO(0, connection)
ZEND_ARG_TYPE_INFO(0, statement, IS_STRING, 0)
ZEND_ARG_TYPE_INFO(0, options, IS_ARRAY, 1)
ZEND_END_ARG_INFO()

ZEND_BEGIN_ARG_INFO_EX(ai_CouchbaseExtension_viewQuery, 0, 0, 5)
ZEND_ARG_INFO(0, connection)
ZEND_ARG_TYPE_INFO(0, bucketName, IS_STRING, 0)
ZEND_ARG_TYPE_INFO(0, designDocumentName, IS_STRING, 0)
ZEND_ARG_TYPE_INFO(0, viewName, IS_STRING, 0)
ZEND_ARG_TYPE_INFO(0, nameSpace, IS_LONG, 0)
ZEND_ARG_TYPE_INFO(0, options, IS_ARRAY, 1)
ZEND_END_ARG_INFO()

ZEND_BEGIN_ARG_INFO_EX(ai_CouchbaseExtension_searchQuery, 0, 0, 3)
ZEND_ARG_INFO(0, connection)
ZEND_ARG_TYPE_INFO(0, indexName, IS_STRING, 0)
ZEND_ARG_TYPE_INFO(0, query, IS_STRING, 0)
ZEND_ARG_TYPE_INFO(0, options, IS_ARRAY, 1)
ZEND_END_ARG_INFO()

ZEND_BEGIN_ARG_INFO_EX(ai_CouchbaseExtension_vectorSearch, 0, 0, 4)
ZEND_ARG_INFO(0, connection)
ZEND_ARG_TYPE_INFO(0, indexName, IS_STRING, 0)
ZEND_ARG_TYPE_INFO(0, query, IS_STRING, 0)
ZEND_ARG_TYPE_INFO(0, vector_search, IS_STRING, 0)
ZEND_ARG_TYPE_INFO(0, options, IS_ARRAY, 1)
ZEND_ARG_TYPE_INFO(0, vector_options, IS_ARRAY, 1)
ZEND_END_ARG_INFO()

ZEND_BEGIN_ARG_INFO_EX(ai_CouchbaseExtension_ping, 0, 0, 1)
ZEND_ARG_INFO(0, connection)
ZEND_ARG_TYPE_INFO(0, options, IS_ARRAY, 1)
ZEND_END_ARG_INFO()

ZEND_BEGIN_ARG_INFO_EX(ai_CouchbaseExtension_diagnostics, 0, 0, 2)
ZEND_ARG_INFO(0, connection)
ZEND_ARG_TYPE_INFO(0, reportId, IS_STRING, 0)
ZEND_ARG_TYPE_INFO(0, options, IS_ARRAY, 1)
ZEND_END_ARG_INFO()

ZEND_BEGIN_ARG_INFO_EX(ai_CouchbaseExtension_searchIndexGet, 0, 0, 2)
ZEND_ARG_INFO(0, connection)
ZEND_ARG_TYPE_INFO(0, indexName, IS_STRING, 0)
ZEND_ARG_TYPE_INFO(0, options, IS_ARRAY, 1)
ZEND_END_ARG_INFO()

ZEND_BEGIN_ARG_INFO_EX(ai_CouchbaseExtension_searchIndexGetAll, 0, 0, 1)
ZEND_ARG_INFO(0, connection)
ZEND_ARG_TYPE_INFO(0, options, IS_ARRAY, 1)
ZEND_END_ARG_INFO()

ZEND_BEGIN_ARG_INFO_EX(ai_CouchbaseExtension_searchIndexUpsert, 0, 0, 2)
ZEND_ARG_INFO(0, connection)
ZEND_ARG_TYPE_INFO(0, index, IS_ARRAY, 0)
ZEND_ARG_TYPE_INFO(0, options, IS_ARRAY, 1)
ZEND_END_ARG_INFO()

ZEND_BEGIN_ARG_INFO_EX(ai_CouchbaseExtension_searchIndexDrop, 0, 0, 2)
ZEND_ARG_INFO(0, connection)
ZEND_ARG_TYPE_INFO(0, indexName, IS_STRING, 0)
ZEND_ARG_TYPE_INFO(0, options, IS_ARRAY, 1)
ZEND_END_ARG_INFO()

ZEND_BEGIN_ARG_INFO_EX(ai_CouchbaseExtension_searchIndexGetDocumentsCount, 0, 0, 2)
ZEND_ARG_INFO(0, connection)
ZEND_ARG_TYPE_INFO(0, indexName, IS_STRING, 0)
ZEND_ARG_TYPE_INFO(0, options, IS_ARRAY, 1)
ZEND_END_ARG_INFO()

ZEND_BEGIN_ARG_INFO_EX(ai_CouchbaseExtension_searchIndexIngestPause, 0, 0, 2)
ZEND_ARG_INFO(0, connection)
ZEND_ARG_TYPE_INFO(0, indexName, IS_STRING, 0)
ZEND_ARG_TYPE_INFO(0, options, IS_ARRAY, 1)
ZEND_END_ARG_INFO()

ZEND_BEGIN_ARG_INFO_EX(ai_CouchbaseExtension_searchIndexIngestResume, 0, 0, 2)
ZEND_ARG_INFO(0, connection)
ZEND_ARG_TYPE_INFO(0, indexName, IS_STRING, 0)
ZEND_ARG_TYPE_INFO(0, options, IS_ARRAY, 1)
ZEND_END_ARG_INFO()

ZEND_BEGIN_ARG_INFO_EX(ai_CouchbaseExtension_searchIndexQueryingAllow, 0, 0, 2)
ZEND_ARG_INFO(0, connection)
ZEND_ARG_TYPE_INFO(0, indexName, IS_STRING, 0)
ZEND_ARG_TYPE_INFO(0, options, IS_ARRAY, 1)
ZEND_END_ARG_INFO()

ZEND_BEGIN_ARG_INFO_EX(ai_CouchbaseExtension_searchIndexQueryingDisallow, 0, 0, 2)
ZEND_ARG_INFO(0, connection)
ZEND_ARG_TYPE_INFO(0, indexName, IS_STRING, 0)
ZEND_ARG_TYPE_INFO(0, options, IS_ARRAY, 1)
ZEND_END_ARG_INFO()

ZEND_BEGIN_ARG_INFO_EX(ai_CouchbaseExtension_searchIndexPlanFreeze, 0, 0, 2)
ZEND_ARG_INFO(0, connection)
ZEND_ARG_TYPE_INFO(0, indexName, IS_STRING, 0)
ZEND_ARG_TYPE_INFO(0, options, IS_ARRAY, 1)
ZEND_END_ARG_INFO()

ZEND_BEGIN_ARG_INFO_EX(ai_CouchbaseExtension_searchIndexPlanUnfreeze, 0, 0, 2)
ZEND_ARG_INFO(0, connection)
ZEND_ARG_TYPE_INFO(0, indexName, IS_STRING, 0)
ZEND_ARG_TYPE_INFO(0, options, IS_ARRAY, 1)
ZEND_END_ARG_INFO()

ZEND_BEGIN_ARG_INFO_EX(ai_CouchbaseExtension_searchIndexDocumentAnalyze, 0, 0, 3)
ZEND_ARG_INFO(0, connection)
ZEND_ARG_TYPE_INFO(0, indexName, IS_STRING, 0)
ZEND_ARG_TYPE_INFO(0, document, IS_STRING, 0)
ZEND_ARG_TYPE_INFO(0, options, IS_ARRAY, 1)
ZEND_END_ARG_INFO()

ZEND_BEGIN_ARG_INFO_EX(ai_CouchbaseExtension_scopeSearchIndexGet, 0, 0, 4)
ZEND_ARG_INFO(0, connection)
ZEND_ARG_TYPE_INFO(0, bucketName, IS_STRING, 0)
ZEND_ARG_TYPE_INFO(0, scopeName, IS_STRING, 0)
ZEND_ARG_TYPE_INFO(0, indexName, IS_STRING, 0)
ZEND_ARG_TYPE_INFO(0, options, IS_ARRAY, 1)
ZEND_END_ARG_INFO()

ZEND_BEGIN_ARG_INFO_EX(ai_CouchbaseExtension_scopeSearchIndexGetAll, 0, 0, 3)
ZEND_ARG_INFO(0, connection)
ZEND_ARG_TYPE_INFO(0, bucketName, IS_STRING, 0)
ZEND_ARG_TYPE_INFO(0, scopeName, IS_STRING, 0)
ZEND_ARG_TYPE_INFO(0, options, IS_ARRAY, 1)
ZEND_END_ARG_INFO()

ZEND_BEGIN_ARG_INFO_EX(ai_CouchbaseExtension_scopeSearchIndexUpsert, 0, 0, 4)
ZEND_ARG_INFO(0, connection)
ZEND_ARG_TYPE_INFO(0, bucketName, IS_STRING, 0)
ZEND_ARG_TYPE_INFO(0, scopeName, IS_STRING, 0)
ZEND_ARG_TYPE_INFO(0, index, IS_ARRAY, 0)
ZEND_ARG_TYPE_INFO(0, options, IS_ARRAY, 1)
ZEND_END_ARG_INFO()

ZEND_BEGIN_ARG_INFO_EX(ai_CouchbaseExtension_scopeSearchIndexDrop, 0, 0, 4)
ZEND_ARG_INFO(0, connection)
ZEND_ARG_TYPE_INFO(0, bucketName, IS_STRING, 0)
ZEND_ARG_TYPE_INFO(0, scopeName, IS_STRING, 0)
ZEND_ARG_TYPE_INFO(0, indexName, IS_STRING, 0)
ZEND_ARG_TYPE_INFO(0, options, IS_ARRAY, 1)
ZEND_END_ARG_INFO()

ZEND_BEGIN_ARG_INFO_EX(ai_CouchbaseExtension_scopeSearchIndexGetDocumentsCount, 0, 0, 4)
ZEND_ARG_INFO(0, connection)
ZEND_ARG_TYPE_INFO(0, bucketName, IS_STRING, 0)
ZEND_ARG_TYPE_INFO(0, scopeName, IS_STRING, 0)
ZEND_ARG_TYPE_INFO(0, indexName, IS_STRING, 0)
ZEND_ARG_TYPE_INFO(0, options, IS_ARRAY, 1)
ZEND_END_ARG_INFO()

ZEND_BEGIN_ARG_INFO_EX(ai_CouchbaseExtension_scopeSearchIndexIngestPause, 0, 0, 4)
ZEND_ARG_INFO(0, connection)
ZEND_ARG_TYPE_INFO(0, bucketName, IS_STRING, 0)
ZEND_ARG_TYPE_INFO(0, scopeName, IS_STRING, 0)
ZEND_ARG_TYPE_INFO(0, indexName, IS_STRING, 0)
ZEND_ARG_TYPE_INFO(0, options, IS_ARRAY, 1)
ZEND_END_ARG_INFO()

ZEND_BEGIN_ARG_INFO_EX(ai_CouchbaseExtension_scopeSearchIndexIngestResume, 0, 0, 4)
ZEND_ARG_INFO(0, connection)
ZEND_ARG_TYPE_INFO(0, bucketName, IS_STRING, 0)
ZEND_ARG_TYPE_INFO(0, scopeName, IS_STRING, 0)
ZEND_ARG_TYPE_INFO(0, indexName, IS_STRING, 0)
ZEND_ARG_TYPE_INFO(0, options, IS_ARRAY, 1)
ZEND_END_ARG_INFO()

ZEND_BEGIN_ARG_INFO_EX(ai_CouchbaseExtension_scopeSearchIndexQueryingAllow, 0, 0, 4)
ZEND_ARG_INFO(0, connection)
ZEND_ARG_TYPE_INFO(0, bucketName, IS_STRING, 0)
ZEND_ARG_TYPE_INFO(0, scopeName, IS_STRING, 0)
ZEND_ARG_TYPE_INFO(0, indexName, IS_STRING, 0)
ZEND_ARG_TYPE_INFO(0, options, IS_ARRAY, 1)
ZEND_END_ARG_INFO()

ZEND_BEGIN_ARG_INFO_EX(ai_CouchbaseExtension_scopeSearchIndexQueryingDisallow, 0, 0, 4)
ZEND_ARG_INFO(0, connection)
ZEND_ARG_TYPE_INFO(0, bucketName, IS_STRING, 0)
ZEND_ARG_TYPE_INFO(0, scopeName, IS_STRING, 0)
ZEND_ARG_TYPE_INFO(0, indexName, IS_STRING, 0)
ZEND_ARG_TYPE_INFO(0, options, IS_ARRAY, 1)
ZEND_END_ARG_INFO()

ZEND_BEGIN_ARG_INFO_EX(ai_CouchbaseExtension_scopeSearchIndexPlanFreeze, 0, 0, 4)
ZEND_ARG_INFO(0, connection)
ZEND_ARG_TYPE_INFO(0, bucketName, IS_STRING, 0)
ZEND_ARG_TYPE_INFO(0, scopeName, IS_STRING, 0)
ZEND_ARG_TYPE_INFO(0, indexName, IS_STRING, 0)
ZEND_ARG_TYPE_INFO(0, options, IS_ARRAY, 1)
ZEND_END_ARG_INFO()

ZEND_BEGIN_ARG_INFO_EX(ai_CouchbaseExtension_scopeSearchIndexPlanUnfreeze, 0, 0, 4)
ZEND_ARG_INFO(0, connection)
ZEND_ARG_TYPE_INFO(0, bucketName, IS_STRING, 0)
ZEND_ARG_TYPE_INFO(0, scopeName, IS_STRING, 0)
ZEND_ARG_TYPE_INFO(0, indexName, IS_STRING, 0)
ZEND_ARG_TYPE_INFO(0, options, IS_ARRAY, 1)
ZEND_END_ARG_INFO()

ZEND_BEGIN_ARG_INFO_EX(ai_CouchbaseExtension_scopeSearchIndexDocumentAnalyze, 0, 0, 5)
ZEND_ARG_INFO(0, connection)
ZEND_ARG_TYPE_INFO(0, bucketName, IS_STRING, 0)
ZEND_ARG_TYPE_INFO(0, scopeName, IS_STRING, 0)
ZEND_ARG_TYPE_INFO(0, indexName, IS_STRING, 0)
ZEND_ARG_TYPE_INFO(0, document, IS_STRING, 0)
ZEND_ARG_TYPE_INFO(0, options, IS_ARRAY, 1)
ZEND_END_ARG_INFO()

ZEND_BEGIN_ARG_INFO_EX(ai_CouchbaseExtension_viewIndexUpsert, 0, 0, 4)
ZEND_ARG_INFO(0, connection)
ZEND_ARG_TYPE_INFO(0, bucketName, IS_STRING, 0)
ZEND_ARG_TYPE_INFO(0, index, IS_ARRAY, 0)
ZEND_ARG_TYPE_INFO(0, nameSpace, IS_LONG, 0)
ZEND_ARG_TYPE_INFO(0, options, IS_ARRAY, 1)
ZEND_END_ARG_INFO()

ZEND_BEGIN_ARG_INFO_EX(ai_CouchbaseExtension_bucketCreate, 0, 0, 2)
ZEND_ARG_INFO(0, connection)
ZEND_ARG_TYPE_INFO(0, bucketSettings, IS_ARRAY, 0)
ZEND_ARG_TYPE_INFO(0, options, IS_ARRAY, 1)
ZEND_END_ARG_INFO()

ZEND_BEGIN_ARG_INFO_EX(ai_CouchbaseExtension_bucketUpdate, 0, 0, 2)
ZEND_ARG_INFO(0, connection)
ZEND_ARG_TYPE_INFO(0, bucketSettings, IS_ARRAY, 0)
ZEND_ARG_TYPE_INFO(0, options, IS_ARRAY, 1)
ZEND_END_ARG_INFO()

ZEND_BEGIN_ARG_INFO_EX(ai_CouchbaseExtension_bucketGet, 0, 0, 2)
ZEND_ARG_INFO(0, connection)
ZEND_ARG_TYPE_INFO(0, name, IS_STRING, 0)
ZEND_ARG_TYPE_INFO(0, options, IS_ARRAY, 1)
ZEND_END_ARG_INFO()

ZEND_BEGIN_ARG_INFO_EX(ai_CouchbaseExtension_bucketGetAll, 0, 0, 1)
ZEND_ARG_INFO(0, connection)
ZEND_ARG_TYPE_INFO(0, options, IS_ARRAY, 1)
ZEND_END_ARG_INFO()

ZEND_BEGIN_ARG_INFO_EX(ai_CouchbaseExtension_bucketDrop, 0, 0, 2)
ZEND_ARG_INFO(0, connection)
ZEND_ARG_TYPE_INFO(0, name, IS_STRING, 0)
ZEND_ARG_TYPE_INFO(0, options, IS_ARRAY, 1)
ZEND_END_ARG_INFO()

ZEND_BEGIN_ARG_INFO_EX(ai_CouchbaseExtension_bucketFlush, 0, 0, 2)
ZEND_ARG_INFO(0, connection)
ZEND_ARG_TYPE_INFO(0, name, IS_STRING, 0)
ZEND_ARG_TYPE_INFO(0, options, IS_ARRAY, 1)
ZEND_END_ARG_INFO()

ZEND_BEGIN_ARG_INFO_EX(ai_CouchbaseExtension_scopeGetAll, 0, 0, 2)
ZEND_ARG_INFO(0, connection)
ZEND_ARG_TYPE_INFO(0, name, IS_STRING, 0)
ZEND_ARG_TYPE_INFO(0, options, IS_ARRAY, 1)
ZEND_END_ARG_INFO()

ZEND_BEGIN_ARG_INFO_EX(ai_CouchbaseExtension_scopeCreate, 0, 0, 3)
ZEND_ARG_INFO(0, connection)
ZEND_ARG_TYPE_INFO(0, bucketName, IS_STRING, 0)
ZEND_ARG_TYPE_INFO(0, scopeName, IS_STRING, 0)
ZEND_ARG_TYPE_INFO(0, options, IS_ARRAY, 1)
ZEND_END_ARG_INFO()

ZEND_BEGIN_ARG_INFO_EX(ai_CouchbaseExtension_scopeDrop, 0, 0, 3)
ZEND_ARG_INFO(0, connection)
ZEND_ARG_TYPE_INFO(0, bucketName, IS_STRING, 0)
ZEND_ARG_TYPE_INFO(0, scopeName, IS_STRING, 0)
ZEND_ARG_TYPE_INFO(0, options, IS_ARRAY, 1)
ZEND_END_ARG_INFO()

ZEND_BEGIN_ARG_INFO_EX(ai_CouchbaseExtension_collectionCreate, 0, 0, 4)
ZEND_ARG_INFO(0, connection)
ZEND_ARG_TYPE_INFO(0, bucketName, IS_STRING, 0)
ZEND_ARG_TYPE_INFO(0, scopeName, IS_STRING, 0)
ZEND_ARG_TYPE_INFO(0, collectionName, IS_STRING, 0)
ZEND_ARG_TYPE_INFO(0, settings, IS_ARRAY, 1)
ZEND_ARG_TYPE_INFO(0, options, IS_ARRAY, 1)
ZEND_END_ARG_INFO()

ZEND_BEGIN_ARG_INFO_EX(ai_CouchbaseExtension_collectionDrop, 0, 0, 4)
ZEND_ARG_INFO(0, connection)
ZEND_ARG_TYPE_INFO(0, bucketName, IS_STRING, 0)
ZEND_ARG_TYPE_INFO(0, scopeName, IS_STRING, 0)
ZEND_ARG_TYPE_INFO(0, collectionName, IS_STRING, 0)
ZEND_ARG_TYPE_INFO(0, options, IS_ARRAY, 1)
ZEND_END_ARG_INFO()

ZEND_BEGIN_ARG_INFO_EX(ai_CouchbaseExtension_collectionUpdate, 0, 0, 5)
ZEND_ARG_INFO(0, connection)
ZEND_ARG_TYPE_INFO(0, bucketName, IS_STRING, 0)
ZEND_ARG_TYPE_INFO(0, scopeName, IS_STRING, 0)
ZEND_ARG_TYPE_INFO(0, collectionName, IS_STRING, 0)
ZEND_ARG_TYPE_INFO(0, settings, IS_ARRAY, 0)
ZEND_ARG_TYPE_INFO(0, options, IS_ARRAY, 1)
ZEND_END_ARG_INFO()

ZEND_BEGIN_ARG_WITH_RETURN_TYPE_INFO_EX(ai_CouchbaseExtension_createTransactions, 0, 0, IS_RESOURCE, 1)
ZEND_ARG_INFO(0, connection)
ZEND_ARG_TYPE_INFO(0, configuration, IS_ARRAY, 1)
ZEND_END_ARG_INFO()

ZEND_BEGIN_ARG_WITH_RETURN_TYPE_INFO_EX(ai_CouchbaseExtension_createTransactionContext, 0, 0, IS_RESOURCE, 1)
ZEND_ARG_INFO(0, transactions)
ZEND_ARG_TYPE_INFO(0, configuration, IS_ARRAY, 1)
ZEND_END_ARG_INFO()

ZEND_BEGIN_ARG_INFO_EX(ai_CouchbaseExtension_transactionNewAttempt, 0, 0, 1)
ZEND_ARG_INFO(0, transactions)
ZEND_END_ARG_INFO()

ZEND_BEGIN_ARG_INFO_EX(ai_CouchbaseExtension_transactionCommit, 0, 0, 1)
ZEND_ARG_INFO(0, transactions)
ZEND_END_ARG_INFO()

ZEND_BEGIN_ARG_INFO_EX(ai_CouchbaseExtension_transactionRollback, 0, 0, 1)
ZEND_ARG_INFO(0, transactions)
ZEND_END_ARG_INFO()

ZEND_BEGIN_ARG_INFO_EX(ai_CouchbaseExtension_transactionGet, 0, 0, 5)
ZEND_ARG_INFO(0, transactions)
ZEND_ARG_TYPE_INFO(0, bucketName, IS_STRING, 0)
ZEND_ARG_TYPE_INFO(0, scopeName, IS_STRING, 0)
ZEND_ARG_TYPE_INFO(0, collectionName, IS_STRING, 0)
ZEND_ARG_TYPE_INFO(0, id, IS_STRING, 0)
ZEND_END_ARG_INFO()

ZEND_BEGIN_ARG_INFO_EX(ai_CouchbaseExtension_transactionInsert, 0, 0, 6)
ZEND_ARG_INFO(0, transactions)
ZEND_ARG_TYPE_INFO(0, bucketName, IS_STRING, 0)
ZEND_ARG_TYPE_INFO(0, scopeName, IS_STRING, 0)
ZEND_ARG_TYPE_INFO(0, collectionName, IS_STRING, 0)
ZEND_ARG_TYPE_INFO(0, id, IS_STRING, 0)
ZEND_ARG_TYPE_INFO(0, value, IS_STRING, 0)
ZEND_END_ARG_INFO()

ZEND_BEGIN_ARG_INFO_EX(ai_CouchbaseExtension_transactionReplace, 0, 0, 3)
ZEND_ARG_INFO(0, transactions)
ZEND_ARG_TYPE_INFO(0, document, IS_ARRAY, 0)
ZEND_ARG_TYPE_INFO(0, value, IS_STRING, 0)
ZEND_END_ARG_INFO()

ZEND_BEGIN_ARG_INFO_EX(ai_CouchbaseExtension_transactionRemove, 0, 0, 2)
ZEND_ARG_INFO(0, transactions)
ZEND_ARG_TYPE_INFO(0, document, IS_ARRAY, 0)
ZEND_END_ARG_INFO()

ZEND_BEGIN_ARG_INFO_EX(ai_CouchbaseExtension_transactionQuery, 0, 0, 2)
ZEND_ARG_INFO(0, transactions)
ZEND_ARG_TYPE_INFO(0, statement, IS_STRING, 0)
ZEND_ARG_TYPE_INFO(0, options, IS_ARRAY, 1)
ZEND_END_ARG_INFO()

ZEND_BEGIN_ARG_INFO_EX(ai_CouchbaseExtension_userUpsert, 0, 0, 2)
ZEND_ARG_INFO(0, connection)
ZEND_ARG_TYPE_INFO(0, user, IS_ARRAY, 0)
ZEND_ARG_TYPE_INFO(0, options, IS_ARRAY, 1)
ZEND_END_ARG_INFO()

ZEND_BEGIN_ARG_INFO_EX(ai_CouchbaseExtension_userGet, 0, 0, 2)
ZEND_ARG_INFO(0, connection)
ZEND_ARG_TYPE_INFO(0, name, IS_STRING, 0)
ZEND_ARG_TYPE_INFO(0, options, IS_ARRAY, 1)
ZEND_END_ARG_INFO()

ZEND_BEGIN_ARG_INFO_EX(ai_CouchbaseExtension_userGetAll, 0, 0, 1)
ZEND_ARG_INFO(0, connection)
ZEND_ARG_TYPE_INFO(0, options, IS_ARRAY, 1)
ZEND_END_ARG_INFO()

ZEND_BEGIN_ARG_INFO_EX(ai_CouchbaseExtension_userDrop, 0, 0, 2)
ZEND_ARG_INFO(0, connection)
ZEND_ARG_TYPE_INFO(0, name, IS_STRING, 0)
ZEND_ARG_TYPE_INFO(0, options, IS_ARRAY, 1)
ZEND_END_ARG_INFO()

ZEND_BEGIN_ARG_INFO_EX(ai_CouchbaseExtension_passwordChange, 0, 0, 2)
ZEND_ARG_INFO(0, connection)
ZEND_ARG_TYPE_INFO(0, new_password, IS_STRING, 0)
ZEND_ARG_TYPE_INFO(0, options, IS_ARRAY, 1)
ZEND_END_ARG_INFO()

ZEND_BEGIN_ARG_INFO_EX(ai_CouchbaseExtension_groupUpsert, 0, 0, 2)
ZEND_ARG_INFO(0, connection)
ZEND_ARG_TYPE_INFO(0, group, IS_ARRAY, 0)
ZEND_ARG_TYPE_INFO(0, options, IS_ARRAY, 1)
ZEND_END_ARG_INFO()

ZEND_BEGIN_ARG_INFO_EX(ai_CouchbaseExtension_groupGet, 0, 0, 2)
ZEND_ARG_INFO(0, connection)
ZEND_ARG_TYPE_INFO(0, name, IS_STRING, 0)
ZEND_ARG_TYPE_INFO(0, options, IS_ARRAY, 1)
ZEND_END_ARG_INFO()

ZEND_BEGIN_ARG_INFO_EX(ai_CouchbaseExtension_groupGetAll, 0, 0, 1)
ZEND_ARG_INFO(0, connection)
ZEND_ARG_TYPE_INFO(0, options, IS_ARRAY, 1)
ZEND_END_ARG_INFO()

ZEND_BEGIN_ARG_INFO_EX(ai_CouchbaseExtension_groupDrop, 0, 0, 2)
ZEND_ARG_INFO(0, connection)
ZEND_ARG_TYPE_INFO(0, name, IS_STRING, 0)
ZEND_ARG_TYPE_INFO(0, options, IS_ARRAY, 1)
ZEND_END_ARG_INFO()

ZEND_BEGIN_ARG_INFO_EX(ai_CouchbaseExtension_roleGetAll, 0, 0, 1)
ZEND_ARG_INFO(0, connection)
ZEND_ARG_TYPE_INFO(0, options, IS_ARRAY, 1)
ZEND_END_ARG_INFO()

ZEND_BEGIN_ARG_INFO_EX(ai_CouchbaseExtension_queryIndexGetAll, 0, 0, 2)
ZEND_ARG_INFO(0, connection)
ZEND_ARG_TYPE_INFO(0, bucketName, IS_STRING, 0)
ZEND_ARG_TYPE_INFO(0, options, IS_ARRAY, 1)
ZEND_END_ARG_INFO()

ZEND_BEGIN_ARG_INFO_EX(ai_CouchbaseExtension_queryIndexCreate, 0, 0, 4)
ZEND_ARG_INFO(0, connection)
ZEND_ARG_TYPE_INFO(0, bucketName, IS_STRING, 0)
ZEND_ARG_TYPE_INFO(0, indexName, IS_STRING, 0)
ZEND_ARG_TYPE_INFO(0, fields, IS_ARRAY, 0)
ZEND_ARG_TYPE_INFO(0, options, IS_ARRAY, 1)
ZEND_END_ARG_INFO()

ZEND_BEGIN_ARG_INFO_EX(ai_CouchbaseExtension_queryIndexCreatePrimary, 0, 0, 2)
ZEND_ARG_INFO(0, connection)
ZEND_ARG_TYPE_INFO(0, bucketName, IS_STRING, 0)
ZEND_ARG_TYPE_INFO(0, options, IS_ARRAY, 1)
ZEND_END_ARG_INFO()

ZEND_BEGIN_ARG_INFO_EX(ai_CouchbaseExtension_queryIndexDrop, 0, 0, 3)
ZEND_ARG_INFO(0, connection)
ZEND_ARG_TYPE_INFO(0, bucketName, IS_STRING, 0)
ZEND_ARG_TYPE_INFO(0, indexName, IS_STRING, 0)
ZEND_ARG_TYPE_INFO(0, options, IS_ARRAY, 1)
ZEND_END_ARG_INFO()

ZEND_BEGIN_ARG_INFO_EX(ai_CouchbaseExtension_queryIndexDropPrimary, 0, 0, 2)
ZEND_ARG_INFO(0, connection)
ZEND_ARG_TYPE_INFO(0, bucketName, IS_STRING, 0)
ZEND_ARG_TYPE_INFO(0, options, IS_ARRAY, 1)
ZEND_END_ARG_INFO()

ZEND_BEGIN_ARG_INFO_EX(ai_CouchbaseExtension_queryIndexBuildDeferred, 0, 0, 2)
ZEND_ARG_INFO(0, connection)
ZEND_ARG_TYPE_INFO(0, bucketName, IS_STRING, 0)
ZEND_ARG_TYPE_INFO(0, options, IS_ARRAY, 1)
ZEND_END_ARG_INFO()

ZEND_BEGIN_ARG_INFO_EX(ai_CouchbaseExtension_collectionQueryIndexGetAll, 0, 0, 4)
ZEND_ARG_INFO(0, connection)
ZEND_ARG_TYPE_INFO(0, bucketName, IS_STRING, 0)
ZEND_ARG_TYPE_INFO(0, scopeName, IS_STRING, 0)
ZEND_ARG_TYPE_INFO(0, collectionName, IS_STRING, 0)
ZEND_ARG_TYPE_INFO(0, options, IS_ARRAY, 1)
ZEND_END_ARG_INFO()

ZEND_BEGIN_ARG_INFO_EX(ai_CouchbaseExtension_collectionQueryIndexCreate, 0, 0, 6)
ZEND_ARG_INFO(0, connection)
ZEND_ARG_TYPE_INFO(0, bucketName, IS_STRING, 0)
ZEND_ARG_TYPE_INFO(0, scopeName, IS_STRING, 0)
ZEND_ARG_TYPE_INFO(0, collectionName, IS_STRING, 0)
ZEND_ARG_TYPE_INFO(0, indexName, IS_STRING, 0)
ZEND_ARG_TYPE_INFO(0, fields, IS_ARRAY, 0)
ZEND_ARG_TYPE_INFO(0, options, IS_ARRAY, 1)
ZEND_END_ARG_INFO()

ZEND_BEGIN_ARG_INFO_EX(ai_CouchbaseExtension_collectionQueryIndexCreatePrimary, 0, 0, 4)
ZEND_ARG_INFO(0, connection)
ZEND_ARG_TYPE_INFO(0, bucketName, IS_STRING, 0)
ZEND_ARG_TYPE_INFO(0, scopeName, IS_STRING, 0)
ZEND_ARG_TYPE_INFO(0, collectionName, IS_STRING, 0)
ZEND_ARG_TYPE_INFO(0, options, IS_ARRAY, 1)
ZEND_END_ARG_INFO()

ZEND_BEGIN_ARG_INFO_EX(ai_CouchbaseExtension_collectionQueryIndexDrop, 0, 0, 5)
ZEND_ARG_INFO(0, connection)
ZEND_ARG_TYPE_INFO(0, bucketName, IS_STRING, 0)
ZEND_ARG_TYPE_INFO(0, scopeName, IS_STRING, 0)
ZEND_ARG_TYPE_INFO(0, collectionName, IS_STRING, 0)
ZEND_ARG_TYPE_INFO(0, indexName, IS_STRING, 0)
ZEND_ARG_TYPE_INFO(0, options, IS_ARRAY, 1)
ZEND_END_ARG_INFO()

ZEND_BEGIN_ARG_INFO_EX(ai_CouchbaseExtension_collectionQueryIndexDropPrimary, 0, 0, 4)
ZEND_ARG_INFO(0, connection)
ZEND_ARG_TYPE_INFO(0, bucketName, IS_STRING, 0)
ZEND_ARG_TYPE_INFO(0, scopeName, IS_STRING, 0)
ZEND_ARG_TYPE_INFO(0, collectionName, IS_STRING, 0)
ZEND_ARG_TYPE_INFO(0, options, IS_ARRAY, 1)
ZEND_END_ARG_INFO()

ZEND_BEGIN_ARG_INFO_EX(ai_CouchbaseExtension_collectionQueryIndexBuildDeferred, 0, 0, 4)
ZEND_ARG_INFO(0, connection)
ZEND_ARG_TYPE_INFO(0, bucketName, IS_STRING, 0)
ZEND_ARG_TYPE_INFO(0, scopeName, IS_STRING, 0)
ZEND_ARG_TYPE_INFO(0, collectionName, IS_STRING, 0)
ZEND_ARG_TYPE_INFO(0, options, IS_ARRAY, 1)
ZEND_END_ARG_INFO()

// clang-format off
static zend_function_entry couchbase_functions[] = {
        ZEND_NS_FE("Couchbase\\Extension", notifyFork, ai_CouchbaseExtension_notifyFork)
        ZEND_NS_FE("Couchbase\\Extension", version, ai_CouchbaseExtension_version)
        ZEND_NS_FE("Couchbase\\Extension", clusterVersion, ai_CouchbaseExtension_clusterVersion)
        ZEND_NS_FE("Couchbase\\Extension", replicasConfiguredForBucket, ai_CouchbaseExtension_replicasConfiguredForBucket)
        ZEND_NS_FE("Couchbase\\Extension", createConnection, ai_CouchbaseExtension_createConnection)
        ZEND_NS_FE("Couchbase\\Extension", openBucket, ai_CouchbaseExtension_openBucket)
        ZEND_NS_FE("Couchbase\\Extension", closeBucket, ai_CouchbaseExtension_closeBucket)
        ZEND_NS_FE("Couchbase\\Extension", documentUpsert, ai_CouchbaseExtension_documentUpsert)
        ZEND_NS_FE("Couchbase\\Extension", documentInsert, ai_CouchbaseExtension_documentInsert)
        ZEND_NS_FE("Couchbase\\Extension", documentReplace, ai_CouchbaseExtension_documentReplace)
        ZEND_NS_FE("Couchbase\\Extension", documentAppend, ai_CouchbaseExtension_documentAppend)
        ZEND_NS_FE("Couchbase\\Extension", documentPrepend, ai_CouchbaseExtension_documentPrepend)
        ZEND_NS_FE("Couchbase\\Extension", documentIncrement, ai_CouchbaseExtension_documentIncrement)
        ZEND_NS_FE("Couchbase\\Extension", documentDecrement, ai_CouchbaseExtension_documentDecrement)
        ZEND_NS_FE("Couchbase\\Extension", documentGet, ai_CouchbaseExtension_documentGet)
        ZEND_NS_FE("Couchbase\\Extension", documentGetAnyReplica, ai_CouchbaseExtension_documentGetAnyReplica)
        ZEND_NS_FE("Couchbase\\Extension", documentGetAllReplicas, ai_CouchbaseExtension_documentGetAllReplicas)
        ZEND_NS_FE("Couchbase\\Extension", documentGetAndTouch, ai_CouchbaseExtension_documentGetAndTouch)
        ZEND_NS_FE("Couchbase\\Extension", documentGetAndLock, ai_CouchbaseExtension_documentGetAndLock)
        ZEND_NS_FE("Couchbase\\Extension", documentUnlock, ai_CouchbaseExtension_documentUnlock)
        ZEND_NS_FE("Couchbase\\Extension", documentRemove, ai_CouchbaseExtension_documentRemove)
        ZEND_NS_FE("Couchbase\\Extension", documentTouch, ai_CouchbaseExtension_documentTouch)
        ZEND_NS_FE("Couchbase\\Extension", documentExists, ai_CouchbaseExtension_documentExists)
        ZEND_NS_FE("Couchbase\\Extension", documentMutateIn, ai_CouchbaseExtension_documentMutateIn)
        ZEND_NS_FE("Couchbase\\Extension", documentLookupIn, ai_CouchbaseExtension_documentLookupIn)
        ZEND_NS_FE("Couchbase\\Extension", createDocumentScanResult, ai_CouchbaseExtension_createDocumentScanResult)
        ZEND_NS_FE("Couchbase\\Extension", documentScanNextItem, ai_CouchbaseExtension_documentScanNextItem)
        ZEND_NS_FE("Couchbase\\Extension", documentLookupInAnyReplica, ai_CouchbaseExtension_documentLookupInAnyReplica)
        ZEND_NS_FE("Couchbase\\Extension", documentLookupInAllReplicas, ai_CouchbaseExtension_documentLookupInAllReplicas)
        ZEND_NS_FE("Couchbase\\Extension", documentGetMulti, ai_CouchbaseExtension_documentGetMulti)
        ZEND_NS_FE("Couchbase\\Extension", documentRemoveMulti, ai_CouchbaseExtension_documentRemoveMulti)
        ZEND_NS_FE("Couchbase\\Extension", documentUpsertMulti, ai_CouchbaseExtension_documentUpsertMulti)
        ZEND_NS_FE("Couchbase\\Extension", query, ai_CouchbaseExtension_query)
        ZEND_NS_FE("Couchbase\\Extension", analyticsQuery, ai_CouchbaseExtension_analyticsQuery)
        ZEND_NS_FE("Couchbase\\Extension", viewQuery, ai_CouchbaseExtension_viewQuery)
        ZEND_NS_FE("Couchbase\\Extension", searchQuery, ai_CouchbaseExtension_searchQuery)
        ZEND_NS_FE("Couchbase\\Extension", vectorSearch, ai_CouchbaseExtension_vectorSearch)
        ZEND_NS_FE("Couchbase\\Extension", ping, ai_CouchbaseExtension_ping)
        ZEND_NS_FE("Couchbase\\Extension", diagnostics, ai_CouchbaseExtension_diagnostics)

        ZEND_NS_FE("Couchbase\\Extension", createTransactions, ai_CouchbaseExtension_createTransactions)
        ZEND_NS_FE("Couchbase\\Extension", createTransactionContext, ai_CouchbaseExtension_createTransactionContext)
        ZEND_NS_FE("Couchbase\\Extension", transactionNewAttempt, ai_CouchbaseExtension_transactionNewAttempt)
        ZEND_NS_FE("Couchbase\\Extension", transactionCommit, ai_CouchbaseExtension_transactionCommit)
        ZEND_NS_FE("Couchbase\\Extension", transactionRollback, ai_CouchbaseExtension_transactionRollback)
        ZEND_NS_FE("Couchbase\\Extension", transactionGet, ai_CouchbaseExtension_transactionGet)
        ZEND_NS_FE("Couchbase\\Extension", transactionInsert, ai_CouchbaseExtension_transactionInsert)
        ZEND_NS_FE("Couchbase\\Extension", transactionReplace, ai_CouchbaseExtension_transactionReplace)
        ZEND_NS_FE("Couchbase\\Extension", transactionRemove, ai_CouchbaseExtension_transactionRemove)
        ZEND_NS_FE("Couchbase\\Extension", transactionQuery, ai_CouchbaseExtension_transactionQuery)

        ZEND_NS_FE("Couchbase\\Extension", searchIndexGet, ai_CouchbaseExtension_searchIndexGet)
        ZEND_NS_FE("Couchbase\\Extension", searchIndexGetAll, ai_CouchbaseExtension_searchIndexGetAll)
        ZEND_NS_FE("Couchbase\\Extension", searchIndexUpsert, ai_CouchbaseExtension_searchIndexUpsert)
        ZEND_NS_FE("Couchbase\\Extension", searchIndexDrop, ai_CouchbaseExtension_searchIndexDrop)
        ZEND_NS_FE("Couchbase\\Extension", searchIndexGetDocumentsCount, ai_CouchbaseExtension_searchIndexGetDocumentsCount)
        ZEND_NS_FE("Couchbase\\Extension", searchIndexIngestPause, ai_CouchbaseExtension_searchIndexIngestPause)
        ZEND_NS_FE("Couchbase\\Extension", searchIndexIngestResume, ai_CouchbaseExtension_searchIndexIngestResume)
        ZEND_NS_FE("Couchbase\\Extension", searchIndexQueryingAllow, ai_CouchbaseExtension_searchIndexQueryingAllow)
        ZEND_NS_FE("Couchbase\\Extension", searchIndexQueryingDisallow, ai_CouchbaseExtension_searchIndexQueryingDisallow)
        ZEND_NS_FE("Couchbase\\Extension", searchIndexPlanFreeze, ai_CouchbaseExtension_searchIndexPlanFreeze)
        ZEND_NS_FE("Couchbase\\Extension", searchIndexPlanUnfreeze, ai_CouchbaseExtension_searchIndexPlanUnfreeze)
        ZEND_NS_FE("Couchbase\\Extension", searchIndexDocumentAnalyze, ai_CouchbaseExtension_searchIndexDocumentAnalyze)
        ZEND_NS_FE("Couchbase\\Extension", scopeSearchIndexGet, ai_CouchbaseExtension_scopeSearchIndexGet)
        ZEND_NS_FE("Couchbase\\Extension", scopeSearchIndexGetAll, ai_CouchbaseExtension_scopeSearchIndexGetAll)
        ZEND_NS_FE("Couchbase\\Extension", scopeSearchIndexUpsert, ai_CouchbaseExtension_scopeSearchIndexUpsert)
        ZEND_NS_FE("Couchbase\\Extension", scopeSearchIndexDrop, ai_CouchbaseExtension_scopeSearchIndexDrop)
        ZEND_NS_FE("Couchbase\\Extension", scopeSearchIndexGetDocumentsCount, ai_CouchbaseExtension_scopeSearchIndexGetDocumentsCount)
        ZEND_NS_FE("Couchbase\\Extension", scopeSearchIndexIngestPause, ai_CouchbaseExtension_scopeSearchIndexIngestPause)
        ZEND_NS_FE("Couchbase\\Extension", scopeSearchIndexIngestResume, ai_CouchbaseExtension_scopeSearchIndexIngestResume)
        ZEND_NS_FE("Couchbase\\Extension", scopeSearchIndexQueryingAllow, ai_CouchbaseExtension_scopeSearchIndexQueryingAllow)
        ZEND_NS_FE("Couchbase\\Extension", scopeSearchIndexQueryingDisallow, ai_CouchbaseExtension_scopeSearchIndexQueryingDisallow)
        ZEND_NS_FE("Couchbase\\Extension", scopeSearchIndexPlanFreeze, ai_CouchbaseExtension_scopeSearchIndexPlanFreeze)
        ZEND_NS_FE("Couchbase\\Extension", scopeSearchIndexPlanUnfreeze, ai_CouchbaseExtension_scopeSearchIndexPlanUnfreeze)
        ZEND_NS_FE("Couchbase\\Extension", scopeSearchIndexDocumentAnalyze, ai_CouchbaseExtension_scopeSearchIndexDocumentAnalyze)
        ZEND_NS_FE("Couchbase\\Extension", viewIndexUpsert, ai_CouchbaseExtension_viewIndexUpsert)
        ZEND_NS_FE("Couchbase\\Extension", bucketCreate, ai_CouchbaseExtension_bucketCreate)
        ZEND_NS_FE("Couchbase\\Extension", bucketUpdate, ai_CouchbaseExtension_bucketUpdate)
        ZEND_NS_FE("Couchbase\\Extension", bucketGet, ai_CouchbaseExtension_bucketGet)
        ZEND_NS_FE("Couchbase\\Extension", bucketGetAll, ai_CouchbaseExtension_bucketGetAll)
        ZEND_NS_FE("Couchbase\\Extension", bucketDrop, ai_CouchbaseExtension_bucketDrop)
        ZEND_NS_FE("Couchbase\\Extension", bucketFlush, ai_CouchbaseExtension_bucketFlush)
        ZEND_NS_FE("Couchbase\\Extension", scopeGetAll, ai_CouchbaseExtension_scopeGetAll)
        ZEND_NS_FE("Couchbase\\Extension", scopeCreate, ai_CouchbaseExtension_scopeCreate)
        ZEND_NS_FE("Couchbase\\Extension", scopeDrop, ai_CouchbaseExtension_scopeDrop)
        ZEND_NS_FE("Couchbase\\Extension", collectionCreate, ai_CouchbaseExtension_collectionCreate)
        ZEND_NS_FE("Couchbase\\Extension", collectionDrop, ai_CouchbaseExtension_collectionDrop)
        ZEND_NS_FE("Couchbase\\Extension", collectionUpdate, ai_CouchbaseExtension_collectionUpdate)
        ZEND_NS_FE("Couchbase\\Extension", userUpsert, ai_CouchbaseExtension_userUpsert)
        ZEND_NS_FE("Couchbase\\Extension", userGet, ai_CouchbaseExtension_userGet)
        ZEND_NS_FE("Couchbase\\Extension", userGetAll, ai_CouchbaseExtension_userGetAll)
        ZEND_NS_FE("Couchbase\\Extension", userDrop, ai_CouchbaseExtension_userDrop)
        ZEND_NS_FE("Couchbase\\Extension", passwordChange, ai_CouchbaseExtension_passwordChange)
        ZEND_NS_FE("Couchbase\\Extension", groupUpsert, ai_CouchbaseExtension_groupUpsert)
        ZEND_NS_FE("Couchbase\\Extension", groupGet, ai_CouchbaseExtension_groupGet)
        ZEND_NS_FE("Couchbase\\Extension", groupGetAll, ai_CouchbaseExtension_groupGetAll)
        ZEND_NS_FE("Couchbase\\Extension", groupDrop, ai_CouchbaseExtension_groupDrop)
        ZEND_NS_FE("Couchbase\\Extension", roleGetAll, ai_CouchbaseExtension_roleGetAll)

        ZEND_NS_FE("Couchbase\\Extension", queryIndexGetAll, ai_CouchbaseExtension_queryIndexGetAll)
        ZEND_NS_FE("Couchbase\\Extension", queryIndexCreate, ai_CouchbaseExtension_queryIndexCreate)
        ZEND_NS_FE("Couchbase\\Extension", queryIndexCreatePrimary, ai_CouchbaseExtension_queryIndexCreatePrimary)
        ZEND_NS_FE("Couchbase\\Extension", queryIndexDrop, ai_CouchbaseExtension_queryIndexDrop)
        ZEND_NS_FE("Couchbase\\Extension", queryIndexDropPrimary, ai_CouchbaseExtension_queryIndexDropPrimary)
        ZEND_NS_FE("Couchbase\\Extension", queryIndexBuildDeferred, ai_CouchbaseExtension_queryIndexBuildDeferred)
        ZEND_NS_FE("Couchbase\\Extension", collectionQueryIndexGetAll, ai_CouchbaseExtension_collectionQueryIndexGetAll)
        ZEND_NS_FE("Couchbase\\Extension", collectionQueryIndexCreate, ai_CouchbaseExtension_collectionQueryIndexCreate)
        ZEND_NS_FE("Couchbase\\Extension", collectionQueryIndexCreatePrimary, ai_CouchbaseExtension_collectionQueryIndexCreatePrimary)
        ZEND_NS_FE("Couchbase\\Extension", collectionQueryIndexDrop, ai_CouchbaseExtension_collectionQueryIndexDrop)
        ZEND_NS_FE("Couchbase\\Extension", collectionQueryIndexDropPrimary, ai_CouchbaseExtension_collectionQueryIndexDropPrimary)
        ZEND_NS_FE("Couchbase\\Extension", collectionQueryIndexBuildDeferred, ai_CouchbaseExtension_collectionQueryIndexBuildDeferred)
        PHP_FE_END
};

static zend_module_dep php_couchbase_deps[] = {
        ZEND_MOD_REQUIRED("json")
        ZEND_MOD_END
};
// clang-format on

zend_module_entry couchbase_module_entry = {
    STANDARD_MODULE_HEADER_EX,
    nullptr,
    php_couchbase_deps,
    PHP_COUCHBASE_EXTENSION_NAME,
    couchbase_functions,      /* extension function list */
    PHP_MINIT(couchbase),     /* extension-wide startup function */
    PHP_MSHUTDOWN(couchbase), /* extension-wide shutdown function */
    PHP_RINIT(couchbase),     /* per-request startup function */
    PHP_RSHUTDOWN(couchbase), /* per-request shutdown function */
    PHP_MINFO(couchbase),     /* information function */
    PHP_COUCHBASE_VERSION,
    PHP_MODULE_GLOBALS(couchbase), /* globals descriptor */
    nullptr,                       /* globals ctor */
    nullptr,                       /* globals dtor */
    nullptr,                       /* post deactivate */
    STANDARD_MODULE_PROPERTIES_EX,
};

#ifdef ZTS
ZEND_TSRMLS_CACHE_DEFINE();
#endif
ZEND_GET_MODULE(couchbase)
