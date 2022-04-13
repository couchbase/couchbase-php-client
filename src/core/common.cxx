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

#include <fmt/core.h>

#include <sstream>

namespace couchbase::php
{

zend_class_entry*
map_error_to_exception(const core_error_info& info)
{
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
    } else if (info.ec.category() == detail::get_transactions_category()) {
        switch (transactions_errc(info.ec.value())) {
            case transactions_errc::operation_failed:
                return transaction_operation_failed_exception_ce;
                break;
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
common_error_context_to_zval(const common_error_context& ctx, zval* return_value, std::string&)
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
common_http_error_context_to_zval(const common_http_error_context& ctx, zval* return_value, std::string& enhanced_error_message)
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
        enhanced_error_message.append(fmt::format("ref=\"{}\"", enhanced_error_message));
    }
    if (ctx.enhanced_error_context) {
        add_assoc_stringl(
          return_value, "enhancedErrorContext", ctx.enhanced_error_context.value().data(), ctx.enhanced_error_context.value().size());
        enhanced_error_message.append(fmt::format("{}ctx=\"{}\"", ctx.enhanced_error_reference ? ", " : "", enhanced_error_message));
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

void
error_context_to_zval(const http_error_context& ctx, zval* return_value, std::string& enhanced_error_message)
{
    add_assoc_stringl(return_value, "method", ctx.method.data(), ctx.method.size());
    add_assoc_stringl(return_value, "path", ctx.path.data(), ctx.path.size());
    common_http_error_context_to_zval(ctx, return_value, enhanced_error_message);
}

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

void
error_context_to_zval(const empty_error_context& /* ctx */, zval* /* return_value */, std::string& /* enhanced_error_message */)
{
    /* nothing to do */
}

void
error_context_to_zval(const core_error_info& info, zval* return_value, std::string& enhanced_error_message)
{
    array_init(return_value);
    add_assoc_stringl(return_value, "error", info.message.data(), info.message.size());
    std::visit(
      [return_value, &enhanced_error_message](const auto& ctx) { error_context_to_zval(ctx, return_value, enhanced_error_message); },
      info.error_context);
}

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
    message << error_info.ec.message() << " (" << error_info.ec.value() << ")";
    if (!error_info.message.empty()) {
        message << ": \"" << error_info.message << "\"";
    }
    if (!enhanced_error_message.empty()) {
        message << ", " << enhanced_error_message;
    }
    if (!error_info.location.function_name.empty()) {
        message << " in '" << error_info.location.function_name << "'";
    }
    couchbase_update_property_string(ex_ce, return_value, "message", message.str().c_str());
    couchbase_update_property_string(ex_ce, return_value, "file", error_info.location.file_name.c_str());
    couchbase_update_property_long(ex_ce, return_value, "line", error_info.location.line);
    couchbase_update_property_long(ex_ce, return_value, "code", error_info.ec.value());
    couchbase_update_property(couchbase_exception_ce, return_value, "context", &context);
    Z_DELREF(context);
}
} // namespace couchbase::php