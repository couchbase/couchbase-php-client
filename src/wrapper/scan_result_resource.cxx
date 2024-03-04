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
#include "conversion_utilities.hxx"
#include "scan_result_resource.hxx"

#include <core/agent_group.hxx>
#include <core/cluster.hxx>
#include <core/range_scan_options.hxx>
#include <core/range_scan_orchestrator.hxx>
#include <core/range_scan_orchestrator_options.hxx>

#include <fmt/core.h>

#include <array>
#include <thread>

namespace couchbase::php
{
static int scan_result_destructor_id_{ 0 };

void
set_scan_result_destructor_id(int id)
{
    scan_result_destructor_id_ = id;
}

int
get_scan_result_destructor_id()
{
    return scan_result_destructor_id_;
}

class scan_result_resource::impl : public std::enable_shared_from_this<scan_result_resource::impl>
{
  public:
    impl(connection_handle* connection, std::unique_ptr<couchbase::core::scan_result> scan_result)
      : cluster_{ connection->cluster() }
      , scan_result_{ std::move(scan_result) }
    {
    }

    impl(impl&& other) = delete;

    impl(const impl& other) = delete;

    const impl& operator=(impl&& other) = delete;

    const impl& operator=(const impl& other) = delete;

    [[nodiscard]] std::pair<std::optional<couchbase::core::range_scan_item>, core_error_info> next_item()
    {
        auto barrier = std::make_shared<std::promise<tl::expected<couchbase::core::range_scan_item, std::error_code>>>();
        auto f = barrier->get_future();
        scan_result_->next([barrier](couchbase::core::range_scan_item item, std::error_code ec) {
            if (ec) {
                return barrier->set_value(tl::unexpected(ec));
            } else {
                return barrier->set_value(item);
            }
        });
        auto resp = f.get();
        if (!resp.has_value()) {
            if (resp.error() != couchbase::errc::key_value::range_scan_completed) {
                return { {}, { resp.error(), ERROR_LOCATION, "Unable to fetch scan item" } };
            }
            return { {}, {} };
        }
        return { resp.value(), {} };
    }

  private:
    std::shared_ptr<couchbase::core::cluster> cluster_;
    std::unique_ptr<couchbase::core::scan_result> scan_result_;
};

COUCHBASE_API
scan_result_resource::scan_result_resource(connection_handle* connection, const couchbase::core::scan_result& scan_result)
  : impl_{ std::make_shared<scan_result_resource::impl>(connection, std::make_unique<couchbase::core::scan_result>(scan_result)) }
{
}

COUCHBASE_API
core_error_info
scan_result_resource::next_item(zval* return_value)
{
    auto [resp, err] = impl_->next_item();
    if (err.ec) {
        return err;
    }
    if (resp) {
        array_init(return_value);
        add_assoc_stringl(return_value, "id", resp->key.data(), resp->key.size());
        if (resp->body.has_value()) {
            auto body = resp->body.value();
            auto cas = fmt::format("{:x}", body.cas.value());
            add_assoc_stringl(return_value, "cas", cas.data(), cas.size());
            add_assoc_long(return_value, "flags", body.flags);
            add_assoc_stringl(return_value, "value", reinterpret_cast<const char*>(body.value.data()), body.value.size());
            add_assoc_long(return_value, "expiry", body.expiry);
            add_assoc_bool(return_value, "idsOnly", 0);
        } else {
            add_assoc_bool(return_value, "idsOnly", 1);
        }
    }
    return {};
}

COUCHBASE_API
std::pair<zend_resource*, core_error_info>
create_scan_result_resource(connection_handle* connection,
                            const zend_string* bucket,
                            const zend_string* scope,
                            const zend_string* collection,
                            const zval* scan_type,
                            const zval* options)
{
    // Get orchestrator options
    couchbase::core::range_scan_orchestrator_options opts;

    if (auto e = cb_assign_timeout(opts, options); e.ec) {
        return { nullptr, e };
    }
    if (auto e = cb_assign_boolean(opts.ids_only, options, "idsOnly"); e.ec) {
        return { nullptr, e };
    }
    if (auto e = cb_assign_integer(opts.concurrency, options, "concurrency"); e.ec) {
        return { nullptr, e };
    }
    if (auto e = cb_assign_integer(opts.batch_byte_limit, options, "batchByteLimit"); e.ec) {
        return { nullptr, e };
    }
    if (auto e = cb_assign_integer(opts.batch_item_limit, options, "batchItemLimit"); e.ec) {
        return { nullptr, e };
    }
    if (const zval* value = zend_symtable_str_find(Z_ARRVAL_P(options), ZEND_STRL("consistentWith"));
        value != nullptr && Z_TYPE_P(value) == IS_ARRAY) {
        couchbase::core::mutation_state mutation_state{};
        const zval* item = nullptr;

        ZEND_HASH_FOREACH_VAL(Z_ARRVAL_P(value), item)
        {
            std::uint64_t partition_uuid;
            std::uint64_t sequence_number;
            std::uint16_t partition_id;
            std::string bucket_name;
            if (auto e = cb_assign_integer(partition_id, options, "partitionId"); e.ec) {
                return { nullptr, e };
            }
            if (auto e = cb_assign_integer(partition_uuid, options, "partitionUuid"); e.ec) {
                return { nullptr, e };
            }
            if (auto e = cb_assign_integer(sequence_number, options, "sequenceNumber"); e.ec) {
                return { nullptr, e };
            }
            if (auto e = cb_assign_string(bucket_name, options, "bucketName"); e.ec) {
                return { nullptr, e };
            }
            mutation_state.tokens.emplace_back(mutation_token{ partition_uuid, sequence_number, partition_id, bucket_name });
        }
        ZEND_HASH_FOREACH_END();

        opts.consistent_with = mutation_state;
    }

    auto bucket_name = cb_string_new(bucket);
    auto scope_name = cb_string_new(scope);
    auto collection_name = cb_string_new(collection);

    // Get operation agent
    auto clust = connection->cluster();

    auto agent_group = couchbase::core::agent_group(clust->io_context(), couchbase::core::agent_group_config{ { *clust } });
    agent_group.open_bucket(bucket_name);
    auto agent = agent_group.get_agent(bucket_name);
    if (!agent.has_value()) {
        return { nullptr, { agent.error(), ERROR_LOCATION, "Cannot perform scan operation. Unable to get operation agent" } };
    }

    // Get vBucket map
    auto barrier = std::make_shared<std::promise<std::pair<std::error_code, core::topology::configuration>>>();
    auto f = barrier->get_future();
    clust->with_bucket_configuration(bucket_name, [barrier](std::error_code ec, const core::topology::configuration& config) {
        barrier->set_value({ ec, config });
    });
    auto [ec, config] = f.get();
    if (ec) {
        return { nullptr, { ec, ERROR_LOCATION, "Cannot perform scan operation. Unable to get bucket config" } };
    }
    if (!config.capabilities.supports_range_scan()) {
        return { nullptr,
                 { errc::common::feature_not_available, ERROR_LOCATION, "Server version does not support key-value scan operations" } };
    }
    auto vbucket_map = config.vbmap;
    if (!vbucket_map || vbucket_map->empty()) {
        return { nullptr, { std::error_code{}, ERROR_LOCATION, "Cannot perform scan operation. Unable to get vBucket map." } };
    }

    // Create scan type

    std::variant<std::monostate, couchbase::core::range_scan, couchbase::core::prefix_scan, couchbase::core::sampling_scan>
      core_scan_type{};

    if (auto [e, type] = cb_get_string(scan_type, "type"); type) {
        if (type == "range_scan") {
            couchbase::core::range_scan range_scan{};
            if (const zval* from = zend_symtable_str_find(Z_ARRVAL_P(scan_type), ZEND_STRL("from"));
                from != nullptr && Z_TYPE_P(from) == IS_ARRAY) {
                couchbase::core::scan_term from_term{};
                if (auto e = cb_assign_string(from_term.term, from, "term"); e.ec) {
                    return { nullptr, e };
                }
                if (auto e = cb_assign_boolean(from_term.exclusive, from, "exclusive"); e.ec) {
                    return { nullptr, e };
                }
                range_scan.from = from_term;
            }
            if (const zval* to = zend_symtable_str_find(Z_ARRVAL_P(scan_type), ZEND_STRL("to"));
                to != nullptr && Z_TYPE_P(to) == IS_ARRAY) {
                couchbase::core::scan_term to_term{};
                if (auto e = cb_assign_string(to_term.term, to, "term"); e.ec) {
                    return { nullptr, e };
                }
                if (auto e = cb_assign_boolean(to_term.exclusive, to, "exclusive"); e.ec) {
                    return { nullptr, e };
                }
                range_scan.to = to_term;
            }
            core_scan_type = range_scan;
        } else if (type == "prefix_scan") {
            couchbase::core::prefix_scan prefix_scan{};
            if (auto e = cb_assign_string(prefix_scan.prefix, scan_type, "prefix"); e.ec) {
                return { nullptr, e };
            }
            core_scan_type = prefix_scan;
        } else if (type == "sampling_scan") {
            couchbase::core::sampling_scan sampling_scan{};
            if (auto e = cb_assign_integer(sampling_scan.limit, scan_type, "limit"); e.ec) {
                return { nullptr, e };
            }
            if (auto e = cb_assign_integer(sampling_scan.seed, scan_type, "seed"); e.ec) {
                return { nullptr, e };
            }
            core_scan_type = sampling_scan;
        } else {
            return { nullptr, { errc::common::invalid_argument, ERROR_LOCATION, "Invalid scan type provided" } };
        }
    } else if (e.ec) {
        return { nullptr, e };
    }
    auto orchestrator = couchbase::core::range_scan_orchestrator(
      clust->io_context(), agent.value(), vbucket_map.value(), scope_name, collection_name, core_scan_type, opts);

    // start scan
    auto resp = orchestrator.scan();
    if (!resp.has_value()) {
        return { nullptr, { resp.error(), ERROR_LOCATION, "Unable to start the scan" } };
    }

    auto* handle = new scan_result_resource(connection, resp.value());

    return { zend_register_resource(handle, scan_result_destructor_id_), {} };
}

COUCHBASE_API
void
destroy_scan_result_resource(zend_resource* res)
{
    if (res->type == scan_result_destructor_id_ && res->ptr != nullptr) {
        auto* handle = static_cast<scan_result_resource*>(res->ptr);
        res->ptr = nullptr;
        std::thread([handle]() { delete handle; }).detach();
    }
}
} // namespace couchbase::php
