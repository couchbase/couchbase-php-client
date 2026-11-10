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

#include <Zend/zend_API.h>

#include <chrono>

namespace couchbase::php
{
/**
 * Supported log levels are:
 *
 * * "trace", "TRACE", "TRAC"
 * * "debug", "DEBUG", "DEBG"
 * * "info", "INFO"
 * * "warning", "WARN", "WARNING"
 * * "error", "ERROR", "ERR"
 * * "critical", "CRITICAL", "FATAL"
 * * "off", "OFF"
 */
COUCHBASE_API
void
initialize_logger();

COUCHBASE_API
void
flush_logger();

COUCHBASE_API
void
shutdown_logger();

#ifndef _WIN32
/**
 * Test hook behind couchbase.test_hooks. Joins the previous holder, then holds the PHP error log
 * sink's mutex on another thread for the given duration, and returns once that thread holds it.
 * Not synchronized: one caller at a time.
 */
COUCHBASE_API
void
hold_log_sink_for_test(std::chrono::milliseconds duration);
#endif

/**
 * Installs pthread_atfork handlers that pass the fork to the core logger, whether or not the
 * application calls Cluster::notifyFork(), and gives a child forked by PHP code a log file of its
 * own. Called at module startup; the handlers are installed once per process.
 */
COUCHBASE_API
void
register_logger_fork_handlers();

/**
 * Restarts the logger of a child forked by PHP code, if the VM interrupt has not done it yet. Code
 * that starts threads which log in the child calls it first, so that they do not depend on when the
 * VM checks the interrupt.
 */
COUCHBASE_API
void
restart_logger_in_forked_child();

/**
 * Removes the hook register_logger_fork_handlers() installs on zend_interrupt_function. Called at
 * module shutdown.
 */
COUCHBASE_API
void
unregister_logger_fork_handlers();
} // namespace couchbase::php
