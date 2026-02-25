<?php

/**
 * Copyright 2014-Present Couchbase, Inc.
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

declare(strict_types=1);

namespace Couchbase\Observability;

/**
 * @internal
 */
interface ObservabilityConstants
{
    // Key-Value Operations
    public const OP_GET = "get";
    public const OP_GET_MULTI = "get_multi";
    public const OP_GET_AND_LOCK = "get_and_lock";
    public const OP_GET_AND_TOUCH = "get_and_touch";
    public const OP_GET_ALL_REPLICAS = "get_all_replicas";
    public const OP_GET_ANY_REPLICA = "get_any_replica";
    public const OP_GET_REPLICA = "get_replica";
    public const OP_EXISTS = "exists";
    public const OP_REPLACE = "replace";
    public const OP_UPSERT = "upsert";
    public const OP_UPSERT_MULTI = "upsert_multi";
    public const OP_REMOVE = "remove";
    public const OP_REMOVE_MULTI = "remove_multi";
    public const OP_INSERT = "insert";
    public const OP_TOUCH = "touch";
    public const OP_UNLOCK = "unlock";
    public const OP_LOOKUP_IN = "lookup_in";
    public const OP_LOOKUP_IN_ALL_REPLICAS = "lookup_all_replicas";
    public const OP_LOOKUP_IN_ANY_REPLICA = "lookup_any_replica";
    public const OP_LOOKUP_IN_REPLICA = "lookup_in_replica";
    public const OP_MUTATE_IN = "mutate_in";
    public const OP_SCAN = "scan";
    public const OP_INCREMENT = "increment";
    public const OP_DECREMENT = "decrement";
    public const OP_APPEND = "append";
    public const OP_PREPEND = "prepend";

    // Query Operations
    public const OP_QUERY = "query";
    public const OP_SEARCH_QUERY = "search";
    public const OP_ANALYTICS_QUERY = "analytics";
    public const OP_VIEW_QUERY = "views";

    // Diagnostics Operations
    public const OP_PING = "ping";
    public const OP_DIAGNOSTICS = "diagnostics";

    // Bucket Manager Operations
    public const OP_BM_CREATE_BUCKET = "manager_buckets_create_bucket";
    public const OP_BM_DROP_BUCKET = "manager_buckets_drop_bucket";
    public const OP_BM_FLUSH_BUCKET = "manager_buckets_flush_bucket";
    public const OP_BM_GET_ALL_BUCKETS = "manager_buckets_get_all_buckets";
    public const OP_BM_GET_BUCKET = "manager_buckets_get_bucket";
    public const OP_BM_UPDATE_BUCKET = "manager_buckets_update_bucket";

    // Collection Manager Operations
    public const OP_CM_CREATE_COLLECTION = "manager_collections_create_collection";
    public const OP_CM_UPDATE_COLLECTION = "manager_collections_update_collection";
    public const OP_CM_DROP_COLLECTION = "manager_collections_drop_collection";
    public const OP_CM_CREATE_SCOPE = "manager_collections_create_scope";
    public const OP_CM_DROP_SCOPE = "manager_collections_drop_scope";
    public const OP_CM_GET_ALL_SCOPES = "manager_collections_get_all_scopes";
    public const OP_CM_GET_SCOPE = "manager_collections_get_scope";

    // Query Index Manager Operations
    public const OP_QM_BUILD_DEFERRED_INDEXES = "manager_query_build_deferred_indexes";
    public const OP_QM_CREATE_INDEX = "manager_query_create_index";
    public const OP_QM_CREATE_PRIMARY_INDEX = "manager_query_create_primary_index";
    public const OP_QM_DROP_INDEX = "manager_query_drop_index";
    public const OP_QM_DROP_PRIMARY_INDEX = "manager_query_drop_primary_index";
    public const OP_QM_GET_ALL_INDEXES = "manager_query_get_all_indexes";
    public const OP_QM_WATCH_INDEXES = "manager_query_watch_indexes";

    // Analytics Manager Operations
    public const OP_AM_CREATE_DATAVERSE = "manager_analytics_create_dataverse";
    public const OP_AM_DROP_DATAVERSE = "manager_analytics_drop_dataverse";
    public const OP_AM_CREATE_DATASET = "manager_analytics_create_dataset";
    public const OP_AM_DROP_DATASET = "manager_analytics_drop_dataset";
    public const OP_AM_GET_ALL_DATASETS = "manager_analytics_get_all_datasets";
    public const OP_AM_CREATE_INDEX = "manager_analytics_create_index";
    public const OP_AM_DROP_INDEX = "manager_analytics_drop_index";
    public const OP_AM_GET_ALL_INDEXES = "manager_analytics_get_all_indexes";
    public const OP_AM_CONNECT_LINK = "manager_analytics_connect_link";
    public const OP_AM_DISCONNECT_LINK = "manager_analytics_disconnect_link";
    public const OP_AM_GET_PENDING_MUTATIONS = "manager_analytics_get_pending_mutations";
    public const OP_AM_CREATE_LINK = "manager_analytics_create_link";
    public const OP_AM_REPLACE_LINK = "manager_analytics_replace_link";
    public const OP_AM_DROP_LINK = "manager_analytics_drop_link";
    public const OP_AM_GET_LINKS = "manager_analytics_get_links";

    // Search Index Manager Operations
    public const OP_SM_GET_INDEX = "manager_search_get_index";
    public const OP_SM_GET_ALL_INDEXES = "manager_search_get_all_indexes";
    public const OP_SM_UPSERT_INDEX = "manager_search_upsert_index";
    public const OP_SM_DROP_INDEX = "manager_search_drop_index";
    public const OP_SM_GET_INDEXED_DOCUMENTS_COUNT = "manager_search_get_indexed_documents_count";
    public const OP_SM_GET_INDEX_STATS = "manager_search_get_index_stats";
    public const OP_SM_GET_STATS = "manager_search_get_stats";
    public const OP_SM_PAUSE_INGEST = "manager_search_pause_index";
    public const OP_SM_RESUME_INGEST = "manager_search_resume_index";
    public const OP_SM_ALLOW_QUERYING = "manager_search_allow_querying";
    public const OP_SM_DISALLOW_QUERYING = "manager_search_disallow_querying";
    public const OP_SM_FREEZE_PLAN = "manager_search_freeze_plan";
    public const OP_SM_UNFREEZE_PLAN = "manager_search_unfreeze_plan";
    public const OP_SM_ANALYZE_DOCUMENT = "manager_search_analyze_document";

    // User Manager Operations
    public const OP_UM_DROP_GROUP = "manager_users_drop_group";
    public const OP_UM_DROP_USER = "manager_users_drop_user";
    public const OP_UM_GET_ALL_GROUPS = "manager_users_get_all_groups";
    public const OP_UM_GET_ALL_USERS = "manager_users_get_all_users";
    public const OP_UM_GET_GROUP = "manager_users_get_group";
    public const OP_UM_GET_ROLES = "manager_users_get_roles";
    public const OP_UM_GET_USER = "manager_users_get_user";
    public const OP_UM_UPSERT_GROUP = "manager_users_upsert_group";
    public const OP_UM_UPSERT_USER = "manager_users_upsert_user";
    public const OP_UM_CHANGE_PASSWORD = "manager_users_change_password";

    // View Manager Operations
    public const OP_VM_GET_DESIGN_DOCUMENT = "manager_views_get_design_document";
    public const OP_VM_GET_ALL_DESIGN_DOCUMENTS = "manager_views_get_all_design_documents";
    public const OP_VM_UPSERT_DESIGN_DOCUMENT = "manager_views_upsert_design_document";
    public const OP_VM_DROP_DESIGN_DOCUMENT = "manager_views_drop_design_document";
    public const OP_VM_PUBLISH_DESIGN_DOCUMENT = "manager_views_publish_design_document";

    // List Operations
    public const OP_LIST_EACH = "list_each";
    public const OP_LIST_LENGTH = "list_length";
    public const OP_LIST_PUSH = "list_push";
    public const OP_LIST_UNSHIFT = "list_unshift";
    public const OP_LIST_INSERT = "list_insert";
    public const OP_LIST_AT = "list_at";
    public const OP_LIST_DELETE_AT = "list_delete_at";
    public const OP_LIST_CLEAR = "list_clear";

    // Map Operations
    public const OP_MAP_EACH = "map_each";
    public const OP_MAP_LENGTH = "map_length";
    public const OP_MAP_CLEAR = "map_clear";
    public const OP_MAP_FETCH = "map_fetch";
    public const OP_MAP_DELETE = "map_delete";
    public const OP_MAP_KEY_EXISTS = "map_key";
    public const OP_MAP_STORE = "map_store";

    // Queue Operations
    public const OP_QUEUE_EACH = "queue_each";
    public const OP_QUEUE_LENGTH = "queue_length";
    public const OP_QUEUE_CLEAR = "queue_clear";
    public const OP_QUEUE_PUSH = "queue_push";
    public const OP_QUEUE_POP = "queue_pop";

    // Set Operations
    public const OP_SET_EACH = "set_each";
    public const OP_SET_LENGTH = "set_length";
    public const OP_SET_ADD = "set_add";
    public const OP_SET_CLEAR = "set_clear";
    public const OP_SET_DELETE = "set_delete";

    // Span Steps
    public const STEP_REQUEST_ENCODING = "request_encoding";
    public const STEP_DISPATCH_TO_SERVER = "dispatch_to_server";

    // Common Attributes
    public const ATTR_SYSTEM_NAME = "db.system.name";
    public const ATTR_CLUSTER_NAME = "couchbase.cluster.name";
    public const ATTR_CLUSTER_UUID = "couchbase.cluster.uuid";

    // Operation-level Attributes
    public const ATTR_OPERATION_NAME = "db.operation.name";
    public const ATTR_SERVICE = "couchbase.service";
    public const ATTR_BUCKET_NAME = "db.namespace";
    public const ATTR_SCOPE_NAME = "couchbase.scope.name";
    public const ATTR_COLLECTION_NAME = "couchbase.collection.name";
    public const ATTR_RETRIES = "couchbase.retries";
    public const ATTR_DURABILITY = "couchbase.durability";
    public const ATTR_QUERY_STATEMENT = "db.query.text";
    public const ATTR_ERROR_TYPE = "error.type";

    // Dispatch-level Attributes
    public const ATTR_LOCAL_ID = "couchbase.local_id";
    public const ATTR_OPERATION_ID = "couchbase.operation_id";
    public const ATTR_PEER_ADDRESS = "network.peer.address";
    public const ATTR_PEER_PORT = "network.peer.port";
    public const ATTR_SERVER_DURATION = "couchbase.server_duration";

    // Reserved Attributes
    public const ATTR_RESERVED_UNIT = "__unit";

    // Attribute Values
    public const ATTR_VALUE_SYSTEM_NAME = "couchbase";

    public const ATTR_VALUE_DURABILITY_MAJORITY = "majority";
    public const ATTR_VALUE_DURABILITY_MAJORITY_AND_PERSIST_TO_ACTIVE = "majority_and_persist_active";
    public const ATTR_VALUE_DURABILITY_PERSIST_TO_MAJORITY = "persist_majority";

    public const ATTR_VALUE_SERVICE_KV = "kv";
    public const ATTR_VALUE_SERVICE_QUERY = "query";
    public const ATTR_VALUE_SERVICE_SEARCH = "search";
    public const ATTR_VALUE_SERVICE_VIEWS = "views";
    public const ATTR_VALUE_SERVICE_ANALYTICS = "analytics";
    public const ATTR_VALUE_SERVICE_MANAGEMENT = "management";
    public const ATTR_VALUE_SERVICE_EVENTING = "eventing";

    public const ATTR_VALUE_RESERVED_UNIT_SECONDS = "s";

    // Meter Names
    public const METER_NAME_OPERATION_DURATION = "db.client.operation.duration";
}
