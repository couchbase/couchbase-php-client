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

#include "connection_handle.hxx"
#include "common.hxx"

#include <couchbase/cluster.hxx>
#include <couchbase/operations/management/cluster_describe.hxx>
#include <couchbase/operations/management/design_document.hxx>
#include <couchbase/operations/management/search_index.hxx>
#include <couchbase/operations/management/search_index_upsert.hxx>
#include <couchbase/operations/management/view_index_upsert.hxx>
#include <couchbase/protocol/mutation_token.hxx>

#include <fmt/core.h>

#include <array>
#include <thread>

namespace couchbase::php
{

static std::string
retry_reason_to_string(io::retry_reason reason)
{
    switch (reason) {
        case io::retry_reason::do_not_retry:
            return "do_not_retry";
        case io::retry_reason::socket_not_available:
            return "socket_not_available";
        case io::retry_reason::service_not_available:
            return "service_not_available";
        case io::retry_reason::node_not_available:
            return "node_not_available";
        case io::retry_reason::kv_not_my_vbucket:
            return "kv_not_my_vbucket";
        case io::retry_reason::kv_collection_outdated:
            return "kv_collection_outdated";
        case io::retry_reason::kv_error_map_retry_indicated:
            return "kv_error_map_retry_indicated";
        case io::retry_reason::kv_locked:
            return "kv_locked";
        case io::retry_reason::kv_temporary_failure:
            return "kv_temporary_failure";
        case io::retry_reason::kv_sync_write_in_progress:
            return "kv_sync_write_in_progress";
        case io::retry_reason::kv_sync_write_re_commit_in_progress:
            return "kv_sync_write_re_commit_in_progress";
        case io::retry_reason::service_response_code_indicated:
            return "service_response_code_indicated";
        case io::retry_reason::socket_closed_while_in_flight:
            return "socket_closed_while_in_flight";
        case io::retry_reason::circuit_breaker_open:
            return "circuit_breaker_open";
        case io::retry_reason::query_prepared_statement_failure:
            return "query_prepared_statement_failure";
        case io::retry_reason::query_index_not_found:
            return "query_index_not_found";
        case io::retry_reason::analytics_temporary_failure:
            return "analytics_temporary_failure";
        case io::retry_reason::search_too_many_requests:
            return "search_too_many_requests";
        case io::retry_reason::views_temporary_failure:
            return "views_temporary_failure";
        case io::retry_reason::views_no_active_partition:
            return "views_no_active_partition";
        case io::retry_reason::unknown:
            return "unknown";
    }
    return "unexpected";
}

static const char*
subdoc_opcode_to_string(protocol::subdoc_opcode opcode)
{
    switch (opcode) {
        case protocol::subdoc_opcode::get_doc:
            return "getDocument";
        case protocol::subdoc_opcode::set_doc:
            return "setDocument";
        case protocol::subdoc_opcode::remove_doc:
            return "removeDocument";
        case protocol::subdoc_opcode::get:
            return "get";
        case protocol::subdoc_opcode::exists:
            return "exists";
        case protocol::subdoc_opcode::dict_add:
            return "dictionaryAdd";
        case protocol::subdoc_opcode::dict_upsert:
            return "dictionaryUpsert";
        case protocol::subdoc_opcode::remove:
            return "remove";
        case protocol::subdoc_opcode::replace:
            return "replace";
        case protocol::subdoc_opcode::array_push_last:
            return "arrayPushLast";
        case protocol::subdoc_opcode::array_push_first:
            return "arrayPushFirst";
        case protocol::subdoc_opcode::array_insert:
            return "arrayInsert";
        case protocol::subdoc_opcode::array_add_unique:
            return "arrayAddUnique";
        case protocol::subdoc_opcode::counter:
            return "counter";
        case protocol::subdoc_opcode::get_count:
            return "getCount";
        case protocol::subdoc_opcode::replace_body_with_xattr:
            return "replaceBodyWithXattr";
    }
    return "unexpected";
}

static std::pair<protocol::subdoc_opcode, core_error_info>
decode_lookup_subdoc_opcode(const zval* spec)
{
    if (spec == nullptr || Z_TYPE_P(spec) != IS_ARRAY) {
        return {
            {},
            { error::common_errc::invalid_argument, { __LINE__, __FILE__, __func__ }, "expected that spec will be represented as an array" }
        };
    }
    const zval* value = zend_symtable_str_find(Z_ARRVAL_P(spec), ZEND_STRL("opcode"));
    if (value == nullptr && Z_TYPE_P(value) != IS_STRING) {
        return { {}, { error::common_errc::invalid_argument, { __LINE__, __FILE__, __func__ }, "missing opcode field of the spec" } };
    }
    if (zend_binary_strcmp(Z_STRVAL_P(value), Z_STRLEN_P(value), ZEND_STRL("getDocument")) == 0) {
        return { { protocol::subdoc_opcode::get_doc }, {} };
    }
    if (zend_binary_strcmp(Z_STRVAL_P(value), Z_STRLEN_P(value), ZEND_STRL("get")) == 0) {
        return { { protocol::subdoc_opcode::get }, {} };
    }
    if (zend_binary_strcmp(Z_STRVAL_P(value), Z_STRLEN_P(value), ZEND_STRL("exists")) == 0) {
        return { { protocol::subdoc_opcode::exists }, {} };
    }
    if (zend_binary_strcmp(Z_STRVAL_P(value), Z_STRLEN_P(value), ZEND_STRL("getCount")) == 0) {
        return { { protocol::subdoc_opcode::get_count }, {} };
    }
    return { {},
             { error::common_errc::invalid_argument,
               { __LINE__, __FILE__, __func__ },
               fmt::format("unexpected opcode field of the spec: \"{}\"", std::string(Z_STRVAL_P(value), Z_STRLEN_P(value))) } };
}

static std::pair<protocol::subdoc_opcode, core_error_info>
decode_mutation_subdoc_opcode(const zval* spec)
{
    if (spec == nullptr || Z_TYPE_P(spec) != IS_ARRAY) {
        return {
            {},
            { error::common_errc::invalid_argument, { __LINE__, __FILE__, __func__ }, "expected that spec will be represented as an array" }
        };
    }
    const zval* value = zend_symtable_str_find(Z_ARRVAL_P(spec), ZEND_STRL("opcode"));
    if (value == nullptr && Z_TYPE_P(value) != IS_STRING) {
        return { {}, { error::common_errc::invalid_argument, { __LINE__, __FILE__, __func__ }, "missing opcode field of the spec" } };
    }
    if (zend_binary_strcmp(Z_STRVAL_P(value), Z_STRLEN_P(value), ZEND_STRL("setDocument")) == 0) {
        return { { protocol::subdoc_opcode::set_doc }, {} };
    }
    if (zend_binary_strcmp(Z_STRVAL_P(value), Z_STRLEN_P(value), ZEND_STRL("removeDocument")) == 0) {
        return { { protocol::subdoc_opcode::remove_doc }, {} };
    }
    if (zend_binary_strcmp(Z_STRVAL_P(value), Z_STRLEN_P(value), ZEND_STRL("dictionaryAdd")) == 0) {
        return { { protocol::subdoc_opcode::dict_add }, {} };
    }
    if (zend_binary_strcmp(Z_STRVAL_P(value), Z_STRLEN_P(value), ZEND_STRL("dictionaryUpsert")) == 0) {
        return { { protocol::subdoc_opcode::dict_upsert }, {} };
    }
    if (zend_binary_strcmp(Z_STRVAL_P(value), Z_STRLEN_P(value), ZEND_STRL("remove")) == 0) {
        return { { protocol::subdoc_opcode::remove }, {} };
    }
    if (zend_binary_strcmp(Z_STRVAL_P(value), Z_STRLEN_P(value), ZEND_STRL("replace")) == 0) {
        return { { protocol::subdoc_opcode::replace }, {} };
    }
    if (zend_binary_strcmp(Z_STRVAL_P(value), Z_STRLEN_P(value), ZEND_STRL("arrayPushLast")) == 0) {
        return { { protocol::subdoc_opcode::array_push_last }, {} };
    }
    if (zend_binary_strcmp(Z_STRVAL_P(value), Z_STRLEN_P(value), ZEND_STRL("arrayPushFirst")) == 0) {
        return { { protocol::subdoc_opcode::array_push_first }, {} };
    }
    if (zend_binary_strcmp(Z_STRVAL_P(value), Z_STRLEN_P(value), ZEND_STRL("arrayInsert")) == 0) {
        return { { protocol::subdoc_opcode::array_insert }, {} };
    }
    if (zend_binary_strcmp(Z_STRVAL_P(value), Z_STRLEN_P(value), ZEND_STRL("arrayAddUnique")) == 0) {
        return { { protocol::subdoc_opcode::array_add_unique }, {} };
    }
    if (zend_binary_strcmp(Z_STRVAL_P(value), Z_STRLEN_P(value), ZEND_STRL("counter")) == 0) {
        return { { protocol::subdoc_opcode::counter }, {} };
    }
    if (zend_binary_strcmp(Z_STRVAL_P(value), Z_STRLEN_P(value), ZEND_STRL("replaceBodyWithXattr")) == 0) {
        return { { protocol::subdoc_opcode::replace_body_with_xattr }, {} };
    }
    return { {},
             { error::common_errc::invalid_argument,
               { __LINE__, __FILE__, __func__ },
               fmt::format("unexpected opcode field of the spec: \"{}\"", std::string(Z_STRVAL_P(value), Z_STRLEN_P(value))) } };
}

static key_value_error_context
build_error_context(const error_context::key_value& ctx)
{
    key_value_error_context out;
    out.bucket = ctx.id.bucket();
    out.scope = ctx.id.scope();
    out.collection = ctx.id.collection();
    out.id = ctx.id.key();
    out.opaque = ctx.opaque;
    out.cas = ctx.cas.value;
    if (ctx.status_code) {
        out.status_code = static_cast<std::uint16_t>(ctx.status_code.value());
    }
    if (ctx.error_map_info) {
        out.error_map_name = ctx.error_map_info->name;
        out.error_map_description = ctx.error_map_info->description;
    }
    if (ctx.enhanced_error_info) {
        out.enhanced_error_reference = ctx.enhanced_error_info->reference;
        out.enhanced_error_context = ctx.error_map_info->description;
    }
    out.last_dispatched_to = ctx.last_dispatched_to;
    out.last_dispatched_from = ctx.last_dispatched_from;
    out.retry_attempts = ctx.retry_attempts;
    if (!ctx.retry_reasons.empty()) {
        for (const auto& reason : ctx.retry_reasons) {
            out.retry_reasons.insert(retry_reason_to_string(reason));
        }
    }
    return out;
}

static query_error_context
build_query_error_context(const error_context::query& ctx)
{
    query_error_context out;
    out.client_context_id = ctx.client_context_id;
    out.statement = ctx.statement;
    out.parameters = ctx.parameters;
    out.first_error_message = ctx.first_error_message;
    out.first_error_code = ctx.first_error_code;
    out.http_status = ctx.http_status;
    out.http_body = ctx.http_body;
    out.retry_attempts = ctx.retry_attempts;
    out.last_dispatched_to = ctx.last_dispatched_to;
    out.last_dispatched_from = ctx.last_dispatched_from;
    if (!ctx.retry_reasons.empty()) {
        for (const auto& reason : ctx.retry_reasons) {
            out.retry_reasons.insert(retry_reason_to_string(reason));
        }
    }
    return out;
}

static analytics_error_context
build_analytics_error_context(const error_context::analytics& ctx)
{
    analytics_error_context out;
    out.client_context_id = ctx.client_context_id;
    out.statement = ctx.statement;
    out.parameters = ctx.parameters;
    out.first_error_message = ctx.first_error_message;
    out.first_error_code = ctx.first_error_code;
    out.http_status = ctx.http_status;
    out.http_body = ctx.http_body;
    out.retry_attempts = ctx.retry_attempts;
    out.last_dispatched_to = ctx.last_dispatched_to;
    out.last_dispatched_from = ctx.last_dispatched_from;
    if (!ctx.retry_reasons.empty()) {
        for (const auto& reason : ctx.retry_reasons) {
            out.retry_reasons.insert(retry_reason_to_string(reason));
        }
    }
    return out;
}

static view_query_error_context
build_view_query_error_context(const error_context::view& ctx)
{
    view_query_error_context out;
    out.client_context_id = ctx.client_context_id;
    out.design_document_name = ctx.design_document_name;
    out.view_name = ctx.view_name;
    out.query_string = ctx.query_string;
    out.http_status = ctx.http_status;
    out.http_body = ctx.http_body;
    out.retry_attempts = ctx.retry_attempts;
    out.last_dispatched_to = ctx.last_dispatched_to;
    out.last_dispatched_from = ctx.last_dispatched_from;
    if (!ctx.retry_reasons.empty()) {
        for (const auto& reason : ctx.retry_reasons) {
            out.retry_reasons.insert(retry_reason_to_string(reason));
        }
    }
    return out;
}

static search_error_context
build_search_query_error_context(const error_context::search& ctx)
{
    search_error_context out;
    out.client_context_id = ctx.client_context_id;
    out.index_name = ctx.index_name;
    out.query = ctx.query;
    out.parameters = ctx.parameters;
    out.http_status = ctx.http_status;
    out.http_body = ctx.http_body;
    out.retry_attempts = ctx.retry_attempts;
    out.last_dispatched_to = ctx.last_dispatched_to;
    out.last_dispatched_from = ctx.last_dispatched_from;
    if (!ctx.retry_reasons.empty()) {
        for (const auto& reason : ctx.retry_reasons) {
            out.retry_reasons.insert(retry_reason_to_string(reason));
        }
    }
    return out;
}

static http_error_context
build_http_error_context(const error_context::http& ctx)
{
    http_error_context out;
    out.client_context_id = ctx.client_context_id;
    out.method = ctx.method;
    out.path = ctx.path;
    out.http_status = ctx.http_status;
    out.http_body = ctx.http_body;
    out.retry_attempts = ctx.retry_attempts;
    if (!ctx.retry_reasons.empty()) {
        for (const auto& reason : ctx.retry_reasons) {
            out.retry_reasons.insert(retry_reason_to_string(reason));
        }
    }
    out.last_dispatched_from = ctx.last_dispatched_from;
    out.last_dispatched_to = ctx.last_dispatched_to;

    return out;
}

class connection_handle::impl : public std::enable_shared_from_this<connection_handle::impl>
{
  public:
    explicit impl(couchbase::origin origin)
      : origin_(std::move(origin))
    {
    }

    impl(impl&& other) = delete;
    impl(const impl& other) = delete;
    const impl& operator=(impl&& other) = delete;
    const impl& operator=(const impl& other) = delete;

    ~impl()
    {
        if (cluster_) {
            auto barrier = std::make_shared<std::promise<void>>();
            auto f = barrier->get_future();
            cluster_->close([barrier]() { barrier->set_value(); });
            f.wait();
            if (worker.joinable()) {
                worker.join();
            }
            cluster_.reset();
        }
    }

    void start()
    {
        worker = std::thread([self = shared_from_this()]() { self->ctx_.run(); });
    }

    std::string cluster_version(const std::string& bucket_name = "")
    {
        auto barrier = std::make_shared<std::promise<couchbase::operations::management::cluster_describe_response>>();
        auto f = barrier->get_future();
        cluster_->execute(
          couchbase::operations::management::cluster_describe_request{},
          [barrier](couchbase::operations::management::cluster_describe_response&& resp) { barrier->set_value(std::move(resp)); });
        auto resp = f.get();
        if (resp.ctx.ec == couchbase::error::common_errc::service_not_available && !bucket_name.empty()) {
            if (auto e = bucket_open(bucket_name); e.ec) {
                return {};
            }
            return cluster_version();
        }
        if (resp.ctx.ec || resp.info.nodes.empty()) {
            return {};
        }
        return resp.info.nodes.front().version;
    }

    core_error_info open()
    {
        auto barrier = std::make_shared<std::promise<std::error_code>>();
        auto f = barrier->get_future();
        cluster_->open(origin_, [barrier](std::error_code ec) { barrier->set_value(ec); });
        if (auto ec = f.get()) {
            return { ec, { __LINE__, __FILE__, __func__ } };
        }
        return {};
    }

    core_error_info bucket_open(const std::string& name)
    {
        auto barrier = std::make_shared<std::promise<std::error_code>>();
        auto f = barrier->get_future();
        cluster_->open_bucket(name, [barrier](std::error_code ec) { barrier->set_value(ec); });
        if (auto ec = f.get()) {
            return { ec, { __LINE__, __FILE__, __func__ } };
        }
        return {};
    }

    core_error_info bucket_close(const std::string& name)
    {
        auto barrier = std::make_shared<std::promise<std::error_code>>();
        auto f = barrier->get_future();
        cluster_->close_bucket(name, [barrier](std::error_code ec) { barrier->set_value(ec); });
        if (auto ec = f.get()) {
            return { ec, { __LINE__, __FILE__, __func__ } };
        }
        return {};
    }

    template<typename Request, typename Response = typename Request::response_type>
    std::pair<Response, core_error_info> key_value_execute(const char* operation, Request request)
    {
        auto barrier = std::make_shared<std::promise<Response>>();
        auto f = barrier->get_future();
        cluster_->execute(std::move(request), [barrier](Response&& resp) { barrier->set_value(std::move(resp)); });
        auto resp = f.get();
        if (resp.ctx.ec) {
            return { std::move(resp),
                     { resp.ctx.ec,
                       { __LINE__, __FILE__, __func__ },
                       fmt::format(
                         R"(unable to execute KV operation "{}": ec={} ({}))", operation, resp.ctx.ec.value(), resp.ctx.ec.message()),
                       build_error_context(resp.ctx) } };
        }
        return { std::move(resp), {} };
    }

    template<typename Request, typename Response = typename Request::response_type>
    std::vector<Response> key_value_execute_multi(std::vector<Request> requests)
    {
        std::vector<std::shared_ptr<std::promise<Response>>> barriers;
        barriers.reserve(requests.size());
        for (auto&& request : requests) {
            auto barrier = std::make_shared<std::promise<Response>>();
            cluster_->execute(request, [barrier](Response&& resp) { barrier->set_value(std::move(resp)); });
            barriers.emplace_back(barrier);
        }
        std::vector<Response> responses;
        responses.reserve(requests.size());
        for (const auto& barrier : barriers) {
            responses.emplace_back(barrier->get_future().get());
        }
        return responses;
    }

    std::pair<core_error_info, couchbase::operations::query_response> query(couchbase::operations::query_request request)
    {
        auto barrier = std::make_shared<std::promise<couchbase::operations::query_response>>();
        auto f = barrier->get_future();
        cluster_->execute(std::move(request),
                          [barrier](couchbase::operations::query_response&& resp) { barrier->set_value(std::move(resp)); });
        auto resp = f.get();
        if (resp.ctx.ec) {
            return { { resp.ctx.ec,
                       { __LINE__, __FILE__, __func__ },
                       fmt::format("unable to query: {}, {}", resp.ctx.ec.value(), resp.ctx.ec.message()),
                       build_query_error_context(resp.ctx) },
                     {} };
        }
        return { {}, std::move(resp) };
    }

    std::pair<core_error_info, couchbase::operations::analytics_response> analytics_query(couchbase::operations::analytics_request request)
    {
        auto barrier = std::make_shared<std::promise<couchbase::operations::analytics_response>>();
        auto f = barrier->get_future();
        cluster_->execute(std::move(request),
                          [barrier](couchbase::operations::analytics_response&& resp) { barrier->set_value(std::move(resp)); });
        auto resp = f.get();
        if (resp.ctx.ec) {
            return { { resp.ctx.ec,
                       { __LINE__, __FILE__, __func__ },
                       fmt::format("unable to query: {}, {}", resp.ctx.ec.value(), resp.ctx.ec.message()),
                       build_analytics_error_context(resp.ctx) },
                     {} };
        }
        return { {}, std::move(resp) };
    }

    std::pair<core_error_info, couchbase::operations::document_view_response> view_query(
      couchbase::operations::document_view_request request)
    {
        auto barrier = std::make_shared<std::promise<couchbase::operations::document_view_response>>();
        auto f = barrier->get_future();
        cluster_->execute(std::move(request),
                          [barrier](couchbase::operations::document_view_response&& resp) { barrier->set_value(std::move(resp)); });
        auto resp = f.get();
        if (resp.ctx.ec) {
            return { { resp.ctx.ec,
                       { __LINE__, __FILE__, __func__ },
                       fmt::format("unable to view query: {}, {}", resp.ctx.ec.value(), resp.ctx.ec.message()),
                       build_view_query_error_context(resp.ctx) },
                     {} };
        }
        return { {}, std::move(resp) };
    }

    std::pair<core_error_info, couchbase::operations::search_response> search_query(couchbase::operations::search_request request)
    {
        auto barrier = std::make_shared<std::promise<couchbase::operations::search_response>>();
        auto f = barrier->get_future();
        cluster_->execute(std::move(request),
                          [barrier](couchbase::operations::search_response&& resp) { barrier->set_value(std::move(resp)); });
        auto resp = f.get();
        if (resp.ctx.ec) {
            return { { resp.ctx.ec,
                       { __LINE__, __FILE__, __func__ },
                       fmt::format("unable to search query: {}, {}", resp.ctx.ec.value(), resp.ctx.ec.message()),
                       build_search_query_error_context(resp.ctx) },
                     {} };
        }
        return { {}, std::move(resp) };
    }

    std::pair<core_error_info, couchbase::operations::management::search_index_upsert_response> search_index_upsert(
      couchbase::operations::management::search_index_upsert_request request)
    {
        auto barrier = std::make_shared<std::promise<couchbase::operations::management::search_index_upsert_response>>();
        auto f = barrier->get_future();
        cluster_->execute(std::move(request), [barrier](couchbase::operations::management::search_index_upsert_response&& resp) {
            barrier->set_value(std::move(resp));
        });
        auto resp = f.get();
        if (resp.ctx.ec) {
            return { { resp.ctx.ec,
                       { __LINE__, __FILE__, __func__ },
                       fmt::format("unable to upsert search index: {}, {}", resp.ctx.ec.value(), resp.ctx.ec.message()),
                       build_http_error_context(resp.ctx) },
                     {} };
        }
        return { {}, std::move(resp) };
    }

    std::pair<core_error_info, couchbase::operations::management::view_index_upsert_response> view_index_upsert(
      couchbase::operations::management::view_index_upsert_request request)
    {
        auto barrier = std::make_shared<std::promise<couchbase::operations::management::view_index_upsert_response>>();
        auto f = barrier->get_future();
        cluster_->execute(std::move(request), [barrier](couchbase::operations::management::view_index_upsert_response&& resp) {
            barrier->set_value(std::move(resp));
        });
        auto resp = f.get();
        if (resp.ctx.ec) {
            return { { resp.ctx.ec,
                       { __LINE__, __FILE__, __func__ },
                       fmt::format("unable to upsert view index: {}, {}", resp.ctx.ec.value(), resp.ctx.ec.message()),
                       build_http_error_context(resp.ctx) },
                     {} };
        }
        return { {}, std::move(resp) };
    }

  private:
    asio::io_context ctx_{};
    std::shared_ptr<couchbase::cluster> cluster_{ couchbase::cluster::create(ctx_) };
    std::thread worker;
    origin origin_;
};

connection_handle::connection_handle(couchbase::origin origin, std::chrono::steady_clock::time_point idle_expiry)
  : idle_expiry_{ idle_expiry }
  , impl_{ std::make_shared<connection_handle::impl>(std::move(origin)) }
{

    impl_->start();
}

core_error_info
connection_handle::open()
{
    return impl_->open();
}

static std::string
cb_string_new(const zend_string* value)
{
    return { ZSTR_VAL(value), ZSTR_LEN(value) };
}

static std::string
cb_string_new(const zval* value)
{
    return { Z_STRVAL_P(value), Z_STRLEN_P(value) };
}

std::string
connection_handle::cluster_version(const zend_string* bucket_name)
{
    return impl_->cluster_version(cb_string_new(bucket_name));
}

core_error_info
connection_handle::bucket_open(const zend_string* name)
{
    return impl_->bucket_open(cb_string_new(name));
}

core_error_info
connection_handle::bucket_close(const zend_string* name)
{
    return impl_->bucket_close(cb_string_new(name));
}

template<typename Duration>
static core_error_info
cb_get_timeout(Duration& timeout, const zval* options)
{
    if (options == nullptr || Z_TYPE_P(options) == IS_NULL) {
        return {};
    }
    if (Z_TYPE_P(options) != IS_ARRAY) {
        return { error::common_errc::invalid_argument, { __LINE__, __FILE__, __func__ }, "expected array for options argument" };
    }

    const zval* value = zend_symtable_str_find(Z_ARRVAL_P(options), ZEND_STRL("timeoutMilliseconds"));
    if (value == nullptr) {
        return {};
    }
    switch (Z_TYPE_P(value)) {
        case IS_NULL:
            return {};
        case IS_LONG:
            break;
        default:
            return { error::common_errc::invalid_argument,
                     { __LINE__, __FILE__, __func__ },
                     "expected timeoutMilliseconds to be a number in the options" };
    }
    timeout = std::chrono::milliseconds(Z_LVAL_P(value));
    return {};
}

template<typename Request>
static core_error_info
cb_assign_timeout(Request& req, const zval* options)
{
    return cb_get_timeout(req.timeout, options);
}

struct durability_holder {
    protocol::durability_level durability_level{ protocol::durability_level::none };
    std::optional<std::uint16_t> durability_timeout{};
};

template<typename Request>
static core_error_info
cb_assign_durability(Request& req, const zval* options)
{
    if (options == nullptr || Z_TYPE_P(options) == IS_NULL) {
        return {};
    }
    if (Z_TYPE_P(options) != IS_ARRAY) {
        return { error::common_errc::invalid_argument, { __LINE__, __FILE__, __func__ }, "expected array for options argument" };
    }

    const zval* value = zend_symtable_str_find(Z_ARRVAL_P(options), ZEND_STRL("durabilityLevel"));
    if (value == nullptr) {
        return {};
    }
    switch (Z_TYPE_P(value)) {
        case IS_NULL:
            return {};
        case IS_STRING:
            break;
        default:
            return { error::common_errc::invalid_argument,
                     { __LINE__, __FILE__, __func__ },
                     "expected durabilityLevel to be a string in the options" };
    }
    if (zend_binary_strcmp(Z_STRVAL_P(value), Z_STRLEN_P(value), ZEND_STRL("none")) == 0) {
        req.durability_level = couchbase::protocol::durability_level::none;
    } else if (zend_binary_strcmp(Z_STRVAL_P(value), Z_STRLEN_P(value), ZEND_STRL("majority")) == 0) {
        req.durability_level = couchbase::protocol::durability_level::majority;
    } else if (zend_binary_strcmp(Z_STRVAL_P(value), Z_STRLEN_P(value), ZEND_STRL("majorityAndPersistToActive")) == 0) {
        req.durability_level = couchbase::protocol::durability_level::majority_and_persist_to_active;
    } else if (zend_binary_strcmp(Z_STRVAL_P(value), Z_STRLEN_P(value), ZEND_STRL("persistToMajority")) == 0) {
        req.durability_level = couchbase::protocol::durability_level::persist_to_majority;
    } else {
        return { error::common_errc::invalid_argument,
                 { __LINE__, __FILE__, __func__ },
                 fmt::format("unknown durabilityLevel: {}", std::string_view(Z_STRVAL_P(value), Z_STRLEN_P(value))) };
    }
    if (req.durability_level != couchbase::protocol::durability_level::none) {
        const zval* timeout = zend_symtable_str_find(Z_ARRVAL_P(options), ZEND_STRL("durabilityTimeoutSeconds"));
        if (timeout == nullptr) {
            return {};
        }
        switch (Z_TYPE_P(timeout)) {
            case IS_NULL:
                return {};
            case IS_LONG:
                break;
            default:
                return { error::common_errc::invalid_argument,
                         { __LINE__, __FILE__, __func__ },
                         "expected durabilityTimeoutSeconds to be a number in the options" };
        }
        req.durability_timeout = std::chrono::seconds(Z_LVAL_P(timeout)).count();
    }
    return {};
}

static inline core_error_info
cb_string_to_cas(const std::string& cas_string, protocol::cas& cas)
{
    try {
        std::uint64_t cas_value = std::stoull(cas_string, nullptr, 16);
        cas = protocol::cas{ cas_value };
    } catch (const std::invalid_argument&) {
        return { error::common_errc::invalid_argument,
                 { __LINE__, __FILE__, __func__ },
                 fmt::format("no numeric conversion could be performed for encoded CAS value: \"{}\"", cas_string) };
    } catch (const std::out_of_range&) {
        return { error::common_errc::invalid_argument,
                 { __LINE__, __FILE__, __func__ },
                 fmt::format("the number encoded as CAS is out of the range of representable values by a unsigned long long: \"{}\"",
                             cas_string) };
    }
    return {};
}

static core_error_info
cb_assign_cas(protocol::cas& cas, const zval* options)
{
    if (options == nullptr || Z_TYPE_P(options) == IS_NULL) {
        return {};
    }
    if (Z_TYPE_P(options) != IS_ARRAY) {
        return { error::common_errc::invalid_argument, { __LINE__, __FILE__, __func__ }, "expected array for options argument" };
    }

    const zval* value = zend_symtable_str_find(Z_ARRVAL_P(options), ZEND_STRL("cas"));
    if (value == nullptr) {
        return {};
    }
    switch (Z_TYPE_P(value)) {
        case IS_NULL:
            return {};
        case IS_STRING:
            break;
        default:
            return { error::common_errc::invalid_argument,
                     { __LINE__, __FILE__, __func__ },
                     "expected durabilityLevel to be a string in the options" };
    }
    cb_string_to_cas(std::string(Z_STRVAL_P(value), Z_STRLEN_P(value)), cas);
    return {};
}

template<typename Boolean>
static core_error_info
cb_assign_boolean(Boolean& field, const zval* options, std::string_view name)
{
    if (options == nullptr || Z_TYPE_P(options) == IS_NULL) {
        return {};
    }
    if (Z_TYPE_P(options) != IS_ARRAY) {
        return { error::common_errc::invalid_argument, { __LINE__, __FILE__, __func__ }, "expected array for options argument" };
    }

    const zval* value = zend_symtable_str_find(Z_ARRVAL_P(options), name.data(), name.size());
    if (value == nullptr) {
        return {};
    }
    switch (Z_TYPE_P(value)) {
        case IS_NULL:
            return {};
        case IS_TRUE:
            field = true;
            break;
        case IS_FALSE:
            field = false;
            break;
        default:
            return { error::common_errc::invalid_argument,
                     { __LINE__, __FILE__, __func__ },
                     fmt::format("expected {} to be a boolean value in the options", name) };
    }
    return {};
}

template<typename Integer>
static core_error_info
cb_assign_integer(Integer& field, const zval* options, std::string_view name)
{
    if (options == nullptr || Z_TYPE_P(options) == IS_NULL) {
        return {};
    }
    if (Z_TYPE_P(options) != IS_ARRAY) {
        return { error::common_errc::invalid_argument, { __LINE__, __FILE__, __func__ }, "expected array for options argument" };
    }

    const zval* value = zend_symtable_str_find(Z_ARRVAL_P(options), name.data(), name.size());
    if (value == nullptr) {
        return {};
    }
    switch (Z_TYPE_P(value)) {
        case IS_NULL:
            return {};
        case IS_LONG:
            break;
        default:
            return { error::common_errc::invalid_argument,
                     { __LINE__, __FILE__, __func__ },
                     fmt::format("expected {} to be a integer value in the options", name) };
    }

    field = Z_LVAL_P(value);
    return {};
}

template<typename String>
static core_error_info
cb_assign_string(String& field, const zval* options, std::string_view name)
{
    if (options == nullptr || Z_TYPE_P(options) == IS_NULL) {
        return {};
    }
    if (Z_TYPE_P(options) != IS_ARRAY) {
        return { error::common_errc::invalid_argument, { __LINE__, __FILE__, __func__ }, "expected array for options argument" };
    }

    const zval* value = zend_symtable_str_find(Z_ARRVAL_P(options), name.data(), name.size());
    if (value == nullptr) {
        return {};
    }
    switch (Z_TYPE_P(value)) {
        case IS_NULL:
            return {};
        case IS_STRING:
            break;
        default:
            return { error::common_errc::invalid_argument,
                     { __LINE__, __FILE__, __func__ },
                     fmt::format("expected {} to be a string value in the options", name) };
    }
    field = { Z_STRVAL_P(value), Z_STRLEN_P(value) };
    return {};
}

static core_error_info
cb_assign_vector_of_strings(std::vector<std::string>& field, const zval* options, std::string_view name)
{
    if (options == nullptr || Z_TYPE_P(options) == IS_NULL) {
        return {};
    }
    if (Z_TYPE_P(options) != IS_ARRAY) {
        return { error::common_errc::invalid_argument, { __LINE__, __FILE__, __func__ }, "expected array for options" };
    }

    const zval* value = zend_symtable_str_find(Z_ARRVAL_P(options), name.data(), name.size());
    if (value == nullptr || Z_TYPE_P(value) == IS_NULL) {
        return {};
    }
    if (Z_TYPE_P(value) != IS_ARRAY) {
        return { error::common_errc::invalid_argument,
                 { __LINE__, __FILE__, __func__ },
                 fmt::format("expected array for options argument \"{}\"", name) };
    }

    zval* item;
    ZEND_HASH_FOREACH_VAL(Z_ARRVAL_P(value), item)
    {
        if (Z_TYPE_P(item) != IS_STRING) {
            return { error::common_errc::invalid_argument,
                     { __LINE__, __FILE__, __func__ },
                     fmt::format("expected \"{}\" option to be an array of strings, detected non-string value", name) };
        }
        auto str = std::string({ Z_STRVAL_P(item), Z_STRLEN_P(item) });
        field.emplace_back(cb_string_new(item));
    }
    ZEND_HASH_FOREACH_END();
    return {};
}

template<typename Integer>
static std::pair<core_error_info, Integer>
cb_get_integer(const zval* options, std::string_view name)
{
    if (options == nullptr || Z_TYPE_P(options) == IS_NULL) {
        return {};
    }
    if (Z_TYPE_P(options) != IS_ARRAY) {
        return { { error::common_errc::invalid_argument, { __LINE__, __FILE__, __func__ }, "expected array for options argument" }, {} };
    }

    const zval* value = zend_symtable_str_find(Z_ARRVAL_P(options), name.data(), name.size());
    if (value == nullptr) {
        return {};
    }
    switch (Z_TYPE_P(value)) {
        case IS_NULL:
            return {};
        case IS_LONG:
            break;
        default:
            return { { error::common_errc::invalid_argument,
                       { __LINE__, __FILE__, __func__ },
                       fmt::format("expected {} to be a integer value in the options", name) },
                     {} };
    }

    return { {}, Z_LVAL_P(value) };
}

static inline void
mutation_token_to_zval(const couchbase::mutation_token& token, zval* return_value)
{
    array_init(return_value);
    add_assoc_stringl(return_value, "bucketName", token.bucket_name.data(), token.bucket_name.size());
    add_assoc_long(return_value, "partitionId", token.partition_id);
    auto val = fmt::format("{:x}", token.partition_uuid);
    add_assoc_stringl(return_value, "partitionUuid", val.data(), val.size());
    val = fmt::format("{:x}", token.sequence_number);
    add_assoc_stringl(return_value, "sequenceNumber", val.data(), val.size());
}

static inline bool
is_mutation_token_valid(const couchbase::mutation_token& token)
{
    return !token.bucket_name.empty() && token.partition_uuid > 0;
}

core_error_info
connection_handle::document_upsert(zval* return_value,
                                   const zend_string* bucket,
                                   const zend_string* scope,
                                   const zend_string* collection,
                                   const zend_string* id,
                                   const zend_string* value,
                                   zend_long flags,
                                   const zval* options)
{
    couchbase::document_id doc_id{
        cb_string_new(bucket),
        cb_string_new(scope),
        cb_string_new(collection),
        cb_string_new(id),
    };
    couchbase::operations::upsert_request request{ doc_id, cb_string_new(value) };
    request.flags = static_cast<std::uint32_t>(flags);
    if (auto e = cb_assign_timeout(request, options); e.ec) {
        return e;
    }
    if (auto e = cb_assign_durability(request, options); e.ec) {
        return e;
    }
    if (auto e = cb_assign_boolean(request.preserve_expiry, options, "preserveExpiry"); e.ec) {
        return e;
    }
    if (auto e = cb_assign_integer(request.expiry, options, "expirySeconds"); e.ec) {
        return e;
    }

    auto [resp, err] = impl_->key_value_execute(__func__, std::move(request));
    if (err.ec) {
        return err;
    }
    array_init(return_value);
    add_assoc_stringl(return_value, "id", resp.ctx.id.key().data(), resp.ctx.id.key().size());
    auto cas = fmt::format("{:x}", resp.cas.value);
    add_assoc_stringl(return_value, "cas", cas.data(), cas.size());
    if (is_mutation_token_valid(resp.token)) {
        zval token_val;
        mutation_token_to_zval(resp.token, &token_val);
        add_assoc_zval(return_value, "mutationToken", &token_val);
    }
    return {};
}

core_error_info
connection_handle::document_insert(zval* return_value,
                                   const zend_string* bucket,
                                   const zend_string* scope,
                                   const zend_string* collection,
                                   const zend_string* id,
                                   const zend_string* value,
                                   zend_long flags,
                                   const zval* options)
{
    couchbase::document_id doc_id{
        cb_string_new(bucket),
        cb_string_new(scope),
        cb_string_new(collection),
        cb_string_new(id),
    };
    couchbase::operations::insert_request request{ doc_id, cb_string_new(value) };
    request.flags = static_cast<std::uint32_t>(flags);
    if (auto e = cb_assign_timeout(request, options); e.ec) {
        return e;
    }
    if (auto e = cb_assign_durability(request, options); e.ec) {
        return e;
    }
    if (auto e = cb_assign_integer(request.expiry, options, "expirySeconds"); e.ec) {
        return e;
    }

    auto [resp, err] = impl_->key_value_execute(__func__, std::move(request));
    if (err.ec) {
        return err;
    }
    array_init(return_value);
    add_assoc_stringl(return_value, "id", resp.ctx.id.key().data(), resp.ctx.id.key().size());
    auto cas = fmt::format("{:x}", resp.cas.value);
    add_assoc_stringl(return_value, "cas", cas.data(), cas.size());
    if (is_mutation_token_valid(resp.token)) {
        zval token_val;
        mutation_token_to_zval(resp.token, &token_val);
        add_assoc_zval(return_value, "mutationToken", &token_val);
    }
    return {};
}

core_error_info
connection_handle::document_replace(zval* return_value,
                                    const zend_string* bucket,
                                    const zend_string* scope,
                                    const zend_string* collection,
                                    const zend_string* id,
                                    const zend_string* value,
                                    zend_long flags,
                                    const zval* options)
{
    couchbase::document_id doc_id{
        cb_string_new(bucket),
        cb_string_new(scope),
        cb_string_new(collection),
        cb_string_new(id),
    };
    couchbase::operations::replace_request request{ doc_id, cb_string_new(value) };
    request.flags = static_cast<std::uint32_t>(flags);
    if (auto e = cb_assign_timeout(request, options); e.ec) {
        return e;
    }
    if (auto e = cb_assign_durability(request, options); e.ec) {
        return e;
    }
    if (auto e = cb_assign_boolean(request.preserve_expiry, options, "preserveExpiry"); e.ec) {
        return e;
    }
    if (auto e = cb_assign_integer(request.expiry, options, "expirySeconds"); e.ec) {
        return e;
    }
    if (auto e = cb_assign_cas(request.cas, options); e.ec) {
        return e;
    }

    auto [resp, err] = impl_->key_value_execute(__func__, std::move(request));
    if (err.ec) {
        return err;
    }
    array_init(return_value);
    add_assoc_stringl(return_value, "id", resp.ctx.id.key().data(), resp.ctx.id.key().size());
    auto cas = fmt::format("{:x}", resp.cas.value);
    add_assoc_stringl(return_value, "cas", cas.data(), cas.size());
    if (is_mutation_token_valid(resp.token)) {
        zval token_val;
        mutation_token_to_zval(resp.token, &token_val);
        add_assoc_zval(return_value, "mutationToken", &token_val);
    }
    return {};
}

core_error_info
connection_handle::document_append(zval* return_value,
                                   const zend_string* bucket,
                                   const zend_string* scope,
                                   const zend_string* collection,
                                   const zend_string* id,
                                   const zend_string* value,
                                   const zval* options)
{
    couchbase::document_id doc_id{
        cb_string_new(bucket),
        cb_string_new(scope),
        cb_string_new(collection),
        cb_string_new(id),
    };
    couchbase::operations::append_request request{ doc_id, cb_string_new(value) };
    if (auto e = cb_assign_timeout(request, options); e.ec) {
        return e;
    }
    if (auto e = cb_assign_durability(request, options); e.ec) {
        return e;
    }

    auto [resp, err] = impl_->key_value_execute(__func__, std::move(request));
    if (err.ec) {
        return err;
    }
    array_init(return_value);
    add_assoc_stringl(return_value, "id", resp.ctx.id.key().data(), resp.ctx.id.key().size());
    auto cas = fmt::format("{:x}", resp.cas.value);
    add_assoc_stringl(return_value, "cas", cas.data(), cas.size());
    if (is_mutation_token_valid(resp.token)) {
        zval token_val;
        mutation_token_to_zval(resp.token, &token_val);
        add_assoc_zval(return_value, "mutationToken", &token_val);
    }
    return {};
}

core_error_info
connection_handle::document_prepend(zval* return_value,
                                    const zend_string* bucket,
                                    const zend_string* scope,
                                    const zend_string* collection,
                                    const zend_string* id,
                                    const zend_string* value,
                                    const zval* options)
{
    couchbase::document_id doc_id{
        cb_string_new(bucket),
        cb_string_new(scope),
        cb_string_new(collection),
        cb_string_new(id),
    };
    couchbase::operations::prepend_request request{ doc_id, cb_string_new(value) };
    if (auto e = cb_assign_timeout(request, options); e.ec) {
        return e;
    }
    if (auto e = cb_assign_durability(request, options); e.ec) {
        return e;
    }

    auto [resp, err] = impl_->key_value_execute(__func__, std::move(request));
    if (err.ec) {
        return err;
    }
    array_init(return_value);
    add_assoc_stringl(return_value, "id", resp.ctx.id.key().data(), resp.ctx.id.key().size());
    auto cas = fmt::format("{:x}", resp.cas.value);
    add_assoc_stringl(return_value, "cas", cas.data(), cas.size());
    if (is_mutation_token_valid(resp.token)) {
        zval token_val;
        mutation_token_to_zval(resp.token, &token_val);
        add_assoc_zval(return_value, "mutationToken", &token_val);
    }
    return {};
}

core_error_info
connection_handle::document_increment(zval* return_value,
                                      const zend_string* bucket,
                                      const zend_string* scope,
                                      const zend_string* collection,
                                      const zend_string* id,
                                      const zval* options)
{
    couchbase::document_id doc_id{
        cb_string_new(bucket),
        cb_string_new(scope),
        cb_string_new(collection),
        cb_string_new(id),
    };
    couchbase::operations::increment_request request{ doc_id };
    if (auto e = cb_assign_timeout(request, options); e.ec) {
        return e;
    }
    if (auto e = cb_assign_durability(request, options); e.ec) {
        return e;
    }
    if (auto e = cb_assign_integer(request.delta, options, "delta"); e.ec) {
        return e;
    }
    if (auto e = cb_assign_integer(request.initial_value, options, "initialValue"); e.ec) {
        return e;
    }

    auto [resp, err] = impl_->key_value_execute(__func__, std::move(request));
    if (err.ec) {
        return err;
    }
    array_init(return_value);
    add_assoc_stringl(return_value, "id", resp.ctx.id.key().data(), resp.ctx.id.key().size());
    add_assoc_long(return_value, "value", resp.content);
    auto value_str = fmt::format("{}", resp.content);
    add_assoc_stringl(return_value, "valueString", value_str.data(), value_str.size());
    auto cas = fmt::format("{:x}", resp.cas.value);
    add_assoc_stringl(return_value, "cas", cas.data(), cas.size());
    if (is_mutation_token_valid(resp.token)) {
        zval token_val;
        mutation_token_to_zval(resp.token, &token_val);
        add_assoc_zval(return_value, "mutationToken", &token_val);
    }
    return {};
}

core_error_info
connection_handle::document_decrement(zval* return_value,
                                      const zend_string* bucket,
                                      const zend_string* scope,
                                      const zend_string* collection,
                                      const zend_string* id,
                                      const zval* options)
{
    couchbase::document_id doc_id{
        cb_string_new(bucket),
        cb_string_new(scope),
        cb_string_new(collection),
        cb_string_new(id),
    };
    couchbase::operations::decrement_request request{ doc_id };
    if (auto e = cb_assign_timeout(request, options); e.ec) {
        return e;
    }
    if (auto e = cb_assign_durability(request, options); e.ec) {
        return e;
    }
    if (auto e = cb_assign_integer(request.delta, options, "delta"); e.ec) {
        return e;
    }
    if (auto e = cb_assign_integer(request.initial_value, options, "initialValue"); e.ec) {
        return e;
    }

    auto [resp, err] = impl_->key_value_execute(__func__, std::move(request));
    if (err.ec) {
        return err;
    }
    array_init(return_value);
    add_assoc_stringl(return_value, "id", resp.ctx.id.key().data(), resp.ctx.id.key().size());
    add_assoc_long(return_value, "value", resp.content);
    auto value_str = fmt::format("{}", resp.content);
    add_assoc_stringl(return_value, "valueString", value_str.data(), value_str.size());
    auto cas = fmt::format("{:x}", resp.cas.value);
    add_assoc_stringl(return_value, "cas", cas.data(), cas.size());
    if (is_mutation_token_valid(resp.token)) {
        zval token_val;
        mutation_token_to_zval(resp.token, &token_val);
        add_assoc_zval(return_value, "mutationToken", &token_val);
    }
    return {};
}

core_error_info
connection_handle::document_get(zval* return_value,
                                const zend_string* bucket,
                                const zend_string* scope,
                                const zend_string* collection,
                                const zend_string* id,
                                const zval* options)
{
    couchbase::document_id doc_id{
        cb_string_new(bucket),
        cb_string_new(scope),
        cb_string_new(collection),
        cb_string_new(id),
    };

    bool with_expiry = false;
    if (auto e = cb_assign_boolean(with_expiry, options, "withExpiry"); e.ec) {
        return e;
    }
    std::vector<std::string> projections{};
    if (auto e = cb_assign_vector_of_strings(projections, options, "projections"); e.ec) {
        return e;
    }
    if (!with_expiry && projections.empty()) {
        couchbase::operations::get_request request{ doc_id };
        if (auto e = cb_assign_timeout(request, options); e.ec) {
            return e;
        }

        auto [resp, err] = impl_->key_value_execute(__func__, std::move(request));
        if (err.ec) {
            return err;
        }
        array_init(return_value);
        add_assoc_stringl(return_value, "id", resp.ctx.id.key().data(), resp.ctx.id.key().size());
        auto cas = fmt::format("{:x}", resp.cas.value);
        add_assoc_stringl(return_value, "cas", cas.data(), cas.size());
        add_assoc_long(return_value, "flags", resp.flags);
        add_assoc_stringl(return_value, "value", resp.value.data(), resp.value.size());
        return {};
    }
    couchbase::operations::get_projected_request request{ doc_id };
    request.with_expiry = with_expiry;
    request.projections = projections;
    if (auto e = cb_assign_timeout(request, options); e.ec) {
        return e;
    }
    auto [resp, err] = impl_->key_value_execute(__func__, std::move(request));
    if (err.ec) {
        return err;
    }
    array_init(return_value);
    add_assoc_stringl(return_value, "id", resp.ctx.id.key().data(), resp.ctx.id.key().size());
    auto cas = fmt::format("{:x}", resp.cas.value);
    add_assoc_stringl(return_value, "cas", cas.data(), cas.size());
    add_assoc_long(return_value, "flags", resp.flags);
    add_assoc_stringl(return_value, "value", resp.value.data(), resp.value.size());
    if (resp.expiry) {
        add_assoc_long(return_value, "expiry", resp.expiry.value());
    }
    return {};
}

core_error_info
connection_handle::document_get_and_lock(zval* return_value,
                                         const zend_string* bucket,
                                         const zend_string* scope,
                                         const zend_string* collection,
                                         const zend_string* id,
                                         zend_long lock_time,
                                         const zval* options)
{
    couchbase::document_id doc_id{
        cb_string_new(bucket),
        cb_string_new(scope),
        cb_string_new(collection),
        cb_string_new(id),
    };

    couchbase::operations::get_and_lock_request request{ doc_id };
    if (auto e = cb_assign_timeout(request, options); e.ec) {
        return e;
    }
    request.lock_time = static_cast<std::uint32_t>(lock_time);

    auto [resp, err] = impl_->key_value_execute(__func__, std::move(request));
    if (err.ec) {
        return err;
    }
    array_init(return_value);
    add_assoc_stringl(return_value, "id", resp.ctx.id.key().data(), resp.ctx.id.key().size());
    auto cas = fmt::format("{:x}", resp.cas.value);
    add_assoc_stringl(return_value, "cas", cas.data(), cas.size());
    add_assoc_long(return_value, "flags", resp.flags);
    add_assoc_stringl(return_value, "value", resp.value.data(), resp.value.size());
    return {};
}

core_error_info
connection_handle::document_get_and_touch(zval* return_value,
                                          const zend_string* bucket,
                                          const zend_string* scope,
                                          const zend_string* collection,
                                          const zend_string* id,
                                          zend_long expiry,
                                          const zval* options)
{
    couchbase::document_id doc_id{
        cb_string_new(bucket),
        cb_string_new(scope),
        cb_string_new(collection),
        cb_string_new(id),
    };

    couchbase::operations::get_and_touch_request request{ doc_id };
    if (auto e = cb_assign_timeout(request, options); e.ec) {
        return e;
    }
    request.expiry = static_cast<std::uint32_t>(expiry);

    auto [resp, err] = impl_->key_value_execute(__func__, std::move(request));
    if (err.ec) {
        return err;
    }
    array_init(return_value);
    add_assoc_stringl(return_value, "id", resp.ctx.id.key().data(), resp.ctx.id.key().size());
    auto cas = fmt::format("{:x}", resp.cas.value);
    add_assoc_stringl(return_value, "cas", cas.data(), cas.size());
    add_assoc_long(return_value, "flags", resp.flags);
    add_assoc_stringl(return_value, "value", resp.value.data(), resp.value.size());
    return {};
}

core_error_info
connection_handle::document_touch(zval* return_value,
                                  const zend_string* bucket,
                                  const zend_string* scope,
                                  const zend_string* collection,
                                  const zend_string* id,
                                  zend_long expiry,
                                  const zval* options)
{
    couchbase::document_id doc_id{
        cb_string_new(bucket),
        cb_string_new(scope),
        cb_string_new(collection),
        cb_string_new(id),
    };

    couchbase::operations::touch_request request{ doc_id };
    if (auto e = cb_assign_timeout(request, options); e.ec) {
        return e;
    }
    request.expiry = static_cast<std::uint32_t>(expiry);

    auto [resp, err] = impl_->key_value_execute(__func__, std::move(request));
    if (err.ec) {
        return err;
    }
    array_init(return_value);
    add_assoc_stringl(return_value, "id", resp.ctx.id.key().data(), resp.ctx.id.key().size());
    auto cas = fmt::format("{:x}", resp.cas.value);
    add_assoc_stringl(return_value, "cas", cas.data(), cas.size());
    return {};
}

core_error_info
connection_handle::document_unlock(zval* return_value,
                                   const zend_string* bucket,
                                   const zend_string* scope,
                                   const zend_string* collection,
                                   const zend_string* id,
                                   const zend_string* locked_cas,
                                   const zval* options)
{
    couchbase::document_id doc_id{
        cb_string_new(bucket),
        cb_string_new(scope),
        cb_string_new(collection),
        cb_string_new(id),
    };

    couchbase::operations::unlock_request request{ doc_id };
    if (auto e = cb_assign_timeout(request, options); e.ec) {
        return e;
    }
    if (auto e = cb_string_to_cas(std::string(ZSTR_VAL(locked_cas), ZSTR_LEN(locked_cas)), request.cas); e.ec) {
        return e;
    }

    auto [resp, err] = impl_->key_value_execute(__func__, std::move(request));
    if (err.ec) {
        return err;
    }
    array_init(return_value);
    add_assoc_stringl(return_value, "id", resp.ctx.id.key().data(), resp.ctx.id.key().size());
    auto cas = fmt::format("{:x}", resp.cas.value);
    add_assoc_stringl(return_value, "cas", cas.data(), cas.size());
    return {};
}

core_error_info
connection_handle::document_remove(zval* return_value,
                                   const zend_string* bucket,
                                   const zend_string* scope,
                                   const zend_string* collection,
                                   const zend_string* id,
                                   const zval* options)
{
    couchbase::document_id doc_id{
        cb_string_new(bucket),
        cb_string_new(scope),
        cb_string_new(collection),
        cb_string_new(id),
    };

    couchbase::operations::remove_request request{ doc_id };
    if (auto e = cb_assign_timeout(request, options); e.ec) {
        return e;
    }
    if (auto e = cb_assign_cas(request.cas, options); e.ec) {
        return e;
    }
    if (auto e = cb_assign_durability(request, options); e.ec) {
        return e;
    }
    auto [resp, err] = impl_->key_value_execute(__func__, std::move(request));
    if (err.ec) {
        return err;
    }
    array_init(return_value);
    add_assoc_stringl(return_value, "id", resp.ctx.id.key().data(), resp.ctx.id.key().size());
    auto cas = fmt::format("{:x}", resp.cas.value);
    add_assoc_stringl(return_value, "cas", cas.data(), cas.size());
    if (is_mutation_token_valid(resp.token)) {
        zval token_val;
        mutation_token_to_zval(resp.token, &token_val);
        add_assoc_zval(return_value, "mutationToken", &token_val);
    }
    return {};
}

core_error_info
connection_handle::document_exists(zval* return_value,
                                   const zend_string* bucket,
                                   const zend_string* scope,
                                   const zend_string* collection,
                                   const zend_string* id,
                                   const zval* options)
{
    couchbase::document_id doc_id{
        cb_string_new(bucket),
        cb_string_new(scope),
        cb_string_new(collection),
        cb_string_new(id),
    };

    couchbase::operations::exists_request request{ doc_id };
    if (auto e = cb_assign_timeout(request, options); e.ec) {
        return e;
    }
    auto [resp, err] = impl_->key_value_execute(__func__, std::move(request));
    if (err.ec && resp.ctx.ec != error::key_value_errc::document_not_found) {
        return err;
    }
    array_init(return_value);
    add_assoc_stringl(return_value, "id", resp.ctx.id.key().data(), resp.ctx.id.key().size());
    add_assoc_bool(return_value, "exists", resp.exists());
    add_assoc_bool(return_value, "deleted", resp.deleted);
    auto cas = fmt::format("{:x}", resp.cas.value);
    add_assoc_stringl(return_value, "cas", cas.data(), cas.size());
    add_assoc_long(return_value, "flags", resp.flags);
    add_assoc_long(return_value, "datatype", resp.datatype);
    add_assoc_long(return_value, "expiry", resp.expiry);
    auto sequence_number = fmt::format("{:x}", resp.sequence_number);
    add_assoc_stringl(return_value, "sequenceNumber", sequence_number.data(), sequence_number.size());
    return {};
}

core_error_info
connection_handle::document_mutate_in(zval* return_value,
                                      const zend_string* bucket,
                                      const zend_string* scope,
                                      const zend_string* collection,
                                      const zend_string* id,
                                      const zval* specs,
                                      const zval* options)
{
    couchbase::document_id doc_id{
        cb_string_new(bucket),
        cb_string_new(scope),
        cb_string_new(collection),
        cb_string_new(id),
    };

    couchbase::operations::mutate_in_request request{ doc_id };
    if (auto e = cb_assign_timeout(request, options); e.ec) {
        return e;
    }
    if (auto e = cb_assign_durability(request, options); e.ec) {
        return e;
    }
    if (auto e = cb_assign_boolean(request.access_deleted, options, "accessDeleted"); e.ec) {
        return e;
    }
    if (auto e = cb_assign_boolean(request.create_as_deleted, options, "createAsDeleted"); e.ec) {
        return e;
    }
    if (auto e = cb_assign_integer(request.expiry, options, "expirySeconds"); e.ec) {
        return e;
    }
    if (auto e = cb_assign_cas(request.cas, options); e.ec) {
        return e;
    }
    std::string store_semantics;
    if (auto e = cb_assign_string(store_semantics, options, "storeSemantics"); e.ec) {
        return e;
    }
    if (store_semantics == "replace") {
        request.store_semantics = protocol::mutate_in_request_body::store_semantics_type::replace;
    } else if (store_semantics == "insert") {
        request.store_semantics = protocol::mutate_in_request_body::store_semantics_type::insert;
    } else if (store_semantics == "upsert") {
        request.store_semantics = protocol::mutate_in_request_body::store_semantics_type::upsert;
    } else if (!store_semantics.empty()) {
        return { error::common_errc::invalid_argument, { __LINE__, __FILE__, __func__ }, "unexpected value for storeSemantics option" };
    }

    if (Z_TYPE_P(specs) == IS_ARRAY) {
        const zval* item = nullptr;
        ZEND_HASH_FOREACH_VAL(Z_ARRVAL_P(specs), item)
        {
            auto [operation, e] = decode_mutation_subdoc_opcode(item);
            if (e.ec) {
                return e;
            }
            bool xattr = false;
            if (e = cb_assign_boolean(xattr, item, "isXattr"); e.ec) {
                return e;
            }
            bool create_parents = false;
            if (e = cb_assign_boolean(create_parents, item, "createParents"); e.ec) {
                return e;
            }
            bool expand_macros = false;
            if (e = cb_assign_boolean(expand_macros, item, "expandMacros"); e.ec) {
                return e;
            }
            std::string path;
            if (e = cb_assign_string(path, item, "path"); e.ec) {
                return e;
            }
            switch (operation) {
                case protocol::subdoc_opcode::counter: {
                    std::int64_t delta = 0;
                    if (e = cb_assign_integer(delta, item, "value"); e.ec) {
                        return e;
                    }
                    request.specs.add_spec(operation, xattr, create_parents, expand_macros, path, delta);
                } break;
                case protocol::subdoc_opcode::remove:
                case protocol::subdoc_opcode::remove_doc: {
                    request.specs.add_spec(operation, xattr, path);
                } break;
                default: {
                    std::string param;
                    if (e = cb_assign_string(param, item, "value"); e.ec) {
                        return e;
                    }
                    request.specs.add_spec(operation, xattr, create_parents, expand_macros, path, param);
                }
            }
        }
        ZEND_HASH_FOREACH_END();
    } else {
        return { error::common_errc::invalid_argument, { __LINE__, __FILE__, __func__ }, "specs must be an array" };
    }

    auto [resp, err] = impl_->key_value_execute(__func__, std::move(request));
    if (err.ec) {
        return err;
    }
    array_init(return_value);
    add_assoc_stringl(return_value, "id", resp.ctx.id.key().data(), resp.ctx.id.key().size());
    add_assoc_bool(return_value, "deleted", resp.deleted);
    auto cas = fmt::format("{:x}", resp.cas.value);
    add_assoc_stringl(return_value, "cas", cas.data(), cas.size());
    if (is_mutation_token_valid(resp.token)) {
        zval token_val;
        mutation_token_to_zval(resp.token, &token_val);
        add_assoc_zval(return_value, "mutationToken", &token_val);
    }
    if (resp.first_error_index) {
        add_assoc_long(return_value, "firstErrorIndex", resp.first_error_index.value());
    }
    zval fields;
    array_init_size(&fields, resp.fields.size());
    for (const auto& field : resp.fields) {
        zval entry;
        array_init(&entry);
        add_assoc_long(&entry, "originalIndex", field.original_index);
        add_assoc_stringl(&entry, "path", field.path.data(), field.path.size());
        add_assoc_stringl(&entry, "value", field.value.data(), field.value.size());
        add_assoc_string(&entry, "opcode", subdoc_opcode_to_string(field.opcode));
        add_assoc_long(&entry, "status", std::uint16_t(field.status));
        if (field.ec) {
            zval ex;
            create_exception(&ex,
                             { field.ec,
                               { __LINE__, __FILE__, __func__ },
                               fmt::format(R"(mutateIn operation "{}" for path "{}" failed: ec={} ({}), status={}, index={})",
                                           subdoc_opcode_to_string(field.opcode),
                                           field.path,
                                           field.ec.value(),
                                           field.ec.message(),
                                           field.status,
                                           field.original_index),
                               build_error_context(resp.ctx) });
            add_assoc_zval(&entry, "error", &ex);
        }
        add_next_index_zval(&fields, &entry);
    }
    add_assoc_zval(return_value, "fields", &fields);
    return {};
}

core_error_info
connection_handle::document_lookup_in(zval* return_value,
                                      const zend_string* bucket,
                                      const zend_string* scope,
                                      const zend_string* collection,
                                      const zend_string* id,
                                      const zval* specs,
                                      const zval* options)
{
    couchbase::document_id doc_id{
        cb_string_new(bucket),
        cb_string_new(scope),
        cb_string_new(collection),
        cb_string_new(id),
    };

    couchbase::operations::lookup_in_request request{ doc_id };
    if (auto e = cb_assign_timeout(request, options); e.ec) {
        return e;
    }
    if (auto e = cb_assign_boolean(request.access_deleted, options, "accessDeleted"); e.ec) {
        return e;
    }

    if (Z_TYPE_P(specs) == IS_ARRAY) {
        const zval* item = nullptr;
        ZEND_HASH_FOREACH_VAL(Z_ARRVAL_P(specs), item)
        {
            auto [operation, e] = decode_lookup_subdoc_opcode(item);
            if (e.ec) {
                return e;
            }
            bool xattr = false;
            if (e = cb_assign_boolean(xattr, item, "isXattr"); e.ec) {
                return e;
            }
            std::string path;
            if (e = cb_assign_string(path, item, "path"); e.ec) {
                return e;
            }
            request.specs.add_spec(operation, xattr, path);
        }
        ZEND_HASH_FOREACH_END();
    } else {
        return { error::common_errc::invalid_argument, { __LINE__, __FILE__, __func__ }, "specs must be an array" };
    }

    auto [resp, err] = impl_->key_value_execute(__func__, std::move(request));
    if (err.ec && resp.ctx.ec != error::key_value_errc::document_not_found) {
        return err;
    }
    array_init(return_value);
    add_assoc_stringl(return_value, "id", resp.ctx.id.key().data(), resp.ctx.id.key().size());
    add_assoc_bool(return_value, "deleted", resp.deleted);
    auto cas = fmt::format("{:x}", resp.cas.value);
    add_assoc_stringl(return_value, "cas", cas.data(), cas.size());
    zval fields;
    array_init_size(&fields, resp.fields.size());
    for (const auto& field : resp.fields) {
        zval entry;
        array_init(&entry);
        add_assoc_long(&entry, "originalIndex", field.original_index);
        add_assoc_stringl(&entry, "path", field.path.data(), field.path.size());
        add_assoc_stringl(&entry, "value", field.value.data(), field.value.size());
        add_assoc_bool(&entry, "exists", field.exists);
        add_assoc_string(&entry, "opcode", subdoc_opcode_to_string(field.opcode));
        add_assoc_long(&entry, "status", std::uint16_t(field.status));
        if (field.ec) {
            zval ex;
            create_exception(&ex,
                             { field.ec,
                               { __LINE__, __FILE__, __func__ },
                               fmt::format(R"(mutateIn operation "{}" for path "{}" failed: ec={} ({}), status={}, index={})",
                                           subdoc_opcode_to_string(field.opcode),
                                           field.path,
                                           field.ec.value(),
                                           field.ec.message(),
                                           field.status,
                                           field.original_index),
                               build_error_context(resp.ctx) });
            add_assoc_zval(&entry, "error", &ex);
        }
        add_next_index_zval(&fields, &entry);
    }
    add_assoc_zval(return_value, "fields", &fields);
    return {};
}

core_error_info
connection_handle::document_get_multi(zval* return_value,
                                      const zend_string* bucket,
                                      const zend_string* scope,
                                      const zend_string* collection,
                                      const zval* ids,
                                      const zval* options)
{
    if (Z_TYPE_P(ids) != IS_ARRAY) {
        return { error::common_errc::invalid_argument, { __LINE__, __FILE__, __func__ }, "expected ids to be an array" };
    }

    std::optional<std::chrono::milliseconds> timeout;
    if (auto e = cb_get_timeout(timeout, options); e.ec) {
        return e;
    }

    std::vector<couchbase::operations::get_request> requests;
    requests.reserve(zend_array_count(Z_ARRVAL_P(ids)));
    const zval* id = nullptr;
    ZEND_HASH_FOREACH_VAL(Z_ARRVAL_P(ids), id)
    {
        couchbase::document_id doc_id{
            cb_string_new(bucket),
            cb_string_new(scope),
            cb_string_new(collection),
            cb_string_new(id),
        };

        couchbase::operations::get_request request{ doc_id };
        request.timeout = timeout;
        requests.emplace_back(request);
    }
    ZEND_HASH_FOREACH_END();

    auto responses = impl_->key_value_execute_multi(std::move(requests));
    array_init(return_value);
    for (const auto& resp : responses) {
        zval entry;
        array_init(&entry);
        add_assoc_stringl(&entry, "id", resp.ctx.id.key().data(), resp.ctx.id.key().size());
        if (resp.ctx.ec) {
            zval ex;
            create_exception(
              &ex,
              { resp.ctx.ec,
                { __LINE__, __FILE__, __func__ },
                fmt::format(R"(unable to execute KV operation "{}": ec={} ({}))", __func__, resp.ctx.ec.value(), resp.ctx.ec.message()),
                build_error_context(resp.ctx) });
            add_assoc_zval(&entry, "error", &ex);
        }
        auto cas = fmt::format("{:x}", resp.cas.value);
        add_assoc_stringl(&entry, "cas", cas.data(), cas.size());
        add_assoc_long(&entry, "flags", resp.flags);
        add_assoc_stringl(&entry, "value", resp.value.data(), resp.value.size());
        add_next_index_zval(return_value, &entry);
    }
    return {};
}

core_error_info
connection_handle::document_remove_multi(zval* return_value,
                                         const zend_string* bucket,
                                         const zend_string* scope,
                                         const zend_string* collection,
                                         const zval* entries,
                                         const zval* options)
{
    if (Z_TYPE_P(entries) != IS_ARRAY) {
        return { error::common_errc::invalid_argument, { __LINE__, __FILE__, __func__ }, "expected entries to be an array" };
    }

    std::optional<std::chrono::milliseconds> timeout;
    if (auto e = cb_get_timeout(timeout, options); e.ec) {
        return e;
    }
    durability_holder durability{};
    if (auto e = cb_assign_durability(durability, options); e.ec) {
        return e;
    }

    std::vector<couchbase::operations::remove_request> requests;
    requests.reserve(zend_array_count(Z_ARRVAL_P(entries)));
    const zval* tuple = nullptr;
    ZEND_HASH_FOREACH_VAL(Z_ARRVAL_P(entries), tuple)
    {
        switch (Z_TYPE_P(tuple)) {
            case IS_STRING: {
                couchbase::document_id doc_id{
                    cb_string_new(bucket),
                    cb_string_new(scope),
                    cb_string_new(collection),
                    cb_string_new(tuple),
                };
                couchbase::operations::remove_request request{ doc_id };
                request.timeout = timeout;
                request.durability_level = durability.durability_level;
                request.durability_timeout = durability.durability_timeout;
                requests.emplace_back(request);
            } break;
            case IS_ARRAY: {
                if (zend_array_count(Z_ARRVAL_P(tuple)) != 2) {
                    return { error::common_errc::invalid_argument,
                             { __LINE__, __FILE__, __func__ },
                             "expected that removeMulti ID-CAS tuples be represented by arrays with exactly two entries" };
                }
                const zval* id = zend_hash_index_find(Z_ARRVAL_P(tuple), 0);
                if (id == nullptr || Z_TYPE_P(id) != IS_STRING) {
                    return { error::common_errc::invalid_argument,
                             { __LINE__, __FILE__, __func__ },
                             "expected that removeMulti first member (ID) of ID-CAS tuple be a string" };
                }
                const zval* cas = zend_hash_index_find(Z_ARRVAL_P(tuple), 1);
                if (cas == nullptr || Z_TYPE_P(cas) != IS_STRING) {
                    return { error::common_errc::invalid_argument,
                             { __LINE__, __FILE__, __func__ },
                             "expected that removeMulti second member (CAS) of ID-CAS tuple be a string" };
                }
                couchbase::document_id doc_id{
                    cb_string_new(bucket),
                    cb_string_new(scope),
                    cb_string_new(collection),
                    cb_string_new(id),
                };
                couchbase::operations::remove_request request{ doc_id };
                request.timeout = timeout;
                request.durability_level = durability.durability_level;
                request.durability_timeout = durability.durability_timeout;
                if (auto e = cb_string_to_cas(std::string(Z_STRVAL_P(cas), Z_STRLEN_P(cas)), request.cas); e.ec) {
                    return e;
                }
                requests.emplace_back(request);
            } break;
            default:
                return { error::common_errc::invalid_argument,
                         { __LINE__, __FILE__, __func__ },
                         "expected that removeMulti entries will be either ID strings or pairs of ID with CAS" };
                break;
        }
    }
    ZEND_HASH_FOREACH_END();

    auto responses = impl_->key_value_execute_multi(std::move(requests));
    array_init(return_value);
    for (const auto& resp : responses) {
        zval entry;
        array_init(&entry);
        add_assoc_stringl(&entry, "id", resp.ctx.id.key().data(), resp.ctx.id.key().size());
        if (resp.ctx.ec) {
            zval ex;
            create_exception(
              &ex,
              { resp.ctx.ec,
                { __LINE__, __FILE__, __func__ },
                fmt::format(R"(unable to execute KV operation "{}": ec={} ({}))", __func__, resp.ctx.ec.value(), resp.ctx.ec.message()),
                build_error_context(resp.ctx) });
            add_assoc_zval(&entry, "error", &ex);
        }
        auto cas = fmt::format("{:x}", resp.cas.value);
        add_assoc_stringl(&entry, "cas", cas.data(), cas.size());
        if (is_mutation_token_valid(resp.token)) {
            zval token_val;
            mutation_token_to_zval(resp.token, &token_val);
            add_assoc_zval(&entry, "mutationToken", &token_val);
        }
        add_next_index_zval(return_value, &entry);
    }
    return {};
}

core_error_info
connection_handle::document_upsert_multi(zval* return_value,
                                         const zend_string* bucket,
                                         const zend_string* scope,
                                         const zend_string* collection,
                                         const zval* entries,
                                         const zval* options)
{
    if (Z_TYPE_P(entries) != IS_ARRAY) {
        return { error::common_errc::invalid_argument, { __LINE__, __FILE__, __func__ }, "expected entries to be an array" };
    }
    std::optional<std::chrono::milliseconds> timeout;
    if (auto e = cb_get_timeout(timeout, options); e.ec) {
        return e;
    }
    durability_holder durability{};
    if (auto e = cb_assign_durability(durability, options); e.ec) {
        return e;
    }
    bool preserve_expiry{ false };
    if (auto e = cb_assign_boolean(preserve_expiry, options, "preserveExpiry"); e.ec) {
        return e;
    }

    std::vector<couchbase::operations::upsert_request> requests;
    requests.reserve(zend_array_count(Z_ARRVAL_P(entries)));
    const zval* tuple = nullptr;
    ZEND_HASH_FOREACH_VAL(Z_ARRVAL_P(entries), tuple)
    {
        if (Z_TYPE_P(tuple) != IS_ARRAY || zend_array_count(Z_ARRVAL_P(tuple)) != 3) {
            return { error::common_errc::invalid_argument,
                     { __LINE__, __FILE__, __func__ },
                     "expected that core upsertMulti entries will be ID-VALUE-FLAGS tuples" };
        }
        const zval* id = zend_hash_index_find(Z_ARRVAL_P(tuple), 0);
        if (id == nullptr || Z_TYPE_P(id) != IS_STRING) {
            return { error::common_errc::invalid_argument,
                     { __LINE__, __FILE__, __func__ },
                     "expected that core upsertMulti first member (ID) of ID-VALUE-FLAGS tuple be a string" };
        }
        const zval* value = zend_hash_index_find(Z_ARRVAL_P(tuple), 1);
        if (value == nullptr || Z_TYPE_P(value) != IS_STRING) {
            return { error::common_errc::invalid_argument,
                     { __LINE__, __FILE__, __func__ },
                     "expected that core upsertMulti second member (CAS) of ID-VALUE-FLAGS tuple be a string" };
        }
        const zval* flags = zend_hash_index_find(Z_ARRVAL_P(tuple), 2);
        if (flags == nullptr || Z_TYPE_P(flags) != IS_LONG) {
            return { error::common_errc::invalid_argument,
                     { __LINE__, __FILE__, __func__ },
                     "expected that core upsertMulti third member (FLAGS) of ID-VALUE-FLAGS tuple be an integer" };
        }
        couchbase::document_id doc_id{
            cb_string_new(bucket),
            cb_string_new(scope),
            cb_string_new(collection),
            cb_string_new(id),
        };
        couchbase::operations::upsert_request request{ doc_id, cb_string_new(value) };
        request.timeout = timeout;
        request.flags = static_cast<std::uint32_t>(Z_LVAL_P(flags));
        request.durability_level = durability.durability_level;
        request.durability_timeout = durability.durability_timeout;
        request.preserve_expiry = preserve_expiry;
        requests.emplace_back(request);
    }
    ZEND_HASH_FOREACH_END();

    auto responses = impl_->key_value_execute_multi(std::move(requests));
    array_init(return_value);
    for (const auto& resp : responses) {
        zval entry;
        array_init(&entry);
        add_assoc_stringl(&entry, "id", resp.ctx.id.key().data(), resp.ctx.id.key().size());
        if (resp.ctx.ec) {
            zval ex;
            create_exception(
              &ex,
              { resp.ctx.ec,
                { __LINE__, __FILE__, __func__ },
                fmt::format(R"(unable to execute KV operation "{}": ec={} ({}))", __func__, resp.ctx.ec.value(), resp.ctx.ec.message()),
                build_error_context(resp.ctx) });
            add_assoc_zval(&entry, "error", &ex);
        }
        auto cas = fmt::format("{:x}", resp.cas.value);
        add_assoc_stringl(&entry, "cas", cas.data(), cas.size());
        if (is_mutation_token_valid(resp.token)) {
            zval token_val;
            mutation_token_to_zval(resp.token, &token_val);
            add_assoc_zval(&entry, "mutationToken", &token_val);
        }
        add_next_index_zval(return_value, &entry);
    }
    return {};
}

std::pair<zval*, core_error_info>
connection_handle::query(const zend_string* statement, const zval* options)
{
    couchbase::operations::query_request request{ cb_string_new(statement) };
    if (auto e = cb_assign_timeout(request, options); e.ec) {
        return { nullptr, e };
    }
    if (auto [e, scan_consistency] = cb_get_integer<uint64_t>(options, "scanConsistency"); !e.ec) {
        switch (scan_consistency) {
            case 1:
                request.scan_consistency = couchbase::operations::query_request::scan_consistency_type::not_bounded;
                break;

            case 2:
                request.scan_consistency = couchbase::operations::query_request::scan_consistency_type::request_plus;
                break;

            default:
                if (scan_consistency > 0) {
                    return { nullptr,
                             { error::common_errc::invalid_argument,
                               { __LINE__, __FILE__, __func__ },
                               fmt::format("invalid value used for scan consistency: {}", scan_consistency) } };
                }
        }
    } else {
        return { nullptr, e };
    }
    if (auto e = cb_assign_integer(request.scan_cap, options, "scanCap"); e.ec) {
        return { nullptr, e };
    }
    if (auto e = cb_assign_integer(request.pipeline_cap, options, "pipelineCap"); e.ec) {
        return { nullptr, e };
    }
    if (auto e = cb_assign_integer(request.pipeline_batch, options, "pipelineBatch"); e.ec) {
        return { nullptr, e };
    }
    if (auto e = cb_assign_integer(request.max_parallelism, options, "maxParallelism"); e.ec) {
        return { nullptr, e };
    }
    if (auto [e, profile] = cb_get_integer<uint64_t>(options, "profile"); !e.ec) {
        switch (profile) {
            case 1:
                request.profile = couchbase::operations::query_request::profile_mode::off;
                break;

            case 2:
                request.profile = couchbase::operations::query_request::profile_mode::phases;
                break;

            case 3:
                request.profile = couchbase::operations::query_request::profile_mode::timings;
                break;

            default:
                if (profile > 0) {
                    return { nullptr,
                             { error::common_errc::invalid_argument,
                               { __LINE__, __FILE__, __func__ },
                               fmt::format("invalid value used for profile: {}", profile) } };
                }
        }
    } else {
        return { nullptr, e };
    }

    if (auto e = cb_assign_boolean(request.readonly, options, "readonly"); e.ec) {
        return { nullptr, e };
    }
    if (auto e = cb_assign_boolean(request.flex_index, options, "flexIndex"); e.ec) {
        return { nullptr, e };
    }
    if (auto e = cb_assign_boolean(request.adhoc, options, "adHoc"); e.ec) {
        return { nullptr, e };
    }
    if (const zval* value = zend_symtable_str_find(Z_ARRVAL_P(options), ZEND_STRL("positionalParameters"));
        value != nullptr && Z_TYPE_P(value) == IS_ARRAY) {
        std::vector<couchbase::json_string> params{};
        const zval* item = nullptr;

        ZEND_HASH_FOREACH_VAL(Z_ARRVAL_P(value), item)
        {
            auto str = std::string({ Z_STRVAL_P(item), Z_STRLEN_P(item) });
            params.emplace_back(std::move(str));
        }
        ZEND_HASH_FOREACH_END();

        request.positional_parameters = params;
    }
    if (const zval* value = zend_symtable_str_find(Z_ARRVAL_P(options), ZEND_STRL("namedParameters"));
        value != nullptr && Z_TYPE_P(value) == IS_ARRAY) {
        std::map<std::string, couchbase::json_string> params{};
        const zend_string* key = nullptr;
        const zval* item = nullptr;

        ZEND_HASH_FOREACH_STR_KEY_VAL(Z_ARRVAL_P(value), key, item)
        {
            params[cb_string_new(key)] = std::string({ Z_STRVAL_P(item), Z_STRLEN_P(item) });
        }
        ZEND_HASH_FOREACH_END();

        request.named_parameters = params;
    }
    if (const zval* value = zend_symtable_str_find(Z_ARRVAL_P(options), ZEND_STRL("raw"));
        value != nullptr && Z_TYPE_P(value) == IS_ARRAY) {
        std::map<std::string, couchbase::json_string> params{};
        const zend_string* key = nullptr;
        const zval* item = nullptr;

        ZEND_HASH_FOREACH_STR_KEY_VAL(Z_ARRVAL_P(value), key, item)
        {
            params[cb_string_new(key)] = std::string({ Z_STRVAL_P(item), Z_STRLEN_P(item) });
        }
        ZEND_HASH_FOREACH_END();

        request.raw = params;
    }
    if (const zval* value = zend_symtable_str_find(Z_ARRVAL_P(options), ZEND_STRL("consistentWith"));
        value != nullptr && Z_TYPE_P(value) == IS_ARRAY) {
        std::vector<couchbase::mutation_token> vectors{};
        const zval* item = nullptr;

        ZEND_HASH_FOREACH_VAL(Z_ARRVAL_P(value), item)
        {
            couchbase::mutation_token token{};
            if (auto e = cb_assign_integer(token.partition_id, item, "partitionId"); e.ec) {
                return { nullptr, e };
            }
            if (auto e = cb_assign_integer(token.partition_uuid, item, "partitionUuid"); e.ec) {
                return { nullptr, e };
            }
            if (auto e = cb_assign_integer(token.sequence_number, item, "sequenceNumber"); e.ec) {
                return { nullptr, e };
            }
            if (auto e = cb_assign_string(token.bucket_name, item, "bucketName"); e.ec) {
                return { nullptr, e };
            }
            vectors.emplace_back(token);
        }
        ZEND_HASH_FOREACH_END();

        request.mutation_state = vectors;
    }
    if (auto e = cb_assign_string(request.client_context_id, options, "clientContextId"); e.ec) {
        return { nullptr, e };
    }
    if (auto e = cb_assign_boolean(request.metrics, options, "metrics"); e.ec) {
        return { nullptr, e };
    }
    if (auto e = cb_assign_boolean(request.preserve_expiry, options, "preserveExpiry"); e.ec) {
        return { nullptr, e };
    }
    if (auto e = cb_assign_string(request.scope_name, options, "scopeName"); e.ec) {
        return { nullptr, e };
    }
    if (auto e = cb_assign_string(request.bucket_name, options, "bucketName"); e.ec) {
        return { nullptr, e };
    }

    auto [err, resp] = impl_->query(std::move(request));
    if (err.ec) {
        return { nullptr, err };
    }

    zval retval;
    array_init(&retval);
    add_assoc_string(&retval, "servedByNode", resp.served_by_node.c_str());

    zval rows;
    array_init(&rows);
    for (const auto& row : resp.rows) {
        add_next_index_string(&rows, row.c_str());
    }
    add_assoc_zval(&retval, "rows", &rows);

    zval meta;
    array_init(&meta);
    add_assoc_string(&meta, "clientContextId", resp.meta.client_context_id.c_str());
    add_assoc_string(&meta, "requestId", resp.meta.request_id.c_str());
    add_assoc_string(&meta, "status", resp.meta.status.c_str());
    if (resp.meta.profile.has_value()) {
        add_assoc_string(&meta, "profile", resp.meta.profile.value().c_str());
    }
    if (resp.meta.signature.has_value()) {
        add_assoc_string(&meta, "signature", resp.meta.signature.value().c_str());
    }
    if (resp.meta.metrics.has_value()) {
        zval metrics;
        array_init(&metrics);
        add_assoc_long(&metrics, "errorCount", resp.meta.metrics.value().error_count);
        add_assoc_long(&metrics, "mutationCount", resp.meta.metrics.value().mutation_count);
        add_assoc_long(&metrics, "resultCount", resp.meta.metrics.value().result_count);
        add_assoc_long(&metrics, "resultSize", resp.meta.metrics.value().result_size);
        add_assoc_long(&metrics, "sortCount", resp.meta.metrics.value().sort_count);
        add_assoc_long(&metrics, "warningCount", resp.meta.metrics.value().warning_count);
        add_assoc_long(
          &metrics, "elapsedTime", std::chrono::duration_cast<std::chrono::milliseconds>(resp.meta.metrics.value().elapsed_time).count());
        add_assoc_long(&metrics,
                       "executionTime",
                       std::chrono::duration_cast<std::chrono::milliseconds>(resp.meta.metrics.value().execution_time).count());

        add_assoc_zval(&meta, "metrics", &metrics);
    }
    if (resp.meta.errors.has_value()) {
        zval errors;
        array_init(&errors);
        for (const auto& e : resp.meta.errors.value()) {
            zval error;
            array_init(&error);

            add_assoc_long(&error, "code", e.code);
            add_assoc_string(&error, "code", e.message.c_str());
            if (e.reason.has_value()) {
                add_assoc_long(&error, "reason", e.reason.value());
            }
            if (e.retry.has_value()) {
                add_assoc_bool(&error, "retry", e.retry.value());
            }

            add_next_index_zval(&errors, &error);
        }
        add_assoc_zval(&retval, "errors", &errors);
    }
    if (resp.meta.warnings.has_value()) {
        zval warnings;
        array_init(&warnings);
        for (const auto& w : resp.meta.warnings.value()) {
            zval warning;
            array_init(&warning);

            add_assoc_long(&warning, "code", w.code);
            add_assoc_string(&warning, "code", w.message.c_str());
            if (w.reason.has_value()) {
                add_assoc_long(&warning, "reason", w.reason.value());
            }
            if (w.retry.has_value()) {
                add_assoc_bool(&warning, "retry", w.retry.value());
            }

            add_next_index_zval(&warnings, &warning);
        }
        add_assoc_zval(&retval, "warnings", &warnings);
    }

    add_assoc_zval(&retval, "meta", &meta);

    return { &retval, {} };
}

std::pair<zval*, core_error_info>
connection_handle::analytics_query(const zend_string* statement, const zval* options)
{
    couchbase::operations::analytics_request request{ cb_string_new(statement) };
    if (auto e = cb_assign_timeout(request, options); e.ec) {
        return { nullptr, e };
    }

    if (auto [e, scan_consistency] = cb_get_integer<uint64_t>(options, "scanConsistency"); !e.ec) {
        switch (scan_consistency) {
            case 1:
                request.scan_consistency = couchbase::operations::analytics_request::scan_consistency_type::not_bounded;
                break;

            case 2:
                request.scan_consistency = couchbase::operations::analytics_request::scan_consistency_type::request_plus;
                break;

            default:
                if (scan_consistency > 0) {
                    return { nullptr,
                             { error::common_errc::invalid_argument,
                               { __LINE__, __FILE__, __func__ },
                               fmt::format("invalid value used for scan consistency: {}", scan_consistency) } };
                }
        }
    } else {
        return { nullptr, e };
    }

    if (auto e = cb_assign_boolean(request.readonly, options, "readonly"); e.ec) {
        return { nullptr, e };
    }
    if (auto e = cb_assign_boolean(request.priority, options, "priority"); e.ec) {
        return { nullptr, e };
    }
    if (const zval* value = zend_symtable_str_find(Z_ARRVAL_P(options), ZEND_STRL("positionalParameters"));
        value != nullptr && Z_TYPE_P(value) == IS_ARRAY) {
        std::vector<couchbase::json_string> params{};
        const zval* item = nullptr;

        ZEND_HASH_FOREACH_VAL(Z_ARRVAL_P(value), item)
        {
            params.emplace_back(std::string({ Z_STRVAL_P(item), Z_STRLEN_P(item) }));
        }
        ZEND_HASH_FOREACH_END();

        request.positional_parameters = params;
    }
    if (const zval* value = zend_symtable_str_find(Z_ARRVAL_P(options), ZEND_STRL("namedParameters"));
        value != nullptr && Z_TYPE_P(value) == IS_ARRAY) {
        std::map<std::string, couchbase::json_string> params{};
        const zend_string* key = nullptr;
        const zval* item = nullptr;

        ZEND_HASH_FOREACH_STR_KEY_VAL(Z_ARRVAL_P(value), key, item)
        {
            params[cb_string_new(key)] = std::string({ Z_STRVAL_P(item), Z_STRLEN_P(item) });
        }
        ZEND_HASH_FOREACH_END();

        request.named_parameters = params;
    }
    if (const zval* value = zend_symtable_str_find(Z_ARRVAL_P(options), ZEND_STRL("raw"));
        value != nullptr && Z_TYPE_P(value) == IS_ARRAY) {
        std::map<std::string, couchbase::json_string> params{};
        const zend_string* key = nullptr;
        const zval* item = nullptr;

        ZEND_HASH_FOREACH_STR_KEY_VAL(Z_ARRVAL_P(value), key, item)
        {
            params[cb_string_new(key)] = std::string({ Z_STRVAL_P(item), Z_STRLEN_P(item) });
        }
        ZEND_HASH_FOREACH_END();

        request.raw = params;
    }
    if (auto e = cb_assign_string(request.client_context_id, options, "clientContextId"); e.ec) {
        return { nullptr, e };
    }
    if (auto e = cb_assign_string(request.scope_name, options, "scopeName"); e.ec) {
        return { nullptr, e };
    }
    if (auto e = cb_assign_string(request.bucket_name, options, "bucketName"); e.ec) {
        return { nullptr, e };
    }

    auto [err, resp] = impl_->analytics_query(std::move(request));
    if (err.ec) {
        return { nullptr, err };
    }

    zval retval;
    array_init(&retval);

    zval rows;
    array_init(&rows);
    for (const auto& row : resp.rows) {
        add_next_index_stringl(&rows, row.data(), row.size());
    }
    add_assoc_zval(&retval, "rows", &rows);
    {
        zval meta;
        array_init(&meta);
        add_assoc_string(&meta, "clientContextId", resp.meta.client_context_id.c_str());
        add_assoc_string(&meta, "requestId", resp.meta.request_id.c_str());
        add_assoc_string(&meta, "status", resp.meta.status.c_str());
        if (resp.meta.signature.has_value()) {
            add_assoc_string(&meta, "signature", resp.meta.signature.value().c_str());
        }

        {
            zval metrics;
            array_init(&metrics);
            add_assoc_long(&metrics, "errorCount", resp.meta.metrics.error_count);
            add_assoc_long(&metrics, "processedObjects", resp.meta.metrics.processed_objects);
            add_assoc_long(&metrics, "resultCount", resp.meta.metrics.result_count);
            add_assoc_long(&metrics, "resultSize", resp.meta.metrics.result_size);
            add_assoc_long(&metrics, "warningCount", resp.meta.metrics.warning_count);
            add_assoc_long(
              &metrics, "elapsedTime", std::chrono::duration_cast<std::chrono::milliseconds>(resp.meta.metrics.elapsed_time).count());
            add_assoc_long(
              &metrics, "executionTime", std::chrono::duration_cast<std::chrono::milliseconds>(resp.meta.metrics.execution_time).count());

            add_assoc_zval(&meta, "metrics", &metrics);
        }

        {
            zval warnings;
            array_init(&warnings);
            for (const auto& w : resp.meta.warnings) {
                zval warning;
                array_init(&warning);

                add_assoc_long(&warning, "code", w.code);
                add_assoc_string(&warning, "code", w.message.c_str());

                add_next_index_zval(&warnings, &warning);
            }
            add_assoc_zval(&retval, "warnings", &warnings);
        }

        add_assoc_zval(&retval, "meta", &meta);
    }

    return { &retval, {} };
}

std::pair<zval*, core_error_info>
connection_handle::search_query(const zend_string* index_name, const zend_string* query, const zval* options)
{
    couchbase::operations::search_request request{ cb_string_new(index_name), cb_string_new(query) };
    if (auto e = cb_assign_timeout(request, options); e.ec) {
        return { nullptr, e };
    }
    if (auto e = cb_assign_integer(request.limit, options, "limit"); e.ec) {
        return { nullptr, e };
    }
    if (auto e = cb_assign_integer(request.skip, options, "skip"); e.ec) {
        return { nullptr, e };
    }
    if (auto e = cb_assign_boolean(request.explain, options, "explain"); e.ec) {
        return { nullptr, e };
    }
    if (auto e = cb_assign_boolean(request.disable_scoring, options, "disableScoring"); e.ec) {
        return { nullptr, e };
    }
    if (auto e = cb_assign_boolean(request.include_locations, options, "includeLocations"); e.ec) {
        return { nullptr, e };
    }
    if (auto e = cb_assign_vector_of_strings(request.highlight_fields, options, "highlightFields"); e.ec) {
        return { nullptr, e };
    }
    if (auto e = cb_assign_vector_of_strings(request.fields, options, "fields"); e.ec) {
        return { nullptr, e };
    }
    if (auto e = cb_assign_vector_of_strings(request.collections, options, "collections"); e.ec) {
        return { nullptr, e };
    }
    if (auto e = cb_assign_vector_of_strings(request.sort_specs, options, "sortSpecs"); e.ec) {
        return { nullptr, e };
    }

    if (auto [e, highlight_style] = cb_get_integer<uint64_t>(options, "highlightStyle"); !e.ec) {
        switch (highlight_style) {
            case 1:
                request.highlight_style = couchbase::operations::search_request::highlight_style_type::ansi;
                break;

            case 2:
                request.highlight_style = couchbase::operations::search_request::highlight_style_type::html;
                break;

            default:
                if (highlight_style > 0) {
                    return { nullptr,
                             { error::common_errc::invalid_argument,
                               { __LINE__, __FILE__, __func__ },
                               fmt::format("invalid value used for highlight style: {}", highlight_style) } };
                }
        }
    } else {
        return { nullptr, e };
    }
    if (const zval* value = zend_symtable_str_find(Z_ARRVAL_P(options), ZEND_STRL("consistentWith"));
        value != nullptr && Z_TYPE_P(value) == IS_ARRAY) {
        std::vector<couchbase::mutation_token> vectors{};
        const zval* item = nullptr;

        ZEND_HASH_FOREACH_VAL(Z_ARRVAL_P(value), item)
        {
            couchbase::mutation_token token{};
            if (auto e = cb_assign_integer(token.partition_id, options, "partitionId"); e.ec) {
                return { nullptr, e };
            }
            if (auto e = cb_assign_integer(token.partition_uuid, options, "partitionUuid"); e.ec) {
                return { nullptr, e };
            }
            if (auto e = cb_assign_integer(token.sequence_number, options, "sequenceNumber"); e.ec) {
                return { nullptr, e };
            }
            if (auto e = cb_assign_string(token.bucket_name, options, "bucketName"); e.ec) {
                return { nullptr, e };
            }
            vectors.emplace_back(token);
        }
        ZEND_HASH_FOREACH_END();

        request.mutation_state = vectors;
    }

    if (const zval* value = zend_symtable_str_find(Z_ARRVAL_P(options), ZEND_STRL("raw"));
        value != nullptr && Z_TYPE_P(value) == IS_ARRAY) {
        std::map<std::string, couchbase::json_string> params{};
        const zend_string* key = nullptr;
        const zval* item = nullptr;

        ZEND_HASH_FOREACH_STR_KEY_VAL(Z_ARRVAL_P(value), key, item)
        {
            params[cb_string_new(key)] = std::string({ Z_STRVAL_P(item), Z_STRLEN_P(item) });
        }
        ZEND_HASH_FOREACH_END();

        request.raw = params;
    }
    if (const zval* value = zend_symtable_str_find(Z_ARRVAL_P(options), ZEND_STRL("facets"));
        value != nullptr && Z_TYPE_P(value) == IS_ARRAY) {
        std::map<std::string, std::string> facets{};
        const zend_string* key = nullptr;
        const zval* item = nullptr;

        ZEND_HASH_FOREACH_STR_KEY_VAL(Z_ARRVAL_P(value), key, item)
        {
            facets[cb_string_new(key)] = std::string({ Z_STRVAL_P(item), Z_STRLEN_P(item) });
        }
        ZEND_HASH_FOREACH_END();

        request.facets = facets;
    }
    if (auto e = cb_assign_string(request.client_context_id, options, "clientContextId"); e.ec) {
        return { nullptr, e };
    }

    auto [err, resp] = impl_->search_query(std::move(request));
    if (err.ec) {
        return { nullptr, err };
    }

    zval retval;
    array_init(&retval);

    add_assoc_string(&retval, "status", resp.status.c_str());
    add_assoc_string(&retval, "error", resp.error.c_str());

    zval rows;
    array_init(&rows);
    for (const auto& row : resp.rows) {
        zval z_row;
        array_init(&z_row);
        add_assoc_string(&z_row, "index", row.index.c_str());
        add_assoc_string(&z_row, "id", row.id.c_str());
        add_assoc_string(&z_row, "fields", row.fields.c_str());
        add_assoc_string(&z_row, "explanation", row.explanation.c_str());
        add_assoc_double(&z_row, "score", row.score);

        zval z_locations;
        array_init(&z_locations);
        for (const auto& location : row.locations) {
            zval z_location;
            array_init(&z_location);
            add_assoc_string(&z_location, "field", location.field.c_str());
            add_assoc_string(&z_location, "term", location.term.c_str());
            add_assoc_long(&z_location, "position", location.position);
            add_assoc_long(&z_location, "startOffset", location.start_offset);
            add_assoc_long(&z_location, "endOffset", location.end_offset);

            if (location.array_positions.has_value()) {
                zval z_array_positions;
                array_init(&z_array_positions);
                for (const auto& position : location.array_positions.value()) {
                    add_next_index_long(&z_array_positions, position);
                }

                add_assoc_zval(&z_location, "arrayPositions", &z_array_positions);
            }
            add_next_index_zval(&z_locations, &z_location);
        }
        add_assoc_zval(&z_row, "locations", &z_locations);

        zval fragments;
        array_init(&fragments);
        for (auto const& fragment : row.fragments) {
            zval z_fragment_values;
            array_init(&z_fragment_values);

            for (const auto& fragment_value : fragment.second) {
                add_next_index_string(&z_fragment_values, fragment_value.c_str());
            }

            add_assoc_zval(&fragments, fragment.first.c_str(), &z_fragment_values);
        }
        add_assoc_zval(&z_row, "fragments", &fragments);

        add_next_index_zval(&rows, &z_row);
    }
    add_assoc_zval(&retval, "rows", &rows);

    zval metadata;
    array_init(&metadata);
    add_assoc_string(&metadata, "clientContextId", resp.meta.client_context_id.c_str());

    zval metrics;
    array_init(&metrics);
    add_assoc_long(&metrics, "tookNanoseconds", resp.meta.metrics.took.count());
    add_assoc_long(&metrics, "totalRows", resp.meta.metrics.total_rows);
    add_assoc_double(&metrics, "maxScore", resp.meta.metrics.max_score);
    add_assoc_long(&metrics, "successPartitionCount", resp.meta.metrics.success_partition_count);
    add_assoc_long(&metrics, "errorPartitionCount", resp.meta.metrics.error_partition_count);
    add_assoc_zval(&metadata, "metrics", &metrics);

    zval errors;
    array_init(&errors);
    std::map<std::string, std::string>::iterator it;
    for (it = resp.meta.errors.begin(); it != resp.meta.errors.end(); it++) {
        add_assoc_string(&errors, it->first.c_str(), it->second.c_str());
    }
    add_assoc_zval(&metadata, "errors", &errors);

    add_assoc_zval(&retval, "meta", &metadata);

    zval facets;
    array_init(&facets);
    for (const auto& facet : resp.facets) {
        zval z_facet;
        array_init(&z_facet);
        add_assoc_string(&z_facet, "name", facet.name.c_str());
        add_assoc_string(&z_facet, "field", facet.field.c_str());
        add_assoc_long(&z_facet, "total", facet.total);
        add_assoc_long(&z_facet, "missing", facet.missing);
        add_assoc_long(&z_facet, "other", facet.other);

        zval terms;
        array_init(&terms);
        for (const auto& term : facet.terms) {
            zval z_term;
            array_init(&z_term);
            add_assoc_string(&z_term, "term", term.term.c_str());
            add_assoc_long(&z_term, "count", term.count);
            add_next_index_zval(&terms, &z_term);
        }
        add_assoc_zval(&z_facet, "terms", &terms);

        zval date_ranges;
        array_init(&date_ranges);
        for (const auto& range : facet.date_ranges) {
            zval z_range;
            array_init(&z_range);
            add_assoc_string(&z_range, "name", range.name.c_str());
            add_assoc_long(&z_range, "count", range.count);
            if (range.start.has_value()) {
                add_assoc_string(&z_range, "start", range.start.value().c_str());
            }
            if (range.end.has_value()) {
                add_assoc_string(&z_range, "end", range.end.value().c_str());
            }
            add_next_index_zval(&date_ranges, &z_range);
        }
        add_assoc_zval(&z_facet, "dateRanges", &date_ranges);

        zval numeric_ranges;
        array_init(&numeric_ranges);
        for (const auto& range : facet.numeric_ranges) {
            zval z_range;
            array_init(&z_range);
            add_assoc_string(&z_range, "name", range.name.c_str());
            add_assoc_long(&z_range, "count", range.count);
            if (std::holds_alternative<std::uint64_t>(range.min)) {
                add_assoc_long(&z_range, "min", std::get<std::uint64_t>(range.min));
            } else if (std::holds_alternative<double>(range.min)) {
                add_assoc_long(&z_range, "min", std::get<double>(range.min));
            }
            if (std::holds_alternative<std::uint64_t>(range.max)) {
                add_assoc_long(&z_range, "max", std::get<std::uint64_t>(range.max));
            } else if (std::holds_alternative<double>(range.max)) {
                add_assoc_long(&z_range, "max", std::get<double>(range.max));
            }
            add_next_index_zval(&numeric_ranges, &z_range);
        }
        add_assoc_zval(&z_facet, "numericRanges", &numeric_ranges);

        add_next_index_zval(&facets, &z_facet);
    }
    add_assoc_zval(&retval, "facets", &facets);

    return { &retval, {} };
}

std::pair<zval*, core_error_info>
connection_handle::view_query(const zend_string* bucket_name,
                              const zend_string* design_document_name,
                              const zend_string* view_name,
                              const zend_long name_space,
                              const zval* options)
{
    couchbase::operations::design_document::name_space cxx_name_space;
    auto name_space_val = std::uint32_t(name_space);
    switch (name_space_val) {
        case 1:
            cxx_name_space = couchbase::operations::design_document::name_space::development;
            break;

        case 2:
            cxx_name_space = couchbase::operations::design_document::name_space::production;
            break;

        default:
            return { nullptr,
                     { error::common_errc::invalid_argument,
                       { __LINE__, __FILE__, __func__ },
                       fmt::format("invalid value used for namespace: {}", name_space_val) } };
    }

    couchbase::operations::document_view_request request{
        cb_string_new(bucket_name),
        cb_string_new(design_document_name),
        cb_string_new(view_name),
        cxx_name_space,
    };
    if (auto e = cb_assign_timeout(request, options); e.ec) {
        return { nullptr, e };
    }
    if (auto [e, scan_consistency] = cb_get_integer<uint64_t>(options, "scanConsistency"); !e.ec) {
        switch (scan_consistency) {
            case 1:
                request.consistency = couchbase::operations::document_view_request::scan_consistency::not_bounded;
                break;

            case 2:
                request.consistency = couchbase::operations::document_view_request::scan_consistency::request_plus;
                break;

            case 3:
                request.consistency = couchbase::operations::document_view_request::scan_consistency::update_after;
                break;

            default:
                if (scan_consistency > 0) {
                    return { nullptr,
                             { error::common_errc::invalid_argument,
                               { __LINE__, __FILE__, __func__ },
                               fmt::format("invalid value used for scan consistency: {}", scan_consistency) } };
                }
        }
    } else {
        return { nullptr, e };
    }

    if (const zval* value = zend_symtable_str_find(Z_ARRVAL_P(options), ZEND_STRL("keys"));
        value != nullptr && Z_TYPE_P(value) == IS_ARRAY) {
        std::vector<std::string> keys{};
        const zval* item = nullptr;

        ZEND_HASH_FOREACH_VAL(Z_ARRVAL_P(value), item)
        {
            keys.emplace_back(std::string({ Z_STRVAL_P(item), Z_STRLEN_P(item) }));
        }
        ZEND_HASH_FOREACH_END();

        request.keys = keys;
    }
    if (auto [e, order] = cb_get_integer<uint64_t>(options, "order"); !e.ec) {
        switch (order) {
            case 0:
                request.order = couchbase::operations::document_view_request::sort_order::ascending;
                break;

            case 1:
                request.order = couchbase::operations::document_view_request::sort_order::descending;
                break;

            default:
                return { nullptr,
                         { error::common_errc::invalid_argument,
                           { __LINE__, __FILE__, __func__ },
                           fmt::format("invalid value used for order: {}", order) } };
        }
    } else {
        return { nullptr, e };
    }
    //    {
    //        const zval* value = zend_symtable_str_find(Z_ARRVAL_P(options), ZEND_STRL("raw"));
    //        if (value != nullptr && Z_TYPE_P(value) == IS_ARRAY) {
    //            std::map<std::string, std::string> values{};
    //            const zend_string* key = nullptr;
    //            const zval *item = nullptr;
    //
    //            ZEND_HASH_FOREACH_STR_KEY_VAL(Z_ARRVAL_P(value), key, item)
    //            {
    //                auto str = std::string({ Z_STRVAL_P(item), Z_STRLEN_P(item) });
    //                auto k = std::string({ ZSTR_VAL(key), ZSTR_LEN(key) });
    //                values.emplace(k, std::move(str));
    //            }
    //            ZEND_HASH_FOREACH_END();
    //
    //            request.raw = values;
    //        }
    //    }
    if (auto e = cb_assign_boolean(request.reduce, options, "reduce"); e.ec) {
        return { nullptr, e };
    }
    if (auto e = cb_assign_boolean(request.group, options, "group"); e.ec) {
        return { nullptr, e };
    }
    if (auto e = cb_assign_integer(request.group_level, options, "groupLevel"); e.ec) {
        return { nullptr, e };
    }
    if (auto e = cb_assign_integer(request.limit, options, "limit"); e.ec) {
        return { nullptr, e };
    }
    if (auto e = cb_assign_boolean(request.skip, options, "skip"); e.ec) {
        return { nullptr, e };
    }
    if (auto e = cb_assign_string(request.key, options, "key"); e.ec) {
        return { nullptr, e };
    }
    if (auto e = cb_assign_string(request.start_key, options, "startKey"); e.ec) {
        return { nullptr, e };
    }
    if (auto e = cb_assign_string(request.end_key, options, "endKey"); e.ec) {
        return { nullptr, e };
    }
    if (auto e = cb_assign_string(request.start_key_doc_id, options, "startKeyDocId"); e.ec) {
        return { nullptr, e };
    }
    if (auto e = cb_assign_string(request.end_key_doc_id, options, "endKeyDocId"); e.ec) {
        return { nullptr, e };
    }
    if (auto e = cb_assign_boolean(request.inclusive_end, options, "inclusiveEnd"); e.ec) {
        return { nullptr, e };
    }
    //    if (auto e = cb_assign_integer(request.on_error, options, "onError"); e.ec) {
    //        return { nullptr, e };
    //    }
    if (auto e = cb_assign_boolean(request.debug, options, "debug"); e.ec) {
        return { nullptr, e };
    }

    auto [err, resp] = impl_->view_query(std::move(request));
    if (err.ec) {
        return { nullptr, err };
    }

    zval retval;
    array_init(&retval);

    zval rows;
    array_init(&rows);
    for (auto& row : resp.rows) {
        zval zrow;
        array_init(&zrow);
        if (row.id.has_value()) {
            add_assoc_string(&zrow, "id", row.id.value().c_str());
        }
        add_assoc_string(&zrow, "value", row.value.c_str());
        add_assoc_string(&zrow, "key", row.key.c_str());

        add_next_index_zval(&rows, &zrow);
    }
    add_assoc_zval(&retval, "rows", &rows);

    {
        zval meta;
        array_init(&meta);
        if (resp.meta.debug_info.has_value()) {
            add_assoc_string(&meta, "debugInfo", resp.meta.debug_info.value().c_str());
        }
        if (resp.meta.total_rows.has_value()) {
            add_assoc_long(&meta, "totalRows", resp.meta.total_rows.value());
        }

        add_assoc_zval(&meta, "meta", &meta);
    }

    return { &retval, {} };
}

std::pair<zval*, core_error_info>
connection_handle::search_index_upsert(const zval* index, const zval* options)
{
    couchbase::operations::management::search_index idx{};
    if (auto e = cb_assign_string(idx.name, index, "name"); e.ec) {
        return { nullptr, e };
    }
    if (auto e = cb_assign_string(idx.type, index, "type"); e.ec) {
        return { nullptr, e };
    }
    if (auto e = cb_assign_string(idx.uuid, index, "uuid"); e.ec) {
        return { nullptr, e };
    }
    if (auto e = cb_assign_string(idx.params_json, index, "params"); e.ec) {
        return { nullptr, e };
    }
    if (auto e = cb_assign_string(idx.source_uuid, index, "sourceUuid"); e.ec) {
        return { nullptr, e };
    }
    if (auto e = cb_assign_string(idx.source_name, index, "sourceName"); e.ec) {
        return { nullptr, e };
    }
    if (auto e = cb_assign_string(idx.source_type, index, "sourceType"); e.ec) {
        return { nullptr, e };
    }
    if (auto e = cb_assign_string(idx.source_params_json, index, "sourceParams"); e.ec) {
        return { nullptr, e };
    }

    couchbase::operations::management::search_index_upsert_request request{ idx };

    if (auto e = cb_assign_timeout(request, options); e.ec) {
        return { nullptr, e };
    }

    auto [err, resp] = impl_->search_index_upsert(std::move(request));
    if (err.ec) {
        return { nullptr, err };
    }

    zval retval;
    array_init(&retval);
    add_assoc_string(&retval, "status", resp.status.c_str());
    add_assoc_string(&retval, "error", resp.error.c_str());

    return { &retval, {} };
}

std::pair<zval*, core_error_info>
connection_handle::view_index_upsert(const zend_string* bucket_name, const zval* design_document, zend_long name_space, const zval* options)
{
    couchbase::operations::design_document idx{};
    if (auto e = cb_assign_string(idx.name, design_document, "name"); e.ec) {
        return { nullptr, e };
    }
    if (auto e = cb_assign_string(idx.rev, design_document, "rev"); e.ec) {
        return { nullptr, e };
    }
    switch (name_space) {
        case 1:
            idx.ns = couchbase::operations::design_document::name_space::development;
            break;

        case 2:
            idx.ns = couchbase::operations::design_document::name_space::production;
            break;

        default:
            return { nullptr,
                     { error::common_errc::invalid_argument,
                       { __LINE__, __FILE__, __func__ },
                       fmt::format("invalid value used for namespace: {}", name_space) } };
    }

    if (const zval* value = zend_symtable_str_find(Z_ARRVAL_P(design_document), ZEND_STRL("views"));
        value != nullptr && Z_TYPE_P(value) == IS_ARRAY) {
        std::map<std::string, couchbase::operations::design_document::view> views{};
        const zend_string* key = nullptr;
        const zval* item = nullptr;

        ZEND_HASH_FOREACH_STR_KEY_VAL(Z_ARRVAL_P(value), key, item)
        {
            couchbase::operations::design_document::view view{};
            if (auto e = cb_assign_string(view.name, item, "name"); e.ec) {
                return { nullptr, e };
            }
            if (auto e = cb_assign_string(view.map, item, "map"); e.ec) {
                return { nullptr, e };
            }
            if (auto e = cb_assign_string(view.reduce, item, "reduce"); e.ec) {
                return { nullptr, e };
            }

            views[cb_string_new(key)] = view;
        }
        ZEND_HASH_FOREACH_END();

        idx.views = views;
    }

    couchbase::operations::management::view_index_upsert_request request{ cb_string_new(bucket_name), idx };

    if (auto e = cb_assign_timeout(request, options); e.ec) {
        return { nullptr, e };
    }

    auto [err, resp] = impl_->view_index_upsert(std::move(request));
    if (err.ec) {
        return { nullptr, err };
    }

    zval retval;
    array_init(&retval);

    return { &retval, {} };
}

bool
connection_handle::is_expired(std::chrono::steady_clock::time_point now) const
{
    return idle_expiry_ < now;
}

#define ASSIGN_DURATION_OPTION(name, field, key, value)                                                                                    \
    if (zend_binary_strcmp(ZSTR_VAL(key), ZSTR_LEN(key), ZEND_STRL(name)) == 0) {                                                          \
        if ((value) == nullptr || Z_TYPE_P(value) == IS_NULL) {                                                                            \
            continue;                                                                                                                      \
        }                                                                                                                                  \
        if (Z_TYPE_P(value) != IS_LONG) {                                                                                                  \
            return { error::common_errc::invalid_argument,                                                                                 \
                     { __LINE__, __FILE__, __func__ },                                                                                     \
                     fmt::format("expected duration as a number for {}", std::string(ZSTR_VAL(key), ZSTR_LEN(key))) };                     \
        }                                                                                                                                  \
        zend_long ms = Z_LVAL_P(value);                                                                                                    \
        if (ms < 0) {                                                                                                                      \
            return { error::common_errc::invalid_argument,                                                                                 \
                     { __LINE__, __FILE__, __func__ },                                                                                     \
                     fmt::format("expected duration as a positive number for {}", std::string(ZSTR_VAL(key), ZSTR_LEN(key))) };            \
        }                                                                                                                                  \
        (field) = std::chrono::milliseconds(ms);                                                                                           \
    }

#define ASSIGN_NUMBER_OPTION(name, field, key, value)                                                                                      \
    if (zend_binary_strcmp(ZSTR_VAL(key), ZSTR_LEN(key), ZEND_STRL(name)) == 0) {                                                          \
        if ((value) == nullptr || Z_TYPE_P(value) == IS_NULL) {                                                                            \
            continue;                                                                                                                      \
        }                                                                                                                                  \
        if (Z_TYPE_P(value) != IS_LONG) {                                                                                                  \
            return { error::common_errc::invalid_argument,                                                                                 \
                     { __LINE__, __FILE__, __func__ },                                                                                     \
                     fmt::format("expected number for {}", std::string(ZSTR_VAL(key), ZSTR_LEN(key))) };                                   \
        }                                                                                                                                  \
        (field) = Z_LVAL_P(value);                                                                                                         \
    }

#define ASSIGN_BOOLEAN_OPTION(name, field, key, value)                                                                                     \
    if (zend_binary_strcmp(ZSTR_VAL(key), ZSTR_LEN(key), ZEND_STRL(name)) == 0) {                                                          \
        if ((value) == nullptr || Z_TYPE_P(value) == IS_NULL) {                                                                            \
            continue;                                                                                                                      \
        }                                                                                                                                  \
        switch (Z_TYPE_P(value)) {                                                                                                         \
            case IS_TRUE:                                                                                                                  \
                (field) = true;                                                                                                            \
                break;                                                                                                                     \
            case IS_FALSE:                                                                                                                 \
                (field) = false;                                                                                                           \
                break;                                                                                                                     \
            default:                                                                                                                       \
                return { error::common_errc::invalid_argument,                                                                             \
                         { __LINE__, __FILE__, __func__ },                                                                                 \
                         fmt::format("expected boolean for {}", std::string(ZSTR_VAL(key), ZSTR_LEN(key))) };                              \
        }                                                                                                                                  \
    }

#define ASSIGN_STRING_OPTION(name, field, key, value)                                                                                      \
    if (zend_binary_strcmp(ZSTR_VAL(key), ZSTR_LEN(key), ZEND_STRL(name)) == 0) {                                                          \
        if ((value) == nullptr || Z_TYPE_P(value) == IS_NULL) {                                                                            \
            continue;                                                                                                                      \
        }                                                                                                                                  \
        if (Z_TYPE_P(value) != IS_STRING) {                                                                                                \
            return { error::common_errc::invalid_argument,                                                                                 \
                     { __LINE__, __FILE__, __func__ },                                                                                     \
                     fmt::format("expected string for {}", std::string(ZSTR_VAL(key), ZSTR_LEN(key))) };                                   \
        }                                                                                                                                  \
        if (Z_STRLEN_P(value) == 0) {                                                                                                      \
            return { error::common_errc::invalid_argument,                                                                                 \
                     { __LINE__, __FILE__, __func__ },                                                                                     \
                     fmt::format("expected non-empty string for {}", std::string(ZSTR_VAL(key), ZSTR_LEN(key))) };                         \
        }                                                                                                                                  \
        (field).assign(Z_STRVAL_P(value), Z_STRLEN_P(value));                                                                              \
    }

static core_error_info
apply_options(couchbase::utils::connection_string& connstr, zval* options)
{
    if (options == nullptr || Z_TYPE_P(options) != IS_ARRAY) {
        return { error::common_errc::invalid_argument, { __LINE__, __FILE__, __func__ }, "expected array for cluster options" };
    }

    const zend_string* key;
    const zval* value;

    ZEND_HASH_FOREACH_STR_KEY_VAL(Z_ARRVAL_P(options), key, value)
    {
        ASSIGN_DURATION_OPTION("analyticsTimeout", connstr.options.analytics_timeout, key, value);
        ASSIGN_DURATION_OPTION("bootstrapTimeout", connstr.options.bootstrap_timeout, key, value);
        ASSIGN_DURATION_OPTION("connectTimeout", connstr.options.connect_timeout, key, value);
        ASSIGN_DURATION_OPTION("dnsSrvTimeout", connstr.options.dns_srv_timeout, key, value);
        ASSIGN_DURATION_OPTION("keyValueDurableTimeout", connstr.options.key_value_durable_timeout, key, value);
        ASSIGN_DURATION_OPTION("keyValueTimeout", connstr.options.key_value_timeout, key, value);
        ASSIGN_DURATION_OPTION("managementTimeout", connstr.options.management_timeout, key, value);
        ASSIGN_DURATION_OPTION("queryTimeout", connstr.options.query_timeout, key, value);
        ASSIGN_DURATION_OPTION("resolveTimeout", connstr.options.resolve_timeout, key, value);
        ASSIGN_DURATION_OPTION("searchTimeout", connstr.options.search_timeout, key, value);
        ASSIGN_DURATION_OPTION("viewTimeout", connstr.options.view_timeout, key, value);

        ASSIGN_NUMBER_OPTION("maxHttpConnections", connstr.options.max_http_connections, key, value);

        ASSIGN_DURATION_OPTION("configIdleRedialTimeout", connstr.options.config_idle_redial_timeout, key, value);
        ASSIGN_DURATION_OPTION("configPollFloor", connstr.options.config_poll_floor, key, value);
        ASSIGN_DURATION_OPTION("configPollInterval", connstr.options.config_poll_interval, key, value);
        ASSIGN_DURATION_OPTION("idleHttpConnectionTimeout", connstr.options.idle_http_connection_timeout, key, value);
        ASSIGN_DURATION_OPTION("tcpKeepAliveInterval", connstr.options.tcp_keep_alive_interval, key, value);

        ASSIGN_BOOLEAN_OPTION("enableClustermapNotification", connstr.options.enable_clustermap_notification, key, value);
        ASSIGN_BOOLEAN_OPTION("enableCompression", connstr.options.enable_compression, key, value);
        ASSIGN_BOOLEAN_OPTION("enableDnsSrv", connstr.options.enable_dns_srv, key, value);
        ASSIGN_BOOLEAN_OPTION("enableMetrics", connstr.options.enable_metrics, key, value);
        ASSIGN_BOOLEAN_OPTION("enableMutationTokens", connstr.options.enable_mutation_tokens, key, value);
        ASSIGN_BOOLEAN_OPTION("enableTcpKeepAlive", connstr.options.enable_tcp_keep_alive, key, value);
        ASSIGN_BOOLEAN_OPTION("enableTls", connstr.options.enable_tls, key, value);
        ASSIGN_BOOLEAN_OPTION("enableTracing", connstr.options.enable_tracing, key, value);
        ASSIGN_BOOLEAN_OPTION("enableUnorderedExecution", connstr.options.enable_unordered_execution, key, value);
        ASSIGN_BOOLEAN_OPTION("forceIpv4", connstr.options.force_ipv4, key, value);
        ASSIGN_BOOLEAN_OPTION("showQueries", connstr.options.show_queries, key, value);

        ASSIGN_STRING_OPTION("network", connstr.options.network, key, value);
        ASSIGN_STRING_OPTION("trustCertificate", connstr.options.trust_certificate, key, value);
        ASSIGN_STRING_OPTION("userAgentExtra", connstr.options.user_agent_extra, key, value);

        if (zend_binary_strcmp(ZSTR_VAL(key), ZSTR_LEN(key), ZEND_STRL("tlsVerify")) == 0) {
            if (value == nullptr || Z_TYPE_P(value) == IS_NULL) {
                continue;
            }
            if (Z_TYPE_P(value) != IS_STRING) {
                return { error::common_errc::invalid_argument,
                         { __LINE__, __FILE__, __func__ },
                         fmt::format("expected string for {}", std::string(ZSTR_VAL(key), ZSTR_LEN(key))) };
            }
            if (zend_binary_strcmp(Z_STRVAL_P(value), Z_STRLEN_P(value), ZEND_STRL("peer")) == 0) {
                connstr.options.tls_verify = couchbase::tls_verify_mode::peer;
            } else if (zend_binary_strcmp(Z_STRVAL_P(value), Z_STRLEN_P(value), ZEND_STRL("none")) == 0) {
                connstr.options.tls_verify = couchbase::tls_verify_mode::none;
            } else {
                return { error::common_errc::invalid_argument,
                         { __LINE__, __FILE__, __func__ },
                         fmt::format(R"(expected mode for TLS verification ({}), supported modes are "peer" and "none")",
                                     std::string(ZSTR_VAL(key), ZSTR_LEN(key))) };
            }
        }

        if (zend_binary_strcmp(ZSTR_VAL(key), ZSTR_LEN(key), ZEND_STRL("thresholdLoggingTracerOptions")) == 0) {
            if (value == nullptr || Z_TYPE_P(value) == IS_NULL) {
                continue;
            }
            if (Z_TYPE_P(value) != IS_ARRAY) {
                return { error::common_errc::invalid_argument,
                         { __LINE__, __FILE__, __func__ },
                         fmt::format("expected array for {} as tracer options", std::string(ZSTR_VAL(key), ZSTR_LEN(key))) };
            }

            const zend_string* k;
            const zval* v;

            ZEND_HASH_FOREACH_STR_KEY_VAL(Z_ARRVAL_P(value), k, v)
            {
                ASSIGN_NUMBER_OPTION("orphanedSampleSize", connstr.options.tracing_options.orphaned_sample_size, k, v);
                ASSIGN_DURATION_OPTION("orphanedEmitInterval", connstr.options.tracing_options.orphaned_emit_interval, k, v);

                ASSIGN_NUMBER_OPTION("thresholdSampleSize", connstr.options.tracing_options.threshold_sample_size, k, v);
                ASSIGN_DURATION_OPTION("thresholdEmitInterval", connstr.options.tracing_options.threshold_emit_interval, k, v);
                ASSIGN_DURATION_OPTION("analyticsThreshold", connstr.options.tracing_options.analytics_threshold, k, v);
                ASSIGN_DURATION_OPTION("eventingThreshold", connstr.options.tracing_options.eventing_threshold, k, v);
                ASSIGN_DURATION_OPTION("keyValueThreshold", connstr.options.tracing_options.key_value_threshold, k, v);
                ASSIGN_DURATION_OPTION("managementThreshold", connstr.options.tracing_options.management_threshold, k, v);
                ASSIGN_DURATION_OPTION("queryThreshold", connstr.options.tracing_options.query_threshold, k, v);
                ASSIGN_DURATION_OPTION("searchThreshold", connstr.options.tracing_options.search_threshold, k, v);
                ASSIGN_DURATION_OPTION("viewThreshold", connstr.options.tracing_options.view_threshold, k, v);
            }
            ZEND_HASH_FOREACH_END();
        }

        if (zend_binary_strcmp(ZSTR_VAL(key), ZSTR_LEN(key), ZEND_STRL("loggingMeterOptions")) == 0) {
            if (value == nullptr || Z_TYPE_P(value) == IS_NULL) {
                continue;
            }
            if (Z_TYPE_P(value) != IS_ARRAY) {
                return { error::common_errc::invalid_argument,
                         { __LINE__, __FILE__, __func__ },
                         fmt::format("expected array for {} as meter options", std::string(ZSTR_VAL(key), ZSTR_LEN(key))) };
            }

            const zend_string* k;
            const zval* v;

            ZEND_HASH_FOREACH_STR_KEY_VAL(Z_ARRVAL_P(value), k, v)
            {
                ASSIGN_DURATION_OPTION("emitInterval", connstr.options.metrics_options.emit_interval, k, v);
            }
            ZEND_HASH_FOREACH_END();
        }
    }
    ZEND_HASH_FOREACH_END();

    return {};
}

#undef ASSIGN_DURATION_OPTION
#undef ASSIGN_NUMBER_OPTION
#undef ASSIGN_BOOLEAN_OPTION
#undef ASSIGN_STRING_OPTION

static core_error_info
extract_credentials(couchbase::cluster_credentials& credentials, zval* options)
{
    if (options == nullptr || Z_TYPE_P(options) != IS_ARRAY) {
        return { error::common_errc::invalid_argument, { __LINE__, __FILE__, __func__ }, "expected array for cluster options" };
    }

    const zval* auth = zend_symtable_str_find(Z_ARRVAL_P(options), ZEND_STRL("authenticator"));
    if (auth == nullptr || Z_TYPE_P(auth) != IS_ARRAY) {
        return { error::common_errc::invalid_argument, { __LINE__, __FILE__, __func__ }, "missing authenticator" };
    }

    const zval* auth_type = zend_symtable_str_find(Z_ARRVAL_P(auth), ZEND_STRL("type"));
    if (auth_type == nullptr || Z_TYPE_P(auth_type) != IS_STRING) {
        return { error::common_errc::invalid_argument, { __LINE__, __FILE__, __func__ }, "unexpected type of the authenticator" };
    }
    if (zend_binary_strcmp(Z_STRVAL_P(auth_type), Z_STRLEN_P(auth_type), ZEND_STRL("password")) == 0) {
        const zval* username = zend_symtable_str_find(Z_ARRVAL_P(auth), ZEND_STRL("username"));
        if (username == nullptr || Z_TYPE_P(username) != IS_STRING) {
            return { error::common_errc::invalid_argument,
                     { __LINE__, __FILE__, __func__ },
                     "expected username to be a string in the authenticator" };
        }
        const zval* password = zend_symtable_str_find(Z_ARRVAL_P(auth), ZEND_STRL("password"));
        if (password == nullptr || Z_TYPE_P(password) != IS_STRING) {
            return { error::common_errc::invalid_argument,
                     { __LINE__, __FILE__, __func__ },
                     "expected password to be a string in the authenticator" };
        }
        credentials.username.assign(Z_STRVAL_P(username));
        credentials.password.assign(Z_STRVAL_P(password));

        if (const zval* allowed_sasl_mechanisms = zend_symtable_str_find(Z_ARRVAL_P(auth), ZEND_STRL("allowedSaslMechanisms"));
            allowed_sasl_mechanisms != nullptr && Z_TYPE_P(allowed_sasl_mechanisms) != IS_NULL) {
            if (Z_TYPE_P(allowed_sasl_mechanisms) != IS_ARRAY) {
                return { error::common_errc::invalid_argument,
                         { __LINE__, __FILE__, __func__ },
                         "expected allowedSaslMechanisms to be an array in the authenticator" };
            }
            credentials.allowed_sasl_mechanisms.clear();
            const zval* mech;
            ZEND_HASH_FOREACH_VAL(Z_ARRVAL_P(allowed_sasl_mechanisms), mech)
            {
                if (mech != nullptr && Z_TYPE_P(mech) == IS_STRING) {
                    credentials.allowed_sasl_mechanisms.emplace_back(Z_STRVAL_P(mech), Z_STRLEN_P(mech));
                }
            }
            ZEND_HASH_FOREACH_END();
        }
        return {};
    }
    if (zend_binary_strcmp(Z_STRVAL_P(auth_type), Z_STRLEN_P(auth_type), ZEND_STRL("certificate")) == 0) {
        const zval* certificate_path = zend_symtable_str_find(Z_ARRVAL_P(auth), ZEND_STRL("certificatePath"));
        if (certificate_path == nullptr || Z_TYPE_P(certificate_path) != IS_STRING) {
            return { error::common_errc::invalid_argument,
                     { __LINE__, __FILE__, __func__ },
                     "expected certificate path to be a string in the authenticator" };
        }
        const zval* key_path = zend_symtable_str_find(Z_ARRVAL_P(auth), ZEND_STRL("keyPath"));
        if (key_path == nullptr || Z_TYPE_P(key_path) != IS_STRING) {
            return { error::common_errc::invalid_argument,
                     { __LINE__, __FILE__, __func__ },
                     "expected key path to be a string in the authenticator" };
        }
        credentials.certificate_path.assign(Z_STRVAL_P(certificate_path));
        credentials.key_path.assign(Z_STRVAL_P(key_path));
        return {};
    }
    return { error::common_errc::invalid_argument,
             { __LINE__, __FILE__, __func__ },
             fmt::format("unknown type of the authenticator: {}", std::string(Z_STRVAL_P(auth_type), Z_STRLEN_P(auth_type))) };
}

std::pair<connection_handle*, core_error_info>
create_connection_handle(const zend_string* connection_string, zval* options, std::chrono::steady_clock::time_point idle_expiry)
{
    auto connstr = couchbase::utils::parse_connection_string(std::string(ZSTR_VAL(connection_string), ZSTR_LEN(connection_string)));
    if (connstr.error) {
        return { nullptr, { couchbase::error::common_errc::parsing_failure, { __LINE__, __FILE__, __func__ }, connstr.error.value() } };
    }
    if (auto e = apply_options(connstr, options); e.ec) {
        return { nullptr, e };
    }
    couchbase::cluster_credentials credentials;
    if (auto e = extract_credentials(credentials, options); e.ec) {
        return { nullptr, e };
    }
    couchbase::origin origin{ credentials, connstr };
    return { new connection_handle(origin, idle_expiry), {} };
}
} // namespace couchbase::php
