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

#include <system_error>

namespace couchbase::php
{
// TODO: use std::source_location when C++20 supported
struct source_location {
    std::uint32_t line{};
    std::string file_name{};
    std::string function_name{};
};

struct core_error_info {
    std::error_code ec{};
    source_location location{};
    std::string message{};
};
} // namespace couchbase::php