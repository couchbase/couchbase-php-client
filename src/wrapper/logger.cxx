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

#include <spdlog/fmt/bundled/chrono.h>

#include <php.h>

#include <tao/json/value.hpp>

#include <algorithm>
#include <chrono>
#include <mutex>
#include <queue>

#ifndef _WIN32
#include <pthread.h>
#endif

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
      { "time",
        fmt::format("{:%F %T}.{}", msg.time, msg.time.time_since_epoch().count() % 1'000'000) },
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

const static std::shared_ptr<php_log_err_sink<std::mutex>> global_php_log_err_sink{
  std::make_shared<php_log_err_sink<std::mutex>>()
};

COUCHBASE_API
void
flush_logger()
{
  if (global_php_log_err_sink) {
    global_php_log_err_sink->flush_deferred_messages();
  }
}

namespace
{
/*
 * Held from the prepare handler to the parent or child handler of one fork(), and while a thread
 * creates or shuts down the logger, which starts or joins the flusher. The C library may run the
 * handlers of concurrent forks in parallel, and a fork in the middle of either would copy a running
 * flusher. The child is a copy of the thread that locked it, and unlocks it.
 */
std::mutex logger_fork_mutex;
} // namespace

COUCHBASE_API
void
shutdown_logger()
{
  std::scoped_lock lock(logger_fork_mutex);
  flush_logger();
  couchbase::core::logger::shutdown();
}

#ifndef _WIN32
namespace
{
/* the interval couchbase::core::logger::create_file_logger() passes to spdlog::flush_every() */
constexpr std::chrono::seconds logger_flush_interval{ 1 };
bool restart_logger_flusher{ false };

/*
 * fork() copies only the calling thread, so a child that inherits spdlog's periodic flusher joins a
 * thread it does not have when the logger shuts down, and never exits. A zero interval joins the
 * flusher before the fork and leaves the registry without one.
 */
void
stop_logger_flusher()
{
  logger_fork_mutex.lock();
  restart_logger_flusher = couchbase::core::logger::is_initialized();
  if (restart_logger_flusher) {
    couchbase::core::logger::flush();
    spdlog::flush_every(std::chrono::seconds::zero());
  }
}

void
restart_logger_flusher_in_parent()
{
  if (restart_logger_flusher) {
    spdlog::flush_every(logger_flush_interval);
  }
  logger_fork_mutex.unlock();
}

bool restart_logger_in_child{ false };
} // namespace
#endif

COUCHBASE_API
void
restart_logger_in_forked_child()
{
#ifndef _WIN32
  if (restart_logger_in_child) {
    restart_logger_in_child = false;
    std::scoped_lock lock(logger_fork_mutex);
    spdlog::flush_every(logger_flush_interval);
  }
#endif
}

#ifndef _WIN32
namespace
{
void (*previous_interrupt_function)(zend_execute_data* execute_data){ nullptr };

/*
 * Runs when the VM next checks for interrupts. A child forked by PHP code reaches it as soon as the
 * fork returns. A child that PHP forks only to exec() another program never does, and starts no
 * thread before the exec().
 */
void
restart_logger_on_interrupt(zend_execute_data* execute_data)
{
  restart_logger_in_forked_child();
  if (previous_interrupt_function != nullptr) {
    previous_interrupt_function(execute_data);
  }
}

/* the child of a multithreaded fork() starts threads only in restart_logger_in_forked_child() */
void
restart_logger_flusher_in_child()
{
  if (restart_logger_flusher) {
    restart_logger_in_child = true;
#if PHP_VERSION_ID >= 80200
    zend_atomic_bool_store_ex(&EG(vm_interrupt), true);
#else
    EG(vm_interrupt) = 1;
#endif
  }
  logger_fork_mutex.unlock();
}
} // namespace
#endif

COUCHBASE_API
void
register_logger_fork_handlers()
{
#ifndef _WIN32
  /*
   * Fork handlers cannot be removed. Where dlclose() does not unload the module (musl), another
   * module startup would add a second set, and fork() would lock logger_fork_mutex twice.
   */
  static bool fork_handlers_registered{ false };
  if (!fork_handlers_registered) {
    fork_handlers_registered = true;
    pthread_atfork(
      stop_logger_flusher, restart_logger_flusher_in_parent, restart_logger_flusher_in_child);
  }
  previous_interrupt_function = zend_interrupt_function;
  zend_interrupt_function = restart_logger_on_interrupt;
#endif
}

COUCHBASE_API
void
unregister_logger_fork_handlers()
{
#ifndef _WIN32
  /* a module loaded again must not chain to the hook of the unloaded one */
  if (zend_interrupt_function == restart_logger_on_interrupt) {
    zend_interrupt_function = previous_interrupt_function;
  }
#endif
}

COUCHBASE_API
void
initialize_logger()
{
  std::scoped_lock lock(logger_fork_mutex);
  auto spd_log_level = spdlog::level::off;
  auto cbpp_log_level = couchbase::core::logger::level::off;
  if (auto env_val = spdlog::details::os::getenv("COUCHBASE_LOG_LEVEL"); !env_val.empty()) {
    cbpp_log_level = couchbase::core::logger::level_from_str(env_val);
    spd_log_level = spdlog::level::from_str(env_val);
  }
  if (const char* ini_val = COUCHBASE_G(log_level); ini_val != nullptr) {
    std::string log_level(ini_val);
    if (!log_level.empty()) {
      std::transform(log_level.begin(), log_level.end(), log_level.begin(), [](auto c) {
        return std::tolower(c);
      });
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
    if (const char* ini_val = COUCHBASE_G(log_path);
        ini_val != nullptr && std::strlen(ini_val) > 0) {
      configuration.filename = ini_val;
      configuration.filename += fmt::format(".{}", spdlog::details::os::pid());
    }
    configuration.unit_test = true;
    configuration.console = COUCHBASE_G(log_stderr);
    configuration.log_level = cbpp_log_level;
    if (COUCHBASE_G(log_php_log_err)) {
      configuration.sink = global_php_log_err_sink;
      global_php_log_err_sink->include_source_info(cbpp_log_level ==
                                                   couchbase::core::logger::level::trace);
    }
    couchbase::core::logger::create_file_logger(configuration);
  }

  spdlog::set_level(spd_log_level);
  couchbase::core::logger::set_log_levels(cbpp_log_level);
}
} // namespace couchbase::php
