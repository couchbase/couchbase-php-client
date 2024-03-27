/**
 * Copyright 2022-Present Couchbase, Inc.
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
#include "transactions_resource.hxx"

#include <core/transactions.hxx>

#include <fmt/core.h>

#include <array>
#include <thread>

namespace couchbase::php
{
static int transactions_destructor_id_{ 0 };

COUCHBASE_API
void
set_transactions_destructor_id(int id)
{
    transactions_destructor_id_ = id;
}

COUCHBASE_API
int
get_transactions_destructor_id()
{
    return transactions_destructor_id_;
}

class transactions_resource::impl : public std::enable_shared_from_this<transactions_resource::impl>
{
  public:
    impl(connection_handle* connection, const couchbase::transactions::transactions_config& config)
      : cluster_{ connection->cluster() }
      , transactions_(*cluster_, config)
    {
    }

    impl(impl&& other) = delete;

    impl(const impl& other) = delete;

    const impl& operator=(impl&& other) = delete;

    const impl& operator=(const impl& other) = delete;

    [[nodiscard]] couchbase::core::transactions::transactions& transactions()
    {
        return transactions_;
    }

    void notify_fork(couchbase::fork_event event)
    {
        transactions_.notify_fork(event);
    }

  private:
    std::shared_ptr<couchbase::core::cluster> cluster_;
    couchbase::core::transactions::transactions transactions_;
};

COUCHBASE_API
transactions_resource::transactions_resource(connection_handle* connection,
                                             const couchbase::transactions::transactions_config& configuration)
  : impl_{ std::make_shared<transactions_resource::impl>(connection, configuration) }
{
}

COUCHBASE_API
core::transactions::transactions&
transactions_resource::transactions()
{
    return impl_->transactions();
}

void
transactions_resource::notify_fork(fork_event event) const
{
    return impl_->notify_fork(event);
}

#define ASSIGN_DURATION_OPTION(name, setter, key, value)                                                                                   \
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
        (setter)(std::chrono::milliseconds(ms));                                                                                           \
    }

#define ASSIGN_NEGATED_BOOLEAN_OPTION(name, setter, key, value)                                                                            \
    if (zend_binary_strcmp(ZSTR_VAL(key), ZSTR_LEN(key), ZEND_STRL(name)) == 0) {                                                          \
        if ((value) == nullptr || Z_TYPE_P(value) == IS_NULL) {                                                                            \
            continue;                                                                                                                      \
        }                                                                                                                                  \
        switch (Z_TYPE_P(value)) {                                                                                                         \
            case IS_TRUE:                                                                                                                  \
                (setter)(false);                                                                                                           \
                break;                                                                                                                     \
            case IS_FALSE:                                                                                                                 \
                (setter)(true);                                                                                                            \
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

static core_error_info
apply_options(couchbase::transactions::transactions_config& config, zval* options)
{
    if (options == nullptr || Z_TYPE_P(options) != IS_ARRAY) {
        return { errc::common::invalid_argument, ERROR_LOCATION, "expected array for transactions configuration" };
    }

    const zend_string* key;
    const zval* value;

    ZEND_HASH_FOREACH_STR_KEY_VAL(Z_ARRVAL_P(options), key, value)
    {
        ASSIGN_DURATION_OPTION("timeout", config.timeout, key, value);
        if (zend_binary_strcmp(ZSTR_VAL(key), ZSTR_LEN(key), ZEND_STRL("durabilityLevel")) == 0) {
            if (value == nullptr || Z_TYPE_P(value) == IS_NULL) {
                continue;
            }
            if (Z_TYPE_P(value) != IS_STRING) {
                return { errc::common::invalid_argument, ERROR_LOCATION, "expected durabilityLevel to be a string" };
            }
            if (zend_binary_strcmp(Z_STRVAL_P(value), Z_STRLEN_P(value), ZEND_STRL("none")) == 0) {
                config.durability_level(couchbase::durability_level::none);
            } else if (zend_binary_strcmp(Z_STRVAL_P(value), Z_STRLEN_P(value), ZEND_STRL("majority")) == 0) {
                config.durability_level(couchbase::durability_level::majority);
            } else if (zend_binary_strcmp(Z_STRVAL_P(value), Z_STRLEN_P(value), ZEND_STRL("majorityAndPersistToActive")) == 0) {
                config.durability_level(couchbase::durability_level::majority_and_persist_to_active);
            } else if (zend_binary_strcmp(Z_STRVAL_P(value), Z_STRLEN_P(value), ZEND_STRL("persistToMajority")) == 0) {
                config.durability_level(couchbase::durability_level::persist_to_majority);
            } else {
                return { errc::common::invalid_argument,
                         ERROR_LOCATION,
                         fmt::format("unknown durabilityLevel: {}", std::string_view(Z_STRVAL_P(value), Z_STRLEN_P(value))) };
            }
        }

        if (zend_binary_strcmp(ZSTR_VAL(key), ZSTR_LEN(key), ZEND_STRL("queryOptions")) == 0) {
            if (value == nullptr || Z_TYPE_P(value) == IS_NULL) {
                continue;
            }
            if (Z_TYPE_P(value) != IS_ARRAY) {
                return { errc::common::invalid_argument,
                         ERROR_LOCATION,
                         fmt::format("expected array for {} as query options for transactions",
                                     std::string(ZSTR_VAL(key), ZSTR_LEN(key))) };
            }

            const zend_string* k;
            const zval* v;
            ZEND_HASH_FOREACH_STR_KEY_VAL(Z_ARRVAL_P(value), k, v)
            {
                if (zend_binary_strcmp(ZSTR_VAL(k), ZSTR_LEN(k), ZEND_STRL("scanConsistency")) == 0) {
                    if (v == nullptr || Z_TYPE_P(v) == IS_NULL) {
                        continue;
                    }
                    if (Z_TYPE_P(v) != IS_STRING) {
                        return { errc::common::invalid_argument, ERROR_LOCATION, "expected scanConsistency to be a string" };
                    }
                    if (zend_binary_strcmp(Z_STRVAL_P(v), Z_STRLEN_P(v), ZEND_STRL("notBounded")) == 0) {
                        config.query_config().scan_consistency(query_scan_consistency::not_bounded);
                    } else if (zend_binary_strcmp(Z_STRVAL_P(v), Z_STRLEN_P(v), ZEND_STRL("requestPlus")) == 0) {
                        config.query_config().scan_consistency(query_scan_consistency::request_plus);
                    } else {
                        return { errc::common::invalid_argument,
                                 ERROR_LOCATION,
                                 fmt::format("unknown scanConsistency: {}", std::string_view(Z_STRVAL_P(v), Z_STRLEN_P(v))) };
                    }
                }
            }
            ZEND_HASH_FOREACH_END();
        }

        if (zend_binary_strcmp(ZSTR_VAL(key), ZSTR_LEN(key), ZEND_STRL("cleanupOptions")) == 0) {
            if (value == nullptr || Z_TYPE_P(value) == IS_NULL) {
                continue;
            }
            if (Z_TYPE_P(value) != IS_ARRAY) {
                return { errc::common::invalid_argument,
                         ERROR_LOCATION,
                         fmt::format("expected array for {} as cleanup options for transactions",
                                     std::string(ZSTR_VAL(key), ZSTR_LEN(key))) };
            }

            const zend_string* k;
            const zval* v;
            ZEND_HASH_FOREACH_STR_KEY_VAL(Z_ARRVAL_P(value), k, v)
            {
                ASSIGN_DURATION_OPTION("cleanupWindow", config.cleanup_config().cleanup_window, k, v);
                ASSIGN_NEGATED_BOOLEAN_OPTION("disableLostAttemptCleanup", config.cleanup_config().cleanup_lost_attempts, k, v);
                ASSIGN_NEGATED_BOOLEAN_OPTION("disableClientAttemptCleanup", config.cleanup_config().cleanup_client_attempts, k, v);
            }
            ZEND_HASH_FOREACH_END();
        }

        if (zend_binary_strcmp(ZSTR_VAL(key), ZSTR_LEN(key), ZEND_STRL("metadataCollection")) == 0) {
            if (value == nullptr || Z_TYPE_P(value) == IS_NULL) {
                continue;
            }
            if (Z_TYPE_P(value) != IS_ARRAY) {
                return { errc::common::invalid_argument,
                         ERROR_LOCATION,
                         fmt::format("expected array for {} as metadata collection for transactions",
                                     std::string(ZSTR_VAL(key), ZSTR_LEN(key))) };
            }

            const zend_string* k;
            const zval* v;
            std::string bucket;
            std::string scope;
            std::string collection;
            ZEND_HASH_FOREACH_STR_KEY_VAL(Z_ARRVAL_P(value), k, v)
            {
                ASSIGN_STRING_OPTION("bucket", bucket, k, v);
                ASSIGN_STRING_OPTION("scope", scope, k, v);
                ASSIGN_STRING_OPTION("collection", collection, k, v);
            }
            ZEND_HASH_FOREACH_END();
            config.metadata_collection({ bucket, scope, collection });
        }
    }
    ZEND_HASH_FOREACH_END();

    return {};
}

COUCHBASE_API
std::pair<zend_resource*, core_error_info>
create_transactions_resource(connection_handle* connection, zval* options)
{
    couchbase::transactions::transactions_config config{};
    if (auto e = apply_options(config, options); e.ec) {
        return { nullptr, e };
    }
    // ensure that metadata collection is opened
    if (auto metadata_collection = config.metadata_collection(); metadata_collection && !metadata_collection->bucket.empty()) {
        if (auto e = connection->bucket_open(metadata_collection->bucket); e.ec) {
            return { nullptr, e };
        }
    }
    auto* handle = new transactions_resource(connection, config);
    return { zend_register_resource(handle, transactions_destructor_id_), {} };
}

COUCHBASE_API
void
destroy_transactions_resource(zend_resource* res)
{
    if (res->type == transactions_destructor_id_ && res->ptr != nullptr) {
        auto* handle = static_cast<transactions_resource*>(res->ptr);
        res->ptr = nullptr;
        std::thread([handle]() { delete handle; }).detach();
    }
}

} // namespace couchbase::php
