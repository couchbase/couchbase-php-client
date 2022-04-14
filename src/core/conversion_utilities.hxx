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

#include <couchbase/cas.hxx>
#include <couchbase/operations/document_query.hxx>

#include <Zend/zend_API.h>

namespace couchbase::php
{
std::string
cb_string_new(const zend_string* value);

std::string
cb_string_new(const zval* value);

std::pair<operations::query_request, core_error_info>
zval_to_query_request(const zend_string* statement, const zval* options);

void
query_response_to_zval(zval* return_value, const operations::query_response& resp);

template<typename Integer>
static std::pair<core_error_info, std::optional<Integer>>
cb_get_integer(const zval* options, std::string_view name)
{
    if (options == nullptr || Z_TYPE_P(options) == IS_NULL) {
        return {};
    }
    if (Z_TYPE_P(options) != IS_ARRAY) {
        return { { error::common_errc::invalid_argument, { __LINE__, __FILE__, __func__ }, "expected array for options argument" }, {} };
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
        default:
            return { { error::common_errc::invalid_argument,
                       { __LINE__, __FILE__, __func__ },
                       fmt::format("expected {} to be a integer value in the options", name) },
                     {} };
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

template<typename Duration>
core_error_info
cb_get_timeout(Duration& timeout, const zval* options)
{
    if (options == nullptr || Z_TYPE_P(options) == IS_NULL) {
        return {};
    }
    if (Z_TYPE_P(options) != IS_ARRAY) {
        return { error::common_errc::invalid_argument, { __LINE__, __FILE__, __func__ }, "expected array for options argument" };
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
            return { error::common_errc::invalid_argument,
                     { __LINE__, __FILE__, __func__ },
                     "expected timeoutMilliseconds to be a number in the options" };
    }
    timeout = std::chrono::milliseconds(Z_LVAL_P(value));
    return {};
}

template<typename Request>
core_error_info
cb_assign_timeout(Request& req, const zval* options)
{
    return cb_get_timeout(req.timeout, options);
}

template<typename Boolean>
core_error_info
cb_assign_boolean(Boolean& field, const zval* options, std::string_view name)
{
    if (options == nullptr || Z_TYPE_P(options) == IS_NULL) {
        return {};
    }
    if (Z_TYPE_P(options) != IS_ARRAY) {
        return { error::common_errc::invalid_argument, { __LINE__, __FILE__, __func__ }, "expected array for options argument" };
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
            return { error::common_errc::invalid_argument,
                     { __LINE__, __FILE__, __func__ },
                     fmt::format("expected {} to be a boolean value in the options", name) };
    }
    return {};
}

core_error_info
cb_string_to_cas(const std::string& cas_string, couchbase::cas& cas);

core_error_info
cb_assign_cas(couchbase::cas& cas, const zval* document);

} // namespace couchbase::php
