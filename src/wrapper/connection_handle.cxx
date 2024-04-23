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

#include "../php_couchbase.hxx"
#include "common.hxx"
#include "connection_handle.hxx"
#include "conversion_utilities.hxx"
#include "logger.hxx"
#include "passthrough_transcoder.hxx"
#include "version.hxx"

#include <core/cluster.hxx>
#include <core/error_context/analytics.hxx>
#include <core/error_context/search.hxx>
#include <core/error_context/view.hxx>
#include <core/logger/logger.hxx>
#include <core/management/bucket_settings.hxx>
#include <core/operations.hxx>
#include <core/operations/management/bucket.hxx>
#include <core/operations/management/cluster_describe.hxx>
#include <core/operations/management/collections.hxx>
#include <core/operations/management/query.hxx>
#include <core/operations/management/search.hxx>
#include <core/operations/management/user.hxx>
#include <core/operations/management/view.hxx>
#include <core/utils/connection_string.hxx>
#include <core/utils/json.hxx>

#include <couchbase/cluster.hxx>
#include <couchbase/collection.hxx>
#include <couchbase/mutation_token.hxx>
#include <couchbase/retry_reason.hxx>

#include <fmt/core.h>
#include <openssl/crypto.h>

#include <array>
#include <thread>

namespace couchbase::php
{

static std::string
retry_reason_to_string(retry_reason reason)
{
    switch (reason) {
        case retry_reason::do_not_retry:
            return "do_not_retry";
        case retry_reason::socket_not_available:
            return "socket_not_available";
        case retry_reason::service_not_available:
            return "service_not_available";
        case retry_reason::node_not_available:
            return "node_not_available";
        case retry_reason::key_value_not_my_vbucket:
            return "key_value_not_my_vbucket";
        case retry_reason::key_value_collection_outdated:
            return "key_value_collection_outdated";
        case retry_reason::key_value_error_map_retry_indicated:
            return "key_value_error_map_retry_indicated";
        case retry_reason::key_value_locked:
            return "key_value_locked";
        case retry_reason::key_value_temporary_failure:
            return "key_value_temporary_failure";
        case retry_reason::key_value_sync_write_in_progress:
            return "key_value_sync_write_in_progress";
        case retry_reason::key_value_sync_write_re_commit_in_progress:
            return "key_value_sync_write_re_commit_in_progress";
        case retry_reason::service_response_code_indicated:
            return "service_response_code_indicated";
        case retry_reason::socket_closed_while_in_flight:
            return "socket_closed_while_in_flight";
        case retry_reason::circuit_breaker_open:
            return "circuit_breaker_open";
        case retry_reason::query_prepared_statement_failure:
            return "query_prepared_statement_failure";
        case retry_reason::query_index_not_found:
            return "query_index_not_found";
        case retry_reason::analytics_temporary_failure:
            return "analytics_temporary_failure";
        case retry_reason::search_too_many_requests:
            return "search_too_many_requests";
        case retry_reason::views_temporary_failure:
            return "views_temporary_failure";
        case retry_reason::views_no_active_partition:
            return "views_no_active_partition";
        case retry_reason::unknown:
            return "unknown";
    }
    return "unexpected";
}

static const char*
subdoc_opcode_to_string(core::protocol::subdoc_opcode opcode)
{
    switch (opcode) {
        case core::protocol::subdoc_opcode::get_doc:
            return "getDocument";
        case core::protocol::subdoc_opcode::set_doc:
            return "setDocument";
        case core::protocol::subdoc_opcode::remove_doc:
            return "removeDocument";
        case core::protocol::subdoc_opcode::get:
            return "get";
        case core::protocol::subdoc_opcode::exists:
            return "exists";
        case core::protocol::subdoc_opcode::dict_add:
            return "dictionaryAdd";
        case core::protocol::subdoc_opcode::dict_upsert:
            return "dictionaryUpsert";
        case core::protocol::subdoc_opcode::remove:
            return "remove";
        case core::protocol::subdoc_opcode::replace:
            return "replace";
        case core::protocol::subdoc_opcode::array_push_last:
            return "arrayPushLast";
        case core::protocol::subdoc_opcode::array_push_first:
            return "arrayPushFirst";
        case core::protocol::subdoc_opcode::array_insert:
            return "arrayInsert";
        case core::protocol::subdoc_opcode::array_add_unique:
            return "arrayAddUnique";
        case core::protocol::subdoc_opcode::counter:
            return "counter";
        case core::protocol::subdoc_opcode::get_count:
            return "getCount";
        case core::protocol::subdoc_opcode::replace_body_with_xattr:
            return "replaceBodyWithXattr";
    }
    return "unexpected";
}

static std::pair<core::protocol::subdoc_opcode, core_error_info>
decode_lookup_subdoc_opcode(const zval* spec)
{
    if (spec == nullptr || Z_TYPE_P(spec) != IS_ARRAY) {
        return { {}, { errc::common::invalid_argument, ERROR_LOCATION, "expected that spec will be represented as an array" } };
    }
    const zval* value = zend_symtable_str_find(Z_ARRVAL_P(spec), ZEND_STRL("opcode"));
    if (value == nullptr && Z_TYPE_P(value) != IS_STRING) {
        return { {}, { errc::common::invalid_argument, ERROR_LOCATION, "missing opcode field of the spec" } };
    }
    if (zend_binary_strcmp(Z_STRVAL_P(value), Z_STRLEN_P(value), ZEND_STRL("getDocument")) == 0) {
        return { { core::protocol::subdoc_opcode::get_doc }, {} };
    }
    if (zend_binary_strcmp(Z_STRVAL_P(value), Z_STRLEN_P(value), ZEND_STRL("get")) == 0) {
        return { { core::protocol::subdoc_opcode::get }, {} };
    }
    if (zend_binary_strcmp(Z_STRVAL_P(value), Z_STRLEN_P(value), ZEND_STRL("exists")) == 0) {
        return { { core::protocol::subdoc_opcode::exists }, {} };
    }
    if (zend_binary_strcmp(Z_STRVAL_P(value), Z_STRLEN_P(value), ZEND_STRL("getCount")) == 0) {
        return { { core::protocol::subdoc_opcode::get_count }, {} };
    }
    return { {},
             { errc::common::invalid_argument,
               ERROR_LOCATION,
               fmt::format("unexpected opcode field of the spec: \"{}\"", std::string(Z_STRVAL_P(value), Z_STRLEN_P(value))) } };
}

static std::pair<core::protocol::subdoc_opcode, core_error_info>
decode_mutation_subdoc_opcode(const zval* spec)
{
    if (spec == nullptr || Z_TYPE_P(spec) != IS_ARRAY) {
        return { {}, { errc::common::invalid_argument, ERROR_LOCATION, "expected that spec will be represented as an array" } };
    }
    const zval* value = zend_symtable_str_find(Z_ARRVAL_P(spec), ZEND_STRL("opcode"));
    if (value == nullptr && Z_TYPE_P(value) != IS_STRING) {
        return { {}, { errc::common::invalid_argument, ERROR_LOCATION, "missing opcode field of the spec" } };
    }
    if (zend_binary_strcmp(Z_STRVAL_P(value), Z_STRLEN_P(value), ZEND_STRL("setDocument")) == 0) {
        return { { core::protocol::subdoc_opcode::set_doc }, {} };
    }
    if (zend_binary_strcmp(Z_STRVAL_P(value), Z_STRLEN_P(value), ZEND_STRL("removeDocument")) == 0) {
        return { { core::protocol::subdoc_opcode::remove_doc }, {} };
    }
    if (zend_binary_strcmp(Z_STRVAL_P(value), Z_STRLEN_P(value), ZEND_STRL("dictionaryAdd")) == 0) {
        return { { core::protocol::subdoc_opcode::dict_add }, {} };
    }
    if (zend_binary_strcmp(Z_STRVAL_P(value), Z_STRLEN_P(value), ZEND_STRL("dictionaryUpsert")) == 0) {
        return { { core::protocol::subdoc_opcode::dict_upsert }, {} };
    }
    if (zend_binary_strcmp(Z_STRVAL_P(value), Z_STRLEN_P(value), ZEND_STRL("remove")) == 0) {
        return { { core::protocol::subdoc_opcode::remove }, {} };
    }
    if (zend_binary_strcmp(Z_STRVAL_P(value), Z_STRLEN_P(value), ZEND_STRL("replace")) == 0) {
        return { { core::protocol::subdoc_opcode::replace }, {} };
    }
    if (zend_binary_strcmp(Z_STRVAL_P(value), Z_STRLEN_P(value), ZEND_STRL("arrayPushLast")) == 0) {
        return { { core::protocol::subdoc_opcode::array_push_last }, {} };
    }
    if (zend_binary_strcmp(Z_STRVAL_P(value), Z_STRLEN_P(value), ZEND_STRL("arrayPushFirst")) == 0) {
        return { { core::protocol::subdoc_opcode::array_push_first }, {} };
    }
    if (zend_binary_strcmp(Z_STRVAL_P(value), Z_STRLEN_P(value), ZEND_STRL("arrayInsert")) == 0) {
        return { { core::protocol::subdoc_opcode::array_insert }, {} };
    }
    if (zend_binary_strcmp(Z_STRVAL_P(value), Z_STRLEN_P(value), ZEND_STRL("arrayAddUnique")) == 0) {
        return { { core::protocol::subdoc_opcode::array_add_unique }, {} };
    }
    if (zend_binary_strcmp(Z_STRVAL_P(value), Z_STRLEN_P(value), ZEND_STRL("counter")) == 0) {
        return { { core::protocol::subdoc_opcode::counter }, {} };
    }
    if (zend_binary_strcmp(Z_STRVAL_P(value), Z_STRLEN_P(value), ZEND_STRL("replaceBodyWithXattr")) == 0) {
        return { { core::protocol::subdoc_opcode::replace_body_with_xattr }, {} };
    }
    return { {},
             { errc::common::invalid_argument,
               ERROR_LOCATION,
               fmt::format("unexpected opcode field of the spec: \"{}\"", std::string(Z_STRVAL_P(value), Z_STRLEN_P(value))) } };
}

static void
build_error_context(const couchbase::key_value_error_context& ctx, key_value_error_context& out)
{
    out.bucket = ctx.bucket();
    out.scope = ctx.scope();
    out.collection = ctx.collection();
    out.id = ctx.id();
    out.opaque = ctx.opaque();
    out.cas = ctx.cas().value();
    if (ctx.status_code()) {
        out.status_code = static_cast<std::uint16_t>(ctx.status_code().value());
    }
    if (ctx.error_map_info()) {
        out.error_map_name = ctx.error_map_info()->name();
        out.error_map_description = ctx.error_map_info()->description();
    }
    if (ctx.extended_error_info()) {
        out.enhanced_error_reference = ctx.extended_error_info()->reference();
        out.enhanced_error_context = ctx.extended_error_info()->context();
    }
    out.last_dispatched_to = ctx.last_dispatched_to();
    out.last_dispatched_from = ctx.last_dispatched_from();
    out.retry_attempts = ctx.retry_attempts();
    if (!ctx.retry_reasons().empty()) {
        for (const auto& reason : ctx.retry_reasons()) {
            out.retry_reasons.insert(retry_reason_to_string(reason));
        }
    }
}

static key_value_error_context
build_error_context(const couchbase::key_value_error_context& ctx)
{
    key_value_error_context out;
    build_error_context(ctx, out);
    return out;
}

static subdocument_error_context
build_error_context(const couchbase::subdocument_error_context& ctx)
{
    subdocument_error_context out;
    build_error_context(ctx, out);
    out.deleted = ctx.deleted();
    out.first_error_index = ctx.first_error_index();
    out.first_error_path = ctx.first_error_path();
    return out;
}

static query_error_context
build_error_context(const core::error_context::query& ctx)
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
build_error_context(const core::error_context::analytics& ctx)
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
build_error_context(const core::error_context::view& ctx)
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
build_error_context(const core::error_context::search& ctx)
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
build_error_context(const core::error_context::http& ctx)
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
    explicit impl(couchbase::core::origin origin)
      : origin_(std::move(origin))
    {
    }

    impl(impl&& other) = delete;

    impl(const impl& other) = delete;

    const impl& operator=(impl&& other) = delete;

    const impl& operator=(const impl& other) = delete;

    ~impl()
    {
        stop();
    }

    [[nodiscard]] std::shared_ptr<couchbase::core::cluster> cluster() const
    {
        return cluster_;
    }

    void start()
    {
        worker_ = std::thread([self = shared_from_this()]() { self->ctx_.run(); });
    }

    void stop()
    {
        if (cluster_) {
            auto barrier = std::make_shared<std::promise<void>>();
            auto f = barrier->get_future();
            cluster_->close([barrier]() { barrier->set_value(); });
            f.wait();
            cluster_.reset();
            if (worker_.joinable()) {
                worker_.join();
            }
        }
    }

    std::string cluster_version(const std::string& bucket_name = "")
    {
        auto barrier = std::make_shared<std::promise<couchbase::core::operations::management::cluster_describe_response>>();
        auto f = barrier->get_future();
        cluster_->execute(
          couchbase::core::operations::management::cluster_describe_request{},
          [barrier](couchbase::core::operations::management::cluster_describe_response&& resp) { barrier->set_value(std::move(resp)); });
        auto resp = f.get();
        if (resp.ctx.ec == couchbase::errc::common::service_not_available && !bucket_name.empty()) {
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

    bool replicas_configured_for_bucket(const std::string& bucket_name)
    {
        if (auto e = bucket_open(bucket_name); e.ec) {
            return false;
        }
        auto barrier = std::make_shared<std::promise<std::pair<std::error_code, core::topology::configuration>>>();
        auto f = barrier->get_future();
        cluster_->with_bucket_configuration(bucket_name, [barrier](std::error_code ec, const core::topology::configuration& config) {
            barrier->set_value({ ec, config });
        });
        auto [ec, config] = f.get();
        return !ec && config.num_replicas && config.num_replicas > 0 && config.nodes.size() > config.num_replicas;
    }

    core_error_info open()
    {
        auto barrier = std::make_shared<std::promise<std::error_code>>();
        auto f = barrier->get_future();
        cluster_->open(origin_, [barrier](std::error_code ec) { barrier->set_value(ec); });
        if (auto ec = f.get()) {
            stop();
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
        if (resp.ctx.ec()) {
            return { std::move(resp),
                     { resp.ctx.ec(),
                       ERROR_LOCATION,
                       fmt::format(R"(unable to execute KV operation "{}")", operation),
                       build_error_context(resp.ctx) } };
        }
        return { std::move(resp), {} };
    }

    template<typename Request, typename Response = typename Request::response_type>
    std::pair<Response, core_error_info> http_execute(const char* operation, Request request)
    {
        auto barrier = std::make_shared<std::promise<Response>>();
        auto f = barrier->get_future();
        cluster_->execute(std::move(request), [barrier](Response&& resp) { barrier->set_value(std::move(resp)); });
        auto resp = f.get();
        if (resp.ctx.ec) {
            return { std::move(resp),
                     { resp.ctx.ec,
                       ERROR_LOCATION,
                       fmt::format(R"(unable to execute HTTP operation "{}")", operation),
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

    std::pair<core_error_info, core::diag::ping_result> ping(std::optional<std::string> report_id,
                                                             std::optional<std::string> bucket_name,
                                                             std::set<core::service_type> services)
    {
        std::optional<std::chrono::milliseconds> timeout{}; // not exposing timeout atm

        auto barrier = std::make_shared<std::promise<core::diag::ping_result>>();
        auto f = barrier->get_future();
        cluster_->ping(
          std::move(report_id), std::move(bucket_name), std::move(services), timeout, [barrier](core::diag::ping_result&& resp) {
              barrier->set_value(std::move(resp));
          });
        auto resp = f.get();
        return { {}, std::move(resp) };
    }

    std::pair<core_error_info, core::diag::diagnostics_result> diagnostics(std::string report_id)
    {
        auto barrier = std::make_shared<std::promise<core::diag::diagnostics_result>>();
        auto f = barrier->get_future();
        cluster_->diagnostics(report_id, [barrier](core::diag::diagnostics_result&& resp) { barrier->set_value(std::move(resp)); });
        auto resp = f.get();
        return { {}, std::move(resp) };
    }

    couchbase::collection collection(std::string_view bucket, std::string_view scope, std::string_view collection)
    {
        return couchbase::cluster(*cluster_).bucket(bucket).scope(scope).collection(collection);
    }

    void notify_fork(fork_event event)
    {
        switch (event) {
            case fork_event::prepare:
                ctx_.stop();
                worker_.join();
                ctx_.notify_fork(asio::execution_context::fork_prepare);
                CB_LOG_INFO("Prepare for fork()");
                shutdown_logger();
                break;

            case fork_event::parent:
                initialize_logger();
                CB_LOG_INFO("Resume parent after fork()");
                ctx_.notify_fork(asio::execution_context::fork_parent);
                ctx_.restart();
                worker_ = std::thread([self = shared_from_this()]() { self->ctx_.run(); });
                break;

            case fork_event::child:
                initialize_logger();
                CB_LOG_INFO("Resume child after fork()");
                ctx_.notify_fork(asio::execution_context::fork_child);
                ctx_.restart();
                worker_ = std::thread([self = shared_from_this()]() { self->ctx_.run(); });
                break;
        }
    }

  private:
    asio::io_context ctx_{};
    std::shared_ptr<couchbase::core::cluster> cluster_{ std::make_shared<couchbase::core::cluster>(ctx_) };
    std::thread worker_;
    core::origin origin_;
};

COUCHBASE_API
connection_handle::connection_handle(std::string connection_string,
                                     std::string connection_hash,
                                     couchbase::core::origin origin,
                                     std::chrono::system_clock::time_point idle_expiry)
  : idle_expiry_{ idle_expiry }
  , impl_{ std::make_shared<connection_handle::impl>(std::move(origin)) }
  , connection_string_(std::move(connection_string))
  , connection_hash_(std::move(connection_hash))
{
    impl_->start();
}

connection_handle::~connection_handle()
{
    impl_->stop();
}

COUCHBASE_API
core_error_info
connection_handle::open()
{
    return impl_->open();
}

COUCHBASE_API
std::string
connection_handle::cluster_version(const zend_string* bucket_name)
{
    return impl_->cluster_version(cb_string_new(bucket_name));
}

COUCHBASE_API
bool
connection_handle::replicas_configured_for_bucket(const zend_string* bucket_name)
{
    return impl_->replicas_configured_for_bucket(cb_string_new(bucket_name));
}

void
connection_handle::notify_fork(fork_event event) const
{
    return impl_->notify_fork(event);
}

COUCHBASE_API
core_error_info
connection_handle::bucket_open(const std::string& name)
{
    return impl_->bucket_open(name);
}

COUCHBASE_API
core_error_info
connection_handle::bucket_open(const zend_string* name)
{
    return impl_->bucket_open(cb_string_new(name));
}

COUCHBASE_API
core_error_info
connection_handle::bucket_close(const zend_string* name)
{
    return impl_->bucket_close(cb_string_new(name));
}

template<typename Request>
static core_error_info
cb_assign_user_domain(Request& req, const zval* options)
{
    if (options == nullptr || Z_TYPE_P(options) == IS_NULL) {
        return {};
    }
    if (Z_TYPE_P(options) != IS_ARRAY) {
        return { errc::common::invalid_argument, ERROR_LOCATION, "expected array for options argument" };
    }

    const zval* value = zend_symtable_str_find(Z_ARRVAL_P(options), ZEND_STRL("domain"));
    if (value == nullptr) {
        return {};
    }
    switch (Z_TYPE_P(value)) {
        case IS_NULL:
            return {};
        case IS_STRING:
            break;
        default:
            return { errc::common::invalid_argument, ERROR_LOCATION, "expected domain to be a string in the options" };
    }
    if (zend_binary_strcmp(Z_STRVAL_P(value), Z_STRLEN_P(value), ZEND_STRL("local")) == 0) {
        req.domain = couchbase::core::management::rbac::auth_domain::local;
    } else if (zend_binary_strcmp(Z_STRVAL_P(value), Z_STRLEN_P(value), ZEND_STRL("external")) == 0) {
        req.domain = couchbase::core::management::rbac::auth_domain::external;
    } else {
        return { errc::common::invalid_argument,
                 ERROR_LOCATION,
                 fmt::format("unknown domain: {}", std::string_view(Z_STRVAL_P(value), Z_STRLEN_P(value))) };
    }
    return {};
}

static inline void
mutation_token_to_zval(const mutation_token& token, zval* return_value)
{
    array_init(return_value);
    add_assoc_stringl(return_value, "bucketName", token.bucket_name().data(), token.bucket_name().size());
    add_assoc_long(return_value, "partitionId", token.partition_id());
    auto val = fmt::format("{:x}", token.partition_uuid());
    add_assoc_stringl(return_value, "partitionUuid", val.data(), val.size());
    val = fmt::format("{:x}", token.sequence_number());
    add_assoc_stringl(return_value, "sequenceNumber", val.data(), val.size());
}

static inline bool
is_mutation_token_valid(const mutation_token& token)
{
    return !token.bucket_name().empty() && token.partition_uuid() > 0;
}

COUCHBASE_API
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
    couchbase::upsert_options opts;
    if (auto e = cb_set_timeout(opts, options); e.ec) {
        return e;
    }
    if (auto e = cb_set_durability(opts, options); e.ec) {
        return e;
    }
    if (auto e = cb_set_expiry(opts, options); e.ec) {
        return e;
    }
    if (auto e = cb_set_preserve_expiry(opts, options); e.ec) {
        return e;
    }

    auto [ctx, resp] =
      impl_->collection(cb_string_new(bucket), cb_string_new(scope), cb_string_new(collection))
        .upsert<couchbase::php::passthrough_transcoder>(
          cb_string_new(id), couchbase::codec::encoded_value{ cb_binary_new(value), static_cast<std::uint32_t>(flags) }, opts)
        .get();
    if (ctx.ec()) {
        return { ctx.ec(), ERROR_LOCATION, "unable to execute upsert", build_error_context(ctx) };
    }
    array_init(return_value);
    add_assoc_stringl(return_value, "id", ctx.id().data(), ctx.id().size());
    auto cas = fmt::format("{:x}", resp.cas().value());
    add_assoc_stringl(return_value, "cas", cas.data(), cas.size());
    if (resp.mutation_token() && is_mutation_token_valid(resp.mutation_token().value())) {
        zval token_val;
        mutation_token_to_zval(resp.mutation_token().value(), &token_val);
        add_assoc_zval(return_value, "mutationToken", &token_val);
    }
    return {};
}

COUCHBASE_API
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
    couchbase::insert_options opts;
    if (auto e = cb_set_timeout(opts, options); e.ec) {
        return e;
    }
    if (auto e = cb_set_durability(opts, options); e.ec) {
        return e;
    }
    if (auto e = cb_set_expiry(opts, options); e.ec) {
        return e;
    }

    auto [ctx, resp] =
      impl_->collection(cb_string_new(bucket), cb_string_new(scope), cb_string_new(collection))
        .insert(cb_string_new(id), couchbase::codec::encoded_value{ cb_binary_new(value), static_cast<std::uint32_t>(flags) }, opts)
        .get();
    if (ctx.ec()) {
        return { ctx.ec(), ERROR_LOCATION, "unable to execute insert", build_error_context(ctx) };
    }
    array_init(return_value);
    add_assoc_stringl(return_value, "id", ctx.id().data(), ctx.id().size());
    auto cas = fmt::format("{:x}", resp.cas().value());
    add_assoc_stringl(return_value, "cas", cas.data(), cas.size());
    if (resp.mutation_token() && is_mutation_token_valid(resp.mutation_token().value())) {
        zval token_val;
        mutation_token_to_zval(resp.mutation_token().value(), &token_val);
        add_assoc_zval(return_value, "mutationToken", &token_val);
    }
    return {};
}

COUCHBASE_API
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
    couchbase::replace_options opts;
    if (auto e = cb_set_timeout(opts, options); e.ec) {
        return e;
    }
    if (auto e = cb_set_durability(opts, options); e.ec) {
        return e;
    }
    if (auto e = cb_set_expiry(opts, options); e.ec) {
        return e;
    }
    if (auto e = cb_set_preserve_expiry(opts, options); e.ec) {
        return e;
    }
    if (auto e = cb_set_cas(opts, options); e.ec) {
        return e;
    }

    auto [ctx, resp] =
      impl_->collection(cb_string_new(bucket), cb_string_new(scope), cb_string_new(collection))
        .replace(cb_string_new(id), couchbase::codec::encoded_value{ cb_binary_new(value), static_cast<std::uint32_t>(flags) }, opts)
        .get();
    if (ctx.ec()) {
        return { ctx.ec(), ERROR_LOCATION, "unable to execute replace", build_error_context(ctx) };
    }
    array_init(return_value);
    add_assoc_stringl(return_value, "id", ctx.id().data(), ctx.id().size());
    auto cas = fmt::format("{:x}", resp.cas().value());
    add_assoc_stringl(return_value, "cas", cas.data(), cas.size());
    if (resp.mutation_token() && is_mutation_token_valid(resp.mutation_token().value())) {
        zval token_val;
        mutation_token_to_zval(resp.mutation_token().value(), &token_val);
        add_assoc_zval(return_value, "mutationToken", &token_val);
    }
    return {};
}

COUCHBASE_API
core_error_info
connection_handle::document_append(zval* return_value,
                                   const zend_string* bucket,
                                   const zend_string* scope,
                                   const zend_string* collection,
                                   const zend_string* id,
                                   const zend_string* value,
                                   const zval* options)
{
    couchbase::append_options opts;
    if (auto e = cb_set_timeout(opts, options); e.ec) {
        return e;
    }
    if (auto e = cb_set_durability(opts, options); e.ec) {
        return e;
    }

    auto [ctx, resp] = impl_->collection(cb_string_new(bucket), cb_string_new(scope), cb_string_new(collection))
                         .binary()
                         .append(cb_string_new(id), cb_binary_new(value), opts)
                         .get();
    if (ctx.ec()) {
        return { ctx.ec(), ERROR_LOCATION, "unable to execute append", build_error_context(ctx) };
    }
    array_init(return_value);
    add_assoc_stringl(return_value, "id", ctx.id().data(), ctx.id().size());
    auto cas = fmt::format("{:x}", resp.cas().value());
    add_assoc_stringl(return_value, "cas", cas.data(), cas.size());
    if (resp.mutation_token() && is_mutation_token_valid(resp.mutation_token().value())) {
        zval token_val;
        mutation_token_to_zval(resp.mutation_token().value(), &token_val);
        add_assoc_zval(return_value, "mutationToken", &token_val);
    }
    return {};
}

COUCHBASE_API
core_error_info
connection_handle::document_prepend(zval* return_value,
                                    const zend_string* bucket,
                                    const zend_string* scope,
                                    const zend_string* collection,
                                    const zend_string* id,
                                    const zend_string* value,
                                    const zval* options)
{
    couchbase::prepend_options opts;
    if (auto e = cb_set_timeout(opts, options); e.ec) {
        return e;
    }
    if (auto e = cb_set_durability(opts, options); e.ec) {
        return e;
    }

    auto [ctx, resp] = impl_->collection(cb_string_new(bucket), cb_string_new(scope), cb_string_new(collection))
                         .binary()
                         .prepend(cb_string_new(id), cb_binary_new(value), opts)
                         .get();
    if (ctx.ec()) {
        return { ctx.ec(), ERROR_LOCATION, "unable to execute prepend", build_error_context(ctx) };
    }
    array_init(return_value);
    add_assoc_stringl(return_value, "id", ctx.id().data(), ctx.id().size());
    auto cas = fmt::format("{:x}", resp.cas().value());
    add_assoc_stringl(return_value, "cas", cas.data(), cas.size());
    if (resp.mutation_token() && is_mutation_token_valid(resp.mutation_token().value())) {
        zval token_val;
        mutation_token_to_zval(resp.mutation_token().value(), &token_val);
        add_assoc_zval(return_value, "mutationToken", &token_val);
    }
    return {};
}

COUCHBASE_API
core_error_info
connection_handle::document_increment(zval* return_value,
                                      const zend_string* bucket,
                                      const zend_string* scope,
                                      const zend_string* collection,
                                      const zend_string* id,
                                      const zval* options)
{
    couchbase::increment_options opts;
    if (auto e = cb_set_timeout(opts, options); e.ec) {
        return e;
    }
    if (auto e = cb_set_durability(opts, options); e.ec) {
        return e;
    }
    if (auto e = cb_set_delta(opts, options); e.ec) {
        return e;
    }
    if (auto e = cb_set_initial_value(opts, options); e.ec) {
        return e;
    }
    if (auto e = cb_set_expiry(opts, options); e.ec) {
        return e;
    }

    auto [ctx, resp] = impl_->collection(cb_string_new(bucket), cb_string_new(scope), cb_string_new(collection))
                         .binary()
                         .increment(cb_string_new(id), opts)
                         .get();
    if (ctx.ec()) {
        return { ctx.ec(), ERROR_LOCATION, "unable to execute increment", build_error_context(ctx) };
    }

    array_init(return_value);
    add_assoc_stringl(return_value, "id", ctx.id().data(), ctx.id().size());
    add_assoc_long(return_value, "value", resp.content());
    auto value_str = fmt::format("{}", resp.content());
    add_assoc_stringl(return_value, "valueString", value_str.data(), value_str.size());
    auto cas = fmt::format("{:x}", resp.cas().value());
    add_assoc_stringl(return_value, "cas", cas.data(), cas.size());
    if (resp.mutation_token() && is_mutation_token_valid(resp.mutation_token().value())) {
        zval token_val;
        mutation_token_to_zval(resp.mutation_token().value(), &token_val);
        add_assoc_zval(return_value, "mutationToken", &token_val);
    }
    return {};
}

COUCHBASE_API
core_error_info
connection_handle::document_decrement(zval* return_value,
                                      const zend_string* bucket,
                                      const zend_string* scope,
                                      const zend_string* collection,
                                      const zend_string* id,
                                      const zval* options)
{
    couchbase::decrement_options opts;
    if (auto e = cb_set_timeout(opts, options); e.ec) {
        return e;
    }
    if (auto e = cb_set_durability(opts, options); e.ec) {
        return e;
    }
    if (auto e = cb_set_delta(opts, options); e.ec) {
        return e;
    }
    if (auto e = cb_set_initial_value(opts, options); e.ec) {
        return e;
    }
    if (auto e = cb_set_expiry(opts, options); e.ec) {
        return e;
    }

    auto [ctx, resp] = impl_->collection(cb_string_new(bucket), cb_string_new(scope), cb_string_new(collection))
                         .binary()
                         .decrement(cb_string_new(id), opts)
                         .get();
    if (ctx.ec()) {
        return { ctx.ec(), ERROR_LOCATION, "unable to execute decrement", build_error_context(ctx) };
    }

    array_init(return_value);
    add_assoc_stringl(return_value, "id", ctx.id().data(), ctx.id().size());
    add_assoc_long(return_value, "value", resp.content());
    auto value_str = fmt::format("{}", resp.content());
    add_assoc_stringl(return_value, "valueString", value_str.data(), value_str.size());
    auto cas = fmt::format("{:x}", resp.cas().value());
    add_assoc_stringl(return_value, "cas", cas.data(), cas.size());
    if (resp.mutation_token() && is_mutation_token_valid(resp.mutation_token().value())) {
        zval token_val;
        mutation_token_to_zval(resp.mutation_token().value(), &token_val);
        add_assoc_zval(return_value, "mutationToken", &token_val);
    }
    return {};
}

COUCHBASE_API
core_error_info
connection_handle::document_get(zval* return_value,
                                const zend_string* bucket,
                                const zend_string* scope,
                                const zend_string* collection,
                                const zend_string* id,
                                const zval* options)
{
    couchbase::core::document_id doc_id{
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
        couchbase::core::operations::get_request request{ doc_id };
        if (auto e = cb_assign_timeout(request, options); e.ec) {
            return e;
        }

        auto [resp, err] = impl_->key_value_execute(__func__, std::move(request));
        if (err.ec) {
            return err;
        }
        array_init(return_value);
        add_assoc_stringl(return_value, "id", resp.ctx.id().data(), resp.ctx.id().size());
        auto cas = fmt::format("{:x}", resp.cas.value());
        add_assoc_stringl(return_value, "cas", cas.data(), cas.size());
        add_assoc_long(return_value, "flags", resp.flags);
        add_assoc_stringl(return_value, "value", reinterpret_cast<const char*>(resp.value.data()), resp.value.size());
        return {};
    }
    couchbase::core::operations::get_projected_request request{ doc_id };
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
    add_assoc_stringl(return_value, "id", resp.ctx.id().data(), resp.ctx.id().size());
    auto cas = fmt::format("{:x}", resp.cas.value());
    add_assoc_stringl(return_value, "cas", cas.data(), cas.size());
    add_assoc_long(return_value, "flags", resp.flags);
    add_assoc_stringl(return_value, "value", reinterpret_cast<const char*>(resp.value.data()), resp.value.size());
    if (resp.expiry) {
        add_assoc_long(return_value, "expiry", resp.expiry.value());
    }
    return {};
}

COUCHBASE_API
core_error_info
connection_handle::document_get_any_replica(zval* return_value,
                                            const zend_string* bucket,
                                            const zend_string* scope,
                                            const zend_string* collection,
                                            const zend_string* id,
                                            const zval* options)
{
    couchbase::get_any_replica_options o;
    if (auto e = cb_set_timeout(o, options); e.ec) {
        return e;
    }
    auto c = impl_->collection(cb_string_new(bucket), cb_string_new(scope), cb_string_new(collection));
    auto [ctx, resp] = c.get_any_replica(cb_string_new(id), o).get();
    if (ctx.ec()) {
        return { ctx.ec(), ERROR_LOCATION, fmt::format(R"(unable to execute KV operation "get_any_replica")"), build_error_context(ctx) };
    }
    array_init(return_value);
    add_assoc_stringl(return_value, "id", ctx.id().data(), ctx.id().size());
    auto cas = fmt::format("{:x}", resp.cas().value());
    add_assoc_stringl(return_value, "cas", cas.data(), cas.size());
    auto encoded = resp.content_as<passthrough_transcoder>();
    add_assoc_long(return_value, "flags", encoded.flags);
    add_assoc_bool(return_value, "isReplica", resp.is_replica());
    add_assoc_stringl(return_value, "value", reinterpret_cast<const char*>(encoded.data.data()), encoded.data.size());
    return {};
}

COUCHBASE_API
core_error_info
connection_handle::document_get_all_replicas(zval* return_value,
                                             const zend_string* bucket,
                                             const zend_string* scope,
                                             const zend_string* collection,
                                             const zend_string* id,
                                             const zval* options)
{
    couchbase::get_all_replicas_options o;
    if (auto e = cb_set_timeout(o, options); e.ec) {
        return e;
    }
    auto c = impl_->collection(cb_string_new(bucket), cb_string_new(scope), cb_string_new(collection));
    auto [ctx, responses] = c.get_all_replicas(cb_string_new(id), o).get();
    if (ctx.ec()) {
        return { ctx.ec(), ERROR_LOCATION, fmt::format(R"(unable to execute KV operation "get_all_replicas")"), build_error_context(ctx) };
    }
    array_init_size(return_value, responses.size());
    for (const auto& resp : responses) {
        zval entry;
        array_init(&entry);
        add_assoc_stringl(&entry, "id", ctx.id().data(), ctx.id().size());
        auto cas = fmt::format("{:x}", resp.cas().value());
        add_assoc_stringl(&entry, "cas", cas.data(), cas.size());
        add_assoc_bool(&entry, "isReplica", resp.is_replica());
        auto encoded = resp.content_as<passthrough_transcoder>();
        add_assoc_long(&entry, "flags", encoded.flags);
        add_assoc_stringl(&entry, "value", reinterpret_cast<const char*>(encoded.data.data()), encoded.data.size());
        add_next_index_zval(return_value, &entry);
    }
    return {};
}

COUCHBASE_API
core_error_info
connection_handle::document_get_and_lock(zval* return_value,
                                         const zend_string* bucket,
                                         const zend_string* scope,
                                         const zend_string* collection,
                                         const zend_string* id,
                                         zend_long lock_time,
                                         const zval* options)
{
    couchbase::core::document_id doc_id{
        cb_string_new(bucket),
        cb_string_new(scope),
        cb_string_new(collection),
        cb_string_new(id),
    };

    couchbase::core::operations::get_and_lock_request request{ doc_id };
    if (auto e = cb_assign_timeout(request, options); e.ec) {
        return e;
    }
    request.lock_time = static_cast<std::uint32_t>(lock_time);

    auto [resp, err] = impl_->key_value_execute(__func__, std::move(request));
    if (err.ec) {
        return err;
    }
    array_init(return_value);
    add_assoc_stringl(return_value, "id", resp.ctx.id().data(), resp.ctx.id().size());
    auto cas = fmt::format("{:x}", resp.cas.value());
    add_assoc_stringl(return_value, "cas", cas.data(), cas.size());
    add_assoc_long(return_value, "flags", resp.flags);
    add_assoc_stringl(return_value, "value", reinterpret_cast<const char*>(resp.value.data()), resp.value.size());
    return {};
}

COUCHBASE_API
core_error_info
connection_handle::document_get_and_touch(zval* return_value,
                                          const zend_string* bucket,
                                          const zend_string* scope,
                                          const zend_string* collection,
                                          const zend_string* id,
                                          zend_long expiry,
                                          const zval* options)
{
    couchbase::core::document_id doc_id{
        cb_string_new(bucket),
        cb_string_new(scope),
        cb_string_new(collection),
        cb_string_new(id),
    };

    couchbase::core::operations::get_and_touch_request request{ doc_id };
    if (auto e = cb_assign_timeout(request, options); e.ec) {
        return e;
    }
    request.expiry = static_cast<std::uint32_t>(expiry);

    auto [resp, err] = impl_->key_value_execute(__func__, std::move(request));
    if (err.ec) {
        return err;
    }
    array_init(return_value);
    add_assoc_stringl(return_value, "id", resp.ctx.id().data(), resp.ctx.id().size());
    auto cas = fmt::format("{:x}", resp.cas.value());
    add_assoc_stringl(return_value, "cas", cas.data(), cas.size());
    add_assoc_long(return_value, "flags", resp.flags);
    add_assoc_stringl(return_value, "value", reinterpret_cast<const char*>(resp.value.data()), resp.value.size());
    return {};
}

COUCHBASE_API
core_error_info
connection_handle::document_touch(zval* return_value,
                                  const zend_string* bucket,
                                  const zend_string* scope,
                                  const zend_string* collection,
                                  const zend_string* id,
                                  zend_long expiry,
                                  const zval* options)
{
    couchbase::core::document_id doc_id{
        cb_string_new(bucket),
        cb_string_new(scope),
        cb_string_new(collection),
        cb_string_new(id),
    };

    couchbase::core::operations::touch_request request{ doc_id };
    if (auto e = cb_assign_timeout(request, options); e.ec) {
        return e;
    }
    request.expiry = static_cast<std::uint32_t>(expiry);

    auto [resp, err] = impl_->key_value_execute(__func__, std::move(request));
    if (err.ec) {
        return err;
    }
    array_init(return_value);
    add_assoc_stringl(return_value, "id", resp.ctx.id().data(), resp.ctx.id().size());
    auto cas = fmt::format("{:x}", resp.cas.value());
    add_assoc_stringl(return_value, "cas", cas.data(), cas.size());
    return {};
}

COUCHBASE_API
core_error_info
connection_handle::document_unlock(zval* return_value,
                                   const zend_string* bucket,
                                   const zend_string* scope,
                                   const zend_string* collection,
                                   const zend_string* id,
                                   const zend_string* locked_cas,
                                   const zval* options)
{
    couchbase::core::document_id doc_id{
        cb_string_new(bucket),
        cb_string_new(scope),
        cb_string_new(collection),
        cb_string_new(id),
    };

    couchbase::core::operations::unlock_request request{ doc_id };
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
    add_assoc_stringl(return_value, "id", resp.ctx.id().data(), resp.ctx.id().size());
    auto cas = fmt::format("{:x}", resp.cas.value());
    add_assoc_stringl(return_value, "cas", cas.data(), cas.size());
    return {};
}

COUCHBASE_API
core_error_info
connection_handle::document_remove(zval* return_value,
                                   const zend_string* bucket,
                                   const zend_string* scope,
                                   const zend_string* collection,
                                   const zend_string* id,
                                   const zval* options)
{
    couchbase::remove_options opts;
    if (auto e = cb_set_timeout(opts, options); e.ec) {
        return e;
    }
    if (auto e = cb_set_durability(opts, options); e.ec) {
        return e;
    }
    if (auto e = cb_set_cas(opts, options); e.ec) {
        return e;
    }

    auto [ctx, resp] =
      impl_->collection(cb_string_new(bucket), cb_string_new(scope), cb_string_new(collection)).remove(cb_string_new(id), opts).get();
    if (ctx.ec()) {
        return { ctx.ec(), ERROR_LOCATION, "unable to execute remove", build_error_context(ctx) };
    }
    array_init(return_value);
    add_assoc_stringl(return_value, "id", ctx.id().data(), ctx.id().size());
    auto cas = fmt::format("{:x}", resp.cas().value());
    add_assoc_stringl(return_value, "cas", cas.data(), cas.size());
    if (resp.mutation_token() && is_mutation_token_valid(resp.mutation_token().value())) {
        zval token_val;
        mutation_token_to_zval(resp.mutation_token().value(), &token_val);
        add_assoc_zval(return_value, "mutationToken", &token_val);
    }
    return {};
}

COUCHBASE_API
core_error_info
connection_handle::document_exists(zval* return_value,
                                   const zend_string* bucket,
                                   const zend_string* scope,
                                   const zend_string* collection,
                                   const zend_string* id,
                                   const zval* options)
{
    couchbase::core::document_id doc_id{
        cb_string_new(bucket),
        cb_string_new(scope),
        cb_string_new(collection),
        cb_string_new(id),
    };

    couchbase::core::operations::exists_request request{ doc_id };
    if (auto e = cb_assign_timeout(request, options); e.ec) {
        return e;
    }
    auto [resp, err] = impl_->key_value_execute(__func__, std::move(request));
    if (err.ec && resp.ctx.ec() != errc::key_value::document_not_found) {
        return err;
    }
    array_init(return_value);
    add_assoc_stringl(return_value, "id", resp.ctx.id().data(), resp.ctx.id().size());
    add_assoc_bool(return_value, "exists", resp.exists());
    add_assoc_bool(return_value, "deleted", resp.deleted);
    auto cas = fmt::format("{:x}", resp.cas.value());
    add_assoc_stringl(return_value, "cas", cas.data(), cas.size());
    add_assoc_long(return_value, "flags", resp.flags);
    add_assoc_long(return_value, "datatype", resp.datatype);
    add_assoc_long(return_value, "expiry", resp.expiry);
    auto sequence_number = fmt::format("{:x}", resp.sequence_number);
    add_assoc_stringl(return_value, "sequenceNumber", sequence_number.data(), sequence_number.size());
    return {};
}

COUCHBASE_API
core_error_info
connection_handle::document_mutate_in(zval* return_value,
                                      const zend_string* bucket,
                                      const zend_string* scope,
                                      const zend_string* collection,
                                      const zend_string* id,
                                      const zval* specs,
                                      const zval* options)
{
    couchbase::mutate_in_options opts;
    if (auto e = cb_set_timeout(opts, options); e.ec) {
        return e;
    }
    if (auto e = cb_set_durability(opts, options); e.ec) {
        return e;
    }
    if (auto e = cb_set_access_deleted(opts, options); e.ec) {
        return e;
    }
    if (auto e = cb_set_create_as_deleted(opts, options); e.ec) {
        return e;
    }
    if (auto e = cb_set_expiry(opts, options); e.ec) {
        return e;
    }
    if (auto e = cb_set_cas(opts, options); e.ec) {
        return e;
    }
    if (auto e = cb_set_store_semantics(opts, options); e.ec) {
        return e;
    }

    if (Z_TYPE_P(specs) != IS_ARRAY) {
        return { errc::common::invalid_argument, ERROR_LOCATION, "specs must be an array" };
    }
    couchbase::mutate_in_specs cxx_specs;
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
        bool create_path = false;
        if (e = cb_assign_boolean(create_path, item, "createPath"); e.ec) {
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
            case core::protocol::subdoc_opcode::counter: {
                std::int64_t delta = 0;
                if (e = cb_assign_integer(delta, item, "value"); e.ec) {
                    return e;
                }
                if (delta < 0) {
                    cxx_specs.push_back(mutate_in_specs::decrement(path, -1 * delta).xattr(xattr).create_path(create_path));
                } else {
                    cxx_specs.push_back(mutate_in_specs::increment(path, delta).xattr(xattr).create_path(create_path));
                }
            } break;
            case core::protocol::subdoc_opcode::remove:
            case core::protocol::subdoc_opcode::remove_doc:
                cxx_specs.push_back(mutate_in_specs::remove(path).xattr(xattr));
                break;
            case core::protocol::subdoc_opcode::set_doc:
            case core::protocol::subdoc_opcode::dict_upsert:
            case core::protocol::subdoc_opcode::dict_add:
            case core::protocol::subdoc_opcode::replace:
            case core::protocol::subdoc_opcode::array_push_last:
            case core::protocol::subdoc_opcode::array_push_first:
            case core::protocol::subdoc_opcode::array_insert:
            case core::protocol::subdoc_opcode::array_add_unique: {
                auto [err, value] = cb_get_binary(item, "value");
                if (err.ec) {
                    return e;
                }
                if (!value) {
                    return { errc::common::invalid_argument, ERROR_LOCATION, fmt::format("unexpected value for \"{}\" spec", path) };
                }
                switch (operation) {
                    case core::protocol::subdoc_opcode::set_doc:
                    case core::protocol::subdoc_opcode::dict_upsert:
                        cxx_specs.push_back(mutate_in_specs::upsert_raw(path, value.value()).xattr(xattr).create_path(create_path));
                        break;
                    case core::protocol::subdoc_opcode::dict_add:
                        cxx_specs.push_back(mutate_in_specs::insert_raw(path, value.value()).xattr(xattr).create_path(create_path));
                        break;
                    case core::protocol::subdoc_opcode::replace:
                        cxx_specs.push_back(mutate_in_specs::replace_raw(path, value.value()).xattr(xattr));
                        break;
                    case core::protocol::subdoc_opcode::array_add_unique:
                        cxx_specs.push_back(
                          mutate_in_specs::array_add_unique_raw(path, value.value()).xattr(xattr).create_path(create_path));
                        break;
                    case core::protocol::subdoc_opcode::array_push_last:
                        cxx_specs.push_back(mutate_in_specs::array_append_raw(path, value.value()).xattr(xattr).create_path(create_path));
                        break;
                    case core::protocol::subdoc_opcode::array_push_first:
                        cxx_specs.push_back(mutate_in_specs::array_prepend_raw(path, value.value()).xattr(xattr).create_path(create_path));
                        break;
                    case core::protocol::subdoc_opcode::array_insert:
                        cxx_specs.push_back(mutate_in_specs::array_insert_raw(path, value.value()).xattr(xattr).create_path(create_path));
                        break;
                    default:
                        break;
                }
            } break;
            default:
                break;
        }
    }
    ZEND_HASH_FOREACH_END();

    auto [ctx, resp] = impl_->collection(cb_string_new(bucket), cb_string_new(scope), cb_string_new(collection))
                         .mutate_in(cb_string_new(id), cxx_specs, opts)
                         .get();
    if (ctx.ec()) {
        return { ctx.ec(), ERROR_LOCATION, "unable to execute mutate_in", build_error_context(ctx) };
    }

    array_init(return_value);
    add_assoc_stringl(return_value, "id", ctx.id().data(), ctx.id().size());
    add_assoc_bool(return_value, "deleted", resp.is_deleted());
    auto cas = fmt::format("{:x}", resp.cas().value());
    add_assoc_stringl(return_value, "cas", cas.data(), cas.size());
    if (resp.mutation_token() && is_mutation_token_valid(resp.mutation_token().value())) {
        zval token_val;
        mutation_token_to_zval(resp.mutation_token().value(), &token_val);
        add_assoc_zval(return_value, "mutationToken", &token_val);
    }

    zval fields;
    array_init_size(&fields, cxx_specs.specs().size());
    for (std::size_t idx = 0; idx < cxx_specs.specs().size(); ++idx) {
        zval entry;
        array_init(&entry);
        add_assoc_stringl(&entry, "path", cxx_specs.specs()[idx].path_.data(), cxx_specs.specs()[idx].path_.size());
        if (resp.has_value(idx)) {
            auto value = resp.content_as<tao::json::value>(idx);
            auto str = core::utils::json::generate(value);
            add_assoc_stringl(&entry, "value", str.data(), str.size());
        }
        add_next_index_zval(&fields, &entry);
    }
    add_assoc_zval(return_value, "fields", &fields);
    return {};
}

COUCHBASE_API
core_error_info
connection_handle::document_lookup_in(zval* return_value,
                                      const zend_string* bucket,
                                      const zend_string* scope,
                                      const zend_string* collection,
                                      const zend_string* id,
                                      const zval* specs,
                                      const zval* options)
{
    couchbase::lookup_in_options opts;
    if (auto e = cb_set_timeout(opts, options); e.ec) {
        return e;
    }
    if (auto e = cb_set_access_deleted(opts, options); e.ec) {
        return e;
    }

    if (Z_TYPE_P(specs) != IS_ARRAY) {
        return { errc::common::invalid_argument, ERROR_LOCATION, "specs must be an array" };
    }
    couchbase::lookup_in_specs cxx_specs;

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
        switch (operation) {
            case core::protocol::subdoc_opcode::get_doc:
            case core::protocol::subdoc_opcode::get:
                cxx_specs.push_back(lookup_in_specs::get(path).xattr(xattr));
                break;
            case core::protocol::subdoc_opcode::exists:
                cxx_specs.push_back(lookup_in_specs::exists(path).xattr(xattr));
                break;
            case core::protocol::subdoc_opcode::get_count:
                cxx_specs.push_back(lookup_in_specs::count(path).xattr(xattr));
                break;
            default:
                break;
        }
    }
    ZEND_HASH_FOREACH_END();

    auto [ctx, resp] = impl_->collection(cb_string_new(bucket), cb_string_new(scope), cb_string_new(collection))
                         .lookup_in(cb_string_new(id), cxx_specs, opts)
                         .get();
    if (ctx.ec()) {
        return { ctx.ec(), ERROR_LOCATION, "unable to execute lookup_in", build_error_context(ctx) };
    }

    array_init(return_value);
    add_assoc_stringl(return_value, "id", ctx.id().data(), ctx.id().size());
    add_assoc_bool(return_value, "deleted", resp.is_deleted());
    auto cas = fmt::format("{:x}", resp.cas().value());
    add_assoc_stringl(return_value, "cas", cas.data(), cas.size());
    zval fields;
    array_init_size(&fields, cxx_specs.specs().size());
    for (std::size_t idx = 0; idx < cxx_specs.specs().size(); ++idx) {
        zval entry;
        array_init(&entry);
        add_assoc_stringl(&entry, "path", cxx_specs.specs()[idx].path_.data(), cxx_specs.specs()[idx].path_.size());
        add_assoc_bool(&entry, "exists", resp.exists(idx));
        if (resp.has_value(idx)) {
            auto value = resp.content_as<tao::json::value>(idx);
            auto str = core::utils::json::generate(value);
            add_assoc_stringl(&entry, "value", str.data(), str.size());
        }
        add_next_index_zval(&fields, &entry);
    }
    add_assoc_zval(return_value, "fields", &fields);
    return {};
}

COUCHBASE_API
core_error_info
connection_handle::document_lookup_in_any_replica(zval* return_value,
                                                  const zend_string* bucket,
                                                  const zend_string* scope,
                                                  const zend_string* collection,
                                                  const zend_string* id,
                                                  const zval* specs,
                                                  const zval* options)
{
    couchbase::lookup_in_any_replica_options opts;
    if (auto e = cb_set_timeout(opts, options); e.ec) {
        return e;
    }

    if (Z_TYPE_P(specs) != IS_ARRAY) {
        return { errc::common::invalid_argument, ERROR_LOCATION, "specs must be an array" };
    }
    couchbase::lookup_in_specs cxx_specs;

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
        switch (operation) {
            case core::protocol::subdoc_opcode::get_doc:
            case core::protocol::subdoc_opcode::get:
                cxx_specs.push_back(lookup_in_specs::get(path).xattr(xattr));
                break;
            case core::protocol::subdoc_opcode::exists:
                cxx_specs.push_back(lookup_in_specs::exists(path).xattr(xattr));
                break;
            case core::protocol::subdoc_opcode::get_count:
                cxx_specs.push_back(lookup_in_specs::count(path).xattr(xattr));
                break;
            default:
                break;
        }
    }
    ZEND_HASH_FOREACH_END();

    auto [ctx, resp] = impl_->collection(cb_string_new(bucket), cb_string_new(scope), cb_string_new(collection))
                         .lookup_in_any_replica(cb_string_new(id), cxx_specs, opts)
                         .get();
    if (ctx.ec()) {
        return { ctx.ec(), ERROR_LOCATION, "unable to execute lookup_in_any_replica", build_error_context(ctx) };
    }

    array_init(return_value);
    add_assoc_stringl(return_value, "id", ctx.id().data(), ctx.id().size());
    add_assoc_bool(return_value, "deleted", resp.is_deleted());
    add_assoc_bool(return_value, "isReplica", resp.is_replica());
    auto cas = fmt::format("{:x}", resp.cas().value());
    add_assoc_stringl(return_value, "cas", cas.data(), cas.size());
    zval fields;
    array_init_size(&fields, cxx_specs.specs().size());
    for (std::size_t idx = 0; idx < cxx_specs.specs().size(); ++idx) {
        zval entry;
        array_init(&entry);
        add_assoc_stringl(&entry, "path", cxx_specs.specs()[idx].path_.data(), cxx_specs.specs()[idx].path_.size());
        add_assoc_bool(&entry, "exists", resp.exists(idx));
        if (resp.has_value(idx)) {
            auto value = resp.content_as<tao::json::value>(idx);
            auto str = core::utils::json::generate(value);
            add_assoc_stringl(&entry, "value", str.data(), str.size());
        }
        add_next_index_zval(&fields, &entry);
    }
    add_assoc_zval(return_value, "fields", &fields);
    return {};
}

COUCHBASE_API
core_error_info
connection_handle::document_lookup_in_all_replicas(zval* return_value,
                                                   const zend_string* bucket,
                                                   const zend_string* scope,
                                                   const zend_string* collection,
                                                   const zend_string* id,
                                                   const zval* specs,
                                                   const zval* options)
{
    couchbase::lookup_in_all_replicas_options opts;
    if (auto e = cb_set_timeout(opts, options); e.ec) {
        return e;
    }

    if (Z_TYPE_P(specs) != IS_ARRAY) {
        return { errc::common::invalid_argument, ERROR_LOCATION, "specs must be an array" };
    }
    couchbase::lookup_in_specs cxx_specs;

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
        switch (operation) {
            case core::protocol::subdoc_opcode::get_doc:
            case core::protocol::subdoc_opcode::get:
                cxx_specs.push_back(lookup_in_specs::get(path).xattr(xattr));
                break;
            case core::protocol::subdoc_opcode::exists:
                cxx_specs.push_back(lookup_in_specs::exists(path).xattr(xattr));
                break;
            case core::protocol::subdoc_opcode::get_count:
                cxx_specs.push_back(lookup_in_specs::count(path).xattr(xattr));
                break;
            default:
                break;
        }
    }
    ZEND_HASH_FOREACH_END();

    auto [ctx, responses] = impl_->collection(cb_string_new(bucket), cb_string_new(scope), cb_string_new(collection))
                              .lookup_in_all_replicas(cb_string_new(id), cxx_specs, opts)
                              .get();
    if (ctx.ec()) {
        return { ctx.ec(), ERROR_LOCATION, "unable to execute lookup_in_all_replicas", build_error_context(ctx) };
    }

    array_init_size(return_value, responses.size());
    for (const auto& resp : responses) {
        zval lookup_in_entry;
        array_init(&lookup_in_entry);
        add_assoc_stringl(&lookup_in_entry, "id", ctx.id().data(), ctx.id().size());
        add_assoc_bool(&lookup_in_entry, "deleted", resp.is_deleted());
        add_assoc_bool(&lookup_in_entry, "isReplica", resp.is_replica());
        auto cas = fmt::format("{:x}", resp.cas().value());
        add_assoc_stringl(&lookup_in_entry, "cas", cas.data(), cas.size());
        zval fields;
        array_init_size(&fields, cxx_specs.specs().size());
        for (std::size_t idx = 0; idx < cxx_specs.specs().size(); ++idx) {
            zval entry;
            array_init(&entry);
            add_assoc_stringl(&entry, "path", cxx_specs.specs()[idx].path_.data(), cxx_specs.specs()[idx].path_.size());
            add_assoc_bool(&entry, "exists", resp.exists(idx));
            if (resp.has_value(idx)) {
                auto value = resp.content_as<tao::json::value>(idx);
                auto str = core::utils::json::generate(value);
                add_assoc_stringl(&entry, "value", str.data(), str.size());
            }
            add_next_index_zval(&fields, &entry);
        }
        add_assoc_zval(&lookup_in_entry, "fields", &fields);
        add_next_index_zval(return_value, &lookup_in_entry);
    }
    return {};
}

COUCHBASE_API
core_error_info
connection_handle::document_get_multi(zval* return_value,
                                      const zend_string* bucket,
                                      const zend_string* scope,
                                      const zend_string* collection,
                                      const zval* ids,
                                      const zval* options)
{
    if (Z_TYPE_P(ids) != IS_ARRAY) {
        return { errc::common::invalid_argument, ERROR_LOCATION, "expected ids to be an array" };
    }
    couchbase::get_options opts;
    if (auto e = cb_set_timeout(opts, options); e.ec) {
        return e;
    }

    std::vector<std::string> requests{};
    requests.reserve(zend_array_count(Z_ARRVAL_P(ids)));

    const zval* id = nullptr;
    ZEND_HASH_FOREACH_VAL(Z_ARRVAL_P(ids), id)
    {
        requests.emplace_back(cb_string_new(id));
    }
    ZEND_HASH_FOREACH_END();

    std::vector<std::future<std::pair<couchbase::key_value_error_context, couchbase::get_result>>> futures;
    futures.reserve(requests.size());

    auto c = impl_->collection(cb_string_new(bucket), cb_string_new(scope), cb_string_new(collection));

    for (auto&& request : requests) {
        futures.emplace_back(c.get(std::move(request), opts));
    }

    array_init(return_value);
    for (auto& f : futures) {
        auto [ctx, resp] = f.get();

        zval entry;
        array_init(&entry);
        add_assoc_stringl(&entry, "id", ctx.id().data(), ctx.id().size());
        if (ctx.ec()) {
            zval ex;
            create_exception(&ex, { ctx.ec(), ERROR_LOCATION, "unable to execute KV operation getMulti", build_error_context(ctx) });
            add_assoc_zval(&entry, "error", &ex);
        }
        auto cas = fmt::format("{:x}", resp.cas().value());
        add_assoc_stringl(&entry, "cas", cas.data(), cas.size());
        auto encoded = resp.content_as<passthrough_transcoder>();
        add_assoc_long(&entry, "flags", encoded.flags);
        add_assoc_stringl(&entry, "value", reinterpret_cast<const char*>(encoded.data.data()), encoded.data.size());
        add_next_index_zval(return_value, &entry);
    }
    return {};
}

COUCHBASE_API
core_error_info
connection_handle::document_remove_multi(zval* return_value,
                                         const zend_string* bucket,
                                         const zend_string* scope,
                                         const zend_string* collection,
                                         const zval* entries,
                                         const zval* options)
{
    if (Z_TYPE_P(entries) != IS_ARRAY) {
        return { errc::common::invalid_argument, ERROR_LOCATION, "expected entries to be an array" };
    }
    couchbase::remove_options opts;
    if (auto e = cb_set_timeout(opts, options); e.ec) {
        return e;
    }
    if (auto e = cb_set_durability(opts, options); e.ec) {
        return e;
    }

    std::vector<std::pair<std::string, couchbase::cas>> requests{};
    requests.reserve(zend_array_count(Z_ARRVAL_P(entries)));

    const zval* tuple = nullptr;
    ZEND_HASH_FOREACH_VAL(Z_ARRVAL_P(entries), tuple)
    {
        switch (Z_TYPE_P(tuple)) {
            case IS_STRING: {
                requests.emplace_back(cb_string_new(tuple), couchbase::cas{});
            } break;
            case IS_ARRAY: {
                if (zend_array_count(Z_ARRVAL_P(tuple)) != 2) {
                    return { errc::common::invalid_argument,
                             ERROR_LOCATION,
                             "expected that removeMulti ID-CAS tuples be represented by arrays with exactly two entries" };
                }
                const zval* id = zend_hash_index_find(Z_ARRVAL_P(tuple), 0);
                if (id == nullptr || Z_TYPE_P(id) != IS_STRING) {
                    return { errc::common::invalid_argument,
                             ERROR_LOCATION,
                             "expected that removeMulti first member (ID) of ID-CAS tuple be a string" };
                }
                const zval* cas = zend_hash_index_find(Z_ARRVAL_P(tuple), 1);
                if (cas == nullptr || Z_TYPE_P(cas) != IS_STRING) {
                    return { errc::common::invalid_argument,
                             ERROR_LOCATION,
                             "expected that removeMulti second member (CAS) of ID-CAS tuple be a string" };
                }
                couchbase::cas cas_value{};
                if (auto e = cb_string_to_cas(std::string(Z_STRVAL_P(cas), Z_STRLEN_P(cas)), cas_value); e.ec) {
                    return e;
                }
                requests.emplace_back(cb_string_new(tuple), cas_value);
            } break;
            default:
                return { errc::common::invalid_argument,
                         ERROR_LOCATION,
                         "expected that removeMulti entries will be either ID strings or pairs of ID with CAS" };
                break;
        }
    }
    ZEND_HASH_FOREACH_END();

    std::vector<std::future<std::pair<couchbase::key_value_error_context, couchbase::mutation_result>>> futures;
    futures.reserve(requests.size());

    auto c = impl_->collection(cb_string_new(bucket), cb_string_new(scope), cb_string_new(collection));

    for (auto& [id, content] : requests) {
        futures.emplace_back(c.remove(std::move(id), opts));
    }

    array_init(return_value);
    for (auto& f : futures) {
        auto [ctx, resp] = f.get();

        zval entry;
        array_init(&entry);
        add_assoc_stringl(&entry, "id", ctx.id().data(), ctx.id().size());
        if (ctx.ec()) {
            zval ex;
            create_exception(&ex, { ctx.ec(), ERROR_LOCATION, "unable to execute KV operation removeMulti", build_error_context(ctx) });
            add_assoc_zval(&entry, "error", &ex);
        }
        auto cas = fmt::format("{:x}", resp.cas().value());
        add_assoc_stringl(&entry, "cas", cas.data(), cas.size());
        if (resp.mutation_token() && is_mutation_token_valid(resp.mutation_token().value())) {
            zval token_val;
            mutation_token_to_zval(resp.mutation_token().value(), &token_val);
            add_assoc_zval(&entry, "mutationToken", &token_val);
        }
        add_next_index_zval(return_value, &entry);
    }
    return {};
}

COUCHBASE_API
core_error_info
connection_handle::document_upsert_multi(zval* return_value,
                                         const zend_string* bucket,
                                         const zend_string* scope,
                                         const zend_string* collection,
                                         const zval* entries,
                                         const zval* options)
{
    if (Z_TYPE_P(entries) != IS_ARRAY) {
        return { errc::common::invalid_argument, ERROR_LOCATION, "expected entries to be an array" };
    }
    couchbase::upsert_options opts;
    if (auto e = cb_set_timeout(opts, options); e.ec) {
        return e;
    }
    if (auto e = cb_set_durability(opts, options); e.ec) {
        return e;
    }
    if (auto e = cb_set_preserve_expiry(opts, options); e.ec) {
        return e;
    }

    std::vector<std::pair<std::string, codec::encoded_value>> requests{};
    requests.reserve(zend_array_count(Z_ARRVAL_P(entries)));

    const zval* tuple = nullptr;
    ZEND_HASH_FOREACH_VAL(Z_ARRVAL_P(entries), tuple)
    {
        if (Z_TYPE_P(tuple) != IS_ARRAY || zend_array_count(Z_ARRVAL_P(tuple)) != 3) {
            return { errc::common::invalid_argument,
                     ERROR_LOCATION,
                     "expected that core upsertMulti entries will be ID-VALUE-FLAGS tuples" };
        }
        const zval* id = zend_hash_index_find(Z_ARRVAL_P(tuple), 0);
        if (id == nullptr || Z_TYPE_P(id) != IS_STRING) {
            return { errc::common::invalid_argument,
                     ERROR_LOCATION,
                     "expected that core upsertMulti first member (ID) of ID-VALUE-FLAGS tuple be a string" };
        }
        const zval* value = zend_hash_index_find(Z_ARRVAL_P(tuple), 1);
        if (value == nullptr || Z_TYPE_P(value) != IS_STRING) {
            return { errc::common::invalid_argument,
                     ERROR_LOCATION,
                     "expected that core upsertMulti second member (CAS) of ID-VALUE-FLAGS tuple be a string" };
        }
        const zval* flags = zend_hash_index_find(Z_ARRVAL_P(tuple), 2);
        if (flags == nullptr || Z_TYPE_P(flags) != IS_LONG) {
            return { errc::common::invalid_argument,
                     ERROR_LOCATION,
                     "expected that core upsertMulti third member (FLAGS) of ID-VALUE-FLAGS tuple be an integer" };
        }
        requests.emplace_back(cb_string_new(id), codec::encoded_value{ cb_binary_new(value), static_cast<std::uint32_t>(Z_LVAL_P(flags)) });
    }
    ZEND_HASH_FOREACH_END();

    std::vector<std::future<std::pair<couchbase::key_value_error_context, couchbase::mutation_result>>> futures;
    futures.reserve(requests.size());

    auto c = impl_->collection(cb_string_new(bucket), cb_string_new(scope), cb_string_new(collection));

    for (auto& [id, content] : requests) {
        futures.emplace_back(c.upsert<php::passthrough_transcoder>(std::move(id), content, opts));
    }

    array_init(return_value);
    for (auto& f : futures) {
        auto [ctx, resp] = f.get();

        zval entry;
        array_init(&entry);
        add_assoc_stringl(&entry, "id", ctx.id().data(), ctx.id().size());
        if (ctx.ec()) {
            zval ex;
            create_exception(&ex, { ctx.ec(), ERROR_LOCATION, "unable to execute KV operation upsertMulti", build_error_context(ctx) });
            add_assoc_zval(&entry, "error", &ex);
        }
        auto cas = fmt::format("{:x}", resp.cas().value());
        add_assoc_stringl(&entry, "cas", cas.data(), cas.size());
        if (resp.mutation_token() && is_mutation_token_valid(resp.mutation_token().value())) {
            zval token_val;
            mutation_token_to_zval(resp.mutation_token().value(), &token_val);
            add_assoc_zval(&entry, "mutationToken", &token_val);
        }
        add_next_index_zval(return_value, &entry);
    }
    return {};
}

COUCHBASE_API
core_error_info
connection_handle::query(zval* return_value, const zend_string* statement, const zval* options)
{
    auto [request, e] = zval_to_query_request(statement, options);
    if (e.ec) {
        return e;
    }

    auto [resp, err] = impl_->http_execute(__func__, std::move(request));
    if (err.ec) {
        return err;
    }
    query_response_to_zval(return_value, resp);
    return {};
}

static const char*
cb_analytics_status_str(core::operations::analytics_response::analytics_status status)
{
    switch (status) {
        case couchbase::core::operations::analytics_response::running:
            return "running";
        case couchbase::core::operations::analytics_response::success:
            return "success";
        case couchbase::core::operations::analytics_response::errors:
            return "errors";
        case couchbase::core::operations::analytics_response::completed:
            return "completed";
        case couchbase::core::operations::analytics_response::stopped:
            return "stopped";
        case couchbase::core::operations::analytics_response::timedout:
            return "timedout";
        case couchbase::core::operations::analytics_response::closed:
            return "closed";
        case couchbase::core::operations::analytics_response::fatal:
            return "fatal";
        case couchbase::core::operations::analytics_response::aborted:
            return "aborted";
        case couchbase::core::operations::analytics_response::unknown:
            return "unknown";
        default:
            break;
    }
    return "unknown";
}

COUCHBASE_API
core_error_info
connection_handle::analytics_query(zval* return_value, const zend_string* statement, const zval* options)
{
    couchbase::core::operations::analytics_request request{ cb_string_new(statement) };
    if (auto e = cb_assign_timeout(request, options); e.ec) {
        return e;
    }

    if (auto [e, scan_consistency] = cb_get_string(options, "scanConsistency"); scan_consistency) {
        if (scan_consistency == "notBounded") {
            request.scan_consistency = core::analytics_scan_consistency::not_bounded;
        } else if (scan_consistency == "requestPlus") {
            request.scan_consistency = core::analytics_scan_consistency::request_plus;
        } else if (scan_consistency) {
            return { errc::common::invalid_argument,
                     ERROR_LOCATION,
                     fmt::format("invalid value used for scan consistency: {}", *scan_consistency) };
        }
    } else if (e.ec) {
        return e;
    }

    if (auto e = cb_assign_boolean(request.readonly, options, "readonly"); e.ec) {
        return e;
    }
    if (auto e = cb_assign_boolean(request.priority, options, "priority"); e.ec) {
        return e;
    }
    if (const zval* value = zend_symtable_str_find(Z_ARRVAL_P(options), ZEND_STRL("positionalParameters"));
        value != nullptr && Z_TYPE_P(value) == IS_ARRAY) {
        std::vector<core::json_string> params{};
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
        std::map<std::string, core::json_string> params{};
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
        std::map<std::string, core::json_string> params{};
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
        return e;
    }
    if (auto e = cb_assign_string(request.scope_name, options, "scopeName"); e.ec) {
        return e;
    }
    if (auto e = cb_assign_string(request.bucket_name, options, "bucketName"); e.ec) {
        return e;
    }

    auto [resp, err] = impl_->http_execute(__func__, std::move(request));
    if (err.ec) {
        return err;
    }

    array_init(return_value);

    zval rows;
    array_init(&rows);
    for (const auto& row : resp.rows) {
        add_next_index_stringl(&rows, row.data(), row.size());
    }
    add_assoc_zval(return_value, "rows", &rows);
    {
        zval meta;
        array_init(&meta);
        add_assoc_string(&meta, "clientContextId", resp.meta.client_context_id.c_str());
        add_assoc_string(&meta, "requestId", resp.meta.request_id.c_str());
        add_assoc_string(&meta, "status", cb_analytics_status_str(resp.meta.status));
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
            add_assoc_zval(return_value, "warnings", &warnings);
        }

        add_assoc_zval(return_value, "meta", &meta);
    }

    return {};
}

COUCHBASE_API
core_error_info
connection_handle::search(zval* return_value,
                          const zend_string* index_name,
                          const zend_string* query,
                          const zval* options,
                          const zend_string* vector_search,
                          const zval* vector_options)
{
    auto [request, e] = zval_to_common_search_request(index_name, query, options);
    if (e.ec) {
        return e;
    }

    request.show_request = false;
    request.vector_search = cb_string_new(vector_search);

    if (auto [e, vector_query_combination] = cb_get_string(vector_options, "vectorQueryCombination"); vector_query_combination) {
        if (vector_query_combination == "or") {
            request.vector_query_combination = core::vector_query_combination::combination_or;
        } else if (vector_query_combination == "and") {
            request.vector_query_combination = core::vector_query_combination::combination_and;
        } else if (vector_query_combination) {
            return { errc::common::invalid_argument,
                     ERROR_LOCATION,
                     fmt::format("invalid value used for vector_query_combination: {}", *vector_query_combination) };
        }
    } else if (e.ec) {
        return e;
    }

    auto [resp, err] = impl_->http_execute(__func__, std::move(request));
    if (err.ec) {
        return err;
    }

    search_query_response_to_zval(return_value, resp);
    return {};
}

COUCHBASE_API
core_error_info
connection_handle::search_query(zval* return_value, const zend_string* index_name, const zend_string* query, const zval* options)
{
    auto [request, e] = zval_to_common_search_request(index_name, query, options);

    auto [resp, err] = impl_->http_execute(__func__, std::move(request));
    if (err.ec) {
        return err;
    }
    search_query_response_to_zval(return_value, resp);
    return {};
}

COUCHBASE_API
core_error_info
connection_handle::view_query(zval* return_value,
                              const zend_string* bucket_name,
                              const zend_string* design_document_name,
                              const zend_string* view_name,
                              const zend_long name_space,
                              const zval* options)
{
    core::design_document_namespace cxx_name_space;
    switch (auto name_space_val = static_cast<std::uint32_t>(name_space); name_space_val) {
        case 1:
            cxx_name_space = core::design_document_namespace::development;
            break;

        case 2:
            cxx_name_space = core::design_document_namespace::production;
            break;

        default:
            return { errc::common::invalid_argument, ERROR_LOCATION, fmt::format("invalid value used for namespace: {}", name_space_val) };
    }

    couchbase::core::operations::document_view_request request{
        cb_string_new(bucket_name),
        cb_string_new(design_document_name),
        cb_string_new(view_name),
        cxx_name_space,
    };
    if (auto e = cb_assign_timeout(request, options); e.ec) {
        return e;
    }
    if (auto [e, scan_consistency] = cb_get_string(options, "scanConsistency"); scan_consistency) {
        if (scan_consistency == "notBounded") {
            request.consistency = core::view_scan_consistency::not_bounded;
        } else if (scan_consistency == "requestPlus") {
            request.consistency = core::view_scan_consistency::request_plus;
        } else if (scan_consistency == "updateAfter") {
            request.consistency = core::view_scan_consistency::update_after;
        } else if (scan_consistency) {
            return { errc::common::invalid_argument,
                     ERROR_LOCATION,
                     fmt::format("invalid value used for scan consistency: {}", *scan_consistency) };
        }
    } else if (e.ec) {
        return e;
    }

    if (const zval* value = zend_symtable_str_find(Z_ARRVAL_P(options), ZEND_STRL("keys"));
        value != nullptr && Z_TYPE_P(value) == IS_ARRAY) {
        std::vector<std::string> keys{};
        const zval* item = nullptr;

        ZEND_HASH_FOREACH_VAL(Z_ARRVAL_P(value), item)
        {
            keys.emplace_back(Z_STRVAL_P(item), Z_STRLEN_P(item));
        }
        ZEND_HASH_FOREACH_END();

        request.keys = keys;
    }
    if (auto [e, order] = cb_get_string(options, "order"); order) {
        if (order == "ascending") {
            request.order = core::view_sort_order::ascending;
        } else if (order == "descending") {
            request.order = core::view_sort_order::descending;
        } else if (order) {
            return { errc::common::invalid_argument, ERROR_LOCATION, fmt::format("invalid value used for order: {}", *order) };
        }
    } else if (e.ec) {
        return e;
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
        return e;
    }
    if (auto e = cb_assign_boolean(request.group, options, "group"); e.ec) {
        return e;
    }
    if (auto e = cb_assign_integer(request.group_level, options, "groupLevel"); e.ec) {
        return e;
    }
    if (auto e = cb_assign_integer(request.limit, options, "limit"); e.ec) {
        return e;
    }
    if (auto e = cb_assign_boolean(request.skip, options, "skip"); e.ec) {
        return e;
    }
    if (auto e = cb_assign_string(request.key, options, "key"); e.ec) {
        return e;
    }
    if (auto e = cb_assign_string(request.start_key, options, "startKey"); e.ec) {
        return e;
    }
    if (auto e = cb_assign_string(request.end_key, options, "endKey"); e.ec) {
        return e;
    }
    if (auto e = cb_assign_string(request.start_key_doc_id, options, "startKeyDocId"); e.ec) {
        return e;
    }
    if (auto e = cb_assign_string(request.end_key_doc_id, options, "endKeyDocId"); e.ec) {
        return e;
    }
    if (auto e = cb_assign_boolean(request.inclusive_end, options, "inclusiveEnd"); e.ec) {
        return e;
    }
    //    if (auto e = cb_assign_integer(request.on_error, options, "onError"); e.ec) {
    //        return { nullptr, e };
    //    }
    if (auto e = cb_assign_boolean(request.debug, options, "debug"); e.ec) {
        return e;
    }

    auto [resp, err] = impl_->http_execute(__func__, std::move(request));
    if (err.ec) {
        return err;
    }

    array_init(return_value);

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
    add_assoc_zval(return_value, "rows", &rows);

    {
        zval meta;
        array_init(&meta);
        if (resp.meta.debug_info.has_value()) {
            add_assoc_string(&meta, "debugInfo", resp.meta.debug_info.value().c_str());
        }
        if (resp.meta.total_rows.has_value()) {
            add_assoc_long(&meta, "totalRows", resp.meta.total_rows.value());
        }

        add_assoc_zval(return_value, "meta", &meta);
    }

    return {};
}

COUCHBASE_API
core_error_info
connection_handle::ping(zval* return_value, const zval* options)
{
    std::optional<std::string> report_id;
    if (auto e = cb_assign_string(report_id, options, "reportId"); e.ec) {
        return e;
    }
    std::optional<std::string> bucket_name;
    if (auto e = cb_assign_string(bucket_name, options, "bucketName"); e.ec) {
        return e;
    }
    std::vector<std::string> service_types;
    if (auto e = cb_assign_vector_of_strings(service_types, options, "serviceTypes"); e.ec) {
        return e;
    }
    std::set<core::service_type> cb_services{};
    if (!service_types.empty()) {
        for (const auto& service_type : service_types) {
            if (service_type == "kv") {
                cb_services.emplace(core::service_type::key_value);
            } else if (service_type == "query") {
                cb_services.emplace(core::service_type::query);
            } else if (service_type == "analytics") {
                cb_services.emplace(core::service_type::analytics);
            } else if (service_type == "search") {
                cb_services.emplace(core::service_type::search);
            } else if (service_type == "views") {
                cb_services.emplace(core::service_type::view);
            } else if (service_type == "mgmt") {
                cb_services.emplace(core::service_type::management);
            } else if (service_type == "eventing") {
                cb_services.emplace(core::service_type::eventing);
            } else {
                return { errc::common::invalid_argument,
                         ERROR_LOCATION,
                         fmt::format("invalid value used for service type: {}", service_type) };
            }
        }
    }

    auto [err, resp] = impl_->ping(std::move(report_id), std::move(bucket_name), std::move(cb_services));
    if (err.ec) {
        return { err };
    }

    array_init(return_value);
    add_assoc_string(return_value, "id", resp.id.c_str());
    add_assoc_string(return_value, "sdk", resp.sdk.c_str());
    add_assoc_long(return_value, "version", resp.version);

    zval services;
    array_init(&services);
    for (const auto& [service_type, service_infos] : resp.services) {
        std::string type_str;
        switch (service_type) {
            case core::service_type::key_value:
                type_str = "kv";
                break;
            case core::service_type::query:
                type_str = "query";
                break;
            case core::service_type::analytics:
                type_str = "analytics";
                break;
            case core::service_type::search:
                type_str = "search";
                break;
            case core::service_type::view:
                type_str = "views";
                break;
            case core::service_type::management:
                type_str = "mgmt";
                break;
            case core::service_type::eventing:
                type_str = "eventing";
                break;
        }

        zval endpoints;
        array_init(&endpoints);
        for (const auto& svc : service_infos) {
            zval endpoint;
            array_init(&endpoint);
            add_assoc_string(&endpoint, "id", svc.id.c_str());
            add_assoc_string(&endpoint, "remote", svc.remote.c_str());
            add_assoc_string(&endpoint, "local", svc.local.c_str());
            add_assoc_long(&endpoint, "latencyUs", svc.latency.count());
            std::string state;
            switch (svc.state) {
                case core::diag::ping_state::ok:
                    state = "ok";
                    break;
                case core::diag::ping_state::timeout:
                    state = "timeout";
                    break;
                case core::diag::ping_state::error:
                    state = "error";
                    break;
            }
            add_assoc_string(&endpoint, "state", state.c_str());
            if (svc.bucket) {
                add_assoc_string(&endpoint, "bucket", svc.bucket->c_str());
            }
            if (svc.error) {
                add_assoc_string(&endpoint, "error", svc.error->c_str());
            }
            add_next_index_zval(&endpoints, &endpoint);
        }
        add_assoc_zval(&services, type_str.c_str(), &endpoints);
    }
    add_assoc_zval(return_value, "services", &services);

    return {};
}

COUCHBASE_API
core_error_info
connection_handle::diagnostics(zval* return_value, const zend_string* report_id, const zval* /* options */)
{
    auto [err, resp] = impl_->diagnostics(cb_string_new(report_id));
    if (err.ec) {
        return { err };
    }

    array_init(return_value);
    add_assoc_string(return_value, "id", resp.id.c_str());
    add_assoc_string(return_value, "sdk", resp.sdk.c_str());
    add_assoc_long(return_value, "version", resp.version);

    zval services;
    array_init(&services);
    for (const auto& [service_type, service_infos] : resp.services) {
        std::string type_str;
        switch (service_type) {
            case core::service_type::key_value:
                type_str = "kv";
                break;
            case core::service_type::query:
                type_str = "query";
                break;
            case core::service_type::analytics:
                type_str = "analytics";
                break;
            case core::service_type::search:
                type_str = "search";
                break;
            case core::service_type::view:
                type_str = "views";
                break;
            case core::service_type::management:
                type_str = "mgmt";
                break;
            case core::service_type::eventing:
                type_str = "eventing";
                break;
        }

        zval endpoints;
        array_init(&endpoints);
        for (const auto& svc : service_infos) {
            zval endpoint;
            array_init(&endpoint);
            if (svc.last_activity) {
                add_assoc_long(&endpoint, "lastActivityUs", svc.last_activity->count());
            }
            add_assoc_string(&endpoint, "id", svc.id.c_str());
            add_assoc_string(&endpoint, "remote", svc.remote.c_str());
            add_assoc_string(&endpoint, "local", svc.local.c_str());
            std::string state;
            switch (svc.state) {
                case core::diag::endpoint_state::disconnected:
                    state = "disconnected";
                    break;
                case core::diag::endpoint_state::connecting:
                    state = "connecting";
                    break;
                case core::diag::endpoint_state::connected:
                    state = "connected";
                    break;
                case core::diag::endpoint_state::disconnecting:
                    state = "disconnecting";
                    break;
            }
            add_assoc_string(&endpoint, "state", state.c_str());
            if (svc.details) {
                add_assoc_string(&endpoint, "details", svc.details->c_str());
            }
            add_next_index_zval(&endpoints, &endpoint);
        }
        add_assoc_zval(&services, type_str.c_str(), &endpoints);
    }
    add_assoc_zval(return_value, "services", &services);

    return {};
}

COUCHBASE_API
core_error_info
cb_search_index_to_zval(zval* return_value, const couchbase::core::management::search::index& index)
{
    array_init(return_value);

    add_assoc_string(return_value, "uuid", index.uuid.c_str());
    add_assoc_string(return_value, "name", index.name.c_str());
    add_assoc_string(return_value, "type", index.type.c_str());
    add_assoc_string(return_value, "params_json", index.params_json.c_str());
    add_assoc_string(return_value, "source_uuid", index.source_uuid.c_str());
    add_assoc_string(return_value, "source_name", index.source_name.c_str());
    add_assoc_string(return_value, "source_type", index.source_type.c_str());
    add_assoc_string(return_value, "source_params_json", index.source_params_json.c_str());
    add_assoc_string(return_value, "plan_params_json", index.plan_params_json.c_str());

    return {};
}

COUCHBASE_API
core_error_info
zval_to_search_index(couchbase::core::operations::management::search_index_upsert_request& request, const zval* index)
{
    couchbase::core::management::search::index idx{};
    if (auto e = cb_assign_string(idx.name, index, "name"); e.ec) {
        return e;
    }
    if (auto e = cb_assign_string(idx.type, index, "type"); e.ec) {
        return e;
    }
    if (auto e = cb_assign_string(idx.uuid, index, "uuid"); e.ec) {
        return e;
    }
    if (auto e = cb_assign_string(idx.params_json, index, "params"); e.ec) {
        return e;
    }
    if (auto e = cb_assign_string(idx.source_uuid, index, "sourceUuid"); e.ec) {
        return e;
    }
    if (auto e = cb_assign_string(idx.source_name, index, "sourceName"); e.ec) {
        return e;
    }
    if (auto e = cb_assign_string(idx.source_type, index, "sourceType"); e.ec) {
        return e;
    }
    if (auto e = cb_assign_string(idx.source_params_json, index, "sourceParams"); e.ec) {
        return e;
    }
    if (auto e = cb_assign_string(idx.plan_params_json, index, "planParams"); e.ec) {
        return e;
    }
    request.index = idx;

    return {};
}

COUCHBASE_API
core_error_info
connection_handle::search_index_get(zval* return_value, const zend_string* index_name, const zval* options)
{
    couchbase::core::operations::management::search_index_get_request request{ cb_string_new(index_name) };

    if (auto e = cb_assign_timeout(request, options); e.ec) {
        return e;
    }

    auto [resp, err] = impl_->http_execute(__func__, std::move(request));
    if (err.ec) {
        return err;
    }

    if (auto e = cb_search_index_to_zval(return_value, resp.index); e.ec) {
        return e;
    }

    return {};
}

COUCHBASE_API
core_error_info
connection_handle::search_index_get_all(zval* return_value, const zval* options)
{
    couchbase::core::operations::management::search_index_get_all_request request{};

    if (auto e = cb_assign_timeout(request, options); e.ec) {
        return e;
    }

    auto [resp, err] = impl_->http_execute(__func__, std::move(request));
    if (err.ec) {
        return err;
    }

    array_init(return_value);
    for (const auto& search_index : resp.indexes) {
        zval this_index;
        if (auto e = cb_search_index_to_zval(&this_index, search_index); e.ec) {
            return e;
        }
        add_next_index_zval(return_value, &this_index);
    }

    return {};
}

COUCHBASE_API
core_error_info
connection_handle::search_index_upsert(zval* return_value, const zval* index, const zval* options)
{
    couchbase::core::operations::management::search_index_upsert_request request{};

    if (auto e = zval_to_search_index(request, index); e.ec) {
        return e;
    }

    if (auto e = cb_assign_timeout(request, options); e.ec) {
        return e;
    }

    auto [resp, err] = impl_->http_execute(__func__, std::move(request));
    if (err.ec) {
        return err;
    }

    array_init(return_value);
    add_assoc_string(return_value, "status", resp.status.c_str());
    add_assoc_string(return_value, "error", resp.error.c_str());

    return {};
}

COUCHBASE_API
core_error_info
connection_handle::search_index_drop(zval* return_value, const zend_string* index_name, const zval* options)
{
    couchbase::core::operations::management::search_index_drop_request request{ cb_string_new(index_name) };

    if (auto e = cb_assign_timeout(request, options); e.ec) {
        return e;
    }

    auto [resp, err] = impl_->http_execute(__func__, std::move(request));
    if (err.ec) {
        return err;
    }

    array_init(return_value);
    return {};
}

COUCHBASE_API
core_error_info
connection_handle::search_index_get_documents_count(zval* return_value, const zend_string* index_name, const zval* options)
{
    couchbase::core::operations::management::search_index_get_documents_count_request request{ cb_string_new(index_name) };

    if (auto e = cb_assign_timeout(request, options); e.ec) {
        return e;
    }

    auto [resp, err] = impl_->http_execute(__func__, std::move(request));
    if (err.ec) {
        return err;
    }

    array_init(return_value);
    add_assoc_long(return_value, "count", resp.count);

    return {};
}

COUCHBASE_API
core_error_info
connection_handle::search_index_control_ingest(zval* return_value, const zend_string* index_name, bool pause, const zval* options)
{
    couchbase::core::operations::management::search_index_control_ingest_request request{};
    request.index_name = cb_string_new(index_name);
    request.pause = pause;

    if (auto e = cb_assign_timeout(request, options); e.ec) {
        return e;
    }

    auto [resp, err] = impl_->http_execute(__func__, std::move(request));
    if (err.ec) {
        return err;
    }

    array_init(return_value);
    return {};
}

COUCHBASE_API
core_error_info
connection_handle::search_index_control_query(zval* return_value, const zend_string* index_name, bool allow, const zval* options)
{
    couchbase::core::operations::management::search_index_control_query_request request{};
    request.index_name = cb_string_new(index_name);
    request.allow = allow;

    if (auto e = cb_assign_timeout(request, options); e.ec) {
        return e;
    }

    auto [resp, err] = impl_->http_execute(__func__, std::move(request));
    if (err.ec) {
        return err;
    }

    array_init(return_value);
    return {};
}

COUCHBASE_API
core_error_info
connection_handle::search_index_control_plan_freeze(zval* return_value, const zend_string* index_name, bool freeze, const zval* options)
{
    couchbase::core::operations::management::search_index_control_plan_freeze_request request{};
    request.index_name = cb_string_new(index_name);
    request.freeze = freeze;

    if (auto e = cb_assign_timeout(request, options); e.ec) {
        return e;
    }

    auto [resp, err] = impl_->http_execute(__func__, std::move(request));
    if (err.ec) {
        return err;
    }

    array_init(return_value);
    return {};
}

COUCHBASE_API
core_error_info
connection_handle::search_index_analyze_document(zval* return_value,
                                                 const zend_string* index_name,
                                                 const zend_string* document,
                                                 const zval* options)
{
    couchbase::core::operations::management::search_index_analyze_document_request request{};
    request.index_name = cb_string_new(index_name);
    request.encoded_document = cb_string_new(document);

    if (auto e = cb_assign_timeout(request, options); e.ec) {
        return e;
    }

    auto [resp, err] = impl_->http_execute(__func__, std::move(request));
    if (err.ec) {
        return err;
    }

    array_init(return_value);
    add_assoc_string(return_value, "analysis", resp.analysis.c_str());

    return {};
}

COUCHBASE_API
core_error_info
connection_handle::scope_search_index_get(zval* return_value,
                                          const zend_string* bucket_name,
                                          const zend_string* scope_name,
                                          const zend_string* index_name,
                                          const zval* options)
{
    couchbase::core::operations::management::search_index_get_request request{ cb_string_new(index_name) };

    request.bucket_name = cb_string_new(bucket_name);
    request.scope_name = cb_string_new(scope_name);

    if (auto e = cb_assign_timeout(request, options); e.ec) {
        return e;
    }

    auto [resp, err] = impl_->http_execute(__func__, std::move(request));
    if (err.ec) {
        return err;
    }

    if (auto e = cb_search_index_to_zval(return_value, resp.index); e.ec) {
        return e;
    }

    return {};
}

COUCHBASE_API
core_error_info
connection_handle::scope_search_index_get_all(zval* return_value,
                                              const zend_string* bucket_name,
                                              const zend_string* scope_name,
                                              const zval* options)
{
    couchbase::core::operations::management::search_index_get_all_request request{};

    request.bucket_name = cb_string_new(bucket_name);
    request.scope_name = cb_string_new(scope_name);

    if (auto e = cb_assign_timeout(request, options); e.ec) {
        return e;
    }

    auto [resp, err] = impl_->http_execute(__func__, std::move(request));
    if (err.ec) {
        return err;
    }

    array_init(return_value);
    for (const auto& search_index : resp.indexes) {
        zval this_index;
        if (auto e = cb_search_index_to_zval(&this_index, search_index); e.ec) {
            return e;
        }
        add_next_index_zval(return_value, &this_index);
    }

    return {};
}

COUCHBASE_API
core_error_info
connection_handle::scope_search_index_upsert(zval* return_value,
                                             const zend_string* bucket_name,
                                             const zend_string* scope_name,
                                             const zval* index,
                                             const zval* options)
{
    couchbase::core::operations::management::search_index_upsert_request request{};

    request.bucket_name = cb_string_new(bucket_name);
    request.scope_name = cb_string_new(scope_name);

    if (auto e = zval_to_search_index(request, index); e.ec) {
        return e;
    }

    if (auto e = cb_assign_timeout(request, options); e.ec) {
        return e;
    }

    auto [resp, err] = impl_->http_execute(__func__, std::move(request));
    if (err.ec) {
        return err;
    }

    array_init(return_value);
    add_assoc_string(return_value, "status", resp.status.c_str());
    add_assoc_string(return_value, "error", resp.error.c_str());

    return {};
}

COUCHBASE_API
core_error_info
connection_handle::scope_search_index_drop(zval* return_value,
                                           const zend_string* bucket_name,
                                           const zend_string* scope_name,
                                           const zend_string* index_name,
                                           const zval* options)
{
    couchbase::core::operations::management::search_index_drop_request request{ cb_string_new(index_name) };

    request.bucket_name = cb_string_new(bucket_name);
    request.scope_name = cb_string_new(scope_name);

    if (auto e = cb_assign_timeout(request, options); e.ec) {
        return e;
    }

    auto [resp, err] = impl_->http_execute(__func__, std::move(request));
    if (err.ec) {
        return err;
    }

    array_init(return_value);
    return {};
}

COUCHBASE_API
core_error_info
connection_handle::scope_search_index_get_documents_count(zval* return_value,
                                                          const zend_string* bucket_name,
                                                          const zend_string* scope_name,
                                                          const zend_string* index_name,
                                                          const zval* options)
{
    couchbase::core::operations::management::search_index_get_documents_count_request request{ cb_string_new(index_name) };

    request.bucket_name = cb_string_new(bucket_name);
    request.scope_name = cb_string_new(scope_name);

    if (auto e = cb_assign_timeout(request, options); e.ec) {
        return e;
    }

    auto [resp, err] = impl_->http_execute(__func__, std::move(request));
    if (err.ec) {
        return err;
    }

    array_init(return_value);
    add_assoc_long(return_value, "count", resp.count);

    return {};
}

COUCHBASE_API
core_error_info
connection_handle::scope_search_index_control_ingest(zval* return_value,
                                                     const zend_string* bucket_name,
                                                     const zend_string* scope_name,
                                                     const zend_string* index_name,
                                                     bool pause,
                                                     const zval* options)
{
    couchbase::core::operations::management::search_index_control_ingest_request request{};

    request.bucket_name = cb_string_new(bucket_name);
    request.scope_name = cb_string_new(scope_name);

    request.index_name = cb_string_new(index_name);
    request.pause = pause;

    if (auto e = cb_assign_timeout(request, options); e.ec) {
        return e;
    }

    auto [resp, err] = impl_->http_execute(__func__, std::move(request));
    if (err.ec) {
        return err;
    }

    array_init(return_value);
    return {};
}

COUCHBASE_API
core_error_info
connection_handle::scope_search_index_control_query(zval* return_value,
                                                    const zend_string* bucket_name,
                                                    const zend_string* scope_name,
                                                    const zend_string* index_name,
                                                    bool allow,
                                                    const zval* options)
{
    couchbase::core::operations::management::search_index_control_query_request request{};

    request.bucket_name = cb_string_new(bucket_name);
    request.scope_name = cb_string_new(scope_name);

    request.index_name = cb_string_new(index_name);
    request.allow = allow;

    if (auto e = cb_assign_timeout(request, options); e.ec) {
        return e;
    }

    auto [resp, err] = impl_->http_execute(__func__, std::move(request));
    if (err.ec) {
        return err;
    }

    array_init(return_value);
    return {};
}

COUCHBASE_API
core_error_info
connection_handle::scope_search_index_control_plan_freeze(zval* return_value,
                                                          const zend_string* bucket_name,
                                                          const zend_string* scope_name,
                                                          const zend_string* index_name,
                                                          bool freeze,
                                                          const zval* options)
{
    couchbase::core::operations::management::search_index_control_plan_freeze_request request{};

    request.bucket_name = cb_string_new(bucket_name);
    request.scope_name = cb_string_new(scope_name);

    request.index_name = cb_string_new(index_name);
    request.freeze = freeze;

    if (auto e = cb_assign_timeout(request, options); e.ec) {
        return e;
    }

    auto [resp, err] = impl_->http_execute(__func__, std::move(request));
    if (err.ec) {
        return err;
    }

    array_init(return_value);
    return {};
}

COUCHBASE_API
core_error_info
connection_handle::scope_search_index_analyze_document(zval* return_value,
                                                       const zend_string* bucket_name,
                                                       const zend_string* scope_name,
                                                       const zend_string* index_name,
                                                       const zend_string* document,
                                                       const zval* options)
{
    couchbase::core::operations::management::search_index_analyze_document_request request{};

    request.bucket_name = cb_string_new(bucket_name);
    request.scope_name = cb_string_new(scope_name);

    request.index_name = cb_string_new(index_name);
    request.encoded_document = cb_string_new(document);

    if (auto e = cb_assign_timeout(request, options); e.ec) {
        return e;
    }

    auto [resp, err] = impl_->http_execute(__func__, std::move(request));
    if (err.ec) {
        return err;
    }

    array_init(return_value);
    add_assoc_string(return_value, "analysis", resp.analysis.c_str());

    return {};
}

COUCHBASE_API
core_error_info
connection_handle::view_index_upsert(zval* return_value,
                                     const zend_string* bucket_name,
                                     const zval* design_document,
                                     zend_long name_space,
                                     const zval* options)
{
    couchbase::core::management::views::design_document idx{};
    if (auto e = cb_assign_string(idx.name, design_document, "name"); e.ec) {
        return e;
    }
    if (auto e = cb_assign_string(idx.rev, design_document, "rev"); e.ec) {
        return e;
    }
    switch (name_space) {
        case 1:
            idx.ns = core::design_document_namespace::development;
            break;

        case 2:
            idx.ns = core::design_document_namespace::production;
            break;

        default:
            return { errc::common::invalid_argument, ERROR_LOCATION, fmt::format("invalid value used for namespace: {}", name_space) };
    }

    if (const zval* value = zend_symtable_str_find(Z_ARRVAL_P(design_document), ZEND_STRL("views"));
        value != nullptr && Z_TYPE_P(value) == IS_ARRAY) {
        std::map<std::string, couchbase::core::management::views::design_document::view> views{};
        const zend_string* key = nullptr;
        const zval* item = nullptr;

        ZEND_HASH_FOREACH_STR_KEY_VAL(Z_ARRVAL_P(value), key, item)
        {
            couchbase::core::management::views::design_document::view view{};
            if (auto e = cb_assign_string(view.name, item, "name"); e.ec) {
                return e;
            }
            if (auto e = cb_assign_string(view.map, item, "map"); e.ec) {
                return e;
            }
            if (auto e = cb_assign_string(view.reduce, item, "reduce"); e.ec) {
                return e;
            }

            views[cb_string_new(key)] = view;
        }
        ZEND_HASH_FOREACH_END();

        idx.views = views;
    }

    couchbase::core::operations::management::view_index_upsert_request request{ cb_string_new(bucket_name), idx };

    if (auto e = cb_assign_timeout(request, options); e.ec) {
        return e;
    }

    auto [resp, err] = impl_->http_execute(__func__, std::move(request));
    if (err.ec) {
        return err;
    }

    array_init(return_value);

    return {};
}

static std::pair<core_error_info, couchbase::core::management::cluster::bucket_settings>
zval_to_bucket_settings(const zval* bucket_settings)
{
    couchbase::core::management::cluster::bucket_settings bucket{};
    if (auto e = cb_assign_string(bucket.name, bucket_settings, "name"); e.ec) {
        return { e, {} };
    }
    if (auto [e, bucket_type] = cb_get_string(bucket_settings, "bucketType"); bucket_type) {
        if (bucket_type == "couchbase") {
            bucket.bucket_type = couchbase::core::management::cluster::bucket_type::couchbase;
        } else if (bucket_type == "ephemeral") {
            bucket.bucket_type = couchbase::core::management::cluster::bucket_type::ephemeral;
        } else if (bucket_type == "memcached") {
            bucket.bucket_type = couchbase::core::management::cluster::bucket_type::memcached;
        } else if (bucket_type) {
            return {
                { errc::common::invalid_argument, ERROR_LOCATION, fmt::format("invalid value used for bucket type: {}", *bucket_type) }, {}
            };
        }
    } else if (e.ec) {
        return { e, {} };
    }
    if (auto e = cb_assign_integer(bucket.ram_quota_mb, bucket_settings, "ramQuotaMB"); e.ec) {
        return { e, {} };
    }
    if (auto e = cb_assign_integer(bucket.max_expiry, bucket_settings, "maxExpiry"); e.ec) {
        return { e, {} };
    }
    if (auto [e, compression_mode] = cb_get_string(bucket_settings, "compressionMode"); compression_mode) {
        if (compression_mode == "off") {
            bucket.compression_mode = couchbase::core::management::cluster::bucket_compression::off;
        } else if (compression_mode == "active") {
            bucket.compression_mode = couchbase::core::management::cluster::bucket_compression::active;
        } else if (compression_mode == "passive") {
            bucket.compression_mode = couchbase::core::management::cluster::bucket_compression::passive;
        } else if (compression_mode) {
            return { { errc::common::invalid_argument,
                       ERROR_LOCATION,
                       fmt::format("invalid value used for compression mode: {}", *compression_mode) },
                     {} };
        }
    } else if (e.ec) {
        return { e, {} };
    }
    if (auto [e, durability_level] = cb_get_string(bucket_settings, "minimumDurabilityLevel"); durability_level) {
        if (durability_level == "none") {
            bucket.minimum_durability_level = durability_level::none;
        } else if (durability_level == "majority") {
            bucket.minimum_durability_level = durability_level::majority;
        } else if (durability_level == "majorityAndPersistToActive") {
            bucket.minimum_durability_level = durability_level::majority_and_persist_to_active;
        } else if (durability_level == "persistToMajority") {
            bucket.minimum_durability_level = durability_level::persist_to_majority;
        } else if (durability_level) {
            return { { errc::common::invalid_argument,
                       ERROR_LOCATION,
                       fmt::format("invalid value used for durability level: {}", *durability_level) },
                     {} };
        }
    } else if (e.ec) {
        return { e, {} };
    }
    if (auto e = cb_assign_integer(bucket.num_replicas, bucket_settings, "numReplicas"); e.ec) {
        return { e, {} };
    }
    if (auto e = cb_assign_boolean(bucket.replica_indexes, bucket_settings, "replicaIndexes"); e.ec) {
        return { e, {} };
    }
    if (auto e = cb_assign_boolean(bucket.flush_enabled, bucket_settings, "flushEnabled"); e.ec) {
        return { e, {} };
    }
    if (auto [e, eviction_policy] = cb_get_string(bucket_settings, "evictionPolicy"); eviction_policy) {
        if (eviction_policy == "noEviction") {
            bucket.eviction_policy = couchbase::core::management::cluster::bucket_eviction_policy::no_eviction;
        } else if (eviction_policy == "fullEviction") {
            bucket.eviction_policy = couchbase::core::management::cluster::bucket_eviction_policy::full;
        } else if (eviction_policy == "valueOnly") {
            bucket.eviction_policy = couchbase::core::management::cluster::bucket_eviction_policy::value_only;
        } else if (eviction_policy == "nruEviction") {
            bucket.eviction_policy = couchbase::core::management::cluster::bucket_eviction_policy::not_recently_used;
        } else if (eviction_policy) {
            return { { errc::common::invalid_argument,
                       ERROR_LOCATION,
                       fmt::format("invalid value used for eviction policy: {}", *eviction_policy) },
                     {} };
        }
    } else if (e.ec) {
        return { e, {} };
    }
    if (auto [e, resolution_type] = cb_get_string(bucket_settings, "conflictResolutionType"); resolution_type) {
        if (resolution_type == "sequenceNumber") {
            bucket.conflict_resolution_type = couchbase::core::management::cluster::bucket_conflict_resolution::sequence_number;
        } else if (resolution_type == "timestamp") {
            bucket.conflict_resolution_type = couchbase::core::management::cluster::bucket_conflict_resolution::timestamp;
        } else if (resolution_type == "custom") {
            bucket.conflict_resolution_type = couchbase::core::management::cluster::bucket_conflict_resolution::custom;
        } else if (resolution_type) {
            return { { errc::common::invalid_argument,
                       ERROR_LOCATION,
                       fmt::format("invalid value used for custom resolution type: {}", *resolution_type) },
                     {} };
        }
    } else if (e.ec) {
        return { e, {} };
    }
    if (auto [e, storage_backend] = cb_get_string(bucket_settings, "storageBackend"); storage_backend) {
        if (storage_backend == "couchstore") {
            bucket.storage_backend = couchbase::core::management::cluster::bucket_storage_backend::couchstore;
        } else if (storage_backend == "magma") {
            bucket.storage_backend = couchbase::core::management::cluster::bucket_storage_backend::magma;
        } else if (storage_backend) {
            return { { errc::common::invalid_argument,
                       ERROR_LOCATION,
                       fmt::format("invalid value used for storage backend: {}", *storage_backend) },
                     {} };
        }
    } else if (e.ec) {
        return { e, {} };
    }
    if (auto e = cb_assign_boolean(bucket.history_retention_collection_default, bucket_settings, "historyRetentionCollectionDefault");
        e.ec) {
        return { e, {} };
    }
    if (auto e = cb_assign_integer(bucket.history_retention_bytes, bucket_settings, "historyRetentionBytes"); e.ec) {
        return { e, {} };
    }
    if (auto e = cb_assign_integer(bucket.history_retention_duration, bucket_settings, "historyRetentionDuration"); e.ec) {
        return { e, {} };
    }

    return { {}, bucket };
}

COUCHBASE_API
core_error_info
connection_handle::bucket_create(zval* return_value, const zval* bucket_settings, const zval* options)
{
    auto [e, bucket] = zval_to_bucket_settings(bucket_settings);
    if (e.ec) {
        return e;
    }

    couchbase::core::operations::management::bucket_create_request request{ bucket };

    if (auto e = cb_assign_timeout(request, options); e.ec) {
        return e;
    }

    auto [resp, err] = impl_->http_execute(__func__, std::move(request));
    if (err.ec) {
        return err;
    }

    array_init(return_value);
    return {};
}

COUCHBASE_API
core_error_info
connection_handle::bucket_update(zval* return_value, const zval* bucket_settings, const zval* options)
{
    auto [e, bucket] = zval_to_bucket_settings(bucket_settings);
    if (e.ec) {
        return e;
    }

    couchbase::core::operations::management::bucket_update_request request{ bucket };

    if (auto e = cb_assign_timeout(request, options); e.ec) {
        return e;
    }

    auto [resp, err] = impl_->http_execute(__func__, std::move(request));
    if (err.ec) {
        return err;
    }

    array_init(return_value);
    return {};
}

COUCHBASE_API
core_error_info
cb_bucket_settings_to_zval(zval* return_value, const couchbase::core::management::cluster::bucket_settings& bucket_settings)
{
    array_init(return_value);

    add_assoc_string(return_value, "name", bucket_settings.name.c_str());
    add_assoc_string(return_value, "uuid", bucket_settings.uuid.c_str());
    std::string bucket_type;
    switch (bucket_settings.bucket_type) {
        case couchbase::core::management::cluster::bucket_type::couchbase:
            bucket_type = "couchbase";
            break;
        case couchbase::core::management::cluster::bucket_type::ephemeral:
            bucket_type = "ephemeral";
            break;
        case couchbase::core::management::cluster::bucket_type::memcached:
            bucket_type = "memcached";
            break;
        default:
            bucket_type = "unknown";
            break;
    }
    add_assoc_string(return_value, "bucketType", bucket_type.c_str());
    add_assoc_long(return_value, "ramQuotaMB", bucket_settings.ram_quota_mb);
    if (bucket_settings.max_expiry.has_value()) {
        add_assoc_long(return_value, "maxExpiry", bucket_settings.max_expiry.value());
    }
    std::string compression_mode;
    switch (bucket_settings.compression_mode) {
        case couchbase::core::management::cluster::bucket_compression::off:
            compression_mode = "off";
            break;
        case couchbase::core::management::cluster::bucket_compression::active:
            compression_mode = "active";
            break;
        case couchbase::core::management::cluster::bucket_compression::passive:
            compression_mode = "passive";
            break;
        default:
            compression_mode = "unknown";
            break;
    }
    add_assoc_string(return_value, "compressionMode", compression_mode.c_str());
    if (bucket_settings.minimum_durability_level) {
        std::string durability_level;
        switch (*bucket_settings.minimum_durability_level) {
            case durability_level::none:
                durability_level = "none";
                break;
            case durability_level::majority:
                durability_level = "majority";
                break;
            case durability_level::majority_and_persist_to_active:
                durability_level = "majorityAndPersistToActive";
                break;
            case durability_level::persist_to_majority:
                durability_level = "persistToMajority";
                break;
        }
        add_assoc_string(return_value, "minimumDurabilityLevel", durability_level.c_str());
    }
    if (bucket_settings.num_replicas.has_value()) {
        add_assoc_long(return_value, "numReplicas", bucket_settings.num_replicas.value());
    }
    if (bucket_settings.replica_indexes.has_value()) {
        add_assoc_bool(return_value, "replicaIndexes", bucket_settings.replica_indexes.value());
    }
    if (bucket_settings.flush_enabled.has_value()) {
        add_assoc_bool(return_value, "flushEnabled", bucket_settings.flush_enabled.value());
    }
    std::string eviction_policy;
    switch (bucket_settings.eviction_policy) {
        case couchbase::core::management::cluster::bucket_eviction_policy::no_eviction:
            eviction_policy = "noEviction";
            break;
        case couchbase::core::management::cluster::bucket_eviction_policy::not_recently_used:
            eviction_policy = "nruEviction";
            break;
        case couchbase::core::management::cluster::bucket_eviction_policy::value_only:
            eviction_policy = "valueOnly";
            break;
        case couchbase::core::management::cluster::bucket_eviction_policy::full:
            eviction_policy = "fullEviction";
            break;
        default:
            eviction_policy = "unknown";
            break;
    }
    add_assoc_string(return_value, "evictionPolicy", eviction_policy.c_str());
    std::string conflict_resolution_type;
    switch (bucket_settings.conflict_resolution_type) {
        case couchbase::core::management::cluster::bucket_conflict_resolution::sequence_number:
            conflict_resolution_type = "sequenceNumber";
            break;
        case couchbase::core::management::cluster::bucket_conflict_resolution::timestamp:
            conflict_resolution_type = "timestamp";
            break;
        case couchbase::core::management::cluster::bucket_conflict_resolution::custom:
            conflict_resolution_type = "custom";
            break;
        default:
            conflict_resolution_type = "unknown";
            break;
    }
    add_assoc_string(return_value, "conflictResolutionType", conflict_resolution_type.c_str());
    std::string storage_backend;
    switch (bucket_settings.storage_backend) {
        case couchbase::core::management::cluster::bucket_storage_backend::couchstore:
            storage_backend = "couchstore";
            break;
        case couchbase::core::management::cluster::bucket_storage_backend::magma:
            storage_backend = "magma";
            break;
        default:
            storage_backend = "unknown";
            break;
    }
    add_assoc_string(return_value, "storageBackend", storage_backend.c_str());
    if (bucket_settings.history_retention_collection_default.has_value()) {
        add_assoc_bool(return_value, "historyRetentionCollectionDefault", bucket_settings.history_retention_collection_default.value());
    }
    if (bucket_settings.history_retention_bytes.has_value()) {
        add_assoc_long(return_value, "historyRetentionBytes", bucket_settings.history_retention_bytes.value());
    }
    if (bucket_settings.history_retention_duration.has_value()) {
        add_assoc_long(return_value, "historyRetentionDuration", bucket_settings.history_retention_duration.value());
    }

    return {};
}

COUCHBASE_API
core_error_info
connection_handle::bucket_get(zval* return_value, const zend_string* name, const zval* options)
{
    couchbase::core::operations::management::bucket_get_request request{ cb_string_new(name) };

    if (auto e = cb_assign_timeout(request, options); e.ec) {
        return e;
    }

    auto [resp, err] = impl_->http_execute(__func__, std::move(request));
    if (err.ec) {
        return err;
    }

    if (auto e = cb_bucket_settings_to_zval(return_value, resp.bucket); e.ec) {
        return e;
    }

    return {};
}

COUCHBASE_API
core_error_info
connection_handle::bucket_get_all(zval* return_value, const zval* options)
{
    couchbase::core::operations::management::bucket_get_all_request request{};

    if (auto e = cb_assign_timeout(request, options); e.ec) {
        return e;
    }

    auto [resp, err] = impl_->http_execute(__func__, std::move(request));
    if (err.ec) {
        return err;
    }

    array_init(return_value);
    for (const auto& bucket_settings : resp.buckets) {
        zval this_settings;
        if (auto e = cb_bucket_settings_to_zval(&this_settings, bucket_settings); e.ec) {
            return e;
        }

        add_next_index_zval(return_value, &this_settings);
    }

    return {};
}

COUCHBASE_API
core_error_info
connection_handle::bucket_drop(zval* return_value, const zend_string* name, const zval* options)
{
    couchbase::core::operations::management::bucket_drop_request request{ cb_string_new(name) };

    if (auto e = cb_assign_timeout(request, options); e.ec) {
        return e;
    }

    auto [resp, err] = impl_->http_execute(__func__, std::move(request));
    if (err.ec) {
        return err;
    }

    array_init(return_value);
    return {};
}

COUCHBASE_API
core_error_info
connection_handle::bucket_flush(zval* return_value, const zend_string* name, const zval* options)
{
    couchbase::core::operations::management::bucket_flush_request request{ cb_string_new(name) };

    if (auto e = cb_assign_timeout(request, options); e.ec) {
        return e;
    }

    auto [resp, err] = impl_->http_execute(__func__, std::move(request));
    if (err.ec) {
        return err;
    }

    array_init(return_value);
    return {};
}

COUCHBASE_API
core_error_info
connection_handle::scope_get_all(zval* return_value, const zend_string* bucket_name, const zval* options)
{
    couchbase::core::operations::management::scope_get_all_request request{};

    if (auto e = cb_assign_timeout(request, options); e.ec) {
        return e;
    }
    request.bucket_name = cb_string_new(bucket_name);

    auto [resp, err] = impl_->http_execute(__func__, std::move(request));
    if (err.ec) {
        return err;
    }

    array_init(return_value);

    zval scopes;
    array_init(&scopes);
    for (const auto& s : resp.manifest.scopes) {
        zval scope;
        array_init(&scope);
        add_assoc_string(&scope, "name", s.name.c_str());
        zval collections;
        array_init(&collections);
        for (const auto& c : s.collections) {
            zval collection;
            array_init(&collection);
            add_assoc_string(&collection, "name", c.name.c_str());
            add_assoc_long(&collection, "max_expiry", c.max_expiry);
            if (c.history.has_value()) {
                add_assoc_bool(&collection, "history", c.history.value());
            }
            add_next_index_zval(&collections, &collection);
        }
        add_assoc_zval(&scope, "collections", &collections);
        add_next_index_zval(&scopes, &scope);
    }
    add_assoc_zval(return_value, "scopes", &scopes);

    return {};
}

COUCHBASE_API
core_error_info
connection_handle::scope_create(zval* return_value, const zend_string* bucket_name, const zend_string* scope_name, const zval* options)
{
    couchbase::core::operations::management::scope_create_request request{};

    if (auto e = cb_assign_timeout(request, options); e.ec) {
        return e;
    }
    request.bucket_name = cb_string_new(bucket_name);
    request.scope_name = cb_string_new(scope_name);

    auto [resp, err] = impl_->http_execute(__func__, std::move(request));
    if (err.ec) {
        return err;
    }

    array_init(return_value);
    return {};
}

COUCHBASE_API
core_error_info
connection_handle::scope_drop(zval* return_value, const zend_string* bucket_name, const zend_string* scope_name, const zval* options)
{
    couchbase::core::operations::management::scope_drop_request request{};

    if (auto e = cb_assign_timeout(request, options); e.ec) {
        return e;
    }
    request.bucket_name = cb_string_new(bucket_name);
    request.scope_name = cb_string_new(scope_name);

    auto [resp, err] = impl_->http_execute(__func__, std::move(request));
    if (err.ec) {
        return err;
    }

    array_init(return_value);
    return {};
}

COUCHBASE_API
core_error_info
connection_handle::collection_create(zval* return_value,
                                     const zend_string* bucket_name,
                                     const zend_string* scope_name,
                                     const zend_string* collection_name,
                                     const zval* settings,
                                     const zval* options)
{
    couchbase::core::operations::management::collection_create_request request{};

    if (auto e = cb_assign_timeout(request, options); e.ec) {
        return e;
    }

    request.bucket_name = cb_string_new(bucket_name);
    request.scope_name = cb_string_new(scope_name);
    request.collection_name = cb_string_new(collection_name);

    if (auto e = cb_assign_integer(request.max_expiry, settings, "maxExpiry"); e.ec) {
        return e;
    }

    if (auto e = cb_assign_boolean(request.history, settings, "history"); e.ec) {
        return e;
    }

    auto [resp, err] = impl_->http_execute(__func__, std::move(request));
    if (err.ec) {
        return err;
    }

    array_init(return_value);
    return {};
}

COUCHBASE_API
core_error_info
connection_handle::collection_drop(zval* return_value,
                                   const zend_string* bucket_name,
                                   const zend_string* scope_name,
                                   const zend_string* collection_name,
                                   const zval* options)
{
    couchbase::core::operations::management::collection_drop_request request{};

    if (auto e = cb_assign_timeout(request, options); e.ec) {
        return e;
    }

    request.bucket_name = cb_string_new(bucket_name);
    request.scope_name = cb_string_new(scope_name);
    request.collection_name = cb_string_new(collection_name);

    auto [resp, err] = impl_->http_execute(__func__, std::move(request));
    if (err.ec) {
        return err;
    }

    array_init(return_value);
    return {};
}

COUCHBASE_API
core_error_info
connection_handle::collection_update(zval* return_value,
                                     const zend_string* bucket_name,
                                     const zend_string* scope_name,
                                     const zend_string* collection_name,
                                     const zval* settings,
                                     const zval* options)
{
    couchbase::core::operations::management::collection_update_request request{};

    if (auto e = cb_assign_timeout(request, options); e.ec) {
        return e;
    }

    request.bucket_name = cb_string_new(bucket_name);
    request.scope_name = cb_string_new(scope_name);
    request.collection_name = cb_string_new(collection_name);

    if (auto e = cb_assign_integer(request.max_expiry, settings, "maxExpiry"); e.ec) {
        return e;
    }

    if (auto e = cb_assign_boolean(request.history, settings, "history"); e.ec) {
        return e;
    }

    auto [resp, err] = impl_->http_execute(__func__, std::move(request));
    if (err.ec) {
        return err;
    }

    array_init(return_value);
    return {};
}

void
cb_role_to_zval(zval* return_value, const couchbase::core::management::rbac::role& role)
{
    add_assoc_string(return_value, "name", role.name.c_str());
    if (role.bucket) {
        add_assoc_string(return_value, "bucket", role.bucket->c_str());
    }
    if (role.scope) {
        add_assoc_string(return_value, "scope", role.scope->c_str());
    }
    if (role.collection) {
        add_assoc_string(return_value, "collection", role.collection->c_str());
    }
}

COUCHBASE_API
core_error_info
cb_user_and_metadata_to_zval(zval* return_value, const couchbase::core::management::rbac::user_and_metadata& user)
{
    array_init(return_value);

    add_assoc_string(return_value, "username", user.username.c_str());
    if (user.display_name) {
        add_assoc_string(return_value, "displayName", user.display_name->c_str());
    }

    zval groups;
    array_init(&groups);
    for (const auto& group : user.groups) {
        add_next_index_string(&groups, group.c_str());
    }
    add_assoc_zval(return_value, "groups", &groups);

    zval roles;
    array_init(&roles);
    for (const auto& role : user.roles) {
        zval z_role;
        array_init(&z_role);
        add_assoc_string(&z_role, "name", role.name.c_str());
        if (role.bucket) {
            add_assoc_string(&z_role, "bucket", role.bucket->c_str());
        }
        if (role.scope) {
            add_assoc_string(&z_role, "scope", role.scope->c_str());
        }
        if (role.collection) {
            add_assoc_string(&z_role, "collection", role.collection->c_str());
        }
        add_next_index_zval(&roles, &z_role);
    }
    add_assoc_zval(return_value, "roles", &roles);

    std::string domain;
    switch (user.domain) {
        case couchbase::core::management::rbac::auth_domain::local:
            domain = "local";
            break;
        case couchbase::core::management::rbac::auth_domain::external:
            domain = "external";
            break;
        default:
            domain = "unknown";
            break;
    }
    add_assoc_string(return_value, "domain", domain.c_str());

    if (user.password_changed) {
        add_assoc_string(return_value, "passwordChanged", user.password_changed->c_str());
    }

    zval external_groups;
    array_init(&external_groups);
    for (const auto& group : user.external_groups) {
        add_next_index_string(&external_groups, group.c_str());
    }
    add_assoc_zval(return_value, "externalGroups", &external_groups);

    zval effective_roles;
    array_init(&effective_roles);
    for (const auto& role : user.effective_roles) {
        zval z_role;
        array_init(&z_role);
        cb_role_to_zval(&z_role, role);

        zval origins;
        array_init(&origins);
        for (const auto& origin : role.origins) {
            zval z_origin;
            array_init(&z_origin);
            add_assoc_string(&z_origin, "type", origin.type.c_str());
            if (origin.name) {
                add_assoc_string(&z_origin, "name", origin.name->c_str());
            }

            add_next_index_zval(&origins, &z_origin);
        }
        add_assoc_zval(&z_role, "origins", &origins);

        add_next_index_zval(&effective_roles, &z_role);
    }
    add_assoc_zval(return_value, "effectiveRoles", &effective_roles);

    return {};
}

static void
cb_group_to_zval(zval* return_value, const couchbase::core::management::rbac::group& group)
{
    array_init(return_value);

    add_assoc_string(return_value, "name", group.name.c_str());
    if (group.description) {
        add_assoc_string(return_value, "description", group.description->c_str());
    }
    if (group.ldap_group_reference) {
        add_assoc_string(return_value, "ldapGroupReference", group.ldap_group_reference->c_str());
    }

    zval roles;
    array_init(&roles);
    for (const auto& role : group.roles) {
        zval z_role;
        array_init(&z_role);
        cb_role_to_zval(&z_role, role);
        add_next_index_zval(&roles, &z_role);
    }
    add_assoc_zval(return_value, "roles", &roles);
}

COUCHBASE_API
core_error_info
connection_handle::user_upsert(zval* return_value, const zval* user, const zval* options)
{
    couchbase::core::management::rbac::user cuser{};
    if (auto e = cb_assign_string(cuser.username, user, "username"); e.ec) {
        return e;
    }
    if (auto e = cb_assign_string(cuser.display_name, user, "displayName"); e.ec) {
        return e;
    }
    if (auto e = cb_assign_string(cuser.password, user, "password"); e.ec) {
        return e;
    }
    if (const zval* value = zend_symtable_str_find(Z_ARRVAL_P(user), ZEND_STRL("roles")); value != nullptr && Z_TYPE_P(value) == IS_ARRAY) {
        std::vector<couchbase::core::management::rbac::role> roles{};
        const zval* item = nullptr;

        ZEND_HASH_FOREACH_VAL(Z_ARRVAL_P(value), item)
        {
            couchbase::core::management::rbac::role role{};
            if (auto e = cb_assign_string(role.name, item, "name"); e.ec) {
                return e;
            }
            if (auto e = cb_assign_string(role.bucket, item, "bucket"); e.ec) {
                return e;
            }
            if (auto e = cb_assign_string(role.scope, item, "scope"); e.ec) {
                return e;
            }
            if (auto e = cb_assign_string(role.collection, item, "collection"); e.ec) {
                return e;
            }
            roles.emplace_back(role);
        }
        ZEND_HASH_FOREACH_END();

        cuser.roles = roles;
    }
    if (const zval* value = zend_symtable_str_find(Z_ARRVAL_P(user), ZEND_STRL("groups"));
        value != nullptr && Z_TYPE_P(value) == IS_ARRAY) {
        std::set<std::string> groups{};
        const zval* item = nullptr;

        ZEND_HASH_FOREACH_VAL(Z_ARRVAL_P(value), item)
        {
            groups.emplace(std::string({ Z_STRVAL_P(item), Z_STRLEN_P(item) }));
        }
        ZEND_HASH_FOREACH_END();

        cuser.groups = groups;
    }

    couchbase::core::operations::management::user_upsert_request request{};

    if (auto e = cb_assign_timeout(request, options); e.ec) {
        return e;
    }
    if (auto e = cb_assign_user_domain(request, options); e.ec) {
        return e;
    }
    request.user = cuser;

    auto [resp, err] = impl_->http_execute(__func__, std::move(request));
    if (err.ec) {
        return err;
    }

    array_init(return_value);
    return {};
}

COUCHBASE_API
core_error_info
connection_handle::user_get_all(zval* return_value, const zval* options)
{
    couchbase::core::operations::management::user_get_all_request request{};

    if (auto e = cb_assign_timeout(request, options); e.ec) {
        return e;
    }
    if (auto e = cb_assign_user_domain(request, options); e.ec) {
        return e;
    }

    auto [resp, err] = impl_->http_execute(__func__, std::move(request));
    if (err.ec) {
        return err;
    }

    array_init(return_value);
    for (const auto& user : resp.users) {
        zval this_user;
        if (auto e = cb_user_and_metadata_to_zval(&this_user, user); e.ec) {
            return e;
        }

        add_next_index_zval(return_value, &this_user);
    }

    return {};
}

COUCHBASE_API
core_error_info
connection_handle::user_get(zval* return_value, const zend_string* name, const zval* options)
{
    couchbase::core::operations::management::user_get_request request{ cb_string_new(name) };

    if (auto e = cb_assign_timeout(request, options); e.ec) {
        return e;
    }
    if (auto e = cb_assign_user_domain(request, options); e.ec) {
        return e;
    }

    auto [resp, err] = impl_->http_execute(__func__, std::move(request));
    if (err.ec) {
        return err;
    }

    if (auto e = cb_user_and_metadata_to_zval(return_value, resp.user); e.ec) {
        return e;
    }

    return {};
}

COUCHBASE_API
core_error_info
connection_handle::user_drop(zval* return_value, const zend_string* name, const zval* options)
{
    couchbase::core::operations::management::user_drop_request request{ cb_string_new(name) };

    if (auto e = cb_assign_timeout(request, options); e.ec) {
        return e;
    }
    if (auto e = cb_assign_user_domain(request, options); e.ec) {
        return e;
    }

    auto [resp, err] = impl_->http_execute(__func__, std::move(request));
    if (err.ec) {
        return err;
    }

    array_init(return_value);
    return {};
}

COUCHBASE_API
core_error_info
connection_handle::change_password(zval* return_value, const zend_string* new_password, const zval* options)
{
    couchbase::core::operations::management::change_password_request request{ cb_string_new(new_password) };

    if (auto e = cb_assign_timeout(request, options); e.ec) {
        return e;
    }

    auto [resp, err] = impl_->http_execute(__func__, std::move(request));
    if (err.ec) {
        return err;
    }

    array_init(return_value);
    return {};
}

COUCHBASE_API
core_error_info
connection_handle::group_upsert(zval* return_value, const zval* group, const zval* options)
{
    couchbase::core::management::rbac::group cgroup{};
    if (auto e = cb_assign_string(cgroup.name, group, "name"); e.ec) {
        return e;
    }
    if (auto e = cb_assign_string(cgroup.description, group, "description"); e.ec) {
        return e;
    }
    if (auto e = cb_assign_string(cgroup.ldap_group_reference, group, "ldapGroupReference"); e.ec) {
        return e;
    }
    if (const zval* value = zend_symtable_str_find(Z_ARRVAL_P(group), ZEND_STRL("roles"));
        value != nullptr && Z_TYPE_P(value) == IS_ARRAY) {
        std::vector<couchbase::core::management::rbac::role> roles{};
        const zval* item = nullptr;

        ZEND_HASH_FOREACH_VAL(Z_ARRVAL_P(value), item)
        {
            couchbase::core::management::rbac::role role{};
            if (auto e = cb_assign_string(role.name, item, "name"); e.ec) {
                return e;
            }
            if (auto e = cb_assign_string(role.bucket, item, "bucket"); e.ec) {
                return e;
            }
            if (auto e = cb_assign_string(role.scope, item, "scope"); e.ec) {
                return e;
            }
            if (auto e = cb_assign_string(role.collection, item, "collection"); e.ec) {
                return e;
            }
            roles.emplace_back(role);
        }
        ZEND_HASH_FOREACH_END();

        cgroup.roles = roles;
    }

    couchbase::core::operations::management::group_upsert_request request{ cgroup };

    if (auto e = cb_assign_timeout(request, options); e.ec) {
        return e;
    }

    auto [resp, err] = impl_->http_execute(__func__, std::move(request));
    if (err.ec) {
        return err;
    }

    array_init(return_value);
    return {};
}

COUCHBASE_API
core_error_info
connection_handle::group_get_all(zval* return_value, const zval* options)
{
    couchbase::core::operations::management::group_get_all_request request{};

    if (auto e = cb_assign_timeout(request, options); e.ec) {
        return e;
    }

    auto [resp, err] = impl_->http_execute(__func__, std::move(request));
    if (err.ec) {
        return err;
    }

    array_init(return_value);
    for (const auto& group : resp.groups) {
        zval this_group;
        cb_group_to_zval(&this_group, group);

        add_next_index_zval(return_value, &this_group);
    }

    return {};
}

COUCHBASE_API
core_error_info
connection_handle::group_get(zval* return_value, const zend_string* name, const zval* options)
{
    couchbase::core::operations::management::group_get_request request{ cb_string_new(name) };

    if (auto e = cb_assign_timeout(request, options); e.ec) {
        return e;
    }

    auto [resp, err] = impl_->http_execute(__func__, std::move(request));
    if (err.ec) {
        return err;
    }

    cb_group_to_zval(return_value, resp.group);

    return {};
}

COUCHBASE_API
core_error_info
connection_handle::group_drop(zval* return_value, const zend_string* name, const zval* options)
{
    couchbase::core::operations::management::group_drop_request request{ cb_string_new(name) };

    if (auto e = cb_assign_timeout(request, options); e.ec) {
        return e;
    }

    auto [resp, err] = impl_->http_execute(__func__, std::move(request));
    if (err.ec) {
        return err;
    }

    array_init(return_value);
    return {};
}

COUCHBASE_API
core_error_info
connection_handle::role_get_all(zval* return_value, const zval* options)
{
    couchbase::core::operations::management::role_get_all_request request{};

    if (auto e = cb_assign_timeout(request, options); e.ec) {
        return e;
    }

    auto [resp, err] = impl_->http_execute(__func__, std::move(request));
    if (err.ec) {
        return err;
    }

    array_init(return_value);
    for (const auto& role : resp.roles) {
        zval this_role;
        array_init(&this_role);
        cb_role_to_zval(&this_role, role);
        add_assoc_string(&this_role, "displayName", role.display_name.c_str());
        add_assoc_string(&this_role, "description", role.description.c_str());

        add_next_index_zval(return_value, &this_role);
    }

    return {};
}

COUCHBASE_API
core_error_info
connection_handle::query_index_get_all(zval* return_value, const zend_string* bucket_name, const zval* options)
{
    couchbase::core::operations::management::query_index_get_all_request request{};

    if (auto e = cb_assign_timeout(request, options); e.ec) {
        return e;
    }
    request.bucket_name = cb_string_new(bucket_name);
    if (auto e = cb_assign_string(request.scope_name, options, "scopeName"); e.ec) {
        return e;
    }
    if (auto e = cb_assign_string(request.collection_name, options, "collectionName"); e.ec) {
        return e;
    }

    auto [resp, err] = impl_->http_execute(__func__, std::move(request));
    if (err.ec) {
        return err;
    }

    array_init(return_value);
    for (const auto& idx : resp.indexes) {
        zval index;
        array_init(&index);
        add_assoc_bool(&index, "isPrimary", idx.is_primary);
        add_assoc_stringl(&index, "name", idx.name.data(), idx.name.size());
        add_assoc_stringl(&index, "state", idx.state.data(), idx.state.size());
        add_assoc_stringl(&index, "type", idx.type.data(), idx.type.size());
        add_assoc_stringl(&index, "bucketName", idx.bucket_name.data(), idx.bucket_name.size());
        if (idx.partition) {
            add_assoc_stringl(&index, "partition", idx.partition->data(), idx.partition->size());
        }
        if (idx.condition) {
            add_assoc_stringl(&index, "condition", idx.condition->data(), idx.condition->size());
        }
        if (idx.scope_name) {
            add_assoc_stringl(&index, "scopeName", idx.scope_name->data(), idx.scope_name->size());
        }
        if (idx.collection_name) {
            add_assoc_stringl(&index, "collectionName", idx.collection_name->data(), idx.collection_name->size());
        }
        zval index_key;
        array_init(&index_key);
        for (const auto& field : idx.index_key) {
            add_next_index_stringl(&index_key, field.data(), field.size());
        }
        add_assoc_zval(&index, "indexKey", &index_key);
        add_next_index_zval(return_value, &index);
    }
    return {};
}

COUCHBASE_API
core_error_info
connection_handle::query_index_create(const zend_string* bucket_name,
                                      const zend_string* index_name,
                                      const zval* fields,
                                      const zval* options)
{
    if (fields == nullptr || Z_TYPE_P(fields) != IS_ARRAY) {
        return { errc::common::invalid_argument, ERROR_LOCATION, "expected array for index fields" };
    }
    couchbase::core::operations::management::query_index_create_request request{};

    if (auto e = cb_assign_timeout(request, options); e.ec) {
        return e;
    }
    request.is_primary = false;
    request.bucket_name = cb_string_new(bucket_name);
    request.index_name = cb_string_new(index_name);

    const zval* value;
    ZEND_HASH_FOREACH_VAL(Z_ARRVAL_P(fields), value)
    {
        if (value == nullptr && Z_TYPE_P(value) == IS_STRING) {
            return { errc::common::invalid_argument, ERROR_LOCATION, "expected index fields to be array of strings" };
        }
        request.keys.emplace_back(cb_string_new(value));
    }
    ZEND_HASH_FOREACH_END();

    if (auto e = cb_assign_string(request.scope_name, options, "scopeName"); e.ec) {
        return e;
    }
    if (auto e = cb_assign_string(request.collection_name, options, "collectionName"); e.ec) {
        return e;
    }
    if (auto e = cb_assign_string(request.condition, options, "condition"); e.ec) {
        return e;
    }
    if (auto e = cb_assign_boolean(request.deferred, options, "deferred"); e.ec) {
        return e;
    }
    if (auto e = cb_assign_boolean(request.ignore_if_exists, options, "ignoreIfExists"); e.ec) {
        return e;
    }
    if (auto e = cb_assign_integer(request.num_replicas, options, "numberOfReplicas"); e.ec) {
        return e;
    }

    auto [resp, err] = impl_->http_execute(__func__, std::move(request));
    if (err.ec) {
        return err;
    }

    return {};
}

COUCHBASE_API
core_error_info
connection_handle::query_index_create_primary(const zend_string* bucket_name, const zval* options)
{
    couchbase::core::operations::management::query_index_create_request request{};

    if (auto e = cb_assign_timeout(request, options); e.ec) {
        return e;
    }
    request.is_primary = true;
    request.bucket_name = cb_string_new(bucket_name);

    if (auto e = cb_assign_string(request.index_name, options, "indexName"); e.ec) {
        return e;
    }
    if (auto e = cb_assign_string(request.scope_name, options, "scopeName"); e.ec) {
        return e;
    }
    if (auto e = cb_assign_string(request.collection_name, options, "collectionName"); e.ec) {
        return e;
    }
    if (auto e = cb_assign_boolean(request.deferred, options, "deferred"); e.ec) {
        return e;
    }
    if (auto e = cb_assign_boolean(request.ignore_if_exists, options, "ignoreIfExists"); e.ec) {
        return e;
    }
    if (auto e = cb_assign_integer(request.num_replicas, options, "numberOfReplicas"); e.ec) {
        return e;
    }

    auto [resp, err] = impl_->http_execute(__func__, std::move(request));
    if (err.ec) {
        return err;
    }

    return {};
}

COUCHBASE_API
core_error_info
connection_handle::query_index_drop(const zend_string* bucket_name, const zend_string* index_name, const zval* options)
{
    couchbase::core::operations::management::query_index_drop_request request{};

    if (auto e = cb_assign_timeout(request, options); e.ec) {
        return e;
    }
    request.is_primary = false;
    request.bucket_name = cb_string_new(bucket_name);
    request.index_name = cb_string_new(index_name);

    if (auto e = cb_assign_string(request.scope_name, options, "scopeName"); e.ec) {
        return e;
    }
    if (auto e = cb_assign_string(request.collection_name, options, "collectionName"); e.ec) {
        return e;
    }
    if (auto e = cb_assign_boolean(request.ignore_if_does_not_exist, options, "ignoreIfDoesNotExist"); e.ec) {
        return e;
    }

    auto [resp, err] = impl_->http_execute(__func__, std::move(request));
    if (err.ec) {
        return err;
    }

    return {};
}

COUCHBASE_API
core_error_info
connection_handle::query_index_drop_primary(const zend_string* bucket_name, const zval* options)
{
    couchbase::core::operations::management::query_index_drop_request request{};

    if (auto e = cb_assign_timeout(request, options); e.ec) {
        return e;
    }
    request.is_primary = true;
    request.bucket_name = cb_string_new(bucket_name);

    if (auto e = cb_assign_string(request.index_name, options, "indexName"); e.ec) {
        return e;
    }
    if (auto e = cb_assign_string(request.scope_name, options, "scopeName"); e.ec) {
        return e;
    }
    if (auto e = cb_assign_string(request.collection_name, options, "collectionName"); e.ec) {
        return e;
    }
    if (auto e = cb_assign_boolean(request.ignore_if_does_not_exist, options, "ignoreIfDoesNotExist"); e.ec) {
        return e;
    }

    auto [resp, err] = impl_->http_execute(__func__, std::move(request));
    if (err.ec) {
        return err;
    }

    return {};
}

COUCHBASE_API
core_error_info
connection_handle::query_index_build_deferred(zval* return_value, const zend_string* bucket_name, const zval* options)
{
    couchbase::core::operations::management::query_index_build_deferred_request request{};

    if (auto e = cb_assign_timeout(request, options); e.ec) {
        return e;
    }
    request.bucket_name = cb_string_new(bucket_name);

    if (auto e = cb_assign_string(request.scope_name, options, "scopeName"); e.ec) {
        return e;
    }
    if (auto e = cb_assign_string(request.collection_name, options, "collectionName"); e.ec) {
        return e;
    }

    auto [resp, err] = impl_->http_execute(__func__, std::move(request));
    if (err.ec) {
        return err;
    }

    return {};
}

COUCHBASE_API
core_error_info
connection_handle::collection_query_index_get_all(zval* return_value,
                                                  const zend_string* bucket_name,
                                                  const zend_string* scope_name,
                                                  const zend_string* collection_name,
                                                  const zval* options)
{
    couchbase::core::operations::management::query_index_get_all_request request{};

    if (auto e = cb_assign_timeout(request, options); e.ec) {
        return e;
    }
    request.bucket_name = cb_string_new(bucket_name);
    request.scope_name = cb_string_new(scope_name);
    request.collection_name = cb_string_new(collection_name);

    auto [resp, err] = impl_->http_execute(__func__, std::move(request));
    if (err.ec) {
        return err;
    }

    array_init(return_value);
    for (const auto& idx : resp.indexes) {
        zval index;
        array_init(&index);
        add_assoc_bool(&index, "isPrimary", idx.is_primary);
        add_assoc_stringl(&index, "name", idx.name.data(), idx.name.size());
        add_assoc_stringl(&index, "state", idx.state.data(), idx.state.size());
        add_assoc_stringl(&index, "type", idx.type.data(), idx.type.size());
        add_assoc_stringl(&index, "bucketName", idx.bucket_name.data(), idx.bucket_name.size());
        if (idx.partition) {
            add_assoc_stringl(&index, "partition", idx.partition->data(), idx.partition->size());
        }
        if (idx.condition) {
            add_assoc_stringl(&index, "condition", idx.condition->data(), idx.condition->size());
        }
        if (idx.scope_name) {
            add_assoc_stringl(&index, "scopeName", idx.scope_name->data(), idx.scope_name->size());
        }
        if (idx.collection_name) {
            add_assoc_stringl(&index, "collectionName", idx.collection_name->data(), idx.collection_name->size());
        }
        zval index_key;
        array_init(&index_key);
        for (const auto& field : idx.index_key) {
            add_next_index_stringl(&index_key, field.data(), field.size());
        }
        add_assoc_zval(&index, "indexKey", &index_key);
        add_next_index_zval(return_value, &index);
    }
    return {};
}

COUCHBASE_API
core_error_info
connection_handle::collection_query_index_create(const zend_string* bucket_name,
                                                 const zend_string* scope_name,
                                                 const zend_string* collection_name,
                                                 const zend_string* index_name,
                                                 const zval* fields,
                                                 const zval* options)
{
    if (fields == nullptr || Z_TYPE_P(fields) != IS_ARRAY) {
        return { errc::common::invalid_argument, ERROR_LOCATION, "expected array for index fields" };
    }
    couchbase::core::operations::management::query_index_create_request request{};

    if (auto e = cb_assign_timeout(request, options); e.ec) {
        return e;
    }
    request.is_primary = false;
    request.bucket_name = cb_string_new(bucket_name);
    request.scope_name = cb_string_new(scope_name);
    request.collection_name = cb_string_new(collection_name);
    request.index_name = cb_string_new(index_name);

    const zval* value;
    ZEND_HASH_FOREACH_VAL(Z_ARRVAL_P(fields), value)
    {
        if (value == nullptr && Z_TYPE_P(value) == IS_STRING) {
            return { errc::common::invalid_argument, ERROR_LOCATION, "expected index fields to be array of strings" };
        }
        request.keys.emplace_back(cb_string_new(value));
    }
    ZEND_HASH_FOREACH_END();

    if (auto e = cb_assign_string(request.condition, options, "condition"); e.ec) {
        return e;
    }
    if (auto e = cb_assign_boolean(request.deferred, options, "deferred"); e.ec) {
        return e;
    }
    if (auto e = cb_assign_boolean(request.ignore_if_exists, options, "ignoreIfExists"); e.ec) {
        return e;
    }
    if (auto e = cb_assign_integer(request.num_replicas, options, "numberOfReplicas"); e.ec) {
        return e;
    }

    auto [resp, err] = impl_->http_execute(__func__, std::move(request));
    if (err.ec) {
        return err;
    }

    return {};
}

COUCHBASE_API
core_error_info
connection_handle::collection_query_index_create_primary(const zend_string* bucket_name,
                                                         const zend_string* scope_name,
                                                         const zend_string* collection_name,
                                                         const zval* options)
{
    couchbase::core::operations::management::query_index_create_request request{};

    if (auto e = cb_assign_timeout(request, options); e.ec) {
        return e;
    }
    request.is_primary = true;
    request.bucket_name = cb_string_new(bucket_name);
    request.scope_name = cb_string_new(scope_name);
    request.collection_name = cb_string_new(collection_name);

    if (auto e = cb_assign_string(request.index_name, options, "indexName"); e.ec) {
        return e;
    }
    if (auto e = cb_assign_boolean(request.deferred, options, "deferred"); e.ec) {
        return e;
    }
    if (auto e = cb_assign_boolean(request.ignore_if_exists, options, "ignoreIfExists"); e.ec) {
        return e;
    }
    if (auto e = cb_assign_integer(request.num_replicas, options, "numberOfReplicas"); e.ec) {
        return e;
    }

    auto [resp, err] = impl_->http_execute(__func__, std::move(request));
    if (err.ec) {
        return err;
    }

    return {};
}

COUCHBASE_API
core_error_info
connection_handle::collection_query_index_drop(const zend_string* bucket_name,
                                               const zend_string* scope_name,
                                               const zend_string* collection_name,
                                               const zend_string* index_name,
                                               const zval* options)
{
    couchbase::core::operations::management::query_index_drop_request request{};

    if (auto e = cb_assign_timeout(request, options); e.ec) {
        return e;
    }
    request.is_primary = false;
    request.bucket_name = cb_string_new(bucket_name);
    request.scope_name = cb_string_new(scope_name);
    request.collection_name = cb_string_new(collection_name);
    request.index_name = cb_string_new(index_name);

    if (auto e = cb_assign_boolean(request.ignore_if_does_not_exist, options, "ignoreIfDoesNotExist"); e.ec) {
        return e;
    }

    auto [resp, err] = impl_->http_execute(__func__, std::move(request));
    if (err.ec) {
        return err;
    }

    return {};
}

COUCHBASE_API
core_error_info
connection_handle::collection_query_index_drop_primary(const zend_string* bucket_name,
                                                       const zend_string* scope_name,
                                                       const zend_string* collection_name,
                                                       const zval* options)
{
    couchbase::core::operations::management::query_index_drop_request request{};

    if (auto e = cb_assign_timeout(request, options); e.ec) {
        return e;
    }
    request.is_primary = true;
    request.bucket_name = cb_string_new(bucket_name);
    request.scope_name = cb_string_new(scope_name);
    request.collection_name = cb_string_new(collection_name);

    if (auto e = cb_assign_string(request.index_name, options, "indexName"); e.ec) {
        return e;
    }
    if (auto e = cb_assign_boolean(request.ignore_if_does_not_exist, options, "ignoreIfDoesNotExist"); e.ec) {
        return e;
    }

    auto [resp, err] = impl_->http_execute(__func__, std::move(request));
    if (err.ec) {
        return err;
    }

    return {};
}

COUCHBASE_API
core_error_info
connection_handle::collection_query_index_build_deferred(zval* return_value,
                                                         const zend_string* bucket_name,
                                                         const zend_string* scope_name,
                                                         const zend_string* collection_name,
                                                         const zval* options)
{
    couchbase::core::operations::management::query_index_build_deferred_request request{};

    if (auto e = cb_assign_timeout(request, options); e.ec) {
        return e;
    }
    request.bucket_name = cb_string_new(bucket_name);
    request.scope_name = cb_string_new(scope_name);
    request.collection_name = cb_string_new(collection_name);

    auto [resp, err] = impl_->http_execute(__func__, std::move(request));
    if (err.ec) {
        return err;
    }

    return {};
}

bool
connection_handle::is_expired(std::chrono::system_clock::time_point now) const
{
    return idle_expiry_ < now;
}

std::shared_ptr<couchbase::core::cluster>
connection_handle::cluster() const
{
    return impl_->cluster();
}

#define ASSIGN_DURATION_OPTION(name, field, key, value)                                                                                    \
    if (zend_binary_strcmp(ZSTR_VAL(key), ZSTR_LEN(key), ZEND_STRL(name)) == 0) {                                                          \
        if ((value) == nullptr || Z_TYPE_P(value) == IS_NULL) {                                                                            \
            continue;                                                                                                                      \
        }                                                                                                                                  \
        if (Z_TYPE_P(value) != IS_LONG) {                                                                                                  \
            return { errc::common::invalid_argument,                                                                                       \
                     ERROR_LOCATION,                                                                                                       \
                     fmt::format("expected duration as a number for {}", std::string(ZSTR_VAL(key), ZSTR_LEN(key))) };                     \
        }                                                                                                                                  \
        zend_long ms = Z_LVAL_P(value);                                                                                                    \
        if (ms < 0) {                                                                                                                      \
            return { errc::common::invalid_argument,                                                                                       \
                     ERROR_LOCATION,                                                                                                       \
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
            return { errc::common::invalid_argument,                                                                                       \
                     ERROR_LOCATION,                                                                                                       \
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
                return { errc::common::invalid_argument,                                                                                   \
                         ERROR_LOCATION,                                                                                                   \
                         fmt::format("expected boolean for {}", std::string(ZSTR_VAL(key), ZSTR_LEN(key))) };                              \
        }                                                                                                                                  \
    }

#define ASSIGN_STRING_OPTION(name, field, key, value)                                                                                      \
    if (zend_binary_strcmp(ZSTR_VAL(key), ZSTR_LEN(key), ZEND_STRL(name)) == 0) {                                                          \
        if ((value) == nullptr || Z_TYPE_P(value) == IS_NULL) {                                                                            \
            continue;                                                                                                                      \
        }                                                                                                                                  \
        if (Z_TYPE_P(value) != IS_STRING) {                                                                                                \
            return { errc::common::invalid_argument,                                                                                       \
                     ERROR_LOCATION,                                                                                                       \
                     fmt::format("expected string for {}", std::string(ZSTR_VAL(key), ZSTR_LEN(key))) };                                   \
        }                                                                                                                                  \
        if (Z_STRLEN_P(value) == 0) {                                                                                                      \
            return { errc::common::invalid_argument,                                                                                       \
                     ERROR_LOCATION,                                                                                                       \
                     fmt::format("expected non-empty string for {}", std::string(ZSTR_VAL(key), ZSTR_LEN(key))) };                         \
        }                                                                                                                                  \
        (field).assign(Z_STRVAL_P(value), Z_STRLEN_P(value));                                                                              \
    }

struct dns_options {
    std::chrono::milliseconds timeout;
    std::string nameserver;
    std::uint16_t port;
};

static core_error_info
apply_options(core::utils::connection_string& connstr, zval* options)
{
    if (options == nullptr || Z_TYPE_P(options) != IS_ARRAY) {
        return { errc::common::invalid_argument, ERROR_LOCATION, "expected array for cluster options" };
    }

    const zend_string* key;
    const zval* value;

    auto system_dns = core::io::dns::dns_config::system_config();
    dns_options dns{ system_dns.timeout(), system_dns.nameserver(), system_dns.port() };

    ZEND_HASH_FOREACH_STR_KEY_VAL(Z_ARRVAL_P(options), key, value)
    {
        ASSIGN_DURATION_OPTION("analyticsTimeout", connstr.options.analytics_timeout, key, value);
        ASSIGN_DURATION_OPTION("bootstrapTimeout", connstr.options.bootstrap_timeout, key, value);
        ASSIGN_DURATION_OPTION("connectTimeout", connstr.options.connect_timeout, key, value);

        ASSIGN_DURATION_OPTION("dnsSrvTimeout", dns.timeout, key, value);
        ASSIGN_STRING_OPTION("dnsSrvNameserver", dns.nameserver, key, value);
        ASSIGN_NUMBER_OPTION("dnsSrvPort", dns.port, key, value);

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
        ASSIGN_BOOLEAN_OPTION("showQueries", connstr.options.show_queries, key, value);

        ASSIGN_STRING_OPTION("network", connstr.options.network, key, value);
        ASSIGN_STRING_OPTION("trustCertificate", connstr.options.trust_certificate, key, value);
        ASSIGN_STRING_OPTION("userAgentExtra", connstr.options.user_agent_extra, key, value);

        if (zend_binary_strcmp(ZSTR_VAL(key), ZSTR_LEN(key), ZEND_STRL("useIpProtocol")) == 0) {
            if (value == nullptr || Z_TYPE_P(value) == IS_NULL) {
                continue;
            }
            if (Z_TYPE_P(value) != IS_STRING) {
                return { errc::common::invalid_argument,
                         ERROR_LOCATION,
                         fmt::format("expected string for {}", std::string(ZSTR_VAL(key), ZSTR_LEN(key))) };
            }
            if (zend_binary_strcmp(Z_STRVAL_P(value), Z_STRLEN_P(value), ZEND_STRL("any")) == 0) {
                connstr.options.use_ip_protocol = core::io::ip_protocol::any;
            } else if (zend_binary_strcmp(Z_STRVAL_P(value), Z_STRLEN_P(value), ZEND_STRL("forceIpv4")) == 0) {
                connstr.options.use_ip_protocol = core::io::ip_protocol::force_ipv4;
            } else if (zend_binary_strcmp(Z_STRVAL_P(value), Z_STRLEN_P(value), ZEND_STRL("forceIpv6")) == 0) {
                connstr.options.use_ip_protocol = core::io::ip_protocol::force_ipv6;
            } else {
                return { errc::common::invalid_argument,
                         ERROR_LOCATION,
                         fmt::format(R"(expected mode for TLS verification ({}), supported modes are "peer" and "none")",
                                     std::string(ZSTR_VAL(key), ZSTR_LEN(key))) };
            }
        }

        if (zend_binary_strcmp(ZSTR_VAL(key), ZSTR_LEN(key), ZEND_STRL("tlsVerify")) == 0) {
            if (value == nullptr || Z_TYPE_P(value) == IS_NULL) {
                continue;
            }
            if (Z_TYPE_P(value) != IS_STRING) {
                return { errc::common::invalid_argument,
                         ERROR_LOCATION,
                         fmt::format("expected string for {}", std::string(ZSTR_VAL(key), ZSTR_LEN(key))) };
            }
            if (zend_binary_strcmp(Z_STRVAL_P(value), Z_STRLEN_P(value), ZEND_STRL("peer")) == 0) {
                connstr.options.tls_verify = core::tls_verify_mode::peer;
            } else if (zend_binary_strcmp(Z_STRVAL_P(value), Z_STRLEN_P(value), ZEND_STRL("none")) == 0) {
                connstr.options.tls_verify = core::tls_verify_mode::none;
            } else {
                return { errc::common::invalid_argument,
                         ERROR_LOCATION,
                         fmt::format(R"(expected mode for TLS verification ({}), supported modes are "peer" and "none")",
                                     std::string(ZSTR_VAL(key), ZSTR_LEN(key))) };
            }
        }

        if (zend_binary_strcmp(ZSTR_VAL(key), ZSTR_LEN(key), ZEND_STRL("thresholdLoggingTracerOptions")) == 0) {
            if (value == nullptr || Z_TYPE_P(value) == IS_NULL) {
                continue;
            }
            if (Z_TYPE_P(value) != IS_ARRAY) {
                return { errc::common::invalid_argument,
                         ERROR_LOCATION,
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
                return { errc::common::invalid_argument,
                         ERROR_LOCATION,
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

    connstr.options.dns_config = core::io::dns::dns_config(dns.nameserver, dns.port, dns.timeout);

    return {};
}

#undef ASSIGN_DURATION_OPTION
#undef ASSIGN_NUMBER_OPTION
#undef ASSIGN_BOOLEAN_OPTION
#undef ASSIGN_STRING_OPTION

static core_error_info
extract_credentials(couchbase::core::cluster_credentials& credentials, zval* options)
{
    if (options == nullptr || Z_TYPE_P(options) != IS_ARRAY) {
        return { errc::common::invalid_argument, ERROR_LOCATION, "expected array for cluster options" };
    }

    const zval* auth = zend_symtable_str_find(Z_ARRVAL_P(options), ZEND_STRL("authenticator"));
    if (auth == nullptr || Z_TYPE_P(auth) != IS_ARRAY) {
        return { errc::common::invalid_argument, ERROR_LOCATION, "missing authenticator" };
    }

    const zval* auth_type = zend_symtable_str_find(Z_ARRVAL_P(auth), ZEND_STRL("type"));
    if (auth_type == nullptr || Z_TYPE_P(auth_type) != IS_STRING) {
        return { errc::common::invalid_argument, ERROR_LOCATION, "unexpected type of the authenticator" };
    }
    if (zend_binary_strcmp(Z_STRVAL_P(auth_type), Z_STRLEN_P(auth_type), ZEND_STRL("password")) == 0) {
        const zval* username = zend_symtable_str_find(Z_ARRVAL_P(auth), ZEND_STRL("username"));
        if (username == nullptr || Z_TYPE_P(username) != IS_STRING) {
            return { errc::common::invalid_argument, ERROR_LOCATION, "expected username to be a string in the authenticator" };
        }
        const zval* password = zend_symtable_str_find(Z_ARRVAL_P(auth), ZEND_STRL("password"));
        if (password == nullptr || Z_TYPE_P(password) != IS_STRING) {
            return { errc::common::invalid_argument, ERROR_LOCATION, "expected password to be a string in the authenticator" };
        }
        credentials.username.assign(Z_STRVAL_P(username));
        credentials.password.assign(Z_STRVAL_P(password));

        if (const zval* allowed_sasl_mechanisms = zend_symtable_str_find(Z_ARRVAL_P(auth), ZEND_STRL("allowedSaslMechanisms"));
            allowed_sasl_mechanisms != nullptr && Z_TYPE_P(allowed_sasl_mechanisms) != IS_NULL) {
            if (Z_TYPE_P(allowed_sasl_mechanisms) != IS_ARRAY) {
                return { errc::common::invalid_argument,
                         ERROR_LOCATION,
                         "expected allowedSaslMechanisms to be an array in the authenticator" };
            }
            std::vector<std::string> mechanisms;
            const zval* mech;
            ZEND_HASH_FOREACH_VAL(Z_ARRVAL_P(allowed_sasl_mechanisms), mech)
            {
                if (mech != nullptr && Z_TYPE_P(mech) == IS_STRING) {
                    mechanisms.emplace_back(Z_STRVAL_P(mech), Z_STRLEN_P(mech));
                }
            }
            ZEND_HASH_FOREACH_END();
            credentials.allowed_sasl_mechanisms = mechanisms;
        }
        return {};
    }
    if (zend_binary_strcmp(Z_STRVAL_P(auth_type), Z_STRLEN_P(auth_type), ZEND_STRL("certificate")) == 0) {
        const zval* certificate_path = zend_symtable_str_find(Z_ARRVAL_P(auth), ZEND_STRL("certificatePath"));
        if (certificate_path == nullptr || Z_TYPE_P(certificate_path) != IS_STRING) {
            return { errc::common::invalid_argument, ERROR_LOCATION, "expected certificate path to be a string in the authenticator" };
        }
        const zval* key_path = zend_symtable_str_find(Z_ARRVAL_P(auth), ZEND_STRL("keyPath"));
        if (key_path == nullptr || Z_TYPE_P(key_path) != IS_STRING) {
            return { errc::common::invalid_argument, ERROR_LOCATION, "expected key path to be a string in the authenticator" };
        }
        credentials.certificate_path.assign(Z_STRVAL_P(certificate_path));
        credentials.key_path.assign(Z_STRVAL_P(key_path));
        return {};
    }
    return { errc::common::invalid_argument,
             ERROR_LOCATION,
             fmt::format("unknown type of the authenticator: {}", std::string(Z_STRVAL_P(auth_type), Z_STRLEN_P(auth_type))) };
}

COUCHBASE_API
std::pair<connection_handle*, core_error_info>
create_connection_handle(const zend_string* connection_string,
                         const zend_string* connection_hash,
                         zval* options,
                         std::chrono::system_clock::time_point idle_expiry)
{
    std::string connection_str(ZSTR_VAL(connection_string), ZSTR_LEN(connection_string));
    auto connstr = core::utils::parse_connection_string(connection_str);
    if (connstr.error) {
        return { nullptr, { couchbase::errc::common::parsing_failure, ERROR_LOCATION, connstr.error.value() } };
    }
    if (auto e = apply_options(connstr, options); e.ec) {
        return { nullptr, e };
    }
    couchbase::core::cluster_credentials credentials;
    if (auto e = extract_credentials(credentials, options); e.ec) {
        return { nullptr, e };
    }
    connstr.options.user_agent_extra = fmt::format("php_sdk/{}/{};ssl/{:x};php/{}",
                                                   PHP_COUCHBASE_VERSION,
                                                   std::string(extension_revision()).substr(0, 8),
                                                   OpenSSL_version_num(),
                                                   PHP_VERSION
#if ZTS
                                                   "/z"
#else
                                                   "/n"
#endif
    );
    couchbase::core::origin origin{ credentials, connstr };
    return { new connection_handle(
               std::move(connection_str), std::string(ZSTR_VAL(connection_hash), ZSTR_LEN(connection_hash)), origin, idle_expiry),
             {} };
}
} // namespace couchbase::php
