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

#include <optional>
#include <set>
#include <system_error>
#include <variant>
#include <vector>

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

struct key_value_error_context {
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
    std::optional<std::string> last_dispatched_to{};
    std::optional<std::string> last_dispatched_from{};
    int retry_attempts{ 0 };
    std::set<std::string> retry_reasons{};
};

struct query_error_context {
    std::uint64_t first_error_code{};
    std::string first_error_message{};
    std::string client_context_id{};
    std::string statement{};
    std::optional<std::string> parameters{};
    int retry_attempts{ 0 };
    std::set<std::string> retry_reasons{};
    std::uint32_t http_status{};
    std::string http_body{};
};

struct analytics_error_context {
    std::uint64_t first_error_code{};
    std::string first_error_message{};
    std::string client_context_id{};
    std::string statement{};
    std::optional<std::string> parameters{};
    int retry_attempts{ 0 };
    std::set<std::string> retry_reasons{};
    std::uint32_t http_status{};
    std::string http_body{};
};

struct view_query_error_context {
    std::string client_context_id{};
    std::string design_document_name{};
    std::string view_name{};
    std::vector<std::string> query_string{};
    int retry_attempts{ 0 };
    std::set<std::string> retry_reasons{};
    std::uint32_t http_status{};
    std::string http_body{};
};

struct core_error_info {
    std::error_code ec{};
    source_location location{};
    std::string message{};
    std::variant<empty_error_context, key_value_error_context, query_error_context,
                 analytics_error_context, view_query_error_context> error_context{};
};
} // namespace couchbase::php
