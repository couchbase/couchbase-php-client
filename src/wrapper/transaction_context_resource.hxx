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

#include <chrono>
#include <memory>
#include <string>
#include <system_error>

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
    transaction_context_resource(transactions_resource* transactions, const couchbase::transactions::transaction_options& configuration);

    COUCHBASE_API
    core_error_info new_attempt();
    COUCHBASE_API
    core_error_info commit(zval* return_value);
    COUCHBASE_API
    core_error_info rollback();

    COUCHBASE_API
    core_error_info get(zval* return_value,
                        const zend_string* bucket,
                        const zend_string* scope,
                        const zend_string* collection,
                        const zend_string* id);

    COUCHBASE_API
    core_error_info insert(zval* return_value,
                           const zend_string* bucket,
                           const zend_string* scope,
                           const zend_string* collection,
                           const zend_string* id,
                           const zend_string* value);

    COUCHBASE_API
    core_error_info replace(zval* return_value, const zval* document, const zend_string* value);
    COUCHBASE_API
    core_error_info remove(const zval* document);

    COUCHBASE_API
    core_error_info query(zval* return_value, const zend_string* statement, const zval* options);

  private:
    class impl;

    std::shared_ptr<impl> impl_;
};

COUCHBASE_API std::pair<zend_resource*, core_error_info>
create_transaction_context_resource(transactions_resource* transactions, zval* options);

COUCHBASE_API void
destroy_transaction_context_resource(zend_resource* res);

COUCHBASE_API void
set_transaction_context_destructor_id(int id);

COUCHBASE_API int
get_transaction_context_destructor_id();

} // namespace couchbase::php
