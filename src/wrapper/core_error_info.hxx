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

#include "api_visibility.hxx"

#include <optional>
#include <set>
#include <system_error>
#include <variant>
#include <vector>
#include <cstdint>

namespace couchbase::php
{
// TODO: use std::source_location when C++20 supported
struct source_location {
    std::uint32_t line{};
    std::string file_name{};
    std::string function_name{};
};

struct empty_error_context {
};

struct common_error_context {
    std::optional<std::string> last_dispatched_to{};
    std::optional<std::string> last_dispatched_from{};
    int retry_attempts{ 0 };
    std::set<std::string, std::less<>> retry_reasons{};
};

struct common_http_error_context : public common_error_context {
    std::string client_context_id{};
    std::uint32_t http_status{};
    std::string http_body{};
};

struct key_value_error_context : public common_error_context {
    std::string bucket;
    std::string scope;
    std::string collection;
    std::string id;
    std::uint32_t opaque{};
    std::uint64_t cas{};
    std::optional<std::uint16_t> status_code{};
    std::optional<std::string> error_map_name{};
    std::optional<std::string> error_map_description{};
    std::optional<std::string> enhanced_error_reference{};
    std::optional<std::string> enhanced_error_context{};
};

struct subdocument_error_context : public key_value_error_context {
    bool deleted;
    std::optional<std::size_t> first_error_index{};
    std::optional<std::string> first_error_path{};
};

struct query_error_context : public common_http_error_context {
    std::uint64_t first_error_code{};
    std::string first_error_message{};
    std::string statement{};
    std::optional<std::string> parameters{};
};

struct analytics_error_context : public common_http_error_context {
    std::uint64_t first_error_code{};
    std::string first_error_message{};
    std::string statement{};
    std::optional<std::string> parameters{};
};

struct view_query_error_context : public common_http_error_context {
    std::string design_document_name{};
    std::string view_name{};
    std::vector<std::string> query_string{};
};

struct search_error_context : public common_http_error_context {
    std::string index_name{};
    std::optional<std::string> query{};
    std::optional<std::string> parameters{};
};

struct http_error_context : public common_http_error_context {
    std::string method{};
    std::string path{};
};

struct transactions_error_context {
    struct transaction_result {
        std::string transaction_id;
        bool unstaging_complete;
    };
    std::optional<bool> should_not_retry{};
    std::optional<bool> should_not_rollback{};
    std::optional<std::string> type{};
    std::optional<std::string> cause{};
    std::optional<transaction_result> result{};
};

#if defined(__GNUC__) || defined(__clang__)
#define ERROR_LOCATION                                                                                                                     \
    {                                                                                                                                      \
        __LINE__, __FILE__, __PRETTY_FUNCTION__                                                                                            \
    }
#else
#define ERROR_LOCATION                                                                                                                     \
    {                                                                                                                                      \
        __LINE__, __FILE__, __FUNCTION__                                                                                                   \
    }
#endif

struct core_error_info {
    std::error_code ec{};
    source_location location{};
    std::string message{};
    std::variant<empty_error_context,
                 key_value_error_context,
                 query_error_context,
                 analytics_error_context,
                 view_query_error_context,
                 search_error_context,
                 http_error_context,
                 transactions_error_context>
      error_context{};
};

enum class transactions_errc {
    operation_failed = 1101,
    std_exception = 1102,
    unexpected_exception = 1103,
    failed = 1104,
    expired = 1105,
    commit_ambiguous = 1106,
};

namespace detail
{
struct transactions_error_category : std::error_category {
    [[nodiscard]] const char* name() const noexcept override
    {
        return "couchbase.transactions";
    }

    [[nodiscard]] std::string message(int ev) const noexcept override
    {
        switch (static_cast<transactions_errc>(ev)) {
            case transactions_errc::operation_failed:
                return "operation_failed";
            case transactions_errc::std_exception:
                return "std_exception";
            case transactions_errc::unexpected_exception:
                return "unexpected_exception";
            case transactions_errc::failed:
                return "failed";
            case transactions_errc::expired:
                return "expired";
            case transactions_errc::commit_ambiguous:
                return "commit_ambiguous";
        }
        return "FIXME: unknown error code in transactions category (recompile with newer library)";
    }
};

inline const std::error_category&
get_transactions_category()
{
    static detail::transactions_error_category instance;
    return instance;
}
} // namespace detail

inline std::error_code
make_error_code(transactions_errc e)
{
    return { static_cast<int>(e), detail::get_transactions_category() };
}

} // namespace couchbase::php

namespace std
{
template<>
struct is_error_code_enum<couchbase::php::transactions_errc> : true_type {
};
} // namespace std
