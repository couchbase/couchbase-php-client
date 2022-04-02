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

ZEND_BEGIN_ARG_WITH_RETURN_TYPE_INFO(ai_Exception_getContext, IS_ARRAY, 0)
ZEND_END_ARG_INFO()

PHP_METHOD(Exception, getContext)
{
    if (zend_parse_parameters_none_throw() == FAILURE) {
        return;
    }

    zval *prop, rv;
    prop = zend_read_property(couchbase_exception_ce, Z_OBJ_P(getThis()), ZEND_STRL("context"), 0, &rv);
    ZVAL_COPY_DEREF(return_value, prop);
}

// clang-format off
static const zend_function_entry exception_functions[] = {
  PHP_ME(Exception, getContext, ai_Exception_getContext, ZEND_ACC_PUBLIC)
  PHP_FE_END
};
// clang-format on

PHP_MINIT_FUNCTION(couchbase)
{
    (void)type;

    couchbase::php::persistent_connection_destructor_id =
      zend_register_list_destructors_ex(nullptr, couchbase_destroy_persistent_connection, "couchbase_persistent_connection", module_number);

    zend_class_entry ce;
    INIT_NS_CLASS_ENTRY(ce, "Couchbase\\Exception", "Exception", exception_functions);
    couchbase_exception_ce = zend_register_internal_class_ex(&ce, zend_ce_exception);
    zend_declare_property_null(couchbase_exception_ce, ZEND_STRL("context"), ZEND_ACC_PRIVATE);

    INIT_NS_CLASS_ENTRY(ce, "Couchbase\\Exception", "TimeoutException", nullptr);
    timeout_exception_ce = zend_register_internal_class_ex(&ce, couchbase_exception_ce);
    INIT_NS_CLASS_ENTRY(ce, "Couchbase\\Exception", "UnambiguousTimeoutException", nullptr);
    unambiguous_timeout_exception_ce = zend_register_internal_class_ex(&ce, timeout_exception_ce);
    INIT_NS_CLASS_ENTRY(ce, "Couchbase\\Exception", "AmbiguousTimeoutException", nullptr);
    ambiguous_timeout_exception_ce = zend_register_internal_class_ex(&ce, timeout_exception_ce);
    INIT_NS_CLASS_ENTRY(ce, "Couchbase\\Exception", "AuthenticationFailureException", nullptr);
    authentication_failure_exception_ce = zend_register_internal_class_ex(&ce, couchbase_exception_ce);
    INIT_NS_CLASS_ENTRY(ce, "Couchbase\\Exception", "BucketExistsException", nullptr);
    bucket_exists_exception_ce = zend_register_internal_class_ex(&ce, couchbase_exception_ce);
    INIT_NS_CLASS_ENTRY(ce, "Couchbase\\Exception", "BucketNotFlushableException", nullptr);
    bucket_not_flushable_exception_ce = zend_register_internal_class_ex(&ce, couchbase_exception_ce);
    INIT_NS_CLASS_ENTRY(ce, "Couchbase\\Exception", "BucketNotFoundException", nullptr);
    bucket_not_found_exception_ce = zend_register_internal_class_ex(&ce, couchbase_exception_ce);
    INIT_NS_CLASS_ENTRY(ce, "Couchbase\\Exception", "CasMismatchException", nullptr);
    cas_mismatch_exception_ce = zend_register_internal_class_ex(&ce, couchbase_exception_ce);
    INIT_NS_CLASS_ENTRY(ce, "Couchbase\\Exception", "CollectionExistsException", nullptr);
    collection_exists_exception_ce = zend_register_internal_class_ex(&ce, couchbase_exception_ce);
    INIT_NS_CLASS_ENTRY(ce, "Couchbase\\Exception", "CollectionNotFoundException", nullptr);
    collection_not_found_exception_ce = zend_register_internal_class_ex(&ce, couchbase_exception_ce);
    INIT_NS_CLASS_ENTRY(ce, "Couchbase\\Exception", "CompilationFailureException", nullptr);
    compilation_failure_exception_ce = zend_register_internal_class_ex(&ce, couchbase_exception_ce);
    INIT_NS_CLASS_ENTRY(ce, "Couchbase\\Exception", "ConsistencyMismatchException", nullptr);
    consistency_mismatch_exception_ce = zend_register_internal_class_ex(&ce, couchbase_exception_ce);
    INIT_NS_CLASS_ENTRY(ce, "Couchbase\\Exception", "DatasetExistsException", nullptr);
    dataset_exists_exception_ce = zend_register_internal_class_ex(&ce, couchbase_exception_ce);
    INIT_NS_CLASS_ENTRY(ce, "Couchbase\\Exception", "DatasetNotFoundException", nullptr);
    dataset_not_found_exception_ce = zend_register_internal_class_ex(&ce, couchbase_exception_ce);
    INIT_NS_CLASS_ENTRY(ce, "Couchbase\\Exception", "DataverseExistsException", nullptr);
    dataverse_exists_exception_ce = zend_register_internal_class_ex(&ce, couchbase_exception_ce);
    INIT_NS_CLASS_ENTRY(ce, "Couchbase\\Exception", "DataverseNotFoundException", nullptr);
    dataverse_not_found_exception_ce = zend_register_internal_class_ex(&ce, couchbase_exception_ce);
    INIT_NS_CLASS_ENTRY(ce, "Couchbase\\Exception", "DecodingFailureException", nullptr);
    decoding_failure_exception_ce = zend_register_internal_class_ex(&ce, couchbase_exception_ce);
    INIT_NS_CLASS_ENTRY(ce, "Couchbase\\Exception", "DeltaInvalidException", nullptr);
    delta_invalid_exception_ce = zend_register_internal_class_ex(&ce, couchbase_exception_ce);
    INIT_NS_CLASS_ENTRY(ce, "Couchbase\\Exception", "DesignDocumentNotFoundException", nullptr);
    design_document_not_found_exception_ce = zend_register_internal_class_ex(&ce, couchbase_exception_ce);
    INIT_NS_CLASS_ENTRY(ce, "Couchbase\\Exception", "DocumentExistsException", nullptr);
    document_exists_exception_ce = zend_register_internal_class_ex(&ce, couchbase_exception_ce);
    INIT_NS_CLASS_ENTRY(ce, "Couchbase\\Exception", "DocumentIrretrievableException", nullptr);
    document_irretrievable_exception_ce = zend_register_internal_class_ex(&ce, couchbase_exception_ce);
    INIT_NS_CLASS_ENTRY(ce, "Couchbase\\Exception", "DocumentLockedException", nullptr);
    document_locked_exception_ce = zend_register_internal_class_ex(&ce, couchbase_exception_ce);
    INIT_NS_CLASS_ENTRY(ce, "Couchbase\\Exception", "DocumentNotFoundException", nullptr);
    document_not_found_exception_ce = zend_register_internal_class_ex(&ce, couchbase_exception_ce);
    INIT_NS_CLASS_ENTRY(ce, "Couchbase\\Exception", "DocumentNotJsonException", nullptr);
    document_not_json_exception_ce = zend_register_internal_class_ex(&ce, couchbase_exception_ce);
    INIT_NS_CLASS_ENTRY(ce, "Couchbase\\Exception", "DurabilityAmbiguousException", nullptr);
    durability_ambiguous_exception_ce = zend_register_internal_class_ex(&ce, couchbase_exception_ce);
    INIT_NS_CLASS_ENTRY(ce, "Couchbase\\Exception", "DurabilityImpossibleException", nullptr);
    durability_impossible_exception_ce = zend_register_internal_class_ex(&ce, couchbase_exception_ce);
    INIT_NS_CLASS_ENTRY(ce, "Couchbase\\Exception", "DurabilityLevelNotAvailableException", nullptr);
    durability_level_not_available_exception_ce = zend_register_internal_class_ex(&ce, couchbase_exception_ce);
    INIT_NS_CLASS_ENTRY(ce, "Couchbase\\Exception", "DurableWriteInProgressException", nullptr);
    durable_write_in_progress_exception_ce = zend_register_internal_class_ex(&ce, couchbase_exception_ce);
    INIT_NS_CLASS_ENTRY(ce, "Couchbase\\Exception", "DurableWriteReCommitInProgressException", nullptr);
    durable_write_re_commit_in_progress_exception_ce = zend_register_internal_class_ex(&ce, couchbase_exception_ce);
    INIT_NS_CLASS_ENTRY(ce, "Couchbase\\Exception", "EncodingFailureException", nullptr);
    encoding_failure_exception_ce = zend_register_internal_class_ex(&ce, couchbase_exception_ce);
    INIT_NS_CLASS_ENTRY(ce, "Couchbase\\Exception", "FeatureNotAvailableException", nullptr);
    feature_not_available_exception_ce = zend_register_internal_class_ex(&ce, couchbase_exception_ce);
    INIT_NS_CLASS_ENTRY(ce, "Couchbase\\Exception", "GroupNotFoundException", nullptr);
    group_not_found_exception_ce = zend_register_internal_class_ex(&ce, couchbase_exception_ce);
    INIT_NS_CLASS_ENTRY(ce, "Couchbase\\Exception", "IndexExistsException", nullptr);
    index_exists_exception_ce = zend_register_internal_class_ex(&ce, couchbase_exception_ce);
    INIT_NS_CLASS_ENTRY(ce, "Couchbase\\Exception", "IndexFailureException", nullptr);
    index_failure_exception_ce = zend_register_internal_class_ex(&ce, couchbase_exception_ce);
    INIT_NS_CLASS_ENTRY(ce, "Couchbase\\Exception", "IndexNotFoundException", nullptr);
    index_not_found_exception_ce = zend_register_internal_class_ex(&ce, couchbase_exception_ce);
    INIT_NS_CLASS_ENTRY(ce, "Couchbase\\Exception", "IndexNotReadyException", nullptr);
    index_not_ready_exception_ce = zend_register_internal_class_ex(&ce, couchbase_exception_ce);
    INIT_NS_CLASS_ENTRY(ce, "Couchbase\\Exception", "InternalServerFailureException", nullptr);
    internal_server_failure_exception_ce = zend_register_internal_class_ex(&ce, couchbase_exception_ce);
    INIT_NS_CLASS_ENTRY(ce, "Couchbase\\Exception", "InvalidArgumentException", nullptr);
    invalid_argument_exception_ce = zend_register_internal_class_ex(&ce, couchbase_exception_ce);
    INIT_NS_CLASS_ENTRY(ce, "Couchbase\\Exception", "JobQueueFullException", nullptr);
    job_queue_full_exception_ce = zend_register_internal_class_ex(&ce, couchbase_exception_ce);
    INIT_NS_CLASS_ENTRY(ce, "Couchbase\\Exception", "LinkExistsException", nullptr);
    link_exists_exception_ce = zend_register_internal_class_ex(&ce, couchbase_exception_ce);
    INIT_NS_CLASS_ENTRY(ce, "Couchbase\\Exception", "LinkNotFoundException", nullptr);
    link_not_found_exception_ce = zend_register_internal_class_ex(&ce, couchbase_exception_ce);
    INIT_NS_CLASS_ENTRY(ce, "Couchbase\\Exception", "NumberTooBigException", nullptr);
    number_too_big_exception_ce = zend_register_internal_class_ex(&ce, couchbase_exception_ce);
    INIT_NS_CLASS_ENTRY(ce, "Couchbase\\Exception", "ParsingFailureException", nullptr);
    parsing_failure_exception_ce = zend_register_internal_class_ex(&ce, couchbase_exception_ce);
    INIT_NS_CLASS_ENTRY(ce, "Couchbase\\Exception", "PathExistsException", nullptr);
    path_exists_exception_ce = zend_register_internal_class_ex(&ce, couchbase_exception_ce);
    INIT_NS_CLASS_ENTRY(ce, "Couchbase\\Exception", "PathInvalidException", nullptr);
    path_invalid_exception_ce = zend_register_internal_class_ex(&ce, couchbase_exception_ce);
    INIT_NS_CLASS_ENTRY(ce, "Couchbase\\Exception", "PathMismatchException", nullptr);
    path_mismatch_exception_ce = zend_register_internal_class_ex(&ce, couchbase_exception_ce);
    INIT_NS_CLASS_ENTRY(ce, "Couchbase\\Exception", "PathNotFoundException", nullptr);
    path_not_found_exception_ce = zend_register_internal_class_ex(&ce, couchbase_exception_ce);
    INIT_NS_CLASS_ENTRY(ce, "Couchbase\\Exception", "PathTooBigException", nullptr);
    path_too_big_exception_ce = zend_register_internal_class_ex(&ce, couchbase_exception_ce);
    INIT_NS_CLASS_ENTRY(ce, "Couchbase\\Exception", "PathTooDeepException", nullptr);
    path_too_deep_exception_ce = zend_register_internal_class_ex(&ce, couchbase_exception_ce);
    INIT_NS_CLASS_ENTRY(ce, "Couchbase\\Exception", "PlanningFailureException", nullptr);
    planning_failure_exception_ce = zend_register_internal_class_ex(&ce, couchbase_exception_ce);
    INIT_NS_CLASS_ENTRY(ce, "Couchbase\\Exception", "PreparedStatementFailureException", nullptr);
    prepared_statement_failure_exception_ce = zend_register_internal_class_ex(&ce, couchbase_exception_ce);
    INIT_NS_CLASS_ENTRY(ce, "Couchbase\\Exception", "RequestCanceledException", nullptr);
    request_canceled_exception_ce = zend_register_internal_class_ex(&ce, couchbase_exception_ce);
    INIT_NS_CLASS_ENTRY(ce, "Couchbase\\Exception", "ScopeExistsException", nullptr);
    scope_exists_exception_ce = zend_register_internal_class_ex(&ce, couchbase_exception_ce);
    INIT_NS_CLASS_ENTRY(ce, "Couchbase\\Exception", "ScopeNotFoundException", nullptr);
    scope_not_found_exception_ce = zend_register_internal_class_ex(&ce, couchbase_exception_ce);
    INIT_NS_CLASS_ENTRY(ce, "Couchbase\\Exception", "ServiceNotAvailableException", nullptr);
    service_not_available_exception_ce = zend_register_internal_class_ex(&ce, couchbase_exception_ce);
    INIT_NS_CLASS_ENTRY(ce, "Couchbase\\Exception", "TemporaryFailureException", nullptr);
    temporary_failure_exception_ce = zend_register_internal_class_ex(&ce, couchbase_exception_ce);
    INIT_NS_CLASS_ENTRY(ce, "Couchbase\\Exception", "UnsupportedOperationException", nullptr);
    unsupported_operation_exception_ce = zend_register_internal_class_ex(&ce, couchbase_exception_ce);
    INIT_NS_CLASS_ENTRY(ce, "Couchbase\\Exception", "UserExistsException", nullptr);
    user_exists_exception_ce = zend_register_internal_class_ex(&ce, couchbase_exception_ce);
    INIT_NS_CLASS_ENTRY(ce, "Couchbase\\Exception", "UserNotFoundException", nullptr);
    user_not_found_exception_ce = zend_register_internal_class_ex(&ce, couchbase_exception_ce);
    INIT_NS_CLASS_ENTRY(ce, "Couchbase\\Exception", "ValueInvalidException", nullptr);
    value_invalid_exception_ce = zend_register_internal_class_ex(&ce, couchbase_exception_ce);
    INIT_NS_CLASS_ENTRY(ce, "Couchbase\\Exception", "ValueTooDeepException", nullptr);
    value_too_deep_exception_ce = zend_register_internal_class_ex(&ce, couchbase_exception_ce);
    INIT_NS_CLASS_ENTRY(ce, "Couchbase\\Exception", "ValueTooLargeException", nullptr);
    value_too_large_exception_ce = zend_register_internal_class_ex(&ce, couchbase_exception_ce);
    INIT_NS_CLASS_ENTRY(ce, "Couchbase\\Exception", "ViewNotFoundException", nullptr);
    view_not_found_exception_ce = zend_register_internal_class_ex(&ce, couchbase_exception_ce);
    INIT_NS_CLASS_ENTRY(ce, "Couchbase\\Exception", "XattrCannotModifyVirtualAttributeException", nullptr);
    xattr_cannot_modify_virtual_attribute_exception_ce = zend_register_internal_class_ex(&ce, couchbase_exception_ce);
    INIT_NS_CLASS_ENTRY(ce, "Couchbase\\Exception", "XattrInvalidKeyComboException", nullptr);
    xattr_invalid_key_combo_exception_ce = zend_register_internal_class_ex(&ce, couchbase_exception_ce);
    INIT_NS_CLASS_ENTRY(ce, "Couchbase\\Exception", "XattrUnknownMacroException", nullptr);
    xattr_unknown_macro_exception_ce = zend_register_internal_class_ex(&ce, couchbase_exception_ce);
    INIT_NS_CLASS_ENTRY(ce, "Couchbase\\Exception", "XattrUnknownVirtualAttributeException", nullptr);
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
    zval context;
    couchbase::php::error_context_to_zval(error_info, &context);
    zend_update_property(couchbase_exception_ce, Z_OBJ(ex), ZEND_STRL("context"), &context);
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
PHP_FUNCTION(clusterVersion)
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

    auto version = handle->cluster_version(name);
    if (version.empty()) {
        RETURN_NULL();
    }
    RETURN_STRINGL(version.data(), version.size());
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

    auto* handle = fetch_couchbase_connection_from_resource(connection);
    if (handle == nullptr) {
        RETURN_THROWS();
    }

    if (auto e = handle->document_get(return_value, bucket, scope, collection, id, options); e.ec) {
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

    auto* handle = fetch_couchbase_connection_from_resource(connection);
    if (handle == nullptr) {
        RETURN_THROWS();
    }

    if (auto e = handle->document_unlock(return_value, bucket, scope, collection, id, cas, options); e.ec) {
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

    auto* handle = fetch_couchbase_connection_from_resource(connection);
    if (handle == nullptr) {
        RETURN_THROWS();
    }

    if (auto e = handle->document_lookup_in(return_value, bucket, scope, collection, id, specs, options); e.ec) {
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

PHP_FUNCTION(viewQuery)
{
    zval* connection = nullptr;
    zend_string* bucketName = nullptr;
    zend_string* designDocumentName = nullptr;
    zend_string* viewName = nullptr;
    zend_long nameSpace = 0;
    zval* options = nullptr;

    ZEND_PARSE_PARAMETERS_START(5, 6)
    Z_PARAM_RESOURCE(connection)
    Z_PARAM_STR(bucketName)
    Z_PARAM_STR(designDocumentName)
    Z_PARAM_STR(viewName)
    Z_PARAM_LONG(nameSpace)
    Z_PARAM_OPTIONAL
    Z_PARAM_ARRAY(options)
    ZEND_PARSE_PARAMETERS_END();

    auto* handle = static_cast<couchbase::php::connection_handle*>(
      zend_fetch_resource(Z_RES_P(connection), "couchbase_persistent_connection", couchbase::php::persistent_connection_destructor_id));
    if (handle == nullptr) {
        RETURN_THROWS();
    }
    auto [res, e] = handle->view_query(bucketName, designDocumentName, viewName, nameSpace, options);
    if (e.ec) {
        couchbase_throw_exception(e);
        RETURN_THROWS();
    }

    RETURN_ZVAL(res, 1, 0);
}

PHP_FUNCTION(searchQuery)
{
    zval* connection = nullptr;
    zend_string* indexName = nullptr;
    zend_string* query = nullptr;
    zval* options = nullptr;

    ZEND_PARSE_PARAMETERS_START(3, 4)
    Z_PARAM_RESOURCE(connection)
    Z_PARAM_STR(indexName)
    Z_PARAM_STR(query)
    Z_PARAM_OPTIONAL
    Z_PARAM_ARRAY(options)
    ZEND_PARSE_PARAMETERS_END();

    auto* handle = static_cast<couchbase::php::connection_handle*>(
      zend_fetch_resource(Z_RES_P(connection), "couchbase_persistent_connection", couchbase::php::persistent_connection_destructor_id));
    if (handle == nullptr) {
        RETURN_THROWS();
    }
    auto [res, e] = handle->search_query(indexName, query, options);
    if (e.ec) {
        couchbase_throw_exception(e);
        RETURN_THROWS();
    }

    RETURN_ZVAL(res, 1, 0);
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
    Z_PARAM_ARRAY(options)
    ZEND_PARSE_PARAMETERS_END();

    auto* handle = static_cast<couchbase::php::connection_handle*>(
      zend_fetch_resource(Z_RES_P(connection), "couchbase_persistent_connection", couchbase::php::persistent_connection_destructor_id));
    if (handle == nullptr) {
        RETURN_THROWS();
    }
    auto [res, e] = handle->search_index_upsert(index, options);
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

ZEND_BEGIN_ARG_INFO_EX(ai_CouchbaseExtension_clusterVersion, 0, 0, 2)
ZEND_ARG_TYPE_INFO(0, connection, IS_RESOURCE, 0)
ZEND_ARG_TYPE_INFO(0, bucketName, IS_STRING, 0)
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

ZEND_BEGIN_ARG_INFO_EX(ai_CouchbaseExtension_documentGet, 0, 0, 5)
ZEND_ARG_TYPE_INFO(0, connection, IS_RESOURCE, 0)
ZEND_ARG_TYPE_INFO(0, bucket, IS_STRING, 0)
ZEND_ARG_TYPE_INFO(0, scope, IS_STRING, 0)
ZEND_ARG_TYPE_INFO(0, collection, IS_STRING, 0)
ZEND_ARG_TYPE_INFO(0, id, IS_STRING, 0)
ZEND_ARG_TYPE_INFO(0, options, IS_ARRAY, 1)
ZEND_END_ARG_INFO()

ZEND_BEGIN_ARG_INFO_EX(ai_CouchbaseExtension_documentGetAndLock, 0, 0, 6)
ZEND_ARG_TYPE_INFO(0, connection, IS_RESOURCE, 0)
ZEND_ARG_TYPE_INFO(0, bucket, IS_STRING, 0)
ZEND_ARG_TYPE_INFO(0, scope, IS_STRING, 0)
ZEND_ARG_TYPE_INFO(0, collection, IS_STRING, 0)
ZEND_ARG_TYPE_INFO(0, id, IS_STRING, 0)
ZEND_ARG_TYPE_INFO(0, lockTimeSeconds, IS_LONG, 0)
ZEND_ARG_TYPE_INFO(0, options, IS_ARRAY, 1)
ZEND_END_ARG_INFO()

ZEND_BEGIN_ARG_INFO_EX(ai_CouchbaseExtension_documentGetAndTouch, 0, 0, 6)
ZEND_ARG_TYPE_INFO(0, connection, IS_RESOURCE, 0)
ZEND_ARG_TYPE_INFO(0, bucket, IS_STRING, 0)
ZEND_ARG_TYPE_INFO(0, scope, IS_STRING, 0)
ZEND_ARG_TYPE_INFO(0, collection, IS_STRING, 0)
ZEND_ARG_TYPE_INFO(0, id, IS_STRING, 0)
ZEND_ARG_TYPE_INFO(0, expirySeconds, IS_LONG, 0)
ZEND_ARG_TYPE_INFO(0, options, IS_ARRAY, 1)
ZEND_END_ARG_INFO()

ZEND_BEGIN_ARG_INFO_EX(ai_CouchbaseExtension_documentUnlock, 0, 0, 6)
ZEND_ARG_TYPE_INFO(0, connection, IS_RESOURCE, 0)
ZEND_ARG_TYPE_INFO(0, bucket, IS_STRING, 0)
ZEND_ARG_TYPE_INFO(0, scope, IS_STRING, 0)
ZEND_ARG_TYPE_INFO(0, collection, IS_STRING, 0)
ZEND_ARG_TYPE_INFO(0, id, IS_STRING, 0)
ZEND_ARG_TYPE_INFO(0, cas, IS_STRING, 0)
ZEND_ARG_TYPE_INFO(0, options, IS_ARRAY, 1)
ZEND_END_ARG_INFO()

ZEND_BEGIN_ARG_INFO_EX(ai_CouchbaseExtension_documentTouch, 0, 0, 6)
ZEND_ARG_TYPE_INFO(0, connection, IS_RESOURCE, 0)
ZEND_ARG_TYPE_INFO(0, bucket, IS_STRING, 0)
ZEND_ARG_TYPE_INFO(0, scope, IS_STRING, 0)
ZEND_ARG_TYPE_INFO(0, collection, IS_STRING, 0)
ZEND_ARG_TYPE_INFO(0, id, IS_STRING, 0)
ZEND_ARG_TYPE_INFO(0, expirySeconds, IS_LONG, 0)
ZEND_ARG_TYPE_INFO(0, options, IS_ARRAY, 1)
ZEND_END_ARG_INFO()

ZEND_BEGIN_ARG_INFO_EX(ai_CouchbaseExtension_documentExists, 0, 0, 5)
ZEND_ARG_TYPE_INFO(0, connection, IS_RESOURCE, 0)
ZEND_ARG_TYPE_INFO(0, bucket, IS_STRING, 0)
ZEND_ARG_TYPE_INFO(0, scope, IS_STRING, 0)
ZEND_ARG_TYPE_INFO(0, collection, IS_STRING, 0)
ZEND_ARG_TYPE_INFO(0, id, IS_STRING, 0)
ZEND_ARG_TYPE_INFO(0, options, IS_ARRAY, 1)
ZEND_END_ARG_INFO()

ZEND_BEGIN_ARG_INFO_EX(ai_CouchbaseExtension_documentMutateIn, 0, 0, 6)
ZEND_ARG_TYPE_INFO(0, connection, IS_RESOURCE, 0)
ZEND_ARG_TYPE_INFO(0, bucket, IS_STRING, 0)
ZEND_ARG_TYPE_INFO(0, scope, IS_STRING, 0)
ZEND_ARG_TYPE_INFO(0, collection, IS_STRING, 0)
ZEND_ARG_TYPE_INFO(0, id, IS_STRING, 0)
ZEND_ARG_TYPE_INFO(0, specs, IS_STRING, 0)
ZEND_ARG_TYPE_INFO(0, options, IS_ARRAY, 1)
ZEND_END_ARG_INFO()

ZEND_BEGIN_ARG_INFO_EX(ai_CouchbaseExtension_documentLookupIn, 0, 0, 6)
ZEND_ARG_TYPE_INFO(0, connection, IS_RESOURCE, 0)
ZEND_ARG_TYPE_INFO(0, bucket, IS_STRING, 0)
ZEND_ARG_TYPE_INFO(0, scope, IS_STRING, 0)
ZEND_ARG_TYPE_INFO(0, collection, IS_STRING, 0)
ZEND_ARG_TYPE_INFO(0, id, IS_STRING, 0)
ZEND_ARG_TYPE_INFO(0, specs, IS_STRING, 0)
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

ZEND_BEGIN_ARG_INFO_EX(ai_CouchbaseExtension_viewQuery, 0, 0, 5)
ZEND_ARG_TYPE_INFO(0, connection, IS_RESOURCE, 0)
ZEND_ARG_TYPE_INFO(0, bucketName, IS_STRING, 0)
ZEND_ARG_TYPE_INFO(0, designDocumentName, IS_STRING, 0)
ZEND_ARG_TYPE_INFO(0, viewName, IS_STRING, 0)
ZEND_ARG_TYPE_INFO(0, nameSpace, IS_LONG, 0)
ZEND_ARG_TYPE_INFO(0, options, IS_ARRAY, 0)
ZEND_END_ARG_INFO()

ZEND_BEGIN_ARG_INFO_EX(ai_CouchbaseExtension_searchQuery, 0, 0, 3)
ZEND_ARG_TYPE_INFO(0, connection, IS_RESOURCE, 0)
ZEND_ARG_TYPE_INFO(0, indexName, IS_STRING, 0)
ZEND_ARG_TYPE_INFO(0, query, IS_STRING, 0)
ZEND_ARG_TYPE_INFO(0, options, IS_ARRAY, 0)
ZEND_END_ARG_INFO()

ZEND_BEGIN_ARG_INFO_EX(ai_CouchbaseExtension_searchIndexUpsert, 0, 0, 2)
ZEND_ARG_TYPE_INFO(0, connection, IS_RESOURCE, 0)
ZEND_ARG_TYPE_INFO(0, index, IS_ARRAY, 0)
ZEND_ARG_TYPE_INFO(0, options, IS_ARRAY, 0)
ZEND_END_ARG_INFO()

// clang-format off
static zend_function_entry couchbase_functions[] = {
    ZEND_NS_FE("Couchbase\\Extension", version, ai_CouchbaseExtension_version)
    ZEND_NS_FE("Couchbase\\Extension", clusterVersion, ai_CouchbaseExtension_clusterVersion)
    ZEND_NS_FE("Couchbase\\Extension", createConnection, ai_CouchbaseExtension_createConnection)
    ZEND_NS_FE("Couchbase\\Extension", openBucket, ai_CouchbaseExtension_openBucket)
    ZEND_NS_FE("Couchbase\\Extension", closeBucket, ai_CouchbaseExtension_closeBucket)
    ZEND_NS_FE("Couchbase\\Extension", documentUpsert, ai_CouchbaseExtension_documentUpsert)
    ZEND_NS_FE("Couchbase\\Extension", documentGet, ai_CouchbaseExtension_documentGet)
    ZEND_NS_FE("Couchbase\\Extension", documentGetAndTouch, ai_CouchbaseExtension_documentGetAndTouch)
    ZEND_NS_FE("Couchbase\\Extension", documentGetAndLock, ai_CouchbaseExtension_documentGetAndLock)
    ZEND_NS_FE("Couchbase\\Extension", documentUnlock, ai_CouchbaseExtension_documentUnlock)
    ZEND_NS_FE("Couchbase\\Extension", documentTouch, ai_CouchbaseExtension_documentTouch)
    ZEND_NS_FE("Couchbase\\Extension", documentExists, ai_CouchbaseExtension_documentExists)
    ZEND_NS_FE("Couchbase\\Extension", documentMutateIn, ai_CouchbaseExtension_documentMutateIn)
    ZEND_NS_FE("Couchbase\\Extension", documentLookupIn, ai_CouchbaseExtension_documentLookupIn)
    ZEND_NS_FE("Couchbase\\Extension", query, ai_CouchbaseExtension_query)
    ZEND_NS_FE("Couchbase\\Extension", analyticsQuery, ai_CouchbaseExtension_analyticsQuery)
    ZEND_NS_FE("Couchbase\\Extension", viewQuery, ai_CouchbaseExtension_viewQuery)
    ZEND_NS_FE("Couchbase\\Extension", searchQuery, ai_CouchbaseExtension_searchQuery)
    ZEND_NS_FE("Couchbase\\Extension", searchIndexUpsert, ai_CouchbaseExtension_searchIndexUpsert)
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
