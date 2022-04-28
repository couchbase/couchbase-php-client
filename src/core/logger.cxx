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

#include "core.hxx"

#include "common.hxx"
#include "logger.hxx"

#include <couchbase/logger/logger.hxx>

#include <spdlog/details/os.h>
#include <spdlog/spdlog.h>

#include <algorithm>

namespace couchbase::php
{
COUCHBASE_API
void
initialize_logger()
{
    auto spd_log_evel = spdlog::level::off;
    auto cbpp_log_level = couchbase::logger::level::off;
    if (auto env_val = spdlog::details::os::getenv("COUCHBASE_LOG_LEVEL"); !env_val.empty()) {
        cbpp_log_level = couchbase::logger::level_from_str(env_val);
        spd_log_evel = spdlog::level::from_str(env_val);
    }
    if (const char* ini_val = COUCHBASE_G(log_level); ini_val != nullptr) {
        std::string log_level(ini_val);
        if (!log_level.empty()) {
            std::for_each(log_level.begin(), log_level.end(), [](unsigned char c) { return std::tolower(c); });
            if (log_level == "fatal" || log_level == "fatl") {
                log_level = "critical";
            } else if (log_level == "trac") {
                log_level = "trace";
            } else if (log_level == "debg") {
                log_level = "debug";
            } else if (log_level == "eror") {
                log_level = "error";
            }
            cbpp_log_level = couchbase::logger::level_from_str(log_level);
            spd_log_evel = spdlog::level::from_str(log_level);
        }
    }
    if (cbpp_log_level != couchbase::logger::level::off) {
        couchbase::logger::create_console_logger();
    }
    spdlog::set_level(spd_log_evel);
    couchbase::logger::set_log_levels(cbpp_log_level);
}
} // namespace couchbase::php
