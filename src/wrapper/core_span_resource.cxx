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

#include "wrapper.hxx"

#include "common.hxx"
#include "connection_handle.hxx"
#include "conversion_utilities.hxx"
#include "core_span_resource.hxx"

#include <core/cluster.hxx>
#include <core/tracing/tracer_wrapper.hxx>

#include <thread>

namespace couchbase::php
{
static int core_span_destructor_id_{ 0 };

void
set_core_span_destructor_id(int id)
{
  core_span_destructor_id_ = id;
}

auto
get_core_span_destructor_id() -> int
{
  return core_span_destructor_id_;
}

class core_span_resource::impl : public std::enable_shared_from_this<core_span_resource::impl>
{
public:
  explicit impl(std::shared_ptr<couchbase::tracing::request_span> span)
    : span_{ std::move(span) }
  {
  }

  impl(impl&& other) = delete;

  impl(const impl& other) = delete;

  const impl& operator=(impl&& other) = delete;

  const impl& operator=(const impl& other) = delete;

  void add_tag(const zend_string* name, const zval* value)
  {
    auto tag_name = cb_string_new(name);

    switch (Z_TYPE_P(value)) {
      case IS_LONG:
        span_->add_tag(tag_name, static_cast<std::uint64_t>(Z_LVAL_P(value)));
        break;
      case IS_STRING:
        span_->add_tag(tag_name, cb_string_new(Z_STR_P(value)));
        break;
    }
  }

  void end()
  {
    span_->end();
  }

  [[nodiscard]] std::shared_ptr<couchbase::tracing::request_span> span()
  {
    return span_;
  }

private:
  std::shared_ptr<couchbase::tracing::request_span> span_;
};

COUCHBASE_API
core_span_resource::core_span_resource(std::shared_ptr<couchbase::tracing::request_span> span)
  : impl_{ std::make_shared<core_span_resource::impl>(std::move(span)) }
{
}

COUCHBASE_API
void
core_span_resource::add_tag(const zend_string* name, const zval* value)
{
  impl_->add_tag(name, value);
}

COUCHBASE_API
void
core_span_resource::end()
{
  impl_->end();
}

COUCHBASE_API
auto
core_span_resource::span() -> std::shared_ptr<couchbase::tracing::request_span> const
{
  return impl_->span();
}

COUCHBASE_API
auto
create_core_span_resource(couchbase::php::connection_handle* handle,
                          zend_string* name,
                          core_span_resource* parent_span_resource) -> zend_resource*
{
  std::shared_ptr<couchbase::tracing::request_span> parent_span = nullptr;
  if (parent_span_resource != nullptr) {
    parent_span = parent_span_resource->span();
  }
  auto span = handle->cluster().tracer()->create_span(cb_string_new(name), std::move(parent_span));

  auto* resource = new core_span_resource(std::move(span));
  return zend_register_resource(resource, core_span_destructor_id_);
}

COUCHBASE_API
void
destroy_core_span_resource(zend_resource* res)
{
  if (res->type == core_span_destructor_id_ && res->ptr != nullptr) {
    auto* handle = static_cast<core_span_resource*>(res->ptr);
    res->ptr = nullptr;
    std::thread([handle]() {
      handle->end();
      delete handle;
    }).detach();
  }
}
} // namespace couchbase::php
