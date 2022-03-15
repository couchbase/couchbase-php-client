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