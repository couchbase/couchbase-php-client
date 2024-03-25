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

#include "connection_handle.hxx"

#include <Zend/zend_API.h>

#include <chrono>
#include <memory>
#include <string>
#include <system_error>

namespace couchbase
{
namespace transactions
{
class transactions_config;
} // namespace transactions
namespace core::transactions
{
class transactions;
} // namespace core::transactions
} // namespace couchbase

namespace couchbase::php
{
class transactions_resource
{
  public:
    COUCHBASE_API
    transactions_resource(connection_handle* connection, const couchbase::transactions::transactions_config& configuration);

    COUCHBASE_API
    core::transactions::transactions& transactions();

    void notify_fork(fork_event event) const;

  private:
    class impl;

    std::shared_ptr<impl> impl_;
};

COUCHBASE_API std::pair<zend_resource*, core_error_info>
create_transactions_resource(connection_handle* connection, zval* options);

COUCHBASE_API void
destroy_transactions_resource(zend_resource* res);

COUCHBASE_API void
set_transactions_destructor_id(int id);

COUCHBASE_API int
get_transactions_destructor_id();
} // namespace couchbase::php
