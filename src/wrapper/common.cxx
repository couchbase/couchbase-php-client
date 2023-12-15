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

#include "wrapper.hxx"

#include "common.hxx"
#include "core/utils/json.hxx"

#include <couchbase/error_codes.hxx>

#include <fmt/core.h>
#include <tao/pegtl/parse_error.hpp>

#include <sstream>
#include <zend_exceptions.h>

COUCHBASE_API
ZEND_DECLARE_MODULE_GLOBALS(couchbase)

namespace couchbase::php
{
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
zend_class_entry* document_not_locked_exception_ce;
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
zend_class_entry* permission_denied_exception_ce;
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

zend_class_entry* transaction_exception_ce;
zend_class_entry* transaction_operation_failed_exception_ce;
zend_class_entry* transaction_failed_exception_ce;
zend_class_entry* transaction_expired_exception_ce;
zend_class_entry* transaction_commit_ambiguous_exception_ce;

COUCHBASE_API
zend_class_entry*
couchbase_exception()
{
    return couchbase_exception_ce;
}

COUCHBASE_API
void
initialize_exceptions(const zend_function_entry* exception_functions)
{
    zend_class_entry ce;
    INIT_NS_CLASS_ENTRY(ce, "Couchbase\\Exception", "CouchbaseException", exception_functions);
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
    INIT_NS_CLASS_ENTRY(ce, "Couchbase\\Exception", "DocumentNotLockedException", nullptr);
    document_not_locked_exception_ce = zend_register_internal_class_ex(&ce, couchbase_exception_ce);
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
    INIT_NS_CLASS_ENTRY(ce, "Couchbase\\Exception", "PermissionDeniedException", nullptr);
    permission_denied_exception_ce = zend_register_internal_class_ex(&ce, couchbase_exception_ce);
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

    INIT_NS_CLASS_ENTRY(ce, "Couchbase\\Exception", "TransactionException", nullptr);
    transaction_exception_ce = zend_register_internal_class_ex(&ce, couchbase_exception_ce);
    INIT_NS_CLASS_ENTRY(ce, "Couchbase\\Exception", "TransactionOperationFailedException", nullptr);
    transaction_operation_failed_exception_ce = zend_register_internal_class_ex(&ce, transaction_exception_ce);
    INIT_NS_CLASS_ENTRY(ce, "Couchbase\\Exception", "TransactionFailedException", nullptr);
    transaction_failed_exception_ce = zend_register_internal_class_ex(&ce, transaction_exception_ce);
    INIT_NS_CLASS_ENTRY(ce, "Couchbase\\Exception", "TransactionExpiredException", nullptr);
    transaction_expired_exception_ce = zend_register_internal_class_ex(&ce, transaction_exception_ce);
    INIT_NS_CLASS_ENTRY(ce, "Couchbase\\Exception", "TransactionCommitAmbiguousException", nullptr);
    transaction_commit_ambiguous_exception_ce = zend_register_internal_class_ex(&ce, transaction_exception_ce);
}

COUCHBASE_API
zend_class_entry*
map_error_to_exception(const core_error_info& info)
{
    if (info.ec.category() == couchbase::core::impl::common_category()) {
        switch (static_cast<couchbase::errc::common>(info.ec.value())) {
            case couchbase::errc::common::service_not_available:
                return service_not_available_exception_ce;
            case couchbase::errc::common::unsupported_operation:
                return unsupported_operation_exception_ce;
            case couchbase::errc::common::temporary_failure:
                return temporary_failure_exception_ce;
            case couchbase::errc::common::invalid_argument:
                return invalid_argument_exception_ce;
            case couchbase::errc::common::internal_server_failure:
                return internal_server_failure_exception_ce;
            case couchbase::errc::common::authentication_failure:
                return authentication_failure_exception_ce;
            case couchbase::errc::common::parsing_failure:
                return parsing_failure_exception_ce;
            case couchbase::errc::common::cas_mismatch:
                return cas_mismatch_exception_ce;
            case couchbase::errc::common::request_canceled:
                return request_canceled_exception_ce;
            case couchbase::errc::common::bucket_not_found:
                return bucket_not_found_exception_ce;
            case couchbase::errc::common::collection_not_found:
                return collection_not_found_exception_ce;
            case couchbase::errc::common::ambiguous_timeout:
                return ambiguous_timeout_exception_ce;
            case couchbase::errc::common::unambiguous_timeout:
                return unambiguous_timeout_exception_ce;
            case couchbase::errc::common::feature_not_available:
                return feature_not_available_exception_ce;
            case couchbase::errc::common::index_not_found:
                return index_not_found_exception_ce;
            case couchbase::errc::common::index_exists:
                return index_exists_exception_ce;
            case couchbase::errc::common::encoding_failure:
                return encoding_failure_exception_ce;
            case couchbase::errc::common::decoding_failure:
                return decoding_failure_exception_ce;
            case couchbase::errc::common::scope_not_found:
                return scope_not_found_exception_ce;
            default:
                break;
        }
    } else if (info.ec.category() == couchbase::core::impl::analytics_category()) {
        switch (static_cast<couchbase::errc::analytics>(info.ec.value())) {
            case couchbase::errc::analytics::compilation_failure:
                return compilation_failure_exception_ce;
            case couchbase::errc::analytics::job_queue_full:
                return job_queue_full_exception_ce;
            case couchbase::errc::analytics::dataset_not_found:
                return dataset_not_found_exception_ce;
            case couchbase::errc::analytics::dataverse_not_found:
                return dataverse_not_found_exception_ce;
            case couchbase::errc::analytics::dataset_exists:
                return dataset_exists_exception_ce;
            case couchbase::errc::analytics::dataverse_exists:
                return dataverse_exists_exception_ce;
            case couchbase::errc::analytics::link_not_found:
                return link_not_found_exception_ce;
            case couchbase::errc::analytics::link_exists:
                return link_exists_exception_ce;
            default:
                break;
        }
    } else if (info.ec.category() == couchbase::core::impl::key_value_category()) {
        switch (static_cast<couchbase::errc::key_value>(info.ec.value())) {
            case couchbase::errc::key_value::document_not_found:
                return document_not_found_exception_ce;
            case couchbase::errc::key_value::document_not_locked:
                return document_not_locked_exception_ce;
            case couchbase::errc::key_value::document_irretrievable:
                return document_irretrievable_exception_ce;
            case couchbase::errc::key_value::document_locked:
                return document_locked_exception_ce;
            case couchbase::errc::key_value::document_exists:
                return document_exists_exception_ce;
            case couchbase::errc::key_value::durability_level_not_available:
                return durability_level_not_available_exception_ce;
            case couchbase::errc::key_value::durability_impossible:
                return durability_impossible_exception_ce;
            case couchbase::errc::key_value::durability_ambiguous:
                return durability_ambiguous_exception_ce;
            case couchbase::errc::key_value::durable_write_in_progress:
                return durable_write_in_progress_exception_ce;
            case couchbase::errc::key_value::durable_write_re_commit_in_progress:
                return durable_write_re_commit_in_progress_exception_ce;
            case couchbase::errc::key_value::path_not_found:
                return path_not_found_exception_ce;
            case couchbase::errc::key_value::path_mismatch:
                return path_mismatch_exception_ce;
            case couchbase::errc::key_value::path_invalid:
                return path_invalid_exception_ce;
            case couchbase::errc::key_value::path_too_big:
                return path_too_big_exception_ce;
            case couchbase::errc::key_value::path_too_deep:
                return path_too_deep_exception_ce;
            case couchbase::errc::key_value::document_not_json:
                return document_not_json_exception_ce;
            case couchbase::errc::key_value::number_too_big:
                return number_too_big_exception_ce;
            case couchbase::errc::key_value::delta_invalid:
                return delta_invalid_exception_ce;
            case couchbase::errc::key_value::path_exists:
                return path_exists_exception_ce;
            case couchbase::errc::key_value::value_invalid:
                return value_invalid_exception_ce;
            case couchbase::errc::key_value::value_too_deep:
                return value_too_deep_exception_ce;
            case couchbase::errc::key_value::value_too_large:
                return value_too_large_exception_ce;
            case couchbase::errc::key_value::xattr_cannot_modify_virtual_attribute:
                return xattr_cannot_modify_virtual_attribute_exception_ce;
            case couchbase::errc::key_value::xattr_invalid_key_combo:
                return xattr_invalid_key_combo_exception_ce;
            case couchbase::errc::key_value::xattr_unknown_macro:
                return xattr_unknown_macro_exception_ce;
            case couchbase::errc::key_value::xattr_unknown_virtual_attribute:
                return xattr_unknown_virtual_attribute_exception_ce;
            default:
                break;
        }
    } else if (info.ec.category() == couchbase::core::impl::management_category()) {
        switch (static_cast<couchbase::errc::management>(info.ec.value())) {
            case couchbase::errc::management::collection_exists:
                return collection_exists_exception_ce;
            case couchbase::errc::management::group_not_found:
                return group_not_found_exception_ce;
            case couchbase::errc::management::bucket_exists:
                return bucket_exists_exception_ce;
            case couchbase::errc::management::bucket_not_flushable:
                return bucket_not_flushable_exception_ce;
            case couchbase::errc::management::scope_exists:
                return scope_exists_exception_ce;
            case couchbase::errc::management::user_exists:
                return user_exists_exception_ce;
            case couchbase::errc::management::user_not_found:
                return user_not_found_exception_ce;
            default:
                break;
        }
    } else if (info.ec.category() == couchbase::core::impl::query_category()) {
        switch (static_cast<couchbase::errc::query>(info.ec.value())) {
            case couchbase::errc::query::planning_failure:
                return planning_failure_exception_ce;
            case couchbase::errc::query::index_failure:
                return index_failure_exception_ce;
            case couchbase::errc::query::prepared_statement_failure:
                return prepared_statement_failure_exception_ce;
            default:
                break;
        }
    } else if (info.ec.category() == couchbase::core::impl::search_category()) {
        switch (static_cast<couchbase::errc::search>(info.ec.value())) {
            case couchbase::errc::search::index_not_ready:
                return index_not_ready_exception_ce;
            case couchbase::errc::search::consistency_mismatch:
                return consistency_mismatch_exception_ce;
            default:
                break;
        }
    } else if (info.ec.category() == couchbase::core::impl::view_category()) {
        switch (static_cast<couchbase::errc::view>(info.ec.value())) {
            case couchbase::errc::view::design_document_not_found:
                return design_document_not_found_exception_ce;
            case couchbase::errc::view::view_not_found:
                return view_not_found_exception_ce;
            default:
                break;
        }
    } else if (info.ec.category() == detail::get_transactions_category()) {
        switch (static_cast<transactions_errc>(info.ec.value())) {
            case transactions_errc::operation_failed:
                return transaction_operation_failed_exception_ce;
            case transactions_errc::failed:
                return transaction_failed_exception_ce;
            case transactions_errc::expired:
                return transaction_expired_exception_ce;
            case transactions_errc::commit_ambiguous:
                return transaction_commit_ambiguous_exception_ce;
            case transactions_errc::std_exception:
            case transactions_errc::unexpected_exception:
                return transaction_exception_ce;
            default:
                break;
        }
    }
    return couchbase_exception_ce;
}

static void
common_error_context_to_zval(const common_error_context& ctx, zval* return_value, const std::string& /* enhanced_error_message */)
{
    if (ctx.last_dispatched_to) {
        add_assoc_stringl(return_value, "lastDispatchedTo", ctx.last_dispatched_to.value().data(), ctx.last_dispatched_to.value().size());
    }
    if (ctx.last_dispatched_from) {
        add_assoc_stringl(
          return_value, "lastDispatchedFrom", ctx.last_dispatched_from.value().data(), ctx.last_dispatched_from.value().size());
    }
    if (ctx.retry_attempts > 0) {
        add_assoc_long(return_value, "retryAttempts", ctx.retry_attempts);
    }
    if (!ctx.retry_reasons.empty()) {
        zval reasons;
        array_init_size(&reasons, ctx.retry_reasons.size());
        for (const auto& reason : ctx.retry_reasons) {
            add_next_index_string(&reasons, reason.c_str());
        }
        add_assoc_zval(return_value, "retryReasons", &reasons);
    }
}

static void
common_http_error_context_to_zval(const common_http_error_context& ctx, zval* return_value, const std::string& enhanced_error_message)
{
    add_assoc_stringl(return_value, "clientContextId", ctx.client_context_id.data(), ctx.client_context_id.size());
    add_assoc_long(return_value, "httpStatus", ctx.http_status);
    add_assoc_stringl(return_value, "httpBody", ctx.http_body.data(), ctx.http_body.size());
    common_error_context_to_zval(ctx, return_value, enhanced_error_message);
}

static void
error_context_to_zval(const key_value_error_context& ctx, zval* return_value, std::string& enhanced_error_message)
{
    add_assoc_stringl(return_value, "bucketName", ctx.bucket.data(), ctx.bucket.size());
    add_assoc_stringl(return_value, "collection", ctx.collection.data(), ctx.collection.size());
    add_assoc_stringl(return_value, "scope", ctx.scope.data(), ctx.scope.size());
    add_assoc_stringl(return_value, "id", ctx.id.data(), ctx.id.size());
    add_assoc_long(return_value, "opaque", ctx.opaque);
    if (ctx.cas > 0) {
        auto cas = fmt::format("{:x}", ctx.cas);
        add_assoc_stringl(return_value, "cas", cas.data(), cas.size());
    }
    if (ctx.status_code) {
        add_assoc_long(return_value, "statusCode", ctx.status_code.value_or(0xffff));
    }
    if (ctx.error_map_name) {
        add_assoc_stringl(return_value, "errorMapName", ctx.error_map_name.value().data(), ctx.error_map_name.value().size());
    }
    if (ctx.error_map_description) {
        add_assoc_stringl(
          return_value, "errorMapDescription", ctx.error_map_description.value().data(), ctx.error_map_description.value().size());
    }
    if (ctx.enhanced_error_reference) {
        add_assoc_stringl(
          return_value, "enhancedErrorReference", ctx.enhanced_error_reference.value().data(), ctx.enhanced_error_reference.value().size());
        enhanced_error_message.append(fmt::format("ref=\"{}\"", ctx.enhanced_error_reference.value()));
    }
    if (ctx.enhanced_error_context) {
        add_assoc_stringl(
          return_value, "enhancedErrorContext", ctx.enhanced_error_context.value().data(), ctx.enhanced_error_context.value().size());
        enhanced_error_message.append(
          fmt::format("{}ctx=\"{}\"", ctx.enhanced_error_reference ? ", " : "", ctx.enhanced_error_context.value()));
    }
    common_error_context_to_zval(ctx, return_value, enhanced_error_message);
}

static void
error_context_to_zval(const query_error_context& ctx, zval* return_value, std::string& enhanced_error_message)
{
    add_assoc_long(return_value, "firstErrorCode", ctx.first_error_code);
    add_assoc_stringl(return_value, "firstErrorMessage", ctx.first_error_message.data(), ctx.first_error_message.size());
    enhanced_error_message = fmt::format("serverError={}, \"{}\"", ctx.first_error_code, ctx.first_error_message);
    add_assoc_stringl(return_value, "statement", ctx.statement.data(), ctx.statement.size());
    if (ctx.parameters) {
        add_assoc_stringl(return_value, "parameters", ctx.parameters.value().data(), ctx.parameters.value().size());
    }
    common_http_error_context_to_zval(ctx, return_value, enhanced_error_message);
}

static void
error_context_to_zval(const analytics_error_context& ctx, zval* return_value, std::string& enhanced_error_message)
{
    add_assoc_long(return_value, "firstErrorCode", ctx.first_error_code);
    add_assoc_stringl(return_value, "firstErrorMessage", ctx.first_error_message.data(), ctx.first_error_message.size());
    enhanced_error_message = fmt::format("serverError={}, \"{}\"", ctx.first_error_code, ctx.first_error_message);
    add_assoc_stringl(return_value, "statement", ctx.statement.data(), ctx.statement.size());
    if (ctx.parameters) {
        add_assoc_stringl(return_value, "parameters", ctx.parameters.value().data(), ctx.parameters.value().size());
    }
    common_http_error_context_to_zval(ctx, return_value, enhanced_error_message);
}

static void
error_context_to_zval(const view_query_error_context& ctx, zval* return_value, std::string& enhanced_error_message)
{
    add_assoc_stringl(return_value, "designDocumentName", ctx.design_document_name.data(), ctx.design_document_name.size());
    add_assoc_stringl(return_value, "viewName", ctx.view_name.data(), ctx.view_name.size());
    common_http_error_context_to_zval(ctx, return_value, enhanced_error_message);
}

static void
error_context_to_zval(const search_error_context& ctx, zval* return_value, std::string& enhanced_error_message)
{
    add_assoc_stringl(return_value, "indexName", ctx.index_name.data(), ctx.index_name.size());
    if (ctx.query) {
        add_assoc_stringl(return_value, "query", ctx.query.value().data(), ctx.query.value().size());
    }
    if (ctx.parameters) {
        add_assoc_stringl(return_value, "parameters", ctx.parameters.value().data(), ctx.parameters.value().size());
    }
    common_http_error_context_to_zval(ctx, return_value, enhanced_error_message);
}

COUCHBASE_API
void
error_context_to_zval(const http_error_context& ctx, zval* return_value, std::string& enhanced_error_message)
{
    add_assoc_stringl(return_value, "method", ctx.method.data(), ctx.method.size());
    add_assoc_stringl(return_value, "path", ctx.path.data(), ctx.path.size());
    try {
        if (auto json_body = core::utils::json::parse(ctx.http_body); json_body.is_object()) {
            if (const auto* errors = json_body.find("errors"); errors != nullptr) {
                enhanced_error_message = "errors=" + core::utils::json::generate(*errors);
            }
        }
    } catch (const tao::pegtl::parse_error&) {
        /* http body is not a JSON */
    }
    common_http_error_context_to_zval(ctx, return_value, enhanced_error_message);
}

COUCHBASE_API
void
error_context_to_zval(const transactions_error_context& ctx, zval* return_value, std::string& /* enhanced_error_message */)
{
    if (ctx.cause) {
        add_assoc_stringl(return_value, "cause", ctx.cause->data(), ctx.cause->size());
    }
    if (ctx.type) {
        add_assoc_stringl(return_value, "type", ctx.type->data(), ctx.type->size());
    }
    if (ctx.result) {
        zval result;
        array_init(&result);
        add_assoc_stringl(&result, "transactionId", ctx.result->transaction_id.data(), ctx.result->transaction_id.size());
        add_assoc_bool(&result, "unstagingComplete", ctx.result->unstaging_complete);
        add_assoc_zval(return_value, "result", &result);
    }
    if (ctx.should_not_rollback) {
        add_assoc_bool(return_value, "shouldNotRollback", ctx.should_not_rollback.value());
    }
    if (ctx.should_not_retry) {
        add_assoc_bool(return_value, "shouldNotRetry", ctx.should_not_retry.value());
    }
}

COUCHBASE_API
void
error_context_to_zval(const empty_error_context& /* ctx */, zval* /* return_value */, std::string& /* enhanced_error_message */)
{
    /* nothing to do */
}

COUCHBASE_API
void
error_context_to_zval(const core_error_info& info, zval* return_value, std::string& enhanced_error_message)
{
    array_init(return_value);
    add_assoc_stringl(return_value, "error", info.message.data(), info.message.size());
    std::visit(
      [return_value, &enhanced_error_message](const auto& ctx) { error_context_to_zval(ctx, return_value, enhanced_error_message); },
      info.error_context);
}

COUCHBASE_API
void
create_exception(zval* return_value, const core_error_info& error_info)
{
    if (!error_info.ec) {
        return; // success
    }

    zval context;
    std::string enhanced_error_message;
    couchbase::php::error_context_to_zval(error_info, &context, enhanced_error_message);

    zend_class_entry* ex_ce = couchbase::php::map_error_to_exception(error_info);
    object_init_ex(return_value, ex_ce);
    std::stringstream message;
    message << error_info.ec.message();
    if (!error_info.message.empty()) {
        message << ": \"" << error_info.message << "\"";
    }
    if (!enhanced_error_message.empty()) {
        message << ", " << enhanced_error_message;
    }
    couchbase_update_property_string(ex_ce, return_value, "message", message.str().c_str());
    couchbase_update_property_string(ex_ce, return_value, "file", error_info.location.file_name.c_str());
    couchbase_update_property_long(ex_ce, return_value, "line", error_info.location.line);
    couchbase_update_property_long(ex_ce, return_value, "code", error_info.ec.value());
    couchbase_update_property(couchbase_exception_ce, return_value, "context", &context);
    Z_DELREF(context);
}
} // namespace couchbase::php
