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

#include "common.hxx"
#include "couchbase/read_preference.hxx"
#include "couchbase/store_semantics.hxx"

#include <core/operations/document_query.hxx>
#include <core/operations/document_search.hxx>

#include <core/management/analytics_link_azure_blob_external.hxx>
#include <core/management/analytics_link_couchbase_remote.hxx>
#include <core/management/analytics_link_s3_external.hxx>

#include <couchbase/cas.hxx>
#include <couchbase/durability_level.hxx>
#include <couchbase/expiry.hxx>
#include <couchbase/lookup_in_specs.hxx>
#include <couchbase/persist_to.hxx>
#include <couchbase/replicate_to.hxx>

#include <Zend/zend_API.h>

#include <spdlog/fmt/bundled/format.h>

#include <chrono>
#include <optional>
#include <type_traits>

namespace couchbase::transactions
{
class transaction_query_options;
} // namespace couchbase::transactions

namespace couchbase::php
{
auto
cb_binary_new(const zend_string* value) -> std::vector<std::byte>;

auto
cb_binary_new(const zval* value) -> std::vector<std::byte>;

auto
cb_string_new(const zend_string* value) -> std::string;

auto
cb_string_new(const zval* value) -> std::string;

auto
zval_to_query_request(const zend_string* statement, const zval* options)
  -> std::pair<core::operations::query_request, core_error_info>;

auto
zval_to_transactions_query_options(const zval* options)
  -> std::pair<transactions::transaction_query_options, core_error_info>;

auto
zval_to_common_search_request(const zend_string* index_name,
                              const zend_string* query,
                              const zval* options)
  -> std::pair<core::operations::search_request, core_error_info>;

auto
cb_fill_analytics_link(core::management::analytics::couchbase_remote_link& dst, const zval* src)
  -> core_error_info;

auto
cb_fill_analytics_link(core::management::analytics::azure_blob_external_link& dst, const zval* src)
  -> core_error_info;

auto
cb_fill_analytics_link(core::management::analytics::s3_external_link& dst, const zval* src)
  -> core_error_info;

void
query_response_to_zval(zval* return_value, const core::operations::query_response& resp);

void
search_query_response_to_zval(zval* return_value, const core::operations::search_response& resp);

template<typename Integer>
static auto
parse_integer(const std::string& str, std::size_t* pos = nullptr, int base = 10) -> Integer
{
  if constexpr (std::is_signed_v<Integer>) {
    return std::stoll(str, pos, base);
  } else {
    return std::stoull(str, pos, base);
  }
}

template<typename Integer>
static auto
cb_get_integer_from_hex(const zend_string* value, std::string_view name)
  -> std::pair<core_error_info, std::optional<Integer>>
{
  auto hex_string = cb_string_new(value);

  if (hex_string.empty()) {
    return { { errc::common::invalid_argument,
               ERROR_LOCATION,
               fmt::format("unexpected empty string for {}", name) },
             {} };
  }

  try {
    std::size_t pos = 0;
    auto result = parse_integer<Integer>(hex_string, &pos, 16);
    if (result < std::numeric_limits<Integer>::min() ||
        result > std::numeric_limits<Integer>::max()) {
      return { { errc::common::invalid_argument,
                 ERROR_LOCATION,
                 fmt::format("number out of range for {}", name) },
               {} };
    }
    if (pos != hex_string.length()) {
      return { { errc::common::invalid_argument,
                 ERROR_LOCATION,
                 fmt::format("trailing garbage in {}", name) },
               {} };
    }
    return { {}, result };
  } catch (const std::invalid_argument& e) {
    return { { errc::common::invalid_argument,
               ERROR_LOCATION,
               fmt::format("invalid hex number for {}", name) },
             {} };
  } catch (const std::out_of_range& e) {
    return { { errc::common::invalid_argument,
               ERROR_LOCATION,
               fmt::format("number out of range for {}", name) },
             {} };
  }
}

template<typename Integer>
static auto
cb_get_integer(const zval* options, std::string_view name)
  -> std::pair<core_error_info, std::optional<Integer>>
{
  if (options == nullptr || Z_TYPE_P(options) == IS_NULL) {
    return {};
  }
  if (Z_TYPE_P(options) != IS_ARRAY) {
    return {
      { errc::common::invalid_argument, ERROR_LOCATION, "expected array for options argument" }, {}
    };
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
    case IS_STRING:
      return cb_get_integer_from_hex<Integer>(Z_STR_P(value), name);
    default:
      return { { errc::common::invalid_argument,
                 ERROR_LOCATION,
                 fmt::format("expected {} to be a integer value in the options", name) },
               {} };
  }

  return { {}, Z_LVAL_P(value) };
}

template<typename Integer>
auto
cb_assign_integer(Integer& field, const zval* options, std::string_view name) -> core_error_info
{
  auto [e, value] = cb_get_integer<Integer>(options, name);
  if (e.ec) {
    return e;
  }
  if (value) {
    field = *value;
  }
  return {};
}

auto
cb_get_string(const zval* options, std::string_view name)
  -> std::pair<core_error_info, std::optional<std::string>>;

template<typename String>
auto
cb_assign_string(String& field, const zval* options, std::string_view name) -> core_error_info
{
  auto [e, value] = cb_get_string(options, name);
  if (e.ec) {
    return e;
  }
  if (value) {
    field = *value;
  }
  return {};
}

auto
cb_get_binary(const zval* options, std::string_view name)
  -> std::pair<core_error_info, std::optional<std::vector<std::byte>>>;

template<typename Binary>
auto
cb_assign_binary(Binary& field, const zval* options, std::string_view name) -> core_error_info
{
  auto [e, value] = cb_get_binary(options, name);
  if (e.ec) {
    return e;
  }
  if (value) {
    field = *value;
  }
  return {};
}

auto
cb_get_timeout(const zval* options)
  -> std::pair<core_error_info, std::optional<std::chrono::milliseconds>>;

template<typename Request>
auto
cb_assign_timeout(Request& req, const zval* options) -> core_error_info
{
  auto [err, timeout] = cb_get_timeout(options);
  if (!err.ec && timeout) {
    req.timeout = timeout.value();
    return {};
  }
  return err;
}

template<typename Request>
auto
cb_assign_content(Request& req, const zend_string* value) -> core_error_info
{
  req.value = cb_binary_new(value);
  return {};
}

template<typename Request>
auto
cb_assign_flags(Request& req, zend_long flags) -> core_error_info
{
  req.flags = static_cast<std::uint32_t>(flags);
  return {};
}

template<typename Request>
auto
cb_assign_expiry(Request& req, const zval* options) -> core_error_info
{
  if (auto [e, value] = cb_get_integer<std::uint64_t>(options, "expirySeconds"); e.ec) {
    return e;
  } else if (value) {
    try {
      req.expiry = core::impl::expiry_relative(std::chrono::seconds{ value.value() });
    } catch (const std::system_error& ec) {
      return { ec.code(), ERROR_LOCATION, ec.what() };
    }
    return {};
  }

  if (auto [e, value] = cb_get_integer<std::uint64_t>(options, "expiryTimestamp"); e.ec) {
    return e;
  } else if (value) {
    try {
      req.expiry = core::impl::expiry_absolute(
        std::chrono::system_clock::time_point{ std::chrono::seconds{ value.value() } });
    } catch (const std::system_error& ec) {
      return { ec.code(), ERROR_LOCATION, ec.what() };
    }
  }
  return {};
}

template<typename Request>
auto
cb_assign_initial_value(Request& req, const zval* options) -> core_error_info
{
  if (auto [e, value] = cb_get_integer<std::uint64_t>(options, "initialValue"); e.ec) {
    return e;
  } else if (value) {
    req.initial_value = value.value();
  }
  return {};
}

template<typename Request>
auto
cb_assign_delta(Request& req, const zval* options) -> core_error_info
{
  if (auto [e, value] = cb_get_integer<std::uint64_t>(options, "delta"); e.ec) {
    return e;
  } else if (value) {
    req.delta = value.value();
  }
  return {};
}

auto
cb_get_boolean(const zval* options, std::string_view name)
  -> std::pair<core_error_info, std::optional<bool>>;

template<typename Request>
auto
cb_assign_access_deleted(Request& req, const zval* options) -> core_error_info
{
  auto [err, value] = cb_get_boolean(options, "accessDeleted");
  if (err.ec) {
    return err;
  }
  if (value.has_value()) {
    req.access_deleted = value.value();
  }
  return {};
}

template<typename Request>
auto
cb_assign_preserve_expiry(Request& req, const zval* options) -> core_error_info
{
  auto [err, value] = cb_get_boolean(options, "preserveExpiry");
  if (err.ec) {
    return err;
  }
  if (value.has_value()) {
    req.preserve_expiry = value.value();
  }
  return {};
}

template<typename Request>
auto
cb_assign_create_as_deleted(Request& req, const zval* options) -> core_error_info
{
  auto [err, value] = cb_get_boolean(options, "createAsDeleted");
  if (err.ec) {
    return err;
  }
  if (value.has_value()) {
    req.create_as_deleted = value.value();
  }
  return {};
}

auto
cb_get_cas(const zval* options) -> std::pair<core_error_info, std::optional<couchbase::cas>>;

template<typename Request>
auto
cb_assign_cas(Request& req, const zval* options) -> core_error_info
{
  auto [err, value] = cb_get_cas(options);
  if (err.ec) {
    return err;
  }
  if (value.has_value()) {
    req.cas = value.value();
  }
  return {};
}

template<typename Boolean>
auto
cb_assign_boolean(Boolean& field, const zval* options, std::string_view name) -> core_error_info
{
  if (options == nullptr || Z_TYPE_P(options) == IS_NULL) {
    return {};
  }
  if (Z_TYPE_P(options) != IS_ARRAY) {
    return { errc::common::invalid_argument,
             ERROR_LOCATION,
             "expected array for options argument" };
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
      return { errc::common::invalid_argument,
               ERROR_LOCATION,
               fmt::format("expected {} to be a boolean value in the options", name) };
  }
  return {};
}

auto
cb_string_to_cas(const std::string& cas_string, couchbase::cas& cas) -> core_error_info;

auto
cb_assign_cas(couchbase::cas& cas, const zval* document) -> core_error_info;

auto
cb_assign_vector_of_strings(std::vector<std::string>& field,
                            const zval* options,
                            std::string_view name) -> core_error_info;

auto
cb_get_durability_level(const zval* options)
  -> std::pair<core_error_info, std::optional<couchbase::durability_level>>;

auto
cb_needs_request_with_legacy_durability(
  const std::optional<std::pair<couchbase::persist_to, couchbase::replicate_to>>& constraints)
  -> bool;

auto
cb_get_legacy_durability_constraints(const zval* options)
  -> std::pair<core_error_info,
               std::optional<std::pair<couchbase::persist_to, couchbase::replicate_to>>>;

template<typename Options>
auto
cb_set_durability(Options& opts, const zval* options) -> core_error_info
{
  if (auto [e, level] = cb_get_durability_level(options); e.ec) {
    return e;
  } else if (level) {
    opts.durability(level.value());
    return {};
  }

  if (auto [e, constraints] = cb_get_legacy_durability_constraints(options); e.ec) {
    return e;
  } else if (constraints) {
    opts.durability(constraints->first, constraints->second);
    return {};
  }

  return {};
}

template<typename Request>
auto
cb_assign_durability_level(Request& req, const zval* options) -> core_error_info
{
  auto [err, durability] = cb_get_durability_level(options);
  if (err.ec) {
    return err;
  }
  if (durability) {
    req.durability_level = durability.value();
  }
  return {};
}

template<typename Request>
auto
cb_assign_store_semantics(Request& req, const zval* options) -> core_error_info
{
  if (options == nullptr || Z_TYPE_P(options) == IS_NULL) {
    return {};
  }
  if (Z_TYPE_P(options) != IS_ARRAY) {
    return { errc::common::invalid_argument,
             ERROR_LOCATION,
             "expected array for options argument" };
  }

  if (auto [e, value] = cb_get_string(options, "storeSemantics"); e.ec) {
    return e;
  } else if (value) {
    if (value.value() == "replace") {
      req.store_semantics = store_semantics::replace;
    } else if (value.value() == "insert") {
      req.store_semantics = store_semantics::insert;
    } else if (value.value() == "upsert") {
      req.store_semantics = store_semantics::upsert;
    } else if (!value.value().empty()) {
      return { errc::common::invalid_argument,
               ERROR_LOCATION,
               fmt::format("unexpected value for storeSemantics option: {}", value.value()) };
    }
  }
  return {};
}

template<typename Request>
auto
cb_assign_read_preference(Request& req, const zval* options) -> core_error_info
{
  if (options == nullptr || Z_TYPE_P(options) == IS_NULL) {
    return {};
  }

  if (Z_TYPE_P(options) != IS_ARRAY) {
    return { errc::common::invalid_argument,
             ERROR_LOCATION,
             "expected array for options argument" };
  }

  if (auto [e, value] = cb_get_string(options, "readPreference"); e.ec) {
    return e;
  } else if (value) {
    if (value.value() == "noPreference") {
      req.read_preference = read_preference::no_preference;
    } else if (value.value() == "selectedServerGroup") {
      req.read_preference = read_preference::selected_server_group;
    } else if (!value.value().empty()) {
      return { errc::common::invalid_argument,
               ERROR_LOCATION,
               fmt::format("unexpected value for readPreference option: {}", value.value()) };
    }
  }
  return {};
}

auto
decode_lookup_subdoc_opcode(const zval* spec)
  -> std::pair<core::protocol::subdoc_opcode, core_error_info>;

template<typename Request>
auto
cb_assign_lookup_in_specs(Request& req, const zval* specs) -> core_error_info
{
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

  req.specs = cxx_specs.specs();
  return {};
}

template<typename Response>
void
cb_create_lookup_in_result(zval* return_value, const Response& resp, const zend_string* id)
{
  array_init(return_value);

  add_assoc_stringl(return_value, "id", ZSTR_VAL(id), ZSTR_LEN(id));
  add_assoc_bool(return_value, "deleted", resp.deleted);

  auto cas = fmt::format("{:x}", resp.cas.value());
  add_assoc_stringl(return_value, "cas", cas.data(), cas.size());

  zval fields;
  array_init_size(&fields, resp.fields.size());
  for (const auto& field : resp.fields) {
    zval entry;
    array_init(&entry);
    add_assoc_stringl(&entry, "path", field.path.data(), field.path.size());
    add_assoc_bool(&entry, "exists", field.exists);
    if (!field.value.empty()) {
      add_assoc_stringl(
        &entry, "value", reinterpret_cast<const char*>(field.value.data()), field.value.size());
    }
    add_index_zval(&fields, field.original_index, &entry);
  }
  add_assoc_zval(return_value, "fields", &fields);
}

template<typename Response>
void
cb_create_lookup_in_replica_result(zval* return_value, const Response& resp, const zend_string* id)
{
  cb_create_lookup_in_result(return_value, resp, id);
  add_assoc_bool(return_value, "isReplica", resp.is_replica);
}

template<typename Response>
void
cb_create_mutation_result(zval* return_value, const Response& resp, const zend_string* id)
{
  array_init(return_value);

  add_assoc_stringl(return_value, "id", ZSTR_VAL(id), ZSTR_LEN(id));

  auto cas = fmt::format("{:x}", resp.cas.value());
  add_assoc_stringl(return_value, "cas", cas.data(), cas.size());

  if (!resp.token.bucket_name().empty() && resp.token.partition_uuid() > 0) {
    zval token_val;
    {
      array_init(&token_val);
      add_assoc_stringl(
        &token_val, "bucketName", resp.token.bucket_name().data(), resp.token.bucket_name().size());
      add_assoc_long(&token_val, "partitionId", resp.token.partition_id());
      auto val = fmt::format("{:x}", resp.token.partition_uuid());
      add_assoc_stringl(&token_val, "partitionUuid", val.data(), val.size());
      val = fmt::format("{:x}", resp.token.sequence_number());
      add_assoc_stringl(&token_val, "sequenceNumber", val.data(), val.size());
    }
    add_assoc_zval(return_value, "mutationToken", &token_val);
  }
}

template<typename Response>
void
cb_create_counter_result(zval* return_value, const Response& resp, const zend_string* id)
{
  cb_create_mutation_result(return_value, resp, id);

  add_assoc_long(return_value, "value", static_cast<zend_long>(resp.content));
  auto value_str = fmt::format("{}", resp.content);
  add_assoc_stringl(return_value, "valueString", value_str.data(), value_str.size());
}

template<typename Response>
void
cb_create_get_result(zval* return_value, const Response& resp, const zend_string* id)
{
  array_init(return_value);
  add_assoc_stringl(return_value, "id", ZSTR_VAL(id), ZSTR_LEN(id));
  auto cas = fmt::format("{:x}", resp.cas.value());
  add_assoc_stringl(return_value, "cas", cas.data(), cas.size());
  add_assoc_long(return_value, "flags", resp.flags);
  add_assoc_stringl(
    return_value, "value", reinterpret_cast<const char*>(resp.value.data()), resp.value.size());
}

template<typename Response>
void
cb_create_get_replica_result(zval* return_value, const Response& resp, const zend_string* id)
{
  cb_create_get_result(return_value, resp, id);
  add_assoc_bool(return_value, "isReplica", resp.replica);
}
} // namespace couchbase::php
