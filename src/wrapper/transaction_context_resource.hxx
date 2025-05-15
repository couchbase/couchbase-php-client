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

#pragma once

#include "api_visibility.hxx"

#include "transactions_resource.hxx"

#include <Zend/zend_API.h>

#include <memory>

namespace couchbase::transactions
{
class transaction_options;
}

namespace couchbase::php
{
class transaction_context_resource
{
public:
  COUCHBASE_API
  transaction_context_resource(transactions_resource* transactions,
                               const couchbase::transactions::transaction_options& configuration);

  COUCHBASE_API
  auto new_attempt() -> core_error_info;
  COUCHBASE_API
  auto commit(zval* return_value) -> core_error_info;
  COUCHBASE_API
  auto rollback() -> core_error_info;

  COUCHBASE_API
  auto get(zval* return_value,
           const zend_string* bucket,
           const zend_string* scope,
           const zend_string* collection,
           const zend_string* id) -> core_error_info;

  COUCHBASE_API
  auto get_replica_from_preferred_server_group(zval* return_value,
                                               const zend_string* bucket,
                                               const zend_string* scope,
                                               const zend_string* collection,
                                               const zend_string* id) -> core_error_info;

  COUCHBASE_API
  auto insert(zval* return_value,
              const zend_string* bucket,
              const zend_string* scope,
              const zend_string* collection,
              const zend_string* id,
              const zend_string* value,
              zend_long flags) -> core_error_info;

  COUCHBASE_API
  auto replace(zval* return_value, const zval* document, const zend_string* value, zend_long flags)
    -> core_error_info;
  COUCHBASE_API
  auto remove(const zval* document) -> core_error_info;

  COUCHBASE_API
  auto query(zval* return_value, const zend_string* statement, const zval* options)
    -> core_error_info;

private:
  class impl;

  std::shared_ptr<impl> impl_;
};

COUCHBASE_API auto
create_transaction_context_resource(transactions_resource* transactions, zval* options)
  -> std::pair<zend_resource*, core_error_info>;

COUCHBASE_API void
destroy_transaction_context_resource(zend_resource* res);

COUCHBASE_API void
set_transaction_context_destructor_id(int id);

COUCHBASE_API auto
get_transaction_context_destructor_id() -> int;

} // namespace couchbase::php
