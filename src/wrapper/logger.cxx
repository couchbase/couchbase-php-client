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
#include "logger.hxx"

#include <core/logger/logger.hxx>
#include <core/utils/json.hxx>

#include <core/logger/configuration.hxx>

#include <spdlog/details/os.h>
#include <spdlog/sinks/base_sink.h>
#include <spdlog/spdlog.h>

#include <fmt/chrono.h>

#include <php.h>

#include <tao/json/value.hpp>

#include <algorithm>
#include <queue>

namespace couchbase::php
{
template<typename Mutex>
class php_log_err_sink : public spdlog::sinks::base_sink<Mutex>
{
  public:
    void flush_deferred_messages()
    {
        std::lock_guard<Mutex> lock(spdlog::sinks::base_sink<Mutex>::mutex_);
        auto messages_ = std::move(deferred_messages_);
        while (!messages_.empty()) {
            write_message(messages_.front());
            messages_.pop();
        }
    }

    void include_source_info(bool include)
    {
        include_source_info_ = include;
    }

  protected:
    struct log_message_for_php {
        spdlog::level::level_enum level{};
        spdlog::log_clock::time_point time{};
        size_t thread_id{};
        std::string payload{};
        const char* filename{};
        int line{};
        const char* funcname{};
    };

    void sink_it_(const spdlog::details::log_msg& msg) override
    {
        tao::json::value data = {
            { "level", fmt::format("{}", spdlog::level::to_string_view(msg.level)) },
            { "time", fmt::format("{:%F %T}.{}", msg.time, msg.time.time_since_epoch().count() % 1'000'000) },
            { "message", std::string(msg.payload.data(), msg.payload.size()) },
            { "thread_id", msg.thread_id },
        };
        if (include_source_info_ && !msg.source.empty()) {
            data["source"] = {
                { "file", msg.source.filename },
                { "line", msg.source.line },
                { "func", msg.source.funcname },
            };
        }
        deferred_messages_.emplace(std::move(data));
    }

    void flush_() override
    {
        /* do nothing here, the flush will be initiated by the SDK */
    }

  private:
    void write_message(const tao::json::value& msg)
    {
#if PHP_VERSION_ID >= 80000
        php_log_err(core::utils::json::generate(msg).c_str());
#else
        auto data = core::utils::json::generate(msg);
        php_log_err(const_cast<char*>(data.c_str()));
#endif
    }

    std::queue<tao::json::value> deferred_messages_{};
    bool include_source_info_{ false };
};

const static std::shared_ptr<php_log_err_sink<std::mutex>> global_php_log_err_sink{ std::make_shared<php_log_err_sink<std::mutex>>() };

COUCHBASE_API
void
flush_logger()
{
    if (global_php_log_err_sink) {
        global_php_log_err_sink->flush_deferred_messages();
    }
}

COUCHBASE_API
void
shutdown_logger()
{
    flush_logger();
    couchbase::core::logger::shutdown();
}


COUCHBASE_API
void
initialize_logger()
{
    auto spd_log_level = spdlog::level::off;
    auto cbpp_log_level = couchbase::core::logger::level::off;
    if (auto env_val = spdlog::details::os::getenv("COUCHBASE_LOG_LEVEL"); !env_val.empty()) {
        cbpp_log_level = couchbase::core::logger::level_from_str(env_val);
        spd_log_level = spdlog::level::from_str(env_val);
    }
    if (const char* ini_val = COUCHBASE_G(log_level); ini_val != nullptr) {
        std::string log_level(ini_val);
        if (!log_level.empty()) {
            std::transform(log_level.begin(), log_level.end(), log_level.begin(), [](auto c) { return std::tolower(c); });
            if (log_level == "fatal" || log_level == "fatl") {
                log_level = "critical";
            } else if (log_level == "trac") {
                log_level = "trace";
            } else if (log_level == "debg") {
                log_level = "debug";
            } else if (log_level == "eror") {
                log_level = "error";
            }
            cbpp_log_level = couchbase::core::logger::level_from_str(log_level);
            spd_log_level = spdlog::level::from_str(log_level);
        }
    }

    if (cbpp_log_level != couchbase::core::logger::level::off) {
        couchbase::core::logger::configuration configuration{};
        if (const char* ini_val = COUCHBASE_G(log_path); ini_val != nullptr && std::strlen(ini_val) > 0) {
            configuration.filename = ini_val;
            configuration.filename += fmt::format(".{}", spdlog::details::os::pid());
        }
        configuration.unit_test = true;
        configuration.console = COUCHBASE_G(log_stderr);
        configuration.log_level = cbpp_log_level;
        if (COUCHBASE_G(log_php_log_err)) {
            configuration.sink = global_php_log_err_sink;
            global_php_log_err_sink->include_source_info(cbpp_log_level == couchbase::core::logger::level::trace);
        }
        couchbase::core::logger::create_file_logger(configuration);
    }

    spdlog::set_level(spd_log_level);
    couchbase::core::logger::set_log_levels(cbpp_log_level);
}
} // namespace couchbase::php
