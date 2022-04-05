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

#include "core_error_info.hxx"

#include <Zend/zend_API.h>

#include <chrono>
#include <memory>
#include <string>
#include <system_error>

namespace couchbase
{
struct origin;
} // namespace couchbase

namespace couchbase::php
{
class connection_handle
{
  public:
    explicit connection_handle(couchbase::origin origin, std::chrono::steady_clock::time_point idle_expiry);

    [[nodiscard]] zend_resource* resource_id() const
    {
        return id_;
    }

    [[nodiscard]] bool is_expired(std::chrono::steady_clock::time_point now) const
    {
        return idle_expiry_ < now;
    }

    [[nodiscard]] std::string cluster_version(const zend_string* name);

    [[nodiscard]] core_error_info open();

    [[nodiscard]] core_error_info bucket_open(const zend_string* name);

    [[nodiscard]] core_error_info bucket_close(const zend_string* name);

    [[nodiscard]] core_error_info document_upsert(zval* return_value,
                                                  const zend_string* bucket,
                                                  const zend_string* scope,
                                                  const zend_string* collection,
                                                  const zend_string* id,
                                                  const zend_string* value,
                                                  zend_long flags,
                                                  const zval* options);

    [[nodiscard]] core_error_info document_insert(zval* return_value,
                                                  const zend_string* bucket,
                                                  const zend_string* scope,
                                                  const zend_string* collection,
                                                  const zend_string* id,
                                                  const zend_string* value,
                                                  zend_long flags,
                                                  const zval* options);

    [[nodiscard]] core_error_info document_replace(zval* return_value,
                                                   const zend_string* bucket,
                                                   const zend_string* scope,
                                                   const zend_string* collection,
                                                   const zend_string* id,
                                                   const zend_string* value,
                                                   zend_long flags,
                                                   const zval* options);

    [[nodiscard]] core_error_info document_append(zval* return_value,
                                                  const zend_string* bucket,
                                                  const zend_string* scope,
                                                  const zend_string* collection,
                                                  const zend_string* id,
                                                  const zend_string* value,
                                                  const zval* options);

    [[nodiscard]] core_error_info document_prepend(zval* return_value,
                                                   const zend_string* bucket,
                                                   const zend_string* scope,
                                                   const zend_string* collection,
                                                   const zend_string* id,
                                                   const zend_string* value,
                                                   const zval* options);

    [[nodiscard]] core_error_info document_increment(zval* return_value,
                                                     const zend_string* bucket,
                                                     const zend_string* scope,
                                                     const zend_string* collection,
                                                     const zend_string* id,
                                                     const zval* options);

    [[nodiscard]] core_error_info document_decrement(zval* return_value,
                                                     const zend_string* bucket,
                                                     const zend_string* scope,
                                                     const zend_string* collection,
                                                     const zend_string* id,
                                                     const zval* options);

    [[nodiscard]] core_error_info document_get(zval* return_value,
                                               const zend_string* bucket,
                                               const zend_string* scope,
                                               const zend_string* collection,
                                               const zend_string* id,
                                               const zval* options);

    [[nodiscard]] core_error_info document_get_and_lock(zval* return_value,
                                                        const zend_string* bucket,
                                                        const zend_string* scope,
                                                        const zend_string* collection,
                                                        const zend_string* id,
                                                        zend_long lock_time,
                                                        const zval* options);

    [[nodiscard]] core_error_info document_get_and_touch(zval* return_value,
                                                         const zend_string* bucket,
                                                         const zend_string* scope,
                                                         const zend_string* collection,
                                                         const zend_string* id,
                                                         zend_long expiry,
                                                         const zval* options);

    [[nodiscard]] core_error_info document_unlock(zval* return_value,
                                                  const zend_string* bucket,
                                                  const zend_string* scope,
                                                  const zend_string* collection,
                                                  const zend_string* id,
                                                  const zend_string* cas,
                                                  const zval* options);

    [[nodiscard]] core_error_info document_remove(zval* return_value,
                                                  const zend_string* bucket,
                                                  const zend_string* scope,
                                                  const zend_string* collection,
                                                  const zend_string* id,
                                                  const zval* options);

    [[nodiscard]] core_error_info document_touch(zval* return_value,
                                                 const zend_string* bucket,
                                                 const zend_string* scope,
                                                 const zend_string* collection,
                                                 const zend_string* id,
                                                 zend_long expiry,
                                                 const zval* options);

    [[nodiscard]] core_error_info document_exists(zval* return_value,
                                                  const zend_string* bucket,
                                                  const zend_string* scope,
                                                  const zend_string* collection,
                                                  const zend_string* id,
                                                  const zval* options);

    [[nodiscard]] core_error_info document_mutate_in(zval* return_value,
                                                     const zend_string* bucket,
                                                     const zend_string* scope,
                                                     const zend_string* collection,
                                                     const zend_string* id,
                                                     const zval* specs,
                                                     const zval* options);

    [[nodiscard]] core_error_info document_lookup_in(zval* return_value,
                                                     const zend_string* bucket,
                                                     const zend_string* scope,
                                                     const zend_string* collection,
                                                     const zend_string* id,
                                                     const zval* specs,
                                                     const zval* options);

    [[nodiscard]] core_error_info document_get_multi(zval* return_value,
                                                     const zend_string* bucket,
                                                     const zend_string* scope,
                                                     const zend_string* collection,
                                                     const zval* ids,
                                                     const zval* options);

    [[nodiscard]] core_error_info document_remove_multi(zval* return_value,
                                                        const zend_string* bucket,
                                                        const zend_string* scope,
                                                        const zend_string* collection,
                                                        const zval* entries,
                                                        const zval* options);

    [[nodiscard]] core_error_info document_upsert_multi(zval* return_value,
                                                        const zend_string* bucket,
                                                        const zend_string* scope,
                                                        const zend_string* collection,
                                                        const zval* entries,
                                                        const zval* options);

    [[nodiscard]] std::pair<zval*, core_error_info> query(const zend_string* statement, const zval* options);

    [[nodiscard]] std::pair<zval*, core_error_info> analytics_query(const zend_string* statement, const zval* options);

    [[nodiscard]] std::pair<zval*, core_error_info> search_query(const zend_string* index_name,
                                                                 const zend_string* query,
                                                                 const zval* options);

    [[nodiscard]] std::pair<zval*, core_error_info> view_query(const zend_string* bucket_name,
                                                               const zend_string* design_document_name,
                                                               const zend_string* view_name,
                                                               zend_long name_space,
                                                               const zval* options);

    [[nodiscard]] std::pair<zval*, core_error_info> search_index_upsert(const zval* index, const zval* options);

    [[nodiscard]] std::pair<zval*, core_error_info> view_index_upsert(const zend_string* bucket_name,
                                                                      const zval* design_document,
                                                                      zend_long name_space,
                                                                      const zval* options);

  private:
    class impl;

    std::chrono::steady_clock::time_point idle_expiry_; /* time when the connection will be considered as expired */
    zend_resource* id_;

    std::shared_ptr<impl> impl_;
};

std::pair<connection_handle*, core_error_info>
create_connection_handle(const zend_string* connection_string, zval* options, std::chrono::steady_clock::time_point idle_expiry);
} // namespace couchbase::php
