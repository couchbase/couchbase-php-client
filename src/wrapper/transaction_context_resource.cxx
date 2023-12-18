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
#include "transaction_context_resource.hxx"
#include "transactions_resource.hxx"

#include <core/transactions.hxx>
#include <core/transactions/internal/exceptions_internal.hxx>
#include <core/transactions/internal/transaction_context.hxx>
#include <core/transactions/internal/utils.hxx>

#include <core/document_id_fmt.hxx>

#include <fmt/core.h>

#include <array>
#include <thread>

namespace couchbase::php
{
static int transaction_context_destructor_id_{ 0 };

void
set_transaction_context_destructor_id(int id)
{
    transaction_context_destructor_id_ = id;
}

int
get_transaction_context_destructor_id()
{
    return transaction_context_destructor_id_;
}

static std::string
external_exception_to_string(core::transactions::external_exception cause)
{
    switch (cause) {
        case core::transactions::UNKNOWN:
            return "unknown";
        case core::transactions::ACTIVE_TRANSACTION_RECORD_ENTRY_NOT_FOUND:
            return "activeTransactionRecordEntryNotFound";
        case core::transactions::ACTIVE_TRANSACTION_RECORD_FULL:
            return "activeTransactionRecordFull";
        case core::transactions::ACTIVE_TRANSACTION_RECORD_NOT_FOUND:
            return "activeTransactionRecordNotFound";
        case core::transactions::DOCUMENT_ALREADY_IN_TRANSACTION:
            return "documentAlreadyInTransaction";
        case core::transactions::DOCUMENT_EXISTS_EXCEPTION:
            return "documentExistsException";
        case core::transactions::DOCUMENT_NOT_FOUND_EXCEPTION:
            return "documentNotFoundException";
        case core::transactions::NOT_SET:
            return "notSet";
        case core::transactions::FEATURE_NOT_AVAILABLE_EXCEPTION:
            return "featureNotAvailableException";
        case core::transactions::TRANSACTION_ABORTED_EXTERNALLY:
            return "transactionAbortedExternally";
        case core::transactions::PREVIOUS_OPERATION_FAILED:
            return "previousOperationFailed";
        case core::transactions::FORWARD_COMPATIBILITY_FAILURE:
            return "forwardCompatibilityFailure";
        case core::transactions::PARSING_FAILURE:
            return "parsingFailure";
        case core::transactions::ILLEGAL_STATE_EXCEPTION:
            return "illegalStateException";
        case core::transactions::COUCHBASE_EXCEPTION:
            return "couchbaseException";
        case core::transactions::SERVICE_NOT_AVAILABLE_EXCEPTION:
            return "serviceNotAvailableException";
        case core::transactions::REQUEST_CANCELED_EXCEPTION:
            return "requestCanceledException";
        case core::transactions::CONCURRENT_OPERATIONS_DETECTED_ON_SAME_DOCUMENT:
            return "concurrentOperationsDetectedOnSameDocument";
        case core::transactions::COMMIT_NOT_PERMITTED:
            return "commitNotPermitted";
        case core::transactions::ROLLBACK_NOT_PERMITTED:
            return "rollbackNotPermitted";
        case core::transactions::TRANSACTION_ALREADY_ABORTED:
            return "transactionAlreadyAborted";
        case core::transactions::TRANSACTION_ALREADY_COMMITTED:
            return "transactionAlreadyCommitted";
    }
    return "unexpectedCause";
}

static transactions_error_context
build_error_context(const core::transactions::transaction_operation_failed& ctx)
{
    transactions_error_context out;
    out.should_not_retry = !ctx.should_retry();
    out.should_not_rollback = !ctx.should_rollback();
    out.cause = external_exception_to_string(ctx.cause());
    return out;
}

static std::string
failure_type_to_string(core::transactions::failure_type failure)
{
    switch (failure) {
        case core::transactions::failure_type::FAIL:
            return "fail";
        case core::transactions::failure_type::EXPIRY:
            return "expiry";
        case core::transactions::failure_type::COMMIT_AMBIGUOUS:
            return "commit_ambiguous";
    }
    return "unknown";
}

static transactions_error_context
build_error_context(const core::transactions::transaction_exception& e)
{
    transactions_error_context out;
    out.type = failure_type_to_string(e.type());
    out.cause = external_exception_to_string(e.cause());
    transactions_error_context::transaction_result res;
    auto [_, core_res] = e.get_transaction_result();
    res.transaction_id = core_res.transaction_id;
    res.unstaging_complete = core_res.unstaging_complete;
    out.result = res;
    return out;
}

static std::error_code
failure_type_to_error_code(core::transactions::failure_type failure)
{
    switch (failure) {
        case core::transactions::failure_type::FAIL:
            return transactions_errc::failed;
        case core::transactions::failure_type::EXPIRY:
            return transactions_errc::expired;
        case core::transactions::failure_type::COMMIT_AMBIGUOUS:
            return transactions_errc::commit_ambiguous;
    }
    return transactions_errc::unexpected_exception;
}

class transaction_context_resource::impl : public std::enable_shared_from_this<transaction_context_resource::impl>
{
  public:
    impl(core::transactions::transactions& transactions, const transactions::transaction_options& configuration)
      : transaction_context_(transactions, configuration)
    {
    }

    impl(impl&& other) = delete;

    impl(const impl& other) = delete;

    const impl& operator=(impl&& other) = delete;

    const impl& operator=(const impl& other) = delete;

    [[nodiscard]] core_error_info new_attempt()
    {
        try {
            transaction_context_.new_attempt_context();
        } catch (const core::transactions::transaction_operation_failed& e) {
            return { transactions_errc::operation_failed,
                     ERROR_LOCATION,
                     fmt::format("unable to create new attempt context: {}, cause: {}", e.what(), external_exception_to_string(e.cause())),
                     build_error_context(e) };
        } catch (const std::exception& e) {
            return { transactions_errc::std_exception, ERROR_LOCATION, fmt::format("unable to create new attempt context: {}", e.what()) };
        } catch (...) {
            return { transactions_errc::unexpected_exception,
                     ERROR_LOCATION,
                     "unable to create new attempt context: unexpected C++ exception" };
        }
        return {};
    }

    [[nodiscard]] core_error_info rollback()
    {
        try {
            auto barrier = std::make_shared<std::promise<void>>();
            auto f = barrier->get_future();
            transaction_context_.rollback([barrier](std::exception_ptr e) {
                if (e) {
                    return barrier->set_exception(std::move(e));
                }
                return barrier->set_value();
            });
            f.get();
        } catch (const core::transactions::transaction_operation_failed& e) {
            return { transactions_errc::operation_failed,
                     ERROR_LOCATION,
                     fmt::format("unable to rollback transaction: {}, cause: {}", e.what(), external_exception_to_string(e.cause())),
                     build_error_context(e) };
        } catch (const std::exception& e) {
            return { transactions_errc::std_exception, ERROR_LOCATION, fmt::format("unable to rollback transaction: {}", e.what()) };
        } catch (...) {
            return { transactions_errc::unexpected_exception, ERROR_LOCATION, "unable to rollback transaction: unexpected C++ exception" };
        }
        return {};
    }

    [[nodiscard]] std::pair<std::optional<transactions::transaction_result>, core_error_info> commit()
    {
        try {
            auto barrier = std::make_shared<std::promise<std::optional<transactions::transaction_result>>>();
            auto f = barrier->get_future();
            transaction_context_.finalize(
              [barrier](std::optional<core::transactions::transaction_exception> e, std::optional<transactions::transaction_result> res) {
                  if (e) {
                      return barrier->set_exception(std::make_exception_ptr(e.value()));
                  }
                  return barrier->set_value(std::move(res));
              });
            return { f.get(), {} };
        } catch (const core::transactions::transaction_exception& e) {
            return { {},
                     { failure_type_to_error_code(e.type()),
                       ERROR_LOCATION,
                       fmt::format("unable to commit transaction: {}, cause: {}", e.what(), external_exception_to_string(e.cause())),
                       build_error_context(e) } };
        } catch (const std::exception& e) {
            return { {}, { transactions_errc::std_exception, ERROR_LOCATION, fmt::format("unable to commit transaction: {}", e.what()) } };
        } catch (...) {
            return {
                {}, { transactions_errc::unexpected_exception, ERROR_LOCATION, "unable to commit transaction: unexpected C++ exception" }
            };
        }
        return {};
    }

    [[nodiscard]] std::pair<std::optional<core::transactions::transaction_get_result>, core_error_info> get_optional(
      const core::document_id& id)
    {
        try {
            auto barrier = std::make_shared<std::promise<std::optional<core::transactions::transaction_get_result>>>();
            auto f = barrier->get_future();
            transaction_context_.get_optional(
              id, [barrier](std::exception_ptr e, std::optional<core::transactions::transaction_get_result> res) mutable {
                  if (e) {
                      return barrier->set_exception(std::move(e));
                  }
                  return barrier->set_value(std::move(res));
              });
            return { f.get(), {} };
        } catch (const core::transactions::transaction_operation_failed& e) {
            return { {},
                     { transactions_errc::operation_failed,
                       ERROR_LOCATION,
                       fmt::format(
                         "unable to get document: {}, cause: {}, id=\"{}\"", e.what(), external_exception_to_string(e.cause()), id),
                       build_error_context(e) } };
        } catch (const std::exception& e) {
            return {
                {}, { transactions_errc::std_exception, ERROR_LOCATION, fmt::format("unable to get document: {}, id=\"{}\"", e.what(), id) }
            };
        } catch (...) {
            return { {},
                     { transactions_errc::unexpected_exception,
                       ERROR_LOCATION,
                       fmt::format("unable to get document: unexpected C++ exception, id=\"{}\"", id) } };
        }
        return {};
    }

    [[nodiscard]] std::pair<std::optional<core::transactions::transaction_get_result>, core_error_info> insert(
      const core::document_id& id,
      const std::vector<std::byte>& content)
    {
        try {
            auto barrier = std::make_shared<std::promise<std::optional<core::transactions::transaction_get_result>>>();
            auto f = barrier->get_future();
            transaction_context_.insert(
              id, content, [barrier](std::exception_ptr e, std::optional<core::transactions::transaction_get_result> res) mutable {
                  if (e) {
                      return barrier->set_exception(std::move(e));
                  }
                  return barrier->set_value(std::move(res));
              });
            return { f.get(), {} };
        } catch (const core::transactions::transaction_operation_failed& e) {
            return { {},
                     { transactions_errc::operation_failed,
                       ERROR_LOCATION,
                       fmt::format(
                         "unable to insert document: {}, cause: {}, id=\"{}\"", e.what(), external_exception_to_string(e.cause()), id),
                       build_error_context(e) } };
        } catch (const std::exception& e) {
            return {
                {},
                { transactions_errc::std_exception, ERROR_LOCATION, fmt::format("unable to insert document: {}, id=\"{}\"", e.what(), id) }
            };
        } catch (...) {
            return { {},
                     { transactions_errc::unexpected_exception,
                       ERROR_LOCATION,
                       fmt::format("unable to insert document: unexpected C++ exception, id=\"{}\"", id) } };
        }
        return {};
    }

    [[nodiscard]] std::pair<std::optional<core::transactions::transaction_get_result>, core_error_info> replace(
      const core::transactions::transaction_get_result& document,
      const std::vector<std::byte>& content)
    {
        try {
            auto barrier = std::make_shared<std::promise<std::optional<core::transactions::transaction_get_result>>>();
            auto f = barrier->get_future();
            transaction_context_.replace(
              document, content, [barrier](std::exception_ptr e, std::optional<core::transactions::transaction_get_result> res) mutable {
                  if (e) {
                      return barrier->set_exception(std::move(e));
                  }
                  return barrier->set_value(std::move(res));
              });
            return { f.get(), {} };
        } catch (const core::transactions::transaction_operation_failed& e) {
            return { {},
                     { transactions_errc::operation_failed,
                       ERROR_LOCATION,
                       fmt::format("unable to replace document: {}, cause: {}, id=\"{}\"",
                                   e.what(),
                                   external_exception_to_string(e.cause()),
                                   document.key()),
                       build_error_context(e) } };
        } catch (const std::exception& e) {
            return { {},
                     { transactions_errc::std_exception,
                       ERROR_LOCATION,
                       fmt::format("unable to replace document: {}, id=\"{}\"", e.what(), document.key()) } };
        } catch (...) {
            return { {},
                     { transactions_errc::unexpected_exception,
                       ERROR_LOCATION,
                       fmt::format("unable to replace document: unexpected C++ exception, id=\"{}\"", document.key()) } };
        }
        return {};
    }

    [[nodiscard]] core_error_info remove(const core::transactions::transaction_get_result& document)
    {
        try {
            auto barrier = std::make_shared<std::promise<void>>();
            auto f = barrier->get_future();
            transaction_context_.remove(document, [barrier](std::exception_ptr e) {
                if (e) {
                    return barrier->set_exception(std::move(e));
                }
                return barrier->set_value();
            });
            f.get();
        } catch (const core::transactions::transaction_operation_failed& e) {
            return { transactions_errc::operation_failed,
                     ERROR_LOCATION,
                     fmt::format("unable to remove document: {}, cause: {}, id=\"{}\"",
                                 e.what(),
                                 external_exception_to_string(e.cause()),
                                 document.key()),
                     build_error_context(e) };
        } catch (const std::exception& e) {
            return { transactions_errc::std_exception,
                     ERROR_LOCATION,
                     fmt::format("unable to remove document: {}, id=\"{}\"", e.what(), document.key()) };
        } catch (...) {
            return { transactions_errc::unexpected_exception,
                     ERROR_LOCATION,
                     fmt::format("unable to remove document: unexpected C++ exception, id=\"{}\"", document.key()) };
        }
        return {};
    }

    [[nodiscard]] std::pair<std::optional<core::operations::query_response>, core_error_info> query(
      const std::string& statement,
      const transactions::transaction_query_options& options)
    {
        try {
            auto barrier = std::make_shared<std::promise<std::optional<core::operations::query_response>>>();
            auto f = barrier->get_future();
            transaction_context_.query(
              statement, options, [barrier](std::exception_ptr e, std::optional<core::operations::query_response> res) {
                  if (e) {
                      return barrier->set_exception(std::move(e));
                  }
                  return barrier->set_value(std::move(res));
              });
            return { f.get(), {} };
        } catch (const core::transactions::transaction_operation_failed& e) {
            return { {},
                     { transactions_errc::operation_failed,
                       ERROR_LOCATION,
                       fmt::format("unable to execute query: {}, cause: {}", e.what(), external_exception_to_string(e.cause())),
                       build_error_context(e) } };
        } catch (const std::exception& e) {
            return { {}, { transactions_errc::std_exception, ERROR_LOCATION, fmt::format("unable to execute query: {}", e.what()) } };
        } catch (...) {
            return { {}, { transactions_errc::unexpected_exception, ERROR_LOCATION, "unable to execute query: unexpected C++ exception" } };
        }
        return { {}, {} };
    }

  private:
    core::transactions::transaction_context transaction_context_;
};

COUCHBASE_API
transaction_context_resource::transaction_context_resource(transactions_resource* transactions,
                                                           const transactions::transaction_options& configuration)
  : impl_{ std::make_shared<transaction_context_resource::impl>(transactions->transactions(), configuration) }
{
}

COUCHBASE_API
core_error_info
transaction_context_resource::new_attempt()
{
    return impl_->new_attempt();
}

COUCHBASE_API
core_error_info
transaction_context_resource::commit(zval* return_value)
{
    ZVAL_NULL(return_value);

    auto [resp, err] = impl_->commit();
    if (err.ec) {
        return err;
    }
    if (resp) {
        array_init(return_value);
        add_assoc_stringl(return_value, "transactionId", resp->transaction_id.data(), resp->transaction_id.size());
        add_assoc_bool(return_value, "unstagingComplete", resp->unstaging_complete);
    }
    return {};
}

COUCHBASE_API
core_error_info
transaction_context_resource::rollback()
{
    return impl_->rollback();
}

static void
transaction_get_result_to_zval(zval* return_value, const core::transactions::transaction_get_result& res)
{
    array_init(return_value);
    add_assoc_stringl(return_value, "id", res.key().data(), res.key().size());
    add_assoc_stringl(return_value, "collectionName", res.collection().data(), res.collection().size());
    add_assoc_stringl(return_value, "scopeName", res.scope().data(), res.scope().size());
    add_assoc_stringl(return_value, "bucketName", res.bucket().data(), res.bucket().size());
    {
        auto cas = fmt::format("{:x}", res.cas().value());
        add_assoc_stringl(return_value, "cas", cas.data(), cas.size());
    }
    {
        const auto& value = res.content();
        add_assoc_stringl(return_value, "value", reinterpret_cast<const char*>(value.data()), value.size());
    }
    if (res.metadata()) {
        zval meta;
        array_init(&meta);
        if (res.metadata()->cas()) {
            add_assoc_stringl(&meta, "cas", res.metadata()->cas()->data(), res.metadata()->cas()->size());
        }
        if (res.metadata()->crc32()) {
            add_assoc_stringl(&meta, "crc32", res.metadata()->crc32()->data(), res.metadata()->crc32()->size());
        }
        if (res.metadata()->revid()) {
            add_assoc_stringl(&meta, "revid", res.metadata()->revid()->data(), res.metadata()->revid()->size());
        }
        if (res.metadata()->exptime()) {
            add_assoc_long(&meta, "exptime", res.metadata()->exptime().value());
        }
        add_assoc_zval(return_value, "metadata", &meta);
    }
    {
        zval links;
        array_init(&links);
        if (res.links().atr_id()) {
            add_assoc_stringl(&links, "atr_id", res.links().atr_id()->data(), res.links().atr_id()->size());
        }
        if (res.links().atr_bucket_name()) {
            add_assoc_stringl(&links, "atr_bucket_name", res.links().atr_bucket_name()->data(), res.links().atr_bucket_name()->size());
        }
        if (res.links().atr_scope_name()) {
            add_assoc_stringl(&links, "atr_scope_name", res.links().atr_scope_name()->data(), res.links().atr_scope_name()->size());
        }
        if (res.links().atr_collection_name()) {
            add_assoc_stringl(
              &links, "atr_collection_name", res.links().atr_collection_name()->data(), res.links().atr_collection_name()->size());
        }
        if (res.links().staged_transaction_id()) {
            add_assoc_stringl(
              &links, "staged_transaction_id", res.links().staged_transaction_id()->data(), res.links().staged_transaction_id()->size());
        }
        if (res.links().staged_attempt_id()) {
            add_assoc_stringl(
              &links, "staged_attempt_id", res.links().staged_attempt_id()->data(), res.links().staged_attempt_id()->size());
        }
        add_assoc_stringl(&links,
                          "staged_content",
                          reinterpret_cast<const char*>(res.links().staged_content().data()),
                          res.links().staged_content().size());
        if (res.links().cas_pre_txn()) {
            add_assoc_stringl(&links, "cas_pre_txn", res.links().cas_pre_txn()->data(), res.links().cas_pre_txn()->size());
        }
        if (res.links().exptime_pre_txn()) {
            add_assoc_long(&links, "exptime_pre_txn", res.links().exptime_pre_txn().value());
        }
        if (res.links().crc32_of_staging()) {
            add_assoc_stringl(&links, "crc32_of_staging", res.links().crc32_of_staging()->data(), res.links().crc32_of_staging()->size());
        }
        if (res.links().op()) {
            add_assoc_stringl(&links, "op", res.links().op()->data(), res.links().op()->size());
        }
        if (res.links().forward_compat()) {
            auto encoded = core::utils::json::generate(res.links().forward_compat().value());
            add_assoc_stringl(&links, "forward_compat", encoded.data(), encoded.size());
        }
        add_assoc_bool(&links, "is_deleted", res.links().is_deleted());
        add_assoc_zval(return_value, "links", &links);
    }
}

static couchbase::core::document_id
zval_to_document_id(const zval* document)
{
    std::string bucket;
    std::string scope;
    std::string collection;
    std::string id;
    cb_assign_string(bucket, document, "bucketName");
    cb_assign_string(scope, document, "scopeName");
    cb_assign_string(collection, document, "collectionName");
    cb_assign_string(id, document, "id");
    return { bucket, scope, collection, id };
}

static std::pair<core::transactions::transaction_links, core_error_info>
zval_to_links(const zval* document)
{
    const zval* links = zend_symtable_str_find(Z_ARRVAL_P(document), ZEND_STRL("links"));
    if (links == nullptr) {
        return { {}, {} };
    }
    if (Z_TYPE_P(links) != IS_ARRAY) {
        return { {}, { errc::common::invalid_argument, ERROR_LOCATION, "expected links to be an array in the document" } };
    }
    std::optional<std::string> atr_id;
    std::optional<std::string> atr_bucket_name;
    std::optional<std::string> atr_scope_name;
    std::optional<std::string> atr_collection_name;
    std::optional<std::string> staged_transaction_id;
    std::optional<std::string> staged_attempt_id;
    std::optional<std::string> staged_operation_id;
    std::optional<std::vector<std::byte>> staged_content;
    std::optional<std::string> cas_pre_txn;
    std::optional<std::string> revid_pre_txn;
    std::optional<uint32_t> exptime_pre_txn;
    std::optional<std::string> crc32_of_staging;
    std::optional<std::string> op;
    std::optional<std::string> forward_compat;
    bool is_deleted{ false };

    cb_assign_string(atr_id, links, "atr_id");
    cb_assign_string(atr_bucket_name, links, "atr_bucket_name");
    cb_assign_string(atr_scope_name, links, "atr_scope_name");
    cb_assign_string(atr_collection_name, links, "atr_collection_name");
    cb_assign_string(staged_transaction_id, links, "staged_transaction_id");
    cb_assign_string(staged_attempt_id, links, "staged_attempt_id");
    cb_assign_string(staged_operation_id, links, "staged_operation_id");
    cb_assign_binary(staged_content, links, "staged_content");
    cb_assign_string(cas_pre_txn, links, "cas_pre_txn");
    cb_assign_string(revid_pre_txn, links, "revid_pre_txn");
    cb_assign_string(crc32_of_staging, links, "crc32_of_staging");
    cb_assign_string(op, links, "op");
    cb_assign_integer(exptime_pre_txn, links, "exptime_pre_txn");
    cb_assign_string(forward_compat, links, "forward_compat");
    cb_assign_boolean(is_deleted, links, "is_deleted");

    std::optional<tao::json::value> forward_compat_json;
    if (forward_compat) {
        forward_compat_json = core::utils::json::parse(forward_compat.value());
    }

    return { core::transactions::transaction_links{ atr_id,
                                                    atr_bucket_name,
                                                    atr_scope_name,
                                                    atr_collection_name,
                                                    staged_transaction_id,
                                                    staged_attempt_id,
                                                    staged_operation_id,
                                                    staged_content,
                                                    cas_pre_txn,
                                                    revid_pre_txn,
                                                    exptime_pre_txn,
                                                    crc32_of_staging,
                                                    op,
                                                    forward_compat_json,
                                                    is_deleted },
             {} };
}

static std::pair<std::optional<core::transactions::document_metadata>, core_error_info>
zval_to_metadata(const zval* document)
{
    const zval* links = zend_symtable_str_find(Z_ARRVAL_P(document), ZEND_STRL("links"));
    if (links == nullptr || Z_TYPE_P(links) == IS_NULL) {
        return { {}, {} };
    }
    if (Z_TYPE_P(links) != IS_ARRAY) {
        return { {}, { errc::common::invalid_argument, ERROR_LOCATION, "expected metadata to be an array in the document" } };
    }
    std::optional<std::string> cas;
    std::optional<std::string> revid;
    std::optional<std::uint32_t> exptime;
    std::optional<std::string> crc32;

    cb_assign_string(cas, links, "cas");
    cb_assign_string(revid, links, "revid");
    cb_assign_string(crc32, links, "crc32");
    cb_assign_integer(exptime, links, "exptime");

    return { core::transactions::document_metadata{ cas, revid, exptime, crc32 }, {} };
}

static std::pair<core::transactions::transaction_get_result, core_error_info>
zval_to_transaction_get_result(const zval* document)
{
    if (document == nullptr || Z_TYPE_P(document) != IS_ARRAY) {
        return { {}, { errc::common::invalid_argument, ERROR_LOCATION, "expected array for transaction document" } };
    }

    couchbase::cas cas{};
    if (auto e = cb_assign_cas(cas, document); e.ec) {
        return { {}, e };
    }
    std::vector<std::byte> content;
    cb_assign_binary(content, document, "value");
    auto [links, e] = zval_to_links(document);
    if (e.ec) {
        return { {}, e };
    }
    auto [metadata, err] = zval_to_metadata(document);
    if (err.ec) {
        return { {}, err };
    }

    return { core::transactions::transaction_get_result(zval_to_document_id(document), content, cas.value(), links, metadata), {} };
}

COUCHBASE_API
core_error_info
transaction_context_resource::get(zval* return_value,
                                  const zend_string* bucket,
                                  const zend_string* scope,
                                  const zend_string* collection,
                                  const zend_string* id)
{
    couchbase::core::document_id doc_id{
        cb_string_new(bucket),
        cb_string_new(scope),
        cb_string_new(collection),
        cb_string_new(id),
    };

    auto [resp, err] = impl_->get_optional(doc_id);
    if (err.ec) {
        return err;
    }
    if (!resp) {
        return { errc::key_value::document_not_found, ERROR_LOCATION, fmt::format("unable to find document {} retrieve", doc_id) };
    }
    transaction_get_result_to_zval(return_value, resp.value());
    return {};
}

COUCHBASE_API
core_error_info
transaction_context_resource::insert(zval* return_value,
                                     const zend_string* bucket,
                                     const zend_string* scope,
                                     const zend_string* collection,
                                     const zend_string* id,
                                     const zend_string* value)
{
    couchbase::core::document_id doc_id{
        cb_string_new(bucket),
        cb_string_new(scope),
        cb_string_new(collection),
        cb_string_new(id),
    };

    auto [resp, err] = impl_->insert(doc_id, cb_binary_new(value));
    if (err.ec) {
        return err;
    }
    if (!resp) {
        return { errc::key_value::document_not_found, ERROR_LOCATION, fmt::format("unable to find document {} to insert", doc_id) };
    }
    transaction_get_result_to_zval(return_value, resp.value());
    return {};
}

COUCHBASE_API
core_error_info
transaction_context_resource::replace(zval* return_value, const zval* document, const zend_string* value)
{
    auto [doc, e] = zval_to_transaction_get_result(document);
    if (e.ec) {
        return e;
    }
    auto [resp, err] = impl_->replace(doc, cb_binary_new(value));
    if (err.ec) {
        return err;
    }
    if (!resp) {
        return { errc::key_value::document_not_found,
                 ERROR_LOCATION,
                 fmt::format("unable to find document {} to replace its content", doc.id()) };
    }
    transaction_get_result_to_zval(return_value, resp.value());
    return {};
}

COUCHBASE_API
core_error_info
transaction_context_resource::remove(const zval* document)
{
    auto [doc, e] = zval_to_transaction_get_result(document);
    if (e.ec) {
        return e;
    }
    if (auto err = impl_->remove(doc); err.ec) {
        return err;
    }
    return {};
}

COUCHBASE_API
core_error_info
transaction_context_resource::query(zval* return_value, const zend_string* statement, const zval* options)
{
    auto [query_options, e] = zval_to_transactions_query_options(options);
    if (e.ec) {
        return e;
    }
    auto [resp, err] = impl_->query(cb_string_new(statement), query_options);
    if (err.ec) {
        return err;
    }
    if (resp.has_value()) {
        query_response_to_zval(return_value, resp.value());
    }
    return {};
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

static core_error_info
apply_options(transactions::transaction_options& config, zval* options)
{
    if (options == nullptr || Z_TYPE_P(options) != IS_ARRAY) {
        return { errc::common::invalid_argument, ERROR_LOCATION, "expected array for per transaction configuration" };
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
    }
    ZEND_HASH_FOREACH_END();

    return {};
}

COUCHBASE_API
std::pair<zend_resource*, core_error_info>
create_transaction_context_resource(transactions_resource* connection, zval* options)
{
    transactions::transaction_options configuration{};
    if (auto e = apply_options(configuration, options); e.ec) {
        return { nullptr, e };
    }
    auto* handle = new transaction_context_resource(connection, configuration);
    return { zend_register_resource(handle, transaction_context_destructor_id_), {} };
}

COUCHBASE_API
void
destroy_transaction_context_resource(zend_resource* res)
{
    if (res->type == transaction_context_destructor_id_ && res->ptr != nullptr) {
        auto* handle = static_cast<transaction_context_resource*>(res->ptr);
        res->ptr = nullptr;
        delete handle;
    }
}

} // namespace couchbase::php
