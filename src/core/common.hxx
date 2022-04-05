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

#ifdef HAVE_CONFIG_H
#include "config.h"
#endif

#include "core_error_info.hxx"
#include "php_7_api_layer.hxx"

#include <Zend/zend_API.h>

ZEND_BEGIN_MODULE_GLOBALS(couchbase)
zend_long max_persistent{};     /* maximum number of persistent connections per process */
zend_long num_persistent{};     /* number of existing persistent connections */
zend_long persistent_timeout{}; /* time period after which idle persistent connection is considered expired */
ZEND_END_MODULE_GLOBALS(couchbase)

ZEND_EXTERN_MODULE_GLOBALS(couchbase)

#ifdef ZTS
#define COUCHBASE_G(v) TSRMG(couchbase_globals_id, zend_couchbase_globals*, v)
#else
#define COUCHBASE_G(v) (couchbase_globals.v)
#endif

extern zend_class_entry* couchbase_exception_ce;
extern zend_class_entry* ambiguous_timeout_exception_ce;
extern zend_class_entry* authentication_failure_exception_ce;
extern zend_class_entry* bucket_exists_exception_ce;
extern zend_class_entry* bucket_not_flushable_exception_ce;
extern zend_class_entry* bucket_not_found_exception_ce;
extern zend_class_entry* cas_mismatch_exception_ce;
extern zend_class_entry* collection_exists_exception_ce;
extern zend_class_entry* collection_not_found_exception_ce;
extern zend_class_entry* compilation_failure_exception_ce;
extern zend_class_entry* consistency_mismatch_exception_ce;
extern zend_class_entry* dataset_exists_exception_ce;
extern zend_class_entry* dataset_not_found_exception_ce;
extern zend_class_entry* dataverse_exists_exception_ce;
extern zend_class_entry* dataverse_not_found_exception_ce;
extern zend_class_entry* decoding_failure_exception_ce;
extern zend_class_entry* delta_invalid_exception_ce;
extern zend_class_entry* design_document_not_found_exception_ce;
extern zend_class_entry* document_exists_exception_ce;
extern zend_class_entry* document_irretrievable_exception_ce;
extern zend_class_entry* document_locked_exception_ce;
extern zend_class_entry* document_not_found_exception_ce;
extern zend_class_entry* document_not_json_exception_ce;
extern zend_class_entry* durability_ambiguous_exception_ce;
extern zend_class_entry* durability_impossible_exception_ce;
extern zend_class_entry* durability_level_not_available_exception_ce;
extern zend_class_entry* durable_write_in_progress_exception_ce;
extern zend_class_entry* durable_write_re_commit_in_progress_exception_ce;
extern zend_class_entry* encoding_failure_exception_ce;
extern zend_class_entry* feature_not_available_exception_ce;
extern zend_class_entry* group_not_found_exception_ce;
extern zend_class_entry* index_exists_exception_ce;
extern zend_class_entry* index_failure_exception_ce;
extern zend_class_entry* index_not_found_exception_ce;
extern zend_class_entry* index_not_ready_exception_ce;
extern zend_class_entry* internal_server_failure_exception_ce;
extern zend_class_entry* invalid_argument_exception_ce;
extern zend_class_entry* job_queue_full_exception_ce;
extern zend_class_entry* link_exists_exception_ce;
extern zend_class_entry* link_not_found_exception_ce;
extern zend_class_entry* number_too_big_exception_ce;
extern zend_class_entry* parsing_failure_exception_ce;
extern zend_class_entry* path_exists_exception_ce;
extern zend_class_entry* path_invalid_exception_ce;
extern zend_class_entry* path_mismatch_exception_ce;
extern zend_class_entry* path_not_found_exception_ce;
extern zend_class_entry* path_too_big_exception_ce;
extern zend_class_entry* path_too_deep_exception_ce;
extern zend_class_entry* planning_failure_exception_ce;
extern zend_class_entry* prepared_statement_failure_exception_ce;
extern zend_class_entry* request_canceled_exception_ce;
extern zend_class_entry* scope_exists_exception_ce;
extern zend_class_entry* scope_not_found_exception_ce;
extern zend_class_entry* service_not_available_exception_ce;
extern zend_class_entry* temporary_failure_exception_ce;
extern zend_class_entry* timeout_exception_ce;
extern zend_class_entry* unambiguous_timeout_exception_ce;
extern zend_class_entry* unsupported_operation_exception_ce;
extern zend_class_entry* user_exists_exception_ce;
extern zend_class_entry* user_not_found_exception_ce;
extern zend_class_entry* value_invalid_exception_ce;
extern zend_class_entry* value_too_deep_exception_ce;
extern zend_class_entry* value_too_large_exception_ce;
extern zend_class_entry* view_not_found_exception_ce;
extern zend_class_entry* xattr_cannot_modify_virtual_attribute_exception_ce;
extern zend_class_entry* xattr_invalid_key_combo_exception_ce;
extern zend_class_entry* xattr_unknown_macro_exception_ce;
extern zend_class_entry* xattr_unknown_virtual_attribute_exception_ce;

namespace couchbase::php
{
zend_class_entry*
map_error_to_exception(const core_error_info& info);

void
error_context_to_zval(const core_error_info& info, zval* return_value);

void
create_exception(zval* return_value, const couchbase::php::core_error_info& error_info);
} // namespace couchbase::php