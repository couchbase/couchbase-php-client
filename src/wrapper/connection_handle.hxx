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

#include <chrono>
#include <couchbase/fork_event.hxx>
#include <memory>
#include <string>
#include <system_error>

namespace couchbase::core
{
struct origin;
class cluster;
} // namespace couchbase::core

namespace couchbase::php
{
class connection_handle
{
  public:
    COUCHBASE_API
    connection_handle(std::string connection_string,
                      std::string connection_hash,
                      couchbase::core::origin origin,
                      std::chrono::system_clock::time_point idle_expiry);

    COUCHBASE_API
    ~connection_handle();

    COUCHBASE_API
    std::shared_ptr<couchbase::core::cluster> cluster() const;

    COUCHBASE_API
    bool is_expired(std::chrono::system_clock::time_point now) const;

    const std::chrono::system_clock::time_point& expires_at() const
    {
        return idle_expiry_;
    }

    void expires_at(const std::chrono::system_clock::time_point& at)
    {
        idle_expiry_ = at;
    }

    COUCHBASE_API
    const std::string& connection_string() const
    {
        return connection_string_;
    }

    COUCHBASE_API
    const std::string& connection_hash() const
    {
        return connection_hash_;
    }

    COUCHBASE_API
    std::string cluster_version(const zend_string* name);

    COUCHBASE_API
    bool replicas_configured_for_bucket(const zend_string* bucket_name);

    COUCHBASE_API
    void notify_fork(fork_event event) const;

    COUCHBASE_API
    core_error_info open();

    COUCHBASE_API
    core_error_info bucket_open(const std::string& name);

    COUCHBASE_API
    core_error_info bucket_open(const zend_string* name);

    COUCHBASE_API
    core_error_info bucket_close(const zend_string* name);

    COUCHBASE_API
    core_error_info document_upsert(zval* return_value,
                                    const zend_string* bucket,
                                    const zend_string* scope,
                                    const zend_string* collection,
                                    const zend_string* id,
                                    const zend_string* value,
                                    zend_long flags,
                                    const zval* options);

    COUCHBASE_API
    core_error_info document_insert(zval* return_value,
                                    const zend_string* bucket,
                                    const zend_string* scope,
                                    const zend_string* collection,
                                    const zend_string* id,
                                    const zend_string* value,
                                    zend_long flags,
                                    const zval* options);

    COUCHBASE_API
    core_error_info document_replace(zval* return_value,
                                     const zend_string* bucket,
                                     const zend_string* scope,
                                     const zend_string* collection,
                                     const zend_string* id,
                                     const zend_string* value,
                                     zend_long flags,
                                     const zval* options);

    COUCHBASE_API
    core_error_info document_append(zval* return_value,
                                    const zend_string* bucket,
                                    const zend_string* scope,
                                    const zend_string* collection,
                                    const zend_string* id,
                                    const zend_string* value,
                                    const zval* options);

    COUCHBASE_API
    core_error_info document_prepend(zval* return_value,
                                     const zend_string* bucket,
                                     const zend_string* scope,
                                     const zend_string* collection,
                                     const zend_string* id,
                                     const zend_string* value,
                                     const zval* options);

    COUCHBASE_API
    core_error_info document_increment(zval* return_value,
                                       const zend_string* bucket,
                                       const zend_string* scope,
                                       const zend_string* collection,
                                       const zend_string* id,
                                       const zval* options);

    COUCHBASE_API
    core_error_info document_decrement(zval* return_value,
                                       const zend_string* bucket,
                                       const zend_string* scope,
                                       const zend_string* collection,
                                       const zend_string* id,
                                       const zval* options);

    COUCHBASE_API
    core_error_info document_get(zval* return_value,
                                 const zend_string* bucket,
                                 const zend_string* scope,
                                 const zend_string* collection,
                                 const zend_string* id,
                                 const zval* options);

    COUCHBASE_API
    core_error_info document_get_any_replica(zval* return_value,
                                             const zend_string* bucket,
                                             const zend_string* scope,
                                             const zend_string* collection,
                                             const zend_string* id,
                                             const zval* options);

    COUCHBASE_API
    core_error_info document_get_all_replicas(zval* return_value,
                                              const zend_string* bucket,
                                              const zend_string* scope,
                                              const zend_string* collection,
                                              const zend_string* id,
                                              const zval* options);

    COUCHBASE_API
    core_error_info document_get_and_lock(zval* return_value,
                                          const zend_string* bucket,
                                          const zend_string* scope,
                                          const zend_string* collection,
                                          const zend_string* id,
                                          zend_long lock_time,
                                          const zval* options);

    COUCHBASE_API
    core_error_info document_get_and_touch(zval* return_value,
                                           const zend_string* bucket,
                                           const zend_string* scope,
                                           const zend_string* collection,
                                           const zend_string* id,
                                           zend_long expiry,
                                           const zval* options);

    COUCHBASE_API
    core_error_info document_unlock(zval* return_value,
                                    const zend_string* bucket,
                                    const zend_string* scope,
                                    const zend_string* collection,
                                    const zend_string* id,
                                    const zend_string* cas,
                                    const zval* options);

    COUCHBASE_API
    core_error_info document_remove(zval* return_value,
                                    const zend_string* bucket,
                                    const zend_string* scope,
                                    const zend_string* collection,
                                    const zend_string* id,
                                    const zval* options);

    COUCHBASE_API
    core_error_info document_touch(zval* return_value,
                                   const zend_string* bucket,
                                   const zend_string* scope,
                                   const zend_string* collection,
                                   const zend_string* id,
                                   zend_long expiry,
                                   const zval* options);

    COUCHBASE_API
    core_error_info document_exists(zval* return_value,
                                    const zend_string* bucket,
                                    const zend_string* scope,
                                    const zend_string* collection,
                                    const zend_string* id,
                                    const zval* options);

    COUCHBASE_API
    core_error_info document_mutate_in(zval* return_value,
                                       const zend_string* bucket,
                                       const zend_string* scope,
                                       const zend_string* collection,
                                       const zend_string* id,
                                       const zval* specs,
                                       const zval* options);

    COUCHBASE_API
    core_error_info document_lookup_in(zval* return_value,
                                       const zend_string* bucket,
                                       const zend_string* scope,
                                       const zend_string* collection,
                                       const zend_string* id,
                                       const zval* specs,
                                       const zval* options);

    COUCHBASE_API
    core_error_info document_lookup_in_any_replica(zval* return_value,
                                                   const zend_string* bucket,
                                                   const zend_string* scope,
                                                   const zend_string* collection,
                                                   const zend_string* id,
                                                   const zval* specs,
                                                   const zval* options);

    COUCHBASE_API
    core_error_info document_lookup_in_all_replicas(zval* return_value,
                                                    const zend_string* bucket,
                                                    const zend_string* scope,
                                                    const zend_string* collection,
                                                    const zend_string* id,
                                                    const zval* specs,
                                                    const zval* options);

    COUCHBASE_API
    core_error_info document_get_multi(zval* return_value,
                                       const zend_string* bucket,
                                       const zend_string* scope,
                                       const zend_string* collection,
                                       const zval* ids,
                                       const zval* options);

    COUCHBASE_API
    core_error_info document_remove_multi(zval* return_value,
                                          const zend_string* bucket,
                                          const zend_string* scope,
                                          const zend_string* collection,
                                          const zval* entries,
                                          const zval* options);

    COUCHBASE_API
    core_error_info document_upsert_multi(zval* return_value,
                                          const zend_string* bucket,
                                          const zend_string* scope,
                                          const zend_string* collection,
                                          const zval* entries,
                                          const zval* options);

    COUCHBASE_API
    core_error_info query(zval* return_value, const zend_string* statement, const zval* options);

    COUCHBASE_API
    core_error_info analytics_query(zval* return_value, const zend_string* statement, const zval* options);

    COUCHBASE_API
    core_error_info search(zval* return_value,
                           const zend_string* index_name,
                           const zend_string* query,
                           const zval* options,
                           const zend_string* vector_search,
                           const zval* vector_options);

    COUCHBASE_API
    core_error_info search_query(zval* return_value, const zend_string* index_name, const zend_string* query, const zval* options);

    COUCHBASE_API
    core_error_info view_query(zval* return_value,
                               const zend_string* bucket_name,
                               const zend_string* design_document_name,
                               const zend_string* view_name,
                               zend_long name_space,
                               const zval* options);

    COUCHBASE_API
    core_error_info ping(zval* return_value, const zval* options);

    COUCHBASE_API
    core_error_info diagnostics(zval* return_value, const zend_string* report_id, const zval* options);

    COUCHBASE_API
    core_error_info search_index_get(zval* return_value, const zend_string* index_name, const zval* options);

    COUCHBASE_API
    core_error_info search_index_get_all(zval* return_value, const zval* options);

    COUCHBASE_API
    core_error_info search_index_upsert(zval* return_value, const zval* index, const zval* options);

    COUCHBASE_API
    core_error_info search_index_drop(zval* return_value, const zend_string* index_name, const zval* options);

    COUCHBASE_API
    core_error_info search_index_get_documents_count(zval* return_value, const zend_string* index_name, const zval* options);

    COUCHBASE_API
    core_error_info search_index_control_ingest(zval* return_value, const zend_string* index_name, bool pause, const zval* options);

    COUCHBASE_API
    core_error_info search_index_control_query(zval* return_value, const zend_string* index_name, bool allow, const zval* options);

    COUCHBASE_API
    core_error_info search_index_control_plan_freeze(zval* return_value, const zend_string* index_name, bool freeze, const zval* options);

    COUCHBASE_API
    core_error_info search_index_analyze_document(zval* return_value, const zend_string* index_name, const zend_string* document, const zval* options);

    COUCHBASE_API
    core_error_info scope_search_index_get(zval* return_value, const zend_string* bucket_name, const zend_string* scope_name, const zend_string* index_name, const zval* options);

    COUCHBASE_API
    core_error_info scope_search_index_get_all(zval* return_value, const zend_string* bucket_name, const zend_string* scope_name, const zval* options);

    COUCHBASE_API
    core_error_info scope_search_index_upsert(zval* return_value, const zend_string* bucket_name, const zend_string* scope_name, const zval* index, const zval* options);

    COUCHBASE_API
    core_error_info scope_search_index_drop(zval* return_value, const zend_string* bucket_name, const zend_string* scope_name, const zend_string* index_name, const zval* options);

    COUCHBASE_API
    core_error_info scope_search_index_get_documents_count(zval* return_value, const zend_string* bucket_name, const zend_string* scope_name, const zend_string* index_name, const zval* options);

    COUCHBASE_API
    core_error_info scope_search_index_control_ingest(zval* return_value, const zend_string* bucket_name, const zend_string* scope_name, const zend_string* index_name, bool pause, const zval* options);

    COUCHBASE_API
    core_error_info scope_search_index_control_query(zval* return_value, const zend_string* bucket_name, const zend_string* scope_name, const zend_string* index_name, bool allow, const zval* options);

    COUCHBASE_API
    core_error_info scope_search_index_control_plan_freeze(zval* return_value, const zend_string* bucket_name, const zend_string* scope_name, const zend_string* index_name, bool freeze, const zval* options);

    COUCHBASE_API
    core_error_info scope_search_index_analyze_document(zval* return_value, const zend_string* bucket_name, const zend_string* scope_name, const zend_string* index_name, const zend_string* document, const zval* options);

    COUCHBASE_API
    core_error_info view_index_upsert(zval* return_value,
                                      const zend_string* bucket_name,
                                      const zval* design_document,
                                      zend_long name_space,
                                      const zval* options);

    COUCHBASE_API
    core_error_info bucket_create(zval* return_value, const zval* bucket_settings, const zval* options);

    COUCHBASE_API
    core_error_info bucket_update(zval* return_value, const zval* bucket_settings, const zval* options);

    COUCHBASE_API
    core_error_info bucket_get(zval* return_value, const zend_string* name, const zval* options);

    COUCHBASE_API
    core_error_info bucket_get_all(zval* return_value, const zval* options);

    COUCHBASE_API
    core_error_info bucket_drop(zval* return_value, const zend_string* name, const zval* options);

    COUCHBASE_API
    core_error_info bucket_flush(zval* return_value, const zend_string* name, const zval* options);

    COUCHBASE_API
    core_error_info scope_get_all(zval* return_value, const zend_string* name, const zval* options);

    COUCHBASE_API
    core_error_info scope_create(zval* return_value, const zend_string* bucket_name, const zend_string* scope_name, const zval* options);

    COUCHBASE_API
    core_error_info scope_drop(zval* return_value, const zend_string* bucket_name, const zend_string* scope_name, const zval* options);

    COUCHBASE_API
    core_error_info collection_create(zval* return_value, const zend_string* bucket_name, const zend_string* scope_name, const zend_string* collection_name, const zval* settings, const zval* options);

    COUCHBASE_API
    core_error_info collection_drop(zval* return_value, const zend_string* bucket_name, const zend_string* scope_name, const zend_string* collection_name, const zval* options);

    COUCHBASE_API
    core_error_info collection_update(zval* return_value, const zend_string* bucket_name, const zend_string* scope_name, const zend_string* collection_name, const zval* settings, const zval* options);

    COUCHBASE_API
    core_error_info user_upsert(zval* return_value, const zval* user, const zval* options);

    COUCHBASE_API
    core_error_info user_get(zval* return_value, const zend_string* name, const zval* options);

    COUCHBASE_API
    core_error_info user_get_all(zval* return_value, const zval* options);

    COUCHBASE_API
    core_error_info user_drop(zval* return_value, const zend_string* name, const zval* options);

    COUCHBASE_API
    core_error_info change_password(zval* return_value, const zend_string* new_password, const zval* options);

    COUCHBASE_API
    core_error_info group_upsert(zval* return_value, const zval* group, const zval* options);

    COUCHBASE_API
    core_error_info group_get(zval* return_value, const zend_string* name, const zval* options);

    COUCHBASE_API
    core_error_info group_get_all(zval* return_value, const zval* options);

    COUCHBASE_API
    core_error_info group_drop(zval* return_value, const zend_string* name, const zval* options);

    COUCHBASE_API
    core_error_info role_get_all(zval* return_value, const zval* options);

    COUCHBASE_API
    core_error_info query_index_get_all(zval* return_value, const zend_string* bucket_name, const zval* options);

    COUCHBASE_API
    core_error_info query_index_create(const zend_string* bucket_name,
                                       const zend_string* index_name,
                                       const zval* keys,
                                       const zval* options);

    COUCHBASE_API
    core_error_info query_index_create_primary(const zend_string* bucket_name, const zval* options);

    COUCHBASE_API
    core_error_info query_index_drop(const zend_string* bucket_name, const zend_string* index_name, const zval* options);

    COUCHBASE_API
    core_error_info query_index_drop_primary(const zend_string* bucket_name, const zval* options);

    COUCHBASE_API
    core_error_info query_index_build_deferred(zval* return_value, const zend_string* bucket_name, const zval* options);

    COUCHBASE_API
    core_error_info collection_query_index_get_all(zval* return_value,
                                                   const zend_string* bucket_name,
                                                   const zend_string* scope_name,
                                                   const zend_string* collection_name,
                                                   const zval* options);

    COUCHBASE_API
    core_error_info collection_query_index_create(const zend_string* bucket_name,
                                                  const zend_string* scope_name,
                                                  const zend_string* collection_name,
                                                  const zend_string* index_name,
                                                  const zval* keys,
                                                  const zval* options);

    COUCHBASE_API
    core_error_info collection_query_index_create_primary(const zend_string* bucket_name,
                                                          const zend_string* scope_name,
                                                          const zend_string* collection_name,
                                                          const zval* options);

    COUCHBASE_API
    core_error_info collection_query_index_drop(const zend_string* bucket_name,
                                                const zend_string* scope_name,
                                                const zend_string* collection_name,
                                                const zend_string* index_name,
                                                const zval* options);

    COUCHBASE_API
    core_error_info collection_query_index_drop_primary(const zend_string* bucket_name,
                                                        const zend_string* scope_name,
                                                        const zend_string* collection_name,
                                                        const zval* options);

    COUCHBASE_API
    core_error_info collection_query_index_build_deferred(zval* return_value,
                                                          const zend_string* bucket_name,
                                                          const zend_string* scope_name,
                                                          const zend_string* collection_name,
                                                          const zval* options);

  private:
    class impl;

    std::chrono::system_clock::time_point idle_expiry_; /* time when the connection will be considered as expired */

    std::shared_ptr<impl> impl_;

    std::string connection_string_;
    std::string connection_hash_;
};

COUCHBASE_API
std::pair<connection_handle*, core_error_info>
create_connection_handle(const zend_string* connection_string,
                         const zend_string* connection_hash,
                         zval* options,
                         std::chrono::system_clock::time_point idle_expiry);
} // namespace couchbase::php
