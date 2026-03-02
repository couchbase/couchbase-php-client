/**
 * Copyright 2014-Present Couchbase, Inc.
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
#include "connection_handle.hxx"

#include <couchbase/tracing/request_span.hxx>

#include <Zend/zend_API.h>

#include <chrono>
#include <memory>
#include <string>
#include <system_error>

namespace couchbase
{
namespace php
{
class core_span_resource
{
public:
  COUCHBASE_API
  explicit core_span_resource(std::shared_ptr<couchbase::tracing::request_span> span);

  COUCHBASE_API
  void add_tag(const zend_string* name, const zval* value);

  COUCHBASE_API
  void end();

  COUCHBASE_API
  auto span() -> const std::shared_ptr<couchbase::tracing::request_span>;

private:
  class impl;

  std::shared_ptr<impl> impl_;
};

COUCHBASE_API auto
create_core_span_resource(couchbase::php::connection_handle* handle,
                          zend_string* name,
                          core_span_resource* parent_span) -> zend_resource*;

COUCHBASE_API void
destroy_core_span_resource(zend_resource* res);

COUCHBASE_API void
set_core_span_destructor_id(int id);

COUCHBASE_API auto
get_core_span_destructor_id() -> int;
} // namespace php
} // namespace couchbase
