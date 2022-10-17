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

#include "conversion_utilities.hxx"

#include <core/utils/binary.hxx>

#include <chrono>

namespace couchbase::php
{

std::vector<std::byte>
cb_binary_new(const zend_string* value)
{
    if (value == nullptr) {
        return {};
    }
    return core::utils::to_binary(ZSTR_VAL(value), ZSTR_LEN(value));
}

std::vector<std::byte>
cb_binary_new(const zval* value)
{
    if (value == nullptr || Z_TYPE_P(value) != IS_STRING) {
        return {};
    }
    return cb_binary_new(Z_STR_P(value));
}

std::string
cb_string_new(const zend_string* value)
{
    if (value == nullptr) {
        return {};
    }
    return { ZSTR_VAL(value), ZSTR_LEN(value) };
}

std::string
cb_string_new(const zval* value)
{
    if (value == nullptr || Z_TYPE_P(value) != IS_STRING) {
        return {};
    }
    return cb_string_new(Z_STR_P(value));
}

std::pair<core_error_info, std::optional<std::string>>
cb_get_string(const zval* options, std::string_view name)
{
    if (options == nullptr || Z_TYPE_P(options) == IS_NULL) {
        return {};
    }
    if (Z_TYPE_P(options) != IS_ARRAY) {
        return { { errc::common::invalid_argument, ERROR_LOCATION, "expected array for options argument" }, {} };
    }

    const zval* value = zend_symtable_str_find(Z_ARRVAL_P(options), name.data(), name.size());
    if (value == nullptr) {
        return {};
    }
    switch (Z_TYPE_P(value)) {
        case IS_NULL:
            return {};
        case IS_STRING:
            break;
        default:
            return {
                { errc::common::invalid_argument, ERROR_LOCATION, fmt::format("expected {} to be a string value in the options", name) }, {}
            };
    }

    return { {}, cb_string_new(value) };
}

std::pair<core_error_info, std::optional<bool>>
cb_get_boolean(const zval* options, std::string_view name)
{
    if (options == nullptr || Z_TYPE_P(options) == IS_NULL) {
        return {};
    }
    if (Z_TYPE_P(options) != IS_ARRAY) {
        return { { errc::common::invalid_argument, ERROR_LOCATION, "expected array for options argument" }, {} };
    }

    const zval* value = zend_symtable_str_find(Z_ARRVAL_P(options), name.data(), name.size());
    if (value == nullptr) {
        return {};
    }
    switch (Z_TYPE_P(value)) {
        case IS_NULL:
            return {};
        case IS_TRUE:
            return { {}, true };
        case IS_FALSE:
            return { {}, false };
        default:
            return {
                { errc::common::invalid_argument, ERROR_LOCATION, fmt::format("expected {} to be a boolean value in the options", name) },
                {}
            };
    }

    return {};
}

std::pair<core_error_info, std::optional<std::vector<std::byte>>>
cb_get_binary(const zval* options, std::string_view name)
{
    if (options == nullptr || Z_TYPE_P(options) == IS_NULL) {
        return {};
    }
    if (Z_TYPE_P(options) != IS_ARRAY) {
        return { { errc::common::invalid_argument, ERROR_LOCATION, "expected array for options argument" }, {} };
    }

    const zval* value = zend_symtable_str_find(Z_ARRVAL_P(options), name.data(), name.size());
    if (value == nullptr) {
        return {};
    }
    switch (Z_TYPE_P(value)) {
        case IS_NULL:
            return {};
        case IS_STRING:
            break;
        default:
            return {
                { errc::common::invalid_argument, ERROR_LOCATION, fmt::format("expected {} to be a string value in the options", name) }, {}
            };
    }

    return { {}, cb_binary_new(value) };
}

std::pair<core::operations::query_request, core_error_info>
zval_to_query_request(const zend_string* statement, const zval* options)
{
    core::operations::query_request request{ cb_string_new(statement) };
    if (auto e = cb_assign_timeout(request, options); e.ec) {
        return { {}, e };
    }
    if (auto [e, scan_consistency] = cb_get_string(options, "scanConsistency"); !e.ec) {
        if (scan_consistency == "notBounded") {
            request.scan_consistency = query_scan_consistency::not_bounded;
        } else if (scan_consistency == "requestPlus") {
            request.scan_consistency = query_scan_consistency::request_plus;
        } else if (scan_consistency) {
            return { {},
                     { errc::common::invalid_argument,
                       ERROR_LOCATION,
                       fmt::format("invalid value used for scan consistency: {}", *scan_consistency) } };
        }
    } else {
        return { {}, e };
    }
    if (auto e = cb_assign_integer(request.scan_cap, options, "scanCap"); e.ec) {
        return { {}, e };
    }
    if (auto e = cb_assign_integer(request.pipeline_cap, options, "pipelineCap"); e.ec) {
        return { {}, e };
    }
    if (auto e = cb_assign_integer(request.pipeline_batch, options, "pipelineBatch"); e.ec) {
        return { {}, e };
    }
    if (auto e = cb_assign_integer(request.max_parallelism, options, "maxParallelism"); e.ec) {
        return { {}, e };
    }
    if (auto [e, profile] = cb_get_string(options, "profile"); !e.ec) {
        if (profile == "off") {
            request.profile = query_profile::off;
        } else if (profile == "phases") {
            request.profile = query_profile::phases;
        } else if (profile == "timings") {
            request.profile = query_profile::timings;
        } else if (profile) {
            return { {}, { errc::common::invalid_argument, ERROR_LOCATION, fmt::format("invalid value used for profile: {}", *profile) } };
        }
    } else {
        return { {}, e };
    }

    if (auto e = cb_assign_boolean(request.readonly, options, "readonly"); e.ec) {
        return { {}, e };
    }
    if (auto e = cb_assign_boolean(request.flex_index, options, "flexIndex"); e.ec) {
        return { {}, e };
    }
    if (auto e = cb_assign_boolean(request.adhoc, options, "adHoc"); e.ec) {
        return { {}, e };
    }
    if (const zval* value = zend_symtable_str_find(Z_ARRVAL_P(options), ZEND_STRL("positionalParameters"));
        value != nullptr && Z_TYPE_P(value) == IS_ARRAY) {
        std::vector<core::json_string> params{};
        const zval* item = nullptr;

        ZEND_HASH_FOREACH_VAL(Z_ARRVAL_P(value), item)
        {
            auto str = std::string({ Z_STRVAL_P(item), Z_STRLEN_P(item) });
            params.emplace_back(std::move(str));
        }
        ZEND_HASH_FOREACH_END();

        request.positional_parameters = params;
    }
    if (const zval* value = zend_symtable_str_find(Z_ARRVAL_P(options), ZEND_STRL("namedParameters"));
        value != nullptr && Z_TYPE_P(value) == IS_ARRAY) {
        std::map<std::string, couchbase::core::json_string, std::less<>> params{};
        const zend_string* key = nullptr;
        const zval* item = nullptr;

        ZEND_HASH_FOREACH_STR_KEY_VAL(Z_ARRVAL_P(value), key, item)
        {
            params[cb_string_new(key)] = std::string({ Z_STRVAL_P(item), Z_STRLEN_P(item) });
        }
        ZEND_HASH_FOREACH_END();

        request.named_parameters = params;
    }
    if (const zval* value = zend_symtable_str_find(Z_ARRVAL_P(options), ZEND_STRL("raw"));
        value != nullptr && Z_TYPE_P(value) == IS_ARRAY) {
        std::map<std::string, couchbase::core::json_string, std::less<>> params{};
        const zend_string* key = nullptr;
        const zval* item = nullptr;

        ZEND_HASH_FOREACH_STR_KEY_VAL(Z_ARRVAL_P(value), key, item)
        {
            params[cb_string_new(key)] = std::string({ Z_STRVAL_P(item), Z_STRLEN_P(item) });
        }
        ZEND_HASH_FOREACH_END();

        request.raw = params;
    }
    if (const zval* value = zend_symtable_str_find(Z_ARRVAL_P(options), ZEND_STRL("consistentWith"));
        value != nullptr && Z_TYPE_P(value) == IS_ARRAY) {
        std::vector<mutation_token> vectors{};
        const zval* item = nullptr;

        ZEND_HASH_FOREACH_VAL(Z_ARRVAL_P(value), item)
        {
            std::uint64_t partition_uuid{ 0 };
            std::uint64_t sequence_number{ 0 };
            std::uint16_t partition_id{ 0 };
            std::string bucket_name{};
            if (auto e = cb_assign_integer(partition_id, item, "partitionId"); e.ec) {
                return { {}, e };
            }
            if (auto e = cb_assign_integer(partition_uuid, item, "partitionUuid"); e.ec) {
                return { {}, e };
            }
            if (auto e = cb_assign_integer(sequence_number, item, "sequenceNumber"); e.ec) {
                return { {}, e };
            }
            if (auto e = cb_assign_string(bucket_name, item, "bucketName"); e.ec) {
                return { {}, e };
            }
            vectors.emplace_back(partition_uuid, sequence_number, partition_id, bucket_name);
        }
        ZEND_HASH_FOREACH_END();

        request.mutation_state = vectors;
    }
    if (auto e = cb_assign_string(request.client_context_id, options, "clientContextId"); e.ec) {
        return { {}, e };
    }
    if (auto e = cb_assign_boolean(request.metrics, options, "metrics"); e.ec) {
        return { {}, e };
    }
    if (auto e = cb_assign_boolean(request.preserve_expiry, options, "preserveExpiry"); e.ec) {
        return { {}, e };
    }
    if (auto e = cb_assign_string(request.scope_name, options, "scopeName"); e.ec) {
        return { {}, e };
    }
    if (auto e = cb_assign_string(request.bucket_name, options, "bucketName"); e.ec) {
        return { {}, e };
    }
    return { request, {} };
}

void
query_response_to_zval(zval* return_value, const core::operations::query_response& resp)
{
    array_init(return_value);
    add_assoc_string(return_value, "servedByNode", resp.served_by_node.c_str());

    zval rows;
    array_init(&rows);
    for (const auto& row : resp.rows) {
        add_next_index_string(&rows, row.c_str());
    }
    add_assoc_zval(return_value, "rows", &rows);

    zval meta;
    array_init(&meta);
    add_assoc_string(&meta, "clientContextId", resp.meta.client_context_id.c_str());
    add_assoc_string(&meta, "requestId", resp.meta.request_id.c_str());
    add_assoc_string(&meta, "status", resp.meta.status.c_str());
    if (resp.meta.profile.has_value()) {
        add_assoc_string(&meta, "profile", resp.meta.profile.value().c_str());
    }
    if (resp.meta.signature.has_value()) {
        add_assoc_string(&meta, "signature", resp.meta.signature.value().c_str());
    }
    if (resp.meta.metrics.has_value()) {
        zval metrics;
        array_init(&metrics);
        add_assoc_long(&metrics, "errorCount", resp.meta.metrics.value().error_count);
        add_assoc_long(&metrics, "mutationCount", resp.meta.metrics.value().mutation_count);
        add_assoc_long(&metrics, "resultCount", resp.meta.metrics.value().result_count);
        add_assoc_long(&metrics, "resultSize", resp.meta.metrics.value().result_size);
        add_assoc_long(&metrics, "sortCount", resp.meta.metrics.value().sort_count);
        add_assoc_long(&metrics, "warningCount", resp.meta.metrics.value().warning_count);
        add_assoc_long(
          &metrics, "elapsedTime", std::chrono::duration_cast<std::chrono::milliseconds>(resp.meta.metrics.value().elapsed_time).count());
        add_assoc_long(&metrics,
                       "executionTime",
                       std::chrono::duration_cast<std::chrono::milliseconds>(resp.meta.metrics.value().execution_time).count());

        add_assoc_zval(&meta, "metrics", &metrics);
    }
    if (resp.meta.errors.has_value()) {
        zval errors;
        array_init(&errors);
        for (const auto& e : resp.meta.errors.value()) {
            zval error;
            array_init(&error);

            add_assoc_long(&error, "code", e.code);
            add_assoc_string(&error, "code", e.message.c_str());
            if (e.reason.has_value()) {
                add_assoc_long(&error, "reason", e.reason.value());
            }
            if (e.retry.has_value()) {
                add_assoc_bool(&error, "retry", e.retry.value());
            }

            add_next_index_zval(&errors, &error);
        }
        add_assoc_zval(return_value, "errors", &errors);
    }
    if (resp.meta.warnings.has_value()) {
        zval warnings;
        array_init(&warnings);
        for (const auto& w : resp.meta.warnings.value()) {
            zval warning;
            array_init(&warning);

            add_assoc_long(&warning, "code", w.code);
            add_assoc_string(&warning, "code", w.message.c_str());
            if (w.reason.has_value()) {
                add_assoc_long(&warning, "reason", w.reason.value());
            }
            if (w.retry.has_value()) {
                add_assoc_bool(&warning, "retry", w.retry.value());
            }

            add_next_index_zval(&warnings, &warning);
        }
        add_assoc_zval(return_value, "warnings", &warnings);
    }

    add_assoc_zval(return_value, "meta", &meta);
}

core_error_info
cb_string_to_cas(const std::string& cas_string, couchbase::cas& cas)
{
    try {
        std::uint64_t cas_value = std::stoull(cas_string, nullptr, 16);
        cas = couchbase::cas{ cas_value };
    } catch (const std::invalid_argument&) {
        return { errc::common::invalid_argument,
                 ERROR_LOCATION,
                 fmt::format("no numeric conversion could be performed for encoded CAS value: \"{}\"", cas_string) };
    } catch (const std::out_of_range&) {
        return { errc::common::invalid_argument,
                 ERROR_LOCATION,
                 fmt::format("the number encoded as CAS is out of the range of representable values by a unsigned long long: \"{}\"",
                             cas_string) };
    }
    return {};
}

std::pair<core_error_info, std::optional<couchbase::cas>>
cb_get_cas(const zval* options)
{
    const zval* value = zend_symtable_str_find(Z_ARRVAL_P(options), ZEND_STRL("cas"));
    if (value == nullptr) {
        return {};
    }
    switch (Z_TYPE_P(value)) {
        case IS_NULL:
            return {};
        case IS_STRING: {
            couchbase::cas cas;
            if (auto e = cb_string_to_cas(std::string(Z_STRVAL_P(value), Z_STRLEN_P(value)), cas); e.ec) {
                return { e, {} };
            }
            return { {}, cas };
        }
        default:
            return { { errc::common::invalid_argument, ERROR_LOCATION, "expected CAS to be a string in the options" }, {} };
    }
    return {};
}

core_error_info
cb_assign_cas(couchbase::cas& cas, const zval* document)
{
    const zval* value = zend_symtable_str_find(Z_ARRVAL_P(document), ZEND_STRL("cas"));
    if (value == nullptr) {
        return {};
    }
    switch (Z_TYPE_P(value)) {
        case IS_NULL:
            return {};
        case IS_STRING:
            break;
        default:
            return { errc::common::invalid_argument, ERROR_LOCATION, "expected CAS to be a string in the options" };
    }
    cb_string_to_cas(std::string(Z_STRVAL_P(value), Z_STRLEN_P(value)), cas);
    return {};
}

std::pair<core_error_info, std::optional<std::chrono::milliseconds>>
cb_get_timeout(const zval* options)
{
    if (options == nullptr || Z_TYPE_P(options) == IS_NULL) {
        return {};
    }
    if (Z_TYPE_P(options) != IS_ARRAY) {
        return { { errc::common::invalid_argument, ERROR_LOCATION, "expected array for options argument" }, {} };
    }

    const zval* value = zend_symtable_str_find(Z_ARRVAL_P(options), ZEND_STRL("timeoutMilliseconds"));
    if (value == nullptr) {
        return {};
    }
    switch (Z_TYPE_P(value)) {
        case IS_NULL:
            return {};
        case IS_LONG:
            break;
        default:
            return { { errc::common::invalid_argument, ERROR_LOCATION, "expected timeoutMilliseconds to be a number in the options" }, {} };
    }
    return { {}, std::chrono::milliseconds(Z_LVAL_P(value)) };
}

std::pair<core_error_info, std::optional<durability_level>>
cb_get_durability_level(const zval* options)
{
    if (options == nullptr || Z_TYPE_P(options) == IS_NULL) {
        return {};
    }
    if (Z_TYPE_P(options) != IS_ARRAY) {
        return { { errc::common::invalid_argument, ERROR_LOCATION, "expected array for options argument" }, {} };
    }

    const zval* value = zend_symtable_str_find(Z_ARRVAL_P(options), ZEND_STRL("durabilityLevel"));
    if (value == nullptr) {
        return {};
    }
    switch (Z_TYPE_P(value)) {
        case IS_NULL:
            return {};
        case IS_STRING:
            break;
        default:
            return { { errc::common::invalid_argument, ERROR_LOCATION, "expected durabilityLevel to be a string in the options" }, {} };
    }
    if (zend_binary_strcmp(Z_STRVAL_P(value), Z_STRLEN_P(value), ZEND_STRL("none")) == 0) {
        return { {}, durability_level::none };
    }
    if (zend_binary_strcmp(Z_STRVAL_P(value), Z_STRLEN_P(value), ZEND_STRL("majority")) == 0) {
        return { {}, durability_level::majority };
    }
    if (zend_binary_strcmp(Z_STRVAL_P(value), Z_STRLEN_P(value), ZEND_STRL("majorityAndPersistToActive")) == 0) {
        return { {}, durability_level::majority_and_persist_to_active };
    }
    if (zend_binary_strcmp(Z_STRVAL_P(value), Z_STRLEN_P(value), ZEND_STRL("persistToMajority")) == 0) {
        return { {}, durability_level::persist_to_majority };
    }
    return { { errc::common::invalid_argument,
               ERROR_LOCATION,
               fmt::format("unknown durabilityLevel: {}", std::string_view(Z_STRVAL_P(value), Z_STRLEN_P(value))) },
             {} };
}

std::pair<core_error_info, std::optional<couchbase::persist_to>>
cb_get_legacy_durability_persist_to(const zval* options)
{
    const zval* value = zend_symtable_str_find(Z_ARRVAL_P(options), ZEND_STRL("persistTo"));
    if (value == nullptr) {
        return { {}, couchbase::persist_to::none };
    }
    switch (Z_TYPE_P(value)) {
        case IS_NULL:
            return { {}, couchbase::persist_to::none };
        case IS_STRING:
            break;
        default:
            return { { errc::common::invalid_argument, ERROR_LOCATION, "expected persistTo to be a string in the options" }, {} };
    }
    if (zend_binary_strcmp(Z_STRVAL_P(value), Z_STRLEN_P(value), ZEND_STRL("none")) == 0) {
        return { {}, couchbase::persist_to::none };
    }
    if (zend_binary_strcmp(Z_STRVAL_P(value), Z_STRLEN_P(value), ZEND_STRL("active")) == 0) {
        return { {}, couchbase::persist_to::active };
    }
    if (zend_binary_strcmp(Z_STRVAL_P(value), Z_STRLEN_P(value), ZEND_STRL("one")) == 0) {
        return { {}, couchbase::persist_to::one };
    }
    if (zend_binary_strcmp(Z_STRVAL_P(value), Z_STRLEN_P(value), ZEND_STRL("two")) == 0) {
        return { {}, couchbase::persist_to::two };
    }
    if (zend_binary_strcmp(Z_STRVAL_P(value), Z_STRLEN_P(value), ZEND_STRL("three")) == 0) {
        return { {}, couchbase::persist_to::three };
    }
    if (zend_binary_strcmp(Z_STRVAL_P(value), Z_STRLEN_P(value), ZEND_STRL("four")) == 0) {
        return { {}, couchbase::persist_to::four };
    }
    return {};
}

std::pair<core_error_info, std::optional<couchbase::replicate_to>>
cb_get_legacy_durability_replicate_to(const zval* options)
{
    const zval* value = zend_symtable_str_find(Z_ARRVAL_P(options), ZEND_STRL("replicateTo"));
    if (value == nullptr) {
        return { {}, couchbase::replicate_to::none };
    }
    switch (Z_TYPE_P(value)) {
        case IS_NULL:
            return { {}, couchbase::replicate_to::none };
        case IS_STRING:
            break;
        default:
            return { { errc::common::invalid_argument, ERROR_LOCATION, "expected replicateTo to be a string in the options" }, {} };
    }
    if (zend_binary_strcmp(Z_STRVAL_P(value), Z_STRLEN_P(value), ZEND_STRL("none")) == 0) {
        return { {}, couchbase::replicate_to::none };
    }
    if (zend_binary_strcmp(Z_STRVAL_P(value), Z_STRLEN_P(value), ZEND_STRL("one")) == 0) {
        return { {}, couchbase::replicate_to::one };
    }
    if (zend_binary_strcmp(Z_STRVAL_P(value), Z_STRLEN_P(value), ZEND_STRL("two")) == 0) {
        return { {}, couchbase::replicate_to::two };
    }
    if (zend_binary_strcmp(Z_STRVAL_P(value), Z_STRLEN_P(value), ZEND_STRL("three")) == 0) {
        return { {}, couchbase::replicate_to::three };
    }
    return {};
}

std::pair<core_error_info, std::optional<std::pair<couchbase::persist_to, couchbase::replicate_to>>>
cb_get_legacy_durability_constraints(const zval* options)
{
    if (options == nullptr || Z_TYPE_P(options) == IS_NULL) {
        return {};
    }
    if (Z_TYPE_P(options) != IS_ARRAY) {
        return { { errc::common::invalid_argument, ERROR_LOCATION, "expected array for options argument" }, {} };
    }

    auto [e1, persist_to] = cb_get_legacy_durability_persist_to(options);
    if (e1.ec) {
        return { e1, {} };
    }
    auto [e2, replicate_to] = cb_get_legacy_durability_replicate_to(options);
    if (e2.ec) {
        return { e2, {} };
    }
    if (!persist_to && !replicate_to) {
        return {};
    }

    return { {}, std::make_pair(persist_to.value_or(couchbase::persist_to::none), replicate_to.value_or(couchbase::replicate_to::none)) };
}
} // namespace couchbase::php