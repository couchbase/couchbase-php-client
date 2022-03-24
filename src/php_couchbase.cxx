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
#include "core/persistent_connections_cache.hxx"
#include "core/version.hxx"

#include "php_couchbase.hxx"

#include <php.h>

#include <Zend/zend_exceptions.h>
#include <ext/standard/info.h>

#include <sstream>

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

zend_class_entry* couchbase_exception_ce;
zend_class_entry* ambiguous_timeout_exception_ce;
zend_class_entry* authentication_failure_exception_ce;
zend_class_entry* bucket_exists_exception_ce;
zend_class_entry* bucket_not_flushable_exception_ce;
zend_class_entry* bucket_not_found_exception_ce;
zend_class_entry* cas_mismatch_exception_ce;
zend_class_entry* collection_exists_exception_ce;
zend_class_entry* collection_not_found_exception_ce;
zend_class_entry* compilation_failure_exception_ce;
zend_class_entry* consistency_mismatch_exception_ce;
zend_class_entry* dataset_exists_exception_ce;
zend_class_entry* dataset_not_found_exception_ce;
zend_class_entry* dataverse_exists_exception_ce;
zend_class_entry* dataverse_not_found_exception_ce;
zend_class_entry* decoding_failure_exception_ce;
zend_class_entry* delta_invalid_exception_ce;
zend_class_entry* design_document_not_found_exception_ce;
zend_class_entry* document_exists_exception_ce;
zend_class_entry* document_irretrievable_exception_ce;
zend_class_entry* document_locked_exception_ce;
zend_class_entry* document_not_found_exception_ce;
zend_class_entry* document_not_json_exception_ce;
zend_class_entry* durability_ambiguous_exception_ce;
zend_class_entry* durability_impossible_exception_ce;
zend_class_entry* durability_level_not_available_exception_ce;
zend_class_entry* durable_write_in_progress_exception_ce;
zend_class_entry* durable_write_re_commit_in_progress_exception_ce;
zend_class_entry* encoding_failure_exception_ce;
zend_class_entry* feature_not_available_exception_ce;
zend_class_entry* group_not_found_exception_ce;
zend_class_entry* index_exists_exception_ce;
zend_class_entry* index_failure_exception_ce;
zend_class_entry* index_not_found_exception_ce;
zend_class_entry* index_not_ready_exception_ce;
zend_class_entry* internal_server_failure_exception_ce;
zend_class_entry* invalid_argument_exception_ce;
zend_class_entry* job_queue_full_exception_ce;
zend_class_entry* link_exists_exception_ce;
zend_class_entry* link_not_found_exception_ce;
zend_class_entry* number_too_big_exception_ce;
zend_class_entry* parsing_failure_exception_ce;
zend_class_entry* path_exists_exception_ce;
zend_class_entry* path_invalid_exception_ce;
zend_class_entry* path_mismatch_exception_ce;
zend_class_entry* path_not_found_exception_ce;
zend_class_entry* path_too_big_exception_ce;
zend_class_entry* path_too_deep_exception_ce;
zend_class_entry* planning_failure_exception_ce;
zend_class_entry* prepared_statement_failure_exception_ce;
zend_class_entry* request_canceled_exception_ce;
zend_class_entry* scope_exists_exception_ce;
zend_class_entry* scope_not_found_exception_ce;
zend_class_entry* service_not_available_exception_ce;
zend_class_entry* temporary_failure_exception_ce;
zend_class_entry* timeout_exception_ce;
zend_class_entry* unambiguous_timeout_exception_ce;
zend_class_entry* unsupported_operation_exception_ce;
zend_class_entry* user_exists_exception_ce;
zend_class_entry* user_not_found_exception_ce;
zend_class_entry* value_invalid_exception_ce;
zend_class_entry* value_too_deep_exception_ce;
zend_class_entry* value_too_large_exception_ce;
zend_class_entry* view_not_found_exception_ce;
zend_class_entry* xattr_cannot_modify_virtual_attribute_exception_ce;
zend_class_entry* xattr_invalid_key_combo_exception_ce;
zend_class_entry* xattr_unknown_macro_exception_ce;
zend_class_entry* xattr_unknown_virtual_attribute_exception_ce;

PHP_MINIT_FUNCTION(couchbase)
{
    (void)type;

    couchbase::php::persistent_connection_destructor_id =
      zend_register_list_destructors_ex(nullptr, couchbase_destroy_persistent_connection, "couchbase_persistent_connection", module_number);

    zend_class_entry ce;
    INIT_NS_CLASS_ENTRY(ce, "Couchbase", "Exception", nullptr);
    couchbase_exception_ce = zend_register_internal_class_ex(&ce, zend_ce_exception);

    INIT_NS_CLASS_ENTRY(ce, "Couchbase", "TimeoutException", nullptr);
    timeout_exception_ce = zend_register_internal_class_ex(&ce, couchbase_exception_ce);
    INIT_NS_CLASS_ENTRY(ce, "Couchbase", "UnambiguousTimeoutException", nullptr);
    unambiguous_timeout_exception_ce = zend_register_internal_class_ex(&ce, timeout_exception_ce);
    INIT_NS_CLASS_ENTRY(ce, "Couchbase", "AmbiguousTimeoutException", nullptr);
    ambiguous_timeout_exception_ce = zend_register_internal_class_ex(&ce, timeout_exception_ce);
    INIT_NS_CLASS_ENTRY(ce, "Couchbase", "AuthenticationFailureException", nullptr);
    authentication_failure_exception_ce = zend_register_internal_class_ex(&ce, couchbase_exception_ce);
    INIT_NS_CLASS_ENTRY(ce, "Couchbase", "BucketExistsException", nullptr);
    bucket_exists_exception_ce = zend_register_internal_class_ex(&ce, couchbase_exception_ce);
    INIT_NS_CLASS_ENTRY(ce, "Couchbase", "BucketNotFlushableException", nullptr);
    bucket_not_flushable_exception_ce = zend_register_internal_class_ex(&ce, couchbase_exception_ce);
    INIT_NS_CLASS_ENTRY(ce, "Couchbase", "BucketNotFoundException", nullptr);
    bucket_not_found_exception_ce = zend_register_internal_class_ex(&ce, couchbase_exception_ce);
    INIT_NS_CLASS_ENTRY(ce, "Couchbase", "CasMismatchException", nullptr);
    cas_mismatch_exception_ce = zend_register_internal_class_ex(&ce, couchbase_exception_ce);
    INIT_NS_CLASS_ENTRY(ce, "Couchbase", "CollectionExistsException", nullptr);
    collection_exists_exception_ce = zend_register_internal_class_ex(&ce, couchbase_exception_ce);
    INIT_NS_CLASS_ENTRY(ce, "Couchbase", "CollectionNotFoundException", nullptr);
    collection_not_found_exception_ce = zend_register_internal_class_ex(&ce, couchbase_exception_ce);
    INIT_NS_CLASS_ENTRY(ce, "Couchbase", "CompilationFailureException", nullptr);
    compilation_failure_exception_ce = zend_register_internal_class_ex(&ce, couchbase_exception_ce);
    INIT_NS_CLASS_ENTRY(ce, "Couchbase", "ConsistencyMismatchException", nullptr);
    consistency_mismatch_exception_ce = zend_register_internal_class_ex(&ce, couchbase_exception_ce);
    INIT_NS_CLASS_ENTRY(ce, "Couchbase", "DatasetExistsException", nullptr);
    dataset_exists_exception_ce = zend_register_internal_class_ex(&ce, couchbase_exception_ce);
    INIT_NS_CLASS_ENTRY(ce, "Couchbase", "DatasetNotFoundException", nullptr);
    dataset_not_found_exception_ce = zend_register_internal_class_ex(&ce, couchbase_exception_ce);
    INIT_NS_CLASS_ENTRY(ce, "Couchbase", "DataverseExistsException", nullptr);
    dataverse_exists_exception_ce = zend_register_internal_class_ex(&ce, couchbase_exception_ce);
    INIT_NS_CLASS_ENTRY(ce, "Couchbase", "DataverseNotFoundException", nullptr);
    dataverse_not_found_exception_ce = zend_register_internal_class_ex(&ce, couchbase_exception_ce);
    INIT_NS_CLASS_ENTRY(ce, "Couchbase", "DecodingFailureException", nullptr);
    decoding_failure_exception_ce = zend_register_internal_class_ex(&ce, couchbase_exception_ce);
    INIT_NS_CLASS_ENTRY(ce, "Couchbase", "DeltaInvalidException", nullptr);
    delta_invalid_exception_ce = zend_register_internal_class_ex(&ce, couchbase_exception_ce);
    INIT_NS_CLASS_ENTRY(ce, "Couchbase", "DesignDocumentNotFoundException", nullptr);
    design_document_not_found_exception_ce = zend_register_internal_class_ex(&ce, couchbase_exception_ce);
    INIT_NS_CLASS_ENTRY(ce, "Couchbase", "DocumentExistsException", nullptr);
    document_exists_exception_ce = zend_register_internal_class_ex(&ce, couchbase_exception_ce);
    INIT_NS_CLASS_ENTRY(ce, "Couchbase", "DocumentIrretrievableException", nullptr);
    document_irretrievable_exception_ce = zend_register_internal_class_ex(&ce, couchbase_exception_ce);
    INIT_NS_CLASS_ENTRY(ce, "Couchbase", "DocumentLockedException", nullptr);
    document_locked_exception_ce = zend_register_internal_class_ex(&ce, couchbase_exception_ce);
    INIT_NS_CLASS_ENTRY(ce, "Couchbase", "DocumentNotFoundException", nullptr);
    document_not_found_exception_ce = zend_register_internal_class_ex(&ce, couchbase_exception_ce);
    INIT_NS_CLASS_ENTRY(ce, "Couchbase", "DocumentNotJsonException", nullptr);
    document_not_json_exception_ce = zend_register_internal_class_ex(&ce, couchbase_exception_ce);
    INIT_NS_CLASS_ENTRY(ce, "Couchbase", "DurabilityAmbiguousException", nullptr);
    durability_ambiguous_exception_ce = zend_register_internal_class_ex(&ce, couchbase_exception_ce);
    INIT_NS_CLASS_ENTRY(ce, "Couchbase", "DurabilityImpossibleException", nullptr);
    durability_impossible_exception_ce = zend_register_internal_class_ex(&ce, couchbase_exception_ce);
    INIT_NS_CLASS_ENTRY(ce, "Couchbase", "DurabilityLevelNotAvailableException", nullptr);
    durability_level_not_available_exception_ce = zend_register_internal_class_ex(&ce, couchbase_exception_ce);
    INIT_NS_CLASS_ENTRY(ce, "Couchbase", "DurableWriteInProgressException", nullptr);
    durable_write_in_progress_exception_ce = zend_register_internal_class_ex(&ce, couchbase_exception_ce);
    INIT_NS_CLASS_ENTRY(ce, "Couchbase", "DurableWriteReCommitInProgressException", nullptr);
    durable_write_re_commit_in_progress_exception_ce = zend_register_internal_class_ex(&ce, couchbase_exception_ce);
    INIT_NS_CLASS_ENTRY(ce, "Couchbase", "EncodingFailureException", nullptr);
    encoding_failure_exception_ce = zend_register_internal_class_ex(&ce, couchbase_exception_ce);
    INIT_NS_CLASS_ENTRY(ce, "Couchbase", "FeatureNotAvailableException", nullptr);
    feature_not_available_exception_ce = zend_register_internal_class_ex(&ce, couchbase_exception_ce);
    INIT_NS_CLASS_ENTRY(ce, "Couchbase", "GroupNotFoundException", nullptr);
    group_not_found_exception_ce = zend_register_internal_class_ex(&ce, couchbase_exception_ce);
    INIT_NS_CLASS_ENTRY(ce, "Couchbase", "IndexExistsException", nullptr);
    index_exists_exception_ce = zend_register_internal_class_ex(&ce, couchbase_exception_ce);
    INIT_NS_CLASS_ENTRY(ce, "Couchbase", "IndexFailureException", nullptr);
    index_failure_exception_ce = zend_register_internal_class_ex(&ce, couchbase_exception_ce);
    INIT_NS_CLASS_ENTRY(ce, "Couchbase", "IndexNotFoundException", nullptr);
    index_not_found_exception_ce = zend_register_internal_class_ex(&ce, couchbase_exception_ce);
    INIT_NS_CLASS_ENTRY(ce, "Couchbase", "IndexNotReadyException", nullptr);
    index_not_ready_exception_ce = zend_register_internal_class_ex(&ce, couchbase_exception_ce);
    INIT_NS_CLASS_ENTRY(ce, "Couchbase", "InternalServerFailureException", nullptr);
    internal_server_failure_exception_ce = zend_register_internal_class_ex(&ce, couchbase_exception_ce);
    INIT_NS_CLASS_ENTRY(ce, "Couchbase", "InvalidArgumentException", nullptr);
    invalid_argument_exception_ce = zend_register_internal_class_ex(&ce, couchbase_exception_ce);
    INIT_NS_CLASS_ENTRY(ce, "Couchbase", "JobQueueFullException", nullptr);
    job_queue_full_exception_ce = zend_register_internal_class_ex(&ce, couchbase_exception_ce);
    INIT_NS_CLASS_ENTRY(ce, "Couchbase", "LinkExistsException", nullptr);
    link_exists_exception_ce = zend_register_internal_class_ex(&ce, couchbase_exception_ce);
    INIT_NS_CLASS_ENTRY(ce, "Couchbase", "LinkNotFoundException", nullptr);
    link_not_found_exception_ce = zend_register_internal_class_ex(&ce, couchbase_exception_ce);
    INIT_NS_CLASS_ENTRY(ce, "Couchbase", "NumberTooBigException", nullptr);
    number_too_big_exception_ce = zend_register_internal_class_ex(&ce, couchbase_exception_ce);
    INIT_NS_CLASS_ENTRY(ce, "Couchbase", "ParsingFailureException", nullptr);
    parsing_failure_exception_ce = zend_register_internal_class_ex(&ce, couchbase_exception_ce);
    INIT_NS_CLASS_ENTRY(ce, "Couchbase", "PathExistsException", nullptr);
    path_exists_exception_ce = zend_register_internal_class_ex(&ce, couchbase_exception_ce);
    INIT_NS_CLASS_ENTRY(ce, "Couchbase", "PathInvalidException", nullptr);
    path_invalid_exception_ce = zend_register_internal_class_ex(&ce, couchbase_exception_ce);
    INIT_NS_CLASS_ENTRY(ce, "Couchbase", "PathMismatchException", nullptr);
    path_mismatch_exception_ce = zend_register_internal_class_ex(&ce, couchbase_exception_ce);
    INIT_NS_CLASS_ENTRY(ce, "Couchbase", "PathNotFoundException", nullptr);
    path_not_found_exception_ce = zend_register_internal_class_ex(&ce, couchbase_exception_ce);
    INIT_NS_CLASS_ENTRY(ce, "Couchbase", "PathTooBigException", nullptr);
    path_too_big_exception_ce = zend_register_internal_class_ex(&ce, couchbase_exception_ce);
    INIT_NS_CLASS_ENTRY(ce, "Couchbase", "PathTooDeepException", nullptr);
    path_too_deep_exception_ce = zend_register_internal_class_ex(&ce, couchbase_exception_ce);
    INIT_NS_CLASS_ENTRY(ce, "Couchbase", "PlanningFailureException", nullptr);
    planning_failure_exception_ce = zend_register_internal_class_ex(&ce, couchbase_exception_ce);
    INIT_NS_CLASS_ENTRY(ce, "Couchbase", "PreparedStatementFailureException", nullptr);
    prepared_statement_failure_exception_ce = zend_register_internal_class_ex(&ce, couchbase_exception_ce);
    INIT_NS_CLASS_ENTRY(ce, "Couchbase", "RequestCanceledException", nullptr);
    request_canceled_exception_ce = zend_register_internal_class_ex(&ce, couchbase_exception_ce);
    INIT_NS_CLASS_ENTRY(ce, "Couchbase", "ScopeExistsException", nullptr);
    scope_exists_exception_ce = zend_register_internal_class_ex(&ce, couchbase_exception_ce);
    INIT_NS_CLASS_ENTRY(ce, "Couchbase", "ScopeNotFoundException", nullptr);
    scope_not_found_exception_ce = zend_register_internal_class_ex(&ce, couchbase_exception_ce);
    INIT_NS_CLASS_ENTRY(ce, "Couchbase", "ServiceNotAvailableException", nullptr);
    service_not_available_exception_ce = zend_register_internal_class_ex(&ce, couchbase_exception_ce);
    INIT_NS_CLASS_ENTRY(ce, "Couchbase", "TemporaryFailureException", nullptr);
    temporary_failure_exception_ce = zend_register_internal_class_ex(&ce, couchbase_exception_ce);
    INIT_NS_CLASS_ENTRY(ce, "Couchbase", "UnsupportedOperationException", nullptr);
    unsupported_operation_exception_ce = zend_register_internal_class_ex(&ce, couchbase_exception_ce);
    INIT_NS_CLASS_ENTRY(ce, "Couchbase", "UserExistsException", nullptr);
    user_exists_exception_ce = zend_register_internal_class_ex(&ce, couchbase_exception_ce);
    INIT_NS_CLASS_ENTRY(ce, "Couchbase", "UserNotFoundException", nullptr);
    user_not_found_exception_ce = zend_register_internal_class_ex(&ce, couchbase_exception_ce);
    INIT_NS_CLASS_ENTRY(ce, "Couchbase", "ValueInvalidException", nullptr);
    value_invalid_exception_ce = zend_register_internal_class_ex(&ce, couchbase_exception_ce);
    INIT_NS_CLASS_ENTRY(ce, "Couchbase", "ValueTooDeepException", nullptr);
    value_too_deep_exception_ce = zend_register_internal_class_ex(&ce, couchbase_exception_ce);
    INIT_NS_CLASS_ENTRY(ce, "Couchbase", "ValueTooLargeException", nullptr);
    value_too_large_exception_ce = zend_register_internal_class_ex(&ce, couchbase_exception_ce);
    INIT_NS_CLASS_ENTRY(ce, "Couchbase", "ViewNotFoundException", nullptr);
    view_not_found_exception_ce = zend_register_internal_class_ex(&ce, couchbase_exception_ce);
    INIT_NS_CLASS_ENTRY(ce, "Couchbase", "XattrCannotModifyVirtualAttributeException", nullptr);
    xattr_cannot_modify_virtual_attribute_exception_ce = zend_register_internal_class_ex(&ce, couchbase_exception_ce);
    INIT_NS_CLASS_ENTRY(ce, "Couchbase", "XattrInvalidKeyComboException", nullptr);
    xattr_invalid_key_combo_exception_ce = zend_register_internal_class_ex(&ce, couchbase_exception_ce);
    INIT_NS_CLASS_ENTRY(ce, "Couchbase", "XattrUnknownMacroException", nullptr);
    xattr_unknown_macro_exception_ce = zend_register_internal_class_ex(&ce, couchbase_exception_ce);
    INIT_NS_CLASS_ENTRY(ce, "Couchbase", "XattrUnknownVirtualAttributeException", nullptr);
    xattr_unknown_virtual_attribute_exception_ce = zend_register_internal_class_ex(&ce, couchbase_exception_ce);

    return SUCCESS;
}

static void
couchbase_throw_exception(const couchbase::php::core_error_info& error_info)
{
    if (!error_info.ec) {
        return; // success
    }

    zval ex;
    zend_class_entry* ex_ce = couchbase::php::map_error_to_exception(error_info);

    object_init_ex(&ex, ex_ce);
    std::stringstream message;
    message << error_info.ec.message() << " (" << error_info.ec.value() << ")";
    if (!error_info.message.empty()) {
        message << ": \"" << error_info.message << "\"";
    }
    if (!error_info.location.function_name.empty()) {
        message << " in '" << error_info.location.function_name << "'";
    }
    zend_update_property_string(ex_ce, Z_OBJ(ex), ZEND_STRL("message"), message.str().c_str());
    zend_update_property_string(ex_ce, Z_OBJ(ex), ZEND_STRL("file"), error_info.location.file_name.c_str());
    zend_update_property_long(ex_ce, Z_OBJ(ex), ZEND_STRL("line"), error_info.location.line);
    zend_update_property_long(ex_ce, Z_OBJ(ex), ZEND_STRL("code"), error_info.ec.value());
    zend_throw_exception_object(&ex);
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

PHP_FUNCTION(createConnection)
{
    zend_string* connection_hash = nullptr;
    zend_string* connection_string = nullptr;
    zval* options = nullptr;

    ZEND_PARSE_PARAMETERS_START(2, 3)
    Z_PARAM_STR(connection_hash)
    Z_PARAM_STR(connection_string)
    Z_PARAM_OPTIONAL
    Z_PARAM_ARRAY(options)
    ZEND_PARSE_PARAMETERS_END();

    auto [handle, e] = couchbase::php::create_persistent_connection(connection_hash, connection_string, options);
    if (e.ec) {
        couchbase_throw_exception(e);
        RETURN_THROWS();
    }

    RETURN_RES(handle->resource_id());
}

static inline couchbase::php::connection_handle*
fetch_couchbase_connection_from_resource(zval* resource)
{
    return static_cast<couchbase::php::connection_handle*>(
      zend_fetch_resource(Z_RES_P(resource), "couchbase_persistent_connection", couchbase::php::persistent_connection_destructor_id));
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

    auto* handle = fetch_couchbase_connection_from_resource(connection);
    if (handle == nullptr) {
        RETURN_THROWS();
    }

    if (auto e = handle->document_upsert(return_value, bucket, scope, collection, id, value, flags, options); e.ec) {
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
    Z_PARAM_ARRAY(options)
    ZEND_PARSE_PARAMETERS_END();

    auto* handle = fetch_couchbase_connection_from_resource(connection);
    if (handle == nullptr) {
        RETURN_THROWS();
    }
    auto [res, e] = handle->query(statement, options);
    if (e.ec) {
        couchbase_throw_exception(e);
        RETURN_THROWS();
    }

    RETURN_ZVAL(res, 1, 0);
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
    Z_PARAM_ARRAY(options)
    ZEND_PARSE_PARAMETERS_END();

    auto* handle = fetch_couchbase_connection_from_resource(connection);
    if (handle == nullptr) {
        RETURN_THROWS();
    }
    auto [res, e] = handle->analytics_query(statement, options);
    if (e.ec) {
        couchbase_throw_exception(e);
        RETURN_THROWS();
    }

    RETURN_ZVAL(res, 1, 0);
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

ZEND_BEGIN_ARG_INFO_EX(ai_CouchbaseExtension_createConnection, 0, 0, 2)
ZEND_ARG_TYPE_INFO(0, connectionHash, IS_STRING, 0)
ZEND_ARG_TYPE_INFO(0, connectionString, IS_STRING, 0)
ZEND_ARG_TYPE_INFO(0, options, IS_ARRAY, 0)
ZEND_END_ARG_INFO()

ZEND_BEGIN_ARG_INFO_EX(ai_CouchbaseExtension_openBucket, 0, 0, 2)
ZEND_ARG_TYPE_INFO(0, connection, IS_RESOURCE, 0)
ZEND_ARG_TYPE_INFO(0, bucketName, IS_STRING, 0)
ZEND_END_ARG_INFO()

ZEND_BEGIN_ARG_INFO_EX(ai_CouchbaseExtension_closeBucket, 0, 0, 2)
ZEND_ARG_TYPE_INFO(0, connection, IS_RESOURCE, 0)
ZEND_ARG_TYPE_INFO(0, bucketName, IS_STRING, 0)
ZEND_END_ARG_INFO()

ZEND_BEGIN_ARG_INFO_EX(ai_CouchbaseExtension_documentUpsert, 0, 0, 7)
ZEND_ARG_TYPE_INFO(0, connection, IS_RESOURCE, 0)
ZEND_ARG_TYPE_INFO(0, bucket, IS_STRING, 0)
ZEND_ARG_TYPE_INFO(0, scope, IS_STRING, 0)
ZEND_ARG_TYPE_INFO(0, collection, IS_STRING, 0)
ZEND_ARG_TYPE_INFO(0, id, IS_STRING, 0)
ZEND_ARG_TYPE_INFO(0, value, IS_STRING, 0)
ZEND_ARG_TYPE_INFO(0, flags, IS_LONG, 0)
ZEND_ARG_TYPE_INFO(0, options, IS_ARRAY, 1)
ZEND_END_ARG_INFO()

ZEND_BEGIN_ARG_INFO_EX(ai_CouchbaseExtension_query, 0, 0, 2)
ZEND_ARG_TYPE_INFO(0, connection, IS_RESOURCE, 0)
ZEND_ARG_TYPE_INFO(0, statement, IS_STRING, 0)
ZEND_ARG_TYPE_INFO(0, options, IS_ARRAY, 0)
ZEND_END_ARG_INFO()

ZEND_BEGIN_ARG_INFO_EX(ai_CouchbaseExtension_analyticsQuery, 0, 0, 2)
ZEND_ARG_TYPE_INFO(0, connection, IS_RESOURCE, 0)
ZEND_ARG_TYPE_INFO(0, statement, IS_STRING, 0)
ZEND_ARG_TYPE_INFO(0, options, IS_ARRAY, 0)
ZEND_END_ARG_INFO()

// clang-format off
static zend_function_entry couchbase_functions[] = {
    ZEND_NS_FE("Couchbase\\Extension", version, ai_CouchbaseExtension_version)
    ZEND_NS_FE("Couchbase\\Extension", createConnection, ai_CouchbaseExtension_createConnection)
    ZEND_NS_FE("Couchbase\\Extension", openBucket, ai_CouchbaseExtension_openBucket)
    ZEND_NS_FE("Couchbase\\Extension", closeBucket, ai_CouchbaseExtension_closeBucket)
    ZEND_NS_FE("Couchbase\\Extension", documentUpsert, ai_CouchbaseExtension_documentUpsert)
    ZEND_NS_FE("Couchbase\\Extension", query, ai_CouchbaseExtension_query)
    ZEND_NS_FE("Couchbase\\Extension", analyticsQuery, ai_CouchbaseExtension_analyticsQuery)
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
    nullptr,
    php_couchbase_deps,
    PHP_COUCHBASE_EXTENSION_NAME,
    couchbase_functions,      /* extension function list */
    PHP_MINIT(couchbase),     /* extension-wide startup function */
    PHP_MSHUTDOWN(couchbase), /* extension-wide shutdown function */
    nullptr,                  /* per-request startup function */
    PHP_RSHUTDOWN(couchbase), /* per-request shutdown function */
    PHP_MINFO(couchbase),     /* information function */
    PHP_COUCHBASE_VERSION,
    PHP_MODULE_GLOBALS(couchbase), /* globals descriptor */
    nullptr,                       /* globals ctor */
    nullptr,                       /* globals dtor */
    nullptr,                       /* post deactivate */
    STANDARD_MODULE_PROPERTIES_EX,
};

#ifdef COMPILE_DL_COUCHBASE
#ifdef ZTS
ZEND_TSRMLS_CACHE_DEFINE();
#endif
ZEND_GET_MODULE(couchbase)
#endif
