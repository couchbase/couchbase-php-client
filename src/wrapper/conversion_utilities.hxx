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

#include "common.hxx"
#include "couchbase/store_semantics.hxx"

#include <core/operations/document_query.hxx>
#include <core/operations/document_search.hxx>

#include <couchbase/cas.hxx>
#include <couchbase/durability_level.hxx>
#include <couchbase/persist_to.hxx>
#include <couchbase/replicate_to.hxx>

#include <Zend/zend_API.h>

#include <chrono>

#include <fmt/format.h>
#include <type_traits>

namespace couchbase::transactions
{
class transaction_query_options;
} // namespace couchbase::transactions

namespace couchbase::php
{
std::vector<std::byte>
cb_binary_new(const zend_string* value);

std::vector<std::byte>
cb_binary_new(const zval* value);

std::string
cb_string_new(const zend_string* value);

std::string
cb_string_new(const zval* value);

std::pair<core::operations::query_request, core_error_info>
zval_to_query_request(const zend_string* statement, const zval* options);

std::pair<transactions::transaction_query_options, core_error_info>
zval_to_transactions_query_options(const zval* options);

std::pair<core::operations::search_request, core_error_info>
zval_to_common_search_request(const zend_string* index_name, const zend_string* query, const zval* options);

void
query_response_to_zval(zval* return_value, const core::operations::query_response& resp);

void
search_query_response_to_zval(zval* return_value, const core::operations::search_response& resp);

template <typename Integer>
static Integer
parse_integer(const std::string& str, std::size_t* pos = 0, int base = 10)
{
    if constexpr (std::is_signed_v<Integer>) {
        return std::stoll(str, pos, base);
    } else {
        return std::stoull(str, pos, base);
    }
}

template<typename Integer>
static std::pair<core_error_info, std::optional<Integer>>
cb_get_integer_from_hex(const zend_string* value, std::string_view name)
{
    auto hex_string = cb_string_new(value);

    if(hex_string.empty()) {
        return { { errc::common::invalid_argument, ERROR_LOCATION, fmt::format("unexpected empty string for {}", name) }, {} };
    }

    try {
        std::size_t pos;
        auto result = parse_integer<Integer>(hex_string, &pos, 16);
        if (result < std::numeric_limits<Integer>::min() || result > std::numeric_limits<Integer>::max()) {
            return { { errc::common::invalid_argument, ERROR_LOCATION, fmt::format("number out of range for {}", name) }, {} };
        }
        if (pos != hex_string.length()) {
            return { { errc::common::invalid_argument, ERROR_LOCATION, fmt::format("trailing garbage in {}", name) }, {} };
        }
        return {{}, result};
    } catch (const std::invalid_argument& e) {
        return { { errc::common::invalid_argument, ERROR_LOCATION, fmt::format("invalid hex number for {}", name) }, {} };
    } catch (const std::out_of_range& e) {
        return { { errc::common::invalid_argument, ERROR_LOCATION, fmt::format("number out of range for {}", name) }, {} };
    }
}

template<typename Integer>
static std::pair<core_error_info, std::optional<Integer>>
cb_get_integer(const zval* options, std::string_view name)
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
        case IS_LONG:
            break;
        case IS_STRING:
            return cb_get_integer_from_hex<Integer>(Z_STR_P(value), name);
        default:
            return {
                { errc::common::invalid_argument, ERROR_LOCATION, fmt::format("expected {} to be a integer value in the options", name) },
                {}
            };
    }

    return { {}, Z_LVAL_P(value) };
}

template<typename Integer>
core_error_info
cb_assign_integer(Integer& field, const zval* options, std::string_view name)
{
    auto [e, value] = cb_get_integer<Integer>(options, name);
    if (e.ec) {
        return e;
    }
    if (value) {
        field = *value;
    }
    return {};
}

std::pair<core_error_info, std::optional<std::string>>
cb_get_string(const zval* options, std::string_view name);

template<typename String>
core_error_info
cb_assign_string(String& field, const zval* options, std::string_view name)
{
    auto [e, value] = cb_get_string(options, name);
    if (e.ec) {
        return e;
    }
    if (value) {
        field = *value;
    }
    return {};
}

std::pair<core_error_info, std::optional<std::vector<std::byte>>>
cb_get_binary(const zval* options, std::string_view name);

template<typename Binary>
core_error_info
cb_assign_binary(Binary& field, const zval* options, std::string_view name)
{
    auto [e, value] = cb_get_binary(options, name);
    if (e.ec) {
        return e;
    }
    if (value) {
        field = *value;
    }
    return {};
}

std::pair<core_error_info, std::optional<std::chrono::milliseconds>>
cb_get_timeout(const zval* options);

template<typename Request>
core_error_info
cb_assign_timeout(Request& req, const zval* options)
{
    auto [err, timeout] = cb_get_timeout(options);
    if (!err.ec && timeout) {
        req.timeout = timeout.value();
        return {};
    }
    return err;
}

template<typename Options>
core_error_info
cb_set_expiry(Options& opts, const zval* options)
{
    if (auto [e, value] = cb_get_integer<std::uint64_t>(options, "expirySeconds"); e.ec) {
        return e;
    } else if (value) {
        try {
            opts.expiry(std::chrono::seconds{ value.value() });
        } catch (const std::system_error& ec) {
            return { ec.code(), ERROR_LOCATION, ec.what() };
        }
        return {};
    }

    if (auto [e, value] = cb_get_integer<std::uint64_t>(options, "expiryTimestamp"); e.ec) {
        return e;
    } else if (value) {
        try {
            opts.expiry(std::chrono::system_clock::time_point{ std::chrono::seconds{ value.value() } });
        } catch (const std::system_error& ec) {
            return { ec.code(), ERROR_LOCATION, ec.what() };
        }
    }
    return {};
}

template<typename Options>
core_error_info
cb_set_initial_value(Options& opts, const zval* options)
{
    if (auto [e, value] = cb_get_integer<std::uint64_t>(options, "initialValue"); e.ec) {
        return e;
    } else if (value) {
        opts.initial(value.value());
    }
    return {};
}

template<typename Options>
core_error_info
cb_set_delta(Options& opts, const zval* options)
{
    if (auto [e, value] = cb_get_integer<std::uint64_t>(options, "delta"); e.ec) {
        return e;
    } else if (value) {
        opts.delta(value.value());
    }
    return {};
}

template<typename Options>
core_error_info
cb_set_timeout(Options& opts, const zval* options)
{
    auto [err, timeout] = cb_get_timeout(options);
    if (err.ec) {
        return err;
    }
    if (timeout) {
        opts.timeout(timeout.value());
    }
    return {};
}

std::pair<core_error_info, std::optional<bool>>
cb_get_boolean(const zval* options, std::string_view name);

template<typename Options>
core_error_info
cb_set_access_deleted(Options& opts, const zval* options)
{
    auto [err, value] = cb_get_boolean(options, "accessDeleted");
    if (err.ec) {
        return err;
    }
    if (value.has_value()) {
        opts.access_deleted(value.value());
    }
    return {};
}

template<typename Options>
core_error_info
cb_set_preserve_expiry(Options& opts, const zval* options)
{
    auto [err, value] = cb_get_boolean(options, "preserveExpiry");
    if (err.ec) {
        return err;
    }
    if (value.has_value()) {
        opts.preserve_expiry(value.value());
    }
    return {};
}

template<typename Options>
core_error_info
cb_set_create_as_deleted(Options& opts, const zval* options)
{
    auto [err, value] = cb_get_boolean(options, "createAsDeleted");
    if (err.ec) {
        return err;
    }
    if (value.has_value()) {
        opts.create_as_deleted(value.value());
    }
    return {};
}

std::pair<core_error_info, std::optional<couchbase::cas>>
cb_get_cas(const zval* options);

template<typename Options>
core_error_info
cb_set_cas(Options& opts, const zval* options)
{
    auto [err, value] = cb_get_cas(options);
    if (err.ec) {
        return err;
    }
    if (value.has_value()) {
        opts.cas(value.value());
    }
    return {};
}

template<typename Boolean>
core_error_info
cb_assign_boolean(Boolean& field, const zval* options, std::string_view name)
{
    if (options == nullptr || Z_TYPE_P(options) == IS_NULL) {
        return {};
    }
    if (Z_TYPE_P(options) != IS_ARRAY) {
        return { errc::common::invalid_argument, ERROR_LOCATION, "expected array for options argument" };
    }

    const zval* value = zend_symtable_str_find(Z_ARRVAL_P(options), name.data(), name.size());
    if (value == nullptr) {
        return {};
    }
    switch (Z_TYPE_P(value)) {
        case IS_NULL:
            return {};
        case IS_TRUE:
            field = true;
            break;
        case IS_FALSE:
            field = false;
            break;
        default:
            return { errc::common::invalid_argument,
                     ERROR_LOCATION,
                     fmt::format("expected {} to be a boolean value in the options", name) };
    }
    return {};
}

core_error_info
cb_string_to_cas(const std::string& cas_string, couchbase::cas& cas);

core_error_info
cb_assign_cas(couchbase::cas& cas, const zval* document);

core_error_info
cb_assign_vector_of_strings(std::vector<std::string>& field, const zval* options, std::string_view name);

std::pair<core_error_info, std::optional<couchbase::durability_level>>
cb_get_durability_level(const zval* options);

std::pair<core_error_info, std::optional<std::pair<couchbase::persist_to, couchbase::replicate_to>>>
cb_get_legacy_durability_constraints(const zval* options);

template<typename Options>
core_error_info
cb_set_durability(Options& opts, const zval* options)
{
    if (auto [e, level] = cb_get_durability_level(options); e.ec) {
        return e;
    } else if (level) {
        opts.durability(level.value());
        return {};
    }

    if (auto [e, constraints] = cb_get_legacy_durability_constraints(options); e.ec) {
        return e;
    } else if (constraints) {
        opts.durability(constraints->first, constraints->second);
        return {};
    }

    return {};
}

template<typename Options>
core_error_info
cb_set_store_semantics(Options& opts, const zval* options)
{
    if (options == nullptr || Z_TYPE_P(options) == IS_NULL) {
        return {};
    }
    if (Z_TYPE_P(options) != IS_ARRAY) {
        return { errc::common::invalid_argument, ERROR_LOCATION, "expected array for options argument" };
    }

    if (auto [e, value] = cb_get_string(options, "storeSemantics"); e.ec) {
        return e;
    } else if (value) {
        if (value.value() == "replace") {
            opts.store_semantics(store_semantics::replace);
        } else if (value.value() == "insert") {
            opts.store_semantics(store_semantics::insert);
        } else if (value.value() == "upsert") {
            opts.store_semantics(store_semantics::upsert);
        } else if (!value.value().empty()) {
            return { errc::common::invalid_argument,
                     ERROR_LOCATION,
                     fmt::format("unexpected value for storeSemantics option: {}", value.value()) };
        }
    }
    return {};
}

} // namespace couchbase::php
