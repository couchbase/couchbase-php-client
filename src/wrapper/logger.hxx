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
} // namespace couchbase::php
