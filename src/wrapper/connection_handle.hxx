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

#include "api_visibility.hxx"

#include "core_error_info.hxx"

#include <Zend/zend_API.h>

#include <couchbase/fork_event.hxx>

#include <chrono>
#include <memory>
#include <string>

namespace couchbase
{
class cluster_options;
namespace core
{
class cluster;
} // namespace core
} // namespace couchbase

namespace couchbase::php
{
class connection_handle
{
public:
  COUCHBASE_API
  connection_handle(std::string connection_string,
                    std::string connection_hash,
                    couchbase::cluster_options cluster_options,
                    std::chrono::system_clock::time_point idle_expiry);

  COUCHBASE_API
  connection_handle(const connection_handle&) = delete;
  COUCHBASE_API
  connection_handle(const connection_handle&&) = delete;
  COUCHBASE_API
  auto operator=(const connection_handle&) -> connection_handle& = delete;
  COUCHBASE_API
  auto operator=(const connection_handle&&) -> connection_handle& = delete;

  COUCHBASE_API
  ~connection_handle();

  COUCHBASE_API
  auto cluster() const -> couchbase::core::cluster;

  COUCHBASE_API
  auto is_expired(std::chrono::system_clock::time_point now) const -> bool;

  [[nodiscard]] auto expires_at() const -> const std::chrono::system_clock::time_point&
  {
    return idle_expiry_;
  }

  void expires_at(const std::chrono::system_clock::time_point& at)
  {
    idle_expiry_ = at;
  }

  COUCHBASE_API
  auto connection_string() const -> const std::string&
  {
    return connection_string_;
  }

  COUCHBASE_API
  auto connection_hash() const -> const std::string&
  {
    return connection_hash_;
  }

  COUCHBASE_API
  auto cluster_version(const zend_string* name) -> std::string;

  COUCHBASE_API
  auto replicas_configured_for_bucket(const zend_string* bucket_name) -> bool;

  COUCHBASE_API
  void notify_fork(fork_event event) const;

  COUCHBASE_API
  auto open() -> core_error_info;

  COUCHBASE_API
  auto bucket_open(const std::string& name) -> core_error_info;

  COUCHBASE_API
  auto bucket_open(const zend_string* name) -> core_error_info;

  COUCHBASE_API
  auto bucket_close(const zend_string* name) -> core_error_info;

  COUCHBASE_API
  auto document_upsert(zval* return_value,
                       const zend_string* bucket,
                       const zend_string* scope,
                       const zend_string* collection,
                       const zend_string* id,
                       const zend_string* value,
                       zend_long flags,
                       const zval* options) -> core_error_info;

  COUCHBASE_API
  auto document_insert(zval* return_value,
                       const zend_string* bucket,
                       const zend_string* scope,
                       const zend_string* collection,
                       const zend_string* id,
                       const zend_string* value,
                       zend_long flags,
                       const zval* options) -> core_error_info;

  COUCHBASE_API
  auto document_replace(zval* return_value,
                        const zend_string* bucket,
                        const zend_string* scope,
                        const zend_string* collection,
                        const zend_string* id,
                        const zend_string* value,
                        zend_long flags,
                        const zval* options) -> core_error_info;

  COUCHBASE_API
  auto document_append(zval* return_value,
                       const zend_string* bucket,
                       const zend_string* scope,
                       const zend_string* collection,
                       const zend_string* id,
                       const zend_string* value,
                       const zval* options) -> core_error_info;

  COUCHBASE_API
  auto document_prepend(zval* return_value,
                        const zend_string* bucket,
                        const zend_string* scope,
                        const zend_string* collection,
                        const zend_string* id,
                        const zend_string* value,
                        const zval* options) -> core_error_info;

  COUCHBASE_API
  auto document_increment(zval* return_value,
                          const zend_string* bucket,
                          const zend_string* scope,
                          const zend_string* collection,
                          const zend_string* id,
                          const zval* options) -> core_error_info;

  COUCHBASE_API
  auto document_decrement(zval* return_value,
                          const zend_string* bucket,
                          const zend_string* scope,
                          const zend_string* collection,
                          const zend_string* id,
                          const zval* options) -> core_error_info;

  COUCHBASE_API
  auto document_get(zval* return_value,
                    const zend_string* bucket,
                    const zend_string* scope,
                    const zend_string* collection,
                    const zend_string* id,
                    const zval* options) -> core_error_info;

  COUCHBASE_API
  auto document_get_any_replica(zval* return_value,
                                const zend_string* bucket,
                                const zend_string* scope,
                                const zend_string* collection,
                                const zend_string* id,
                                const zval* options) -> core_error_info;

  COUCHBASE_API
  auto document_get_all_replicas(zval* return_value,
                                 const zend_string* bucket,
                                 const zend_string* scope,
                                 const zend_string* collection,
                                 const zend_string* id,
                                 const zval* options) -> core_error_info;

  COUCHBASE_API
  auto document_get_and_lock(zval* return_value,
                             const zend_string* bucket,
                             const zend_string* scope,
                             const zend_string* collection,
                             const zend_string* id,
                             zend_long lock_time,
                             const zval* options) -> core_error_info;

  COUCHBASE_API
  auto document_get_and_touch(zval* return_value,
                              const zend_string* bucket,
                              const zend_string* scope,
                              const zend_string* collection,
                              const zend_string* id,
                              zend_long expiry,
                              const zval* options) -> core_error_info;

  COUCHBASE_API
  auto document_unlock(zval* return_value,
                       const zend_string* bucket,
                       const zend_string* scope,
                       const zend_string* collection,
                       const zend_string* id,
                       const zend_string* cas,
                       const zval* options) -> core_error_info;

  COUCHBASE_API
  auto document_remove(zval* return_value,
                       const zend_string* bucket,
                       const zend_string* scope,
                       const zend_string* collection,
                       const zend_string* id,
                       const zval* options) -> core_error_info;

  COUCHBASE_API
  auto document_touch(zval* return_value,
                      const zend_string* bucket,
                      const zend_string* scope,
                      const zend_string* collection,
                      const zend_string* id,
                      zend_long expiry,
                      const zval* options) -> core_error_info;

  COUCHBASE_API
  auto document_exists(zval* return_value,
                       const zend_string* bucket,
                       const zend_string* scope,
                       const zend_string* collection,
                       const zend_string* id,
                       const zval* options) -> core_error_info;

  COUCHBASE_API
  auto document_mutate_in(zval* return_value,
                          const zend_string* bucket,
                          const zend_string* scope,
                          const zend_string* collection,
                          const zend_string* id,
                          const zval* specs,
                          const zval* options) -> core_error_info;

  COUCHBASE_API
  auto document_lookup_in(zval* return_value,
                          const zend_string* bucket,
                          const zend_string* scope,
                          const zend_string* collection,
                          const zend_string* id,
                          const zval* specs,
                          const zval* options) -> core_error_info;

  COUCHBASE_API
  auto document_lookup_in_any_replica(zval* return_value,
                                      const zend_string* bucket,
                                      const zend_string* scope,
                                      const zend_string* collection,
                                      const zend_string* id,
                                      const zval* specs,
                                      const zval* options) -> core_error_info;

  COUCHBASE_API
  auto document_lookup_in_all_replicas(zval* return_value,
                                       const zend_string* bucket,
                                       const zend_string* scope,
                                       const zend_string* collection,
                                       const zend_string* id,
                                       const zval* specs,
                                       const zval* options) -> core_error_info;

  COUCHBASE_API
  auto document_get_multi(zval* return_value,
                          const zend_string* bucket,
                          const zend_string* scope,
                          const zend_string* collection,
                          const zval* ids,
                          const zval* options) -> core_error_info;

  COUCHBASE_API
  auto document_remove_multi(zval* return_value,
                             const zend_string* bucket,
                             const zend_string* scope,
                             const zend_string* collection,
                             const zval* entries,
                             const zval* options) -> core_error_info;

  COUCHBASE_API
  auto document_upsert_multi(zval* return_value,
                             const zend_string* bucket,
                             const zend_string* scope,
                             const zend_string* collection,
                             const zval* entries,
                             const zval* options) -> core_error_info;

  COUCHBASE_API
  auto query(zval* return_value, const zend_string* statement, const zval* options)
    -> core_error_info;

  COUCHBASE_API
  auto analytics_query(zval* return_value, const zend_string* statement, const zval* options)
    -> core_error_info;

  COUCHBASE_API
  auto search(zval* return_value,
              const zend_string* index_name,
              const zend_string* query,
              const zval* options,
              const zend_string* vector_search,
              const zval* vector_options) -> core_error_info;

  COUCHBASE_API
  auto search_query(zval* return_value,
                    const zend_string* index_name,
                    const zend_string* query,
                    const zval* options) -> core_error_info;

  COUCHBASE_API
  auto view_query(zval* return_value,
                  const zend_string* bucket_name,
                  const zend_string* design_document_name,
                  const zend_string* view_name,
                  zend_long name_space,
                  const zval* options) -> core_error_info;

  COUCHBASE_API
  auto ping(zval* return_value, const zval* options) -> core_error_info;

  COUCHBASE_API
  auto diagnostics(zval* return_value, const zend_string* report_id, const zval* options)
    -> core_error_info;

  COUCHBASE_API
  auto search_index_get(zval* return_value, const zend_string* index_name, const zval* options)
    -> core_error_info;

  COUCHBASE_API
  auto search_index_get_all(zval* return_value, const zval* options) -> core_error_info;

  COUCHBASE_API
  auto search_index_upsert(zval* return_value, const zval* index, const zval* options)
    -> core_error_info;

  COUCHBASE_API
  auto search_index_drop(zval* return_value, const zend_string* index_name, const zval* options)
    -> core_error_info;

  COUCHBASE_API
  auto search_index_get_documents_count(zval* return_value,
                                        const zend_string* index_name,
                                        const zval* options) -> core_error_info;

  COUCHBASE_API
  auto search_index_control_ingest(zval* return_value,
                                   const zend_string* index_name,
                                   bool pause,
                                   const zval* options) -> core_error_info;

  COUCHBASE_API
  auto search_index_control_query(zval* return_value,
                                  const zend_string* index_name,
                                  bool allow,
                                  const zval* options) -> core_error_info;

  COUCHBASE_API
  auto search_index_control_plan_freeze(zval* return_value,
                                        const zend_string* index_name,
                                        bool freeze,
                                        const zval* options) -> core_error_info;

  COUCHBASE_API
  auto search_index_analyze_document(zval* return_value,
                                     const zend_string* index_name,
                                     const zend_string* document,
                                     const zval* options) -> core_error_info;

  COUCHBASE_API
  auto scope_search_index_get(zval* return_value,
                              const zend_string* bucket_name,
                              const zend_string* scope_name,
                              const zend_string* index_name,
                              const zval* options) -> core_error_info;

  COUCHBASE_API
  auto scope_search_index_get_all(zval* return_value,
                                  const zend_string* bucket_name,
                                  const zend_string* scope_name,
                                  const zval* options) -> core_error_info;

  COUCHBASE_API
  auto scope_search_index_upsert(zval* return_value,
                                 const zend_string* bucket_name,
                                 const zend_string* scope_name,
                                 const zval* index,
                                 const zval* options) -> core_error_info;

  COUCHBASE_API
  auto scope_search_index_drop(zval* return_value,
                               const zend_string* bucket_name,
                               const zend_string* scope_name,
                               const zend_string* index_name,
                               const zval* options) -> core_error_info;

  COUCHBASE_API
  auto scope_search_index_get_documents_count(zval* return_value,
                                              const zend_string* bucket_name,
                                              const zend_string* scope_name,
                                              const zend_string* index_name,
                                              const zval* options) -> core_error_info;

  COUCHBASE_API
  auto scope_search_index_control_ingest(zval* return_value,
                                         const zend_string* bucket_name,
                                         const zend_string* scope_name,
                                         const zend_string* index_name,
                                         bool pause,
                                         const zval* options) -> core_error_info;

  COUCHBASE_API
  auto scope_search_index_control_query(zval* return_value,
                                        const zend_string* bucket_name,
                                        const zend_string* scope_name,
                                        const zend_string* index_name,
                                        bool allow,
                                        const zval* options) -> core_error_info;

  COUCHBASE_API
  auto scope_search_index_control_plan_freeze(zval* return_value,
                                              const zend_string* bucket_name,
                                              const zend_string* scope_name,
                                              const zend_string* index_name,
                                              bool freeze,
                                              const zval* options) -> core_error_info;

  COUCHBASE_API
  auto scope_search_index_analyze_document(zval* return_value,
                                           const zend_string* bucket_name,
                                           const zend_string* scope_name,
                                           const zend_string* index_name,
                                           const zend_string* document,
                                           const zval* options) -> core_error_info;

  COUCHBASE_API
  auto view_index_upsert(zval* return_value,
                         const zend_string* bucket_name,
                         const zval* design_document,
                         zend_long name_space,
                         const zval* options) -> core_error_info;

  COUCHBASE_API
  auto bucket_create(zval* return_value, const zval* bucket_settings, const zval* options)
    -> core_error_info;

  COUCHBASE_API
  auto bucket_update(zval* return_value, const zval* bucket_settings, const zval* options)
    -> core_error_info;

  COUCHBASE_API
  auto bucket_get(zval* return_value, const zend_string* name, const zval* options)
    -> core_error_info;

  COUCHBASE_API
  auto bucket_get_all(zval* return_value, const zval* options) -> core_error_info;

  COUCHBASE_API
  auto bucket_drop(zval* return_value, const zend_string* name, const zval* options)
    -> core_error_info;

  COUCHBASE_API
  auto bucket_flush(zval* return_value, const zend_string* name, const zval* options)
    -> core_error_info;

  COUCHBASE_API
  auto scope_get_all(zval* return_value, const zend_string* name, const zval* options)
    -> core_error_info;

  COUCHBASE_API
  auto scope_create(zval* return_value,
                    const zend_string* bucket_name,
                    const zend_string* scope_name,
                    const zval* options) -> core_error_info;

  COUCHBASE_API
  auto scope_drop(zval* return_value,
                  const zend_string* bucket_name,
                  const zend_string* scope_name,
                  const zval* options) -> core_error_info;

  COUCHBASE_API
  auto collection_create(zval* return_value,
                         const zend_string* bucket_name,
                         const zend_string* scope_name,
                         const zend_string* collection_name,
                         const zval* settings,
                         const zval* options) -> core_error_info;

  COUCHBASE_API
  auto collection_drop(zval* return_value,
                       const zend_string* bucket_name,
                       const zend_string* scope_name,
                       const zend_string* collection_name,
                       const zval* options) -> core_error_info;

  COUCHBASE_API
  auto collection_update(zval* return_value,
                         const zend_string* bucket_name,
                         const zend_string* scope_name,
                         const zend_string* collection_name,
                         const zval* settings,
                         const zval* options) -> core_error_info;

  COUCHBASE_API
  auto user_upsert(zval* return_value, const zval* user, const zval* options) -> core_error_info;

  COUCHBASE_API
  auto user_get(zval* return_value, const zend_string* name, const zval* options)
    -> core_error_info;

  COUCHBASE_API
  auto user_get_all(zval* return_value, const zval* options) -> core_error_info;

  COUCHBASE_API
  auto user_drop(zval* return_value, const zend_string* name, const zval* options)
    -> core_error_info;

  COUCHBASE_API
  auto change_password(zval* return_value, const zend_string* new_password, const zval* options)
    -> core_error_info;

  COUCHBASE_API
  auto group_upsert(zval* return_value, const zval* group, const zval* options) -> core_error_info;

  COUCHBASE_API
  auto group_get(zval* return_value, const zend_string* name, const zval* options)
    -> core_error_info;

  COUCHBASE_API
  auto group_get_all(zval* return_value, const zval* options) -> core_error_info;

  COUCHBASE_API
  auto group_drop(zval* return_value, const zend_string* name, const zval* options)
    -> core_error_info;

  COUCHBASE_API
  auto role_get_all(zval* return_value, const zval* options) -> core_error_info;

  COUCHBASE_API
  auto query_index_get_all(zval* return_value, const zend_string* bucket_name, const zval* options)
    -> core_error_info;

  COUCHBASE_API
  auto query_index_create(const zend_string* bucket_name,
                          const zend_string* index_name,
                          const zval* keys,
                          const zval* options) -> core_error_info;

  COUCHBASE_API
  auto query_index_create_primary(const zend_string* bucket_name, const zval* options)
    -> core_error_info;

  COUCHBASE_API
  auto query_index_drop(const zend_string* bucket_name,
                        const zend_string* index_name,
                        const zval* options) -> core_error_info;

  COUCHBASE_API
  auto query_index_drop_primary(const zend_string* bucket_name, const zval* options)
    -> core_error_info;

  COUCHBASE_API
  auto query_index_build_deferred(zval* return_value,
                                  const zend_string* bucket_name,
                                  const zval* options) -> core_error_info;

  COUCHBASE_API
  auto collection_query_index_get_all(zval* return_value,
                                      const zend_string* bucket_name,
                                      const zend_string* scope_name,
                                      const zend_string* collection_name,
                                      const zval* options) -> core_error_info;

  COUCHBASE_API
  auto collection_query_index_create(const zend_string* bucket_name,
                                     const zend_string* scope_name,
                                     const zend_string* collection_name,
                                     const zend_string* index_name,
                                     const zval* keys,
                                     const zval* options) -> core_error_info;

  COUCHBASE_API
  auto collection_query_index_create_primary(const zend_string* bucket_name,
                                             const zend_string* scope_name,
                                             const zend_string* collection_name,
                                             const zval* options) -> core_error_info;

  COUCHBASE_API
  auto collection_query_index_drop(const zend_string* bucket_name,
                                   const zend_string* scope_name,
                                   const zend_string* collection_name,
                                   const zend_string* index_name,
                                   const zval* options) -> core_error_info;

  COUCHBASE_API
  auto collection_query_index_drop_primary(const zend_string* bucket_name,
                                           const zend_string* scope_name,
                                           const zend_string* collection_name,
                                           const zval* options) -> core_error_info;

  COUCHBASE_API
  auto collection_query_index_build_deferred(zval* return_value,
                                             const zend_string* bucket_name,
                                             const zend_string* scope_name,
                                             const zend_string* collection_name,
                                             const zval* options) -> core_error_info;

  COUCHBASE_API
  auto analytics_create_dataverse(zval* return_value,
                                  const zend_string* dataverse_name,
                                  const zval* options) -> core_error_info;

  COUCHBASE_API
  auto analytics_drop_dataverse(zval* return_value,
                                const zend_string* dataverse_name,
                                const zval* options) -> core_error_info;

  COUCHBASE_API
  auto analytics_create_dataset(zval* return_value,
                                const zend_string* dataset_name,
                                const zend_string* bucket_name,
                                const zval* options) -> core_error_info;

  COUCHBASE_API
  auto analytics_drop_dataset(zval* return_value,
                              const zend_string* dataset_name,
                              const zval* options) -> core_error_info;

  COUCHBASE_API
  auto analytics_get_all_datasets(zval* return_value, const zval* options) -> core_error_info;

  COUCHBASE_API
  auto analytics_create_index(zval* return_value,
                              const zend_string* dataset_name,
                              const zend_string* index_name,
                              const zval* fields,
                              const zval* options) -> core_error_info;

  COUCHBASE_API
  auto analytics_drop_index(zval* return_value,
                            const zend_string* dataset_name,
                            const zend_string* index_name,
                            const zval* options) -> core_error_info;

  COUCHBASE_API
  auto analytics_get_all_indexes(zval* return_value, const zval* options) -> core_error_info;

  COUCHBASE_API
  auto analytics_connect_link(zval* return_value, const zval* options) -> core_error_info;

  COUCHBASE_API
  auto analytics_disconnect_link(zval* return_value, const zval* options) -> core_error_info;

  COUCHBASE_API
  auto analytics_get_pending_mutations(zval* return_value, const zval* options) -> core_error_info;

  COUCHBASE_API
  auto analytics_create_link(zval* return_value, const zval* analytics_link, const zval* options)
    -> core_error_info;

  COUCHBASE_API
  auto analytics_replace_link(zval* return_value, const zval* analytics_link, const zval* options)
    -> core_error_info;

  COUCHBASE_API
  auto analytics_drop_link(zval* return_value,
                           const zend_string* link_name,
                           const zend_string* dataverse_name,
                           const zval* options) -> core_error_info;

  COUCHBASE_API
  auto analytics_get_all_links(zval* return_value, const zval* options) -> core_error_info;

private:
  class impl;

  std::chrono::system_clock::time_point
    idle_expiry_; /* time when the connection will be considered as expired */

  std::string connection_string_;
  std::string connection_hash_;

  std::shared_ptr<impl> impl_;
};

COUCHBASE_API
auto
create_connection_handle(const zend_string* connection_string,
                         const zend_string* connection_hash,
                         zval* options,
                         std::chrono::system_clock::time_point idle_expiry)
  -> std::pair<connection_handle*, core_error_info>;
} // namespace couchbase::php
