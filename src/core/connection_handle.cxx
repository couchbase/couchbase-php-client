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
#include "persistent_connections_cache.hxx"

#include <couchbase/cluster.hxx>

#include <fmt/core.h>

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
        out.status_code = std::uint16_t(ctx.status_code.value());
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
    if (!ctx.retry_reasons.empty()) {
        for (const auto& reason : ctx.retry_reasons) {
            out.retry_reasons.insert(retry_reason_to_string(reason));
        }
    }
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

    std::pair<core_error_info, couchbase::operations::upsert_response> document_upsert(couchbase::operations::upsert_request request)
    {
        auto barrier = std::make_shared<std::promise<couchbase::operations::upsert_response>>();
        auto f = barrier->get_future();
        cluster_->execute(std::move(request),
                          [barrier](couchbase::operations::upsert_response&& resp) { barrier->set_value(std::move(resp)); });
        auto resp = f.get();
        if (resp.ctx.ec) {
            return { { resp.ctx.ec,
                       { __LINE__, __FILE__, __func__ },
                       fmt::format("unable to upsert document: {}, {}", resp.ctx.ec.value(), resp.ctx.ec.message()),
                       build_error_context(resp.ctx) },
                     {} };
        }
        return { {}, std::move(resp) };
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

  private:
    asio::io_context ctx_{};
    std::shared_ptr<couchbase::cluster> cluster_{ couchbase::cluster::create(ctx_) };
    std::thread worker;
    origin origin_;
};

connection_handle::connection_handle(couchbase::origin origin, std::chrono::steady_clock::time_point idle_expiry)
  : idle_expiry_{ idle_expiry }
  , id_{ zend_register_resource(this, persistent_connection_destructor_id) }
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

template<typename Request>
static core_error_info
cb_assign_timeout(Request& req, const zval* options)
{
    if (options == nullptr || !Z_TYPE_P(options)) {
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
    req.timeout = std::chrono::milliseconds(Z_LVAL_P(value));
    return {};
}

template<typename Request>
static core_error_info
cb_assign_durability(Request& req, const zval* options)
{
    if (options == nullptr || !Z_TYPE_P(options)) {
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
                     "expected durabilityLevel to be a string in the authenticator" };
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

static core_error_info
cb_assign_boolean(bool& field, const zval* options, std::string_view name)
{
    if (options == nullptr || !Z_TYPE_P(options)) {
        return {};
    }
    if (Z_TYPE_P(options) != IS_ARRAY) {
        return { error::common_errc::invalid_argument, { __LINE__, __FILE__, __func__ }, "expected array for options argument" };
    }

    const zval* value = zend_symtable_str_find(Z_ARRVAL_P(options), name.data(), name.size());
    if (value == nullptr) {
        return { error::common_errc::invalid_argument,
                 { __LINE__, __FILE__, __func__ },
                 fmt::format("expected {} to be a boolean value in the options", name) };
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
    if (options == nullptr || !Z_TYPE_P(options)) {
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
    if (options == nullptr || !Z_TYPE_P(options)) {
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

template<typename Integer>
static std::pair<core_error_info, Integer>
cb_get_integer(const zval* options, std::string_view name)
{
    if (options == nullptr || !Z_TYPE_P(options)) {
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
                     fmt::format("expected {} to be a integer value in the options", name) }, {} };
    }

    return { {}, Z_LVAL_P(value) };
}

std::pair<zval*, core_error_info>
connection_handle::document_upsert(const zend_string* bucket,
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
    request.flags = std::uint32_t(flags);
    if (auto e = cb_assign_timeout(request, options); e.ec) {
        return { nullptr, e };
    }
    if (auto e = cb_assign_durability(request, options); e.ec) {
        return { nullptr, e };
    }
    if (auto e = cb_assign_boolean(request.preserve_expiry, options, "preserveExpiry"); e.ec) {
        return { nullptr, e };
    }
    if (auto e = cb_assign_integer(request.expiry, options, "expiry"); e.ec) {
        return { nullptr, e };
    }

    auto [err, resp] = impl_->document_upsert(std::move(request));
    if (err.ec) {
        return { nullptr, err };
    }
    return { nullptr, {} };
}

std::pair<zval*, core_error_info>
connection_handle::query(const zend_string* statement,
                         const zval* options)
{
    couchbase::operations::query_request request{ cb_string_new(statement) };
    if (auto e = cb_assign_timeout(request, options); e.ec) {
        return { nullptr, e };
    }
    {
        auto [err, scanC] = cb_get_integer<uint64_t>(options, "scanConsistency");
        if (err.ec) {
            return { nullptr, err };
        }

        if (scanC > 0) {
            if (scanC == 1) {
                request.scan_consistency = couchbase::operations::query_request::scan_consistency_type::not_bounded;
            } else if (scanC == 2) {
                request.scan_consistency = couchbase::operations::query_request::scan_consistency_type::request_plus;
            } else {
                return {
                    nullptr,
                    { error::common_errc::invalid_argument, { __LINE__, __FILE__, __func__ }, "invalid value used for scan consistency" }
                };
            }
        }
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
    {
        auto [err, profile] = cb_get_integer<uint64_t>(options, "profile");
        if (err.ec) {
            return { nullptr, err };
        }

        if (profile > 0) {
            if (profile == 1) {
                request.profile = couchbase::operations::query_request::profile_mode::off;
            } else if (profile == 2) {
                request.profile = couchbase::operations::query_request::profile_mode::phases;
            } else if (profile == 3) {
                request.profile = couchbase::operations::query_request::profile_mode::timings;
            } else {
                return { nullptr,
                         { error::common_errc::invalid_argument, { __LINE__, __FILE__, __func__ },
                           "invalid value used for profile" } };
            }
        }
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
    {
        const zval* value = zend_symtable_str_find(Z_ARRVAL_P(options), ZEND_STRL("posParams"));
        if (value != nullptr && Z_TYPE_P(value) == IS_ARRAY) {
            std::vector<couchbase::json_string> params{};
            const zval *item = nullptr;

            ZEND_HASH_FOREACH_VAL(Z_ARRVAL_P(value), item)
            {
                auto str = std::string({Z_STRVAL_P(item), Z_STRLEN_P(item)});
                params.emplace_back(couchbase::json_string{std::move(str)});
            }
            ZEND_HASH_FOREACH_END();

            request.positional_parameters = params;
        }
    }
    {
        const zval* value = zend_symtable_str_find(Z_ARRVAL_P(options), ZEND_STRL("namedParams"));
        if (value != nullptr && Z_TYPE_P(value) == IS_ARRAY) {
            std::map<std::string, couchbase::json_string> params{};
            const zend_string* key = nullptr;
            const zval *item = nullptr;

            ZEND_HASH_FOREACH_STR_KEY_VAL(Z_ARRVAL_P(value), key, item)
            {
                auto str = std::string({ Z_STRVAL_P(item), Z_STRLEN_P(item) });
                auto k = std::string({ ZSTR_VAL(key), ZSTR_LEN(key) });
                params.emplace(k, couchbase::json_string{std::move(str)});
            }
            ZEND_HASH_FOREACH_END();

            request.named_parameters = params;
        }
    }
    {
        const zval* value = zend_symtable_str_find(Z_ARRVAL_P(options), ZEND_STRL("raw"));
        if (value != nullptr && Z_TYPE_P(value) == IS_ARRAY) {
            std::map<std::string, couchbase::json_string> params{};
            const zend_string* key = nullptr;
            const zval *item = nullptr;

            ZEND_HASH_FOREACH_STR_KEY_VAL(Z_ARRVAL_P(value), key, item)
            {
                auto str = std::string({ Z_STRVAL_P(item), Z_STRLEN_P(item) });
                auto k = std::string({ ZSTR_VAL(key), ZSTR_LEN(key) });
                params.emplace(k, couchbase::json_string{std::move(str)});
            }
            ZEND_HASH_FOREACH_END();

            request.raw = params;
        }
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

    auto [err, resp] = impl_->query(std::move(request));
    if (err.ec) {
        return { nullptr, err };
    }

    zval retval;
    array_init(&retval);
    add_assoc_string(&retval, "servedByNode", resp.served_by_node.c_str());

    zval rows;
    array_init(&rows);
    for (auto& row : resp.rows) {
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
        add_assoc_long(&metrics, "elapsedTimeMilliseconds", resp.meta.metrics.value().elapsed_time.count() * 1000);
        add_assoc_long(&metrics, "executionTimeMilliseconds", resp.meta.metrics.value().execution_time.count() * 1000);

        add_assoc_zval(&meta, "metrics", &metrics);
    }
    if (resp.meta.errors.has_value()) {
        zval errors;
        array_init(&errors);
        for (auto& e : resp.meta.errors.value()) {
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
        for (auto& w : resp.meta.warnings.value()) {
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
connection_handle::analytics_query(const zend_string* statement,
                                   const zval* options)
{
    couchbase::operations::analytics_request request{ cb_string_new(statement) };
    if (auto e = cb_assign_timeout(request, options); e.ec) {
        return { nullptr, e };
    }
    {
        auto [err, scanC] = cb_get_integer<uint64_t>(options, "scanConsistency");
        if (err.ec) {
            return { nullptr, err };
        }

        if (scanC > 0) {
            if (scanC == 1) {
                request.scan_consistency = couchbase::operations::analytics_request::scan_consistency_type::not_bounded;
            } else if (scanC == 2) {
                request.scan_consistency = couchbase::operations::analytics_request::scan_consistency_type::request_plus;
            } else {
                return {
                    nullptr,
                    { error::common_errc::invalid_argument, { __LINE__, __FILE__, __func__ }, "invalid value used for scan consistency" }
                };
            }
        }
    }
    if (auto e = cb_assign_boolean(request.readonly, options, "readonly"); e.ec) {
        return { nullptr, e };
    }
    if (auto e = cb_assign_boolean(request.priority, options, "priority"); e.ec) {
        return { nullptr, e };
    }
    {
        const zval* value = zend_symtable_str_find(Z_ARRVAL_P(options), ZEND_STRL("posParams"));
        if (value != nullptr && Z_TYPE_P(value) == IS_ARRAY) {
            std::vector<couchbase::json_string> params{};
            const zval *item = nullptr;

            ZEND_HASH_FOREACH_VAL(Z_ARRVAL_P(value), item)
            {
                auto str = std::string({Z_STRVAL_P(item), Z_STRLEN_P(item)});
                params.emplace_back(couchbase::json_string{std::move(str)});
            }
            ZEND_HASH_FOREACH_END();

            request.positional_parameters = params;
        }
    }
    {
        const zval* value = zend_symtable_str_find(Z_ARRVAL_P(options), ZEND_STRL("namedParams"));
        if (value != nullptr && Z_TYPE_P(value) == IS_ARRAY) {
            std::map<std::string, couchbase::json_string> params{};
            const zend_string* key = nullptr;
            const zval *item = nullptr;

            ZEND_HASH_FOREACH_STR_KEY_VAL(Z_ARRVAL_P(value), key, item)
            {
                auto str = std::string({ Z_STRVAL_P(item), Z_STRLEN_P(item) });
                auto k = std::string({ ZSTR_VAL(key), ZSTR_LEN(key) });
                params.emplace(k, couchbase::json_string{std::move(str)});
            }
            ZEND_HASH_FOREACH_END();

            request.named_parameters = params;
        }
    }
    {
        const zval* value = zend_symtable_str_find(Z_ARRVAL_P(options), ZEND_STRL("raw"));
        if (value != nullptr && Z_TYPE_P(value) == IS_ARRAY) {
            std::map<std::string, couchbase::json_string> params{};
            const zend_string* key = nullptr;
            const zval *item = nullptr;

            ZEND_HASH_FOREACH_STR_KEY_VAL(Z_ARRVAL_P(value), key, item)
            {
                auto str = std::string({ Z_STRVAL_P(item), Z_STRLEN_P(item) });
                auto k = std::string({ ZSTR_VAL(key), ZSTR_LEN(key) });
                params.emplace(k, couchbase::json_string{std::move(str)});
            }
            ZEND_HASH_FOREACH_END();

            request.raw = params;
        }
    }
    if (auto e = cb_assign_string(request.client_context_id, options, "clientContextId"); e.ec) {
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
    for (auto& row : resp.rows) {
        add_next_index_string(&rows, row.c_str());
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
            add_assoc_long(&metrics, "elapsedTimeMilliseconds", resp.meta.metrics.elapsed_time.count() * 1000);
            add_assoc_long(&metrics, "executionTimeMilliseconds", resp.meta.metrics.execution_time.count() * 1000);

            add_assoc_zval(&meta, "metrics", &metrics);
        }

        {
            zval warnings;
            array_init(&warnings);
            for (auto& w : resp.meta.warnings) {
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
