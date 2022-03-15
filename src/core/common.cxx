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

#include "common.hxx"

#include <couchbase/errors.hxx>

namespace couchbase::php
{
zend_class_entry*
map_error_to_exception(const core_error_info& info)
{
    /*
     */
    if (info.ec.category() == couchbase::error::detail::get_common_category()) {
        switch (couchbase::error::common_errc(info.ec.value())) {
            case couchbase::error::common_errc::service_not_available:
                return service_not_available_exception_ce;
            case couchbase::error::common_errc::unsupported_operation:
                return unsupported_operation_exception_ce;
            case couchbase::error::common_errc::temporary_failure:
                return temporary_failure_exception_ce;
            case couchbase::error::common_errc::invalid_argument:
                return invalid_argument_exception_ce;
            case couchbase::error::common_errc::internal_server_failure:
                return internal_server_failure_exception_ce;
            case couchbase::error::common_errc::authentication_failure:
                return authentication_failure_exception_ce;
            case couchbase::error::common_errc::parsing_failure:
                return parsing_failure_exception_ce;
            case couchbase::error::common_errc::cas_mismatch:
                return cas_mismatch_exception_ce;
            case couchbase::error::common_errc::request_canceled:
                return request_canceled_exception_ce;
            case couchbase::error::common_errc::bucket_not_found:
                return bucket_not_found_exception_ce;
            case couchbase::error::common_errc::collection_not_found:
                return collection_not_found_exception_ce;
            case couchbase::error::common_errc::ambiguous_timeout:
                return ambiguous_timeout_exception_ce;
            case couchbase::error::common_errc::unambiguous_timeout:
                return unambiguous_timeout_exception_ce;
            case couchbase::error::common_errc::feature_not_available:
                return feature_not_available_exception_ce;
            case couchbase::error::common_errc::index_not_found:
                return index_not_found_exception_ce;
            case couchbase::error::common_errc::index_exists:
                return index_exists_exception_ce;
            case couchbase::error::common_errc::encoding_failure:
                return encoding_failure_exception_ce;
            case couchbase::error::common_errc::decoding_failure:
                return decoding_failure_exception_ce;
            case couchbase::error::common_errc::scope_not_found:
                return scope_not_found_exception_ce;
            default:
                break;
        }
    } else if (info.ec.category() == couchbase::error::detail::get_analytics_category()) {
        switch (couchbase::error::analytics_errc(info.ec.value())) {
            case couchbase::error::analytics_errc::compilation_failure:
                return compilation_failure_exception_ce;
            case couchbase::error::analytics_errc::job_queue_full:
                return job_queue_full_exception_ce;
            case couchbase::error::analytics_errc::dataset_not_found:
                return dataset_not_found_exception_ce;
            case couchbase::error::analytics_errc::dataverse_not_found:
                return dataverse_not_found_exception_ce;
            case couchbase::error::analytics_errc::dataset_exists:
                return dataset_exists_exception_ce;
            case couchbase::error::analytics_errc::dataverse_exists:
                return dataverse_exists_exception_ce;
            case couchbase::error::analytics_errc::link_not_found:
                return link_not_found_exception_ce;
            case couchbase::error::analytics_errc::link_exists:
                return link_exists_exception_ce;
            default:
                break;
        }
    } else if (info.ec.category() == couchbase::error::detail::get_key_value_category()) {
        switch (couchbase::error::key_value_errc(info.ec.value())) {
            case couchbase::error::key_value_errc::document_not_found:
                return document_not_found_exception_ce;
            case couchbase::error::key_value_errc::document_irretrievable:
                return document_irretrievable_exception_ce;
            case couchbase::error::key_value_errc::document_locked:
                return document_locked_exception_ce;
            case couchbase::error::key_value_errc::document_exists:
                return document_exists_exception_ce;
            case couchbase::error::key_value_errc::durability_level_not_available:
                return durability_level_not_available_exception_ce;
            case couchbase::error::key_value_errc::durability_impossible:
                return durability_impossible_exception_ce;
            case couchbase::error::key_value_errc::durability_ambiguous:
                return durability_ambiguous_exception_ce;
            case couchbase::error::key_value_errc::durable_write_in_progress:
                return durable_write_in_progress_exception_ce;
            case couchbase::error::key_value_errc::durable_write_re_commit_in_progress:
                return durable_write_re_commit_in_progress_exception_ce;
            case couchbase::error::key_value_errc::path_not_found:
                return path_not_found_exception_ce;
            case couchbase::error::key_value_errc::path_mismatch:
                return path_mismatch_exception_ce;
            case couchbase::error::key_value_errc::path_invalid:
                return path_invalid_exception_ce;
            case couchbase::error::key_value_errc::path_too_big:
                return path_too_big_exception_ce;
            case couchbase::error::key_value_errc::path_too_deep:
                return path_too_deep_exception_ce;
            case couchbase::error::key_value_errc::document_not_json:
                return document_not_json_exception_ce;
            case couchbase::error::key_value_errc::number_too_big:
                return number_too_big_exception_ce;
            case couchbase::error::key_value_errc::delta_invalid:
                return delta_invalid_exception_ce;
            case couchbase::error::key_value_errc::path_exists:
                return path_exists_exception_ce;
            case couchbase::error::key_value_errc::value_invalid:
                return value_invalid_exception_ce;
            case couchbase::error::key_value_errc::value_too_deep:
                return value_too_deep_exception_ce;
            case couchbase::error::key_value_errc::value_too_large:
                return value_too_large_exception_ce;
            case couchbase::error::key_value_errc::xattr_cannot_modify_virtual_attribute:
                return xattr_cannot_modify_virtual_attribute_exception_ce;
            case couchbase::error::key_value_errc::xattr_invalid_key_combo:
                return xattr_invalid_key_combo_exception_ce;
            case couchbase::error::key_value_errc::xattr_unknown_macro:
                return xattr_unknown_macro_exception_ce;
            case couchbase::error::key_value_errc::xattr_unknown_virtual_attribute:
                return xattr_unknown_virtual_attribute_exception_ce;
            default:
                break;
        }
    } else if (info.ec.category() == couchbase::error::detail::get_management_category()) {
        switch (couchbase::error::management_errc(info.ec.value())) {
            case couchbase::error::management_errc::collection_exists:
                return collection_exists_exception_ce;
            case couchbase::error::management_errc::group_not_found:
                return group_not_found_exception_ce;
            case couchbase::error::management_errc::bucket_exists:
                return bucket_exists_exception_ce;
            case couchbase::error::management_errc::bucket_not_flushable:
                return bucket_not_flushable_exception_ce;
            case couchbase::error::management_errc::scope_exists:
                return scope_exists_exception_ce;
            case couchbase::error::management_errc::user_exists:
                return user_exists_exception_ce;
            case couchbase::error::management_errc::user_not_found:
                return user_not_found_exception_ce;
            default:
                break;
        }
    } else if (info.ec.category() == couchbase::error::detail::get_query_category()) {
        switch (couchbase::error::query_errc(info.ec.value())) {
            case couchbase::error::query_errc::planning_failure:
                return planning_failure_exception_ce;
            case couchbase::error::query_errc::index_failure:
                return index_failure_exception_ce;
            case couchbase::error::query_errc::prepared_statement_failure:
                return prepared_statement_failure_exception_ce;
            default:
                break;
        }
    } else if (info.ec.category() == couchbase::error::detail::get_search_category()) {
        switch (couchbase::error::search_errc(info.ec.value())) {
            case couchbase::error::search_errc::index_not_ready:
                return index_not_ready_exception_ce;
            case couchbase::error::search_errc::consistency_mismatch:
                return consistency_mismatch_exception_ce;
            default:
                break;
        }
    } else if (info.ec.category() == couchbase::error::detail::get_view_category()) {
        switch (couchbase::error::view_errc(info.ec.value())) {
            case couchbase::error::view_errc::design_document_not_found:
                return design_document_not_found_exception_ce;
            case couchbase::error::view_errc::view_not_found:
                return view_not_found_exception_ce;
            default:
                break;
        }
    }
    return couchbase_exception_ce;
}
} // namespace couchbase::php