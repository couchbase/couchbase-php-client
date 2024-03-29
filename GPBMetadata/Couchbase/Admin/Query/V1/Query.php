<?php
# Generated by the protocol buffer compiler.  DO NOT EDIT!
# source: couchbase/admin/query/v1/query.proto

namespace GPBMetadata\Couchbase\Admin\Query\V1;

class Query
{
    public static $is_initialized = false;

    public static function initOnce() {
        $pool = \Google\Protobuf\Internal\DescriptorPool::getGeneratedPool();

        if (static::$is_initialized == true) {
          return;
        }
        $pool->internalAddGeneratedFile(
            '
�
$couchbase/admin/query/v1/query.protocouchbase.admin.query.v1"�
GetAllIndexesRequest
bucket_name (	H �

scope_name (	H�
collection_name (	H�B
_bucket_nameB
_scope_nameB
_collection_name"�
GetAllIndexesResponseF
indexes (25.couchbase.admin.query.v1.GetAllIndexesResponse.Index�
Index
bucket_name (	

scope_name (	
collection_name (	
name (	

is_primary (1
type (2#.couchbase.admin.query.v1.IndexType3
state (2$.couchbase.admin.query.v1.IndexState
fields (	
	condition	 (	H �
	partition
 (	H�B

_conditionB

_partition"�
CreatePrimaryIndexRequest
bucket_name (	

scope_name (	H �
collection_name (	H�
name (	H�
num_replicas (H�
deferred (H�
ignore_if_exists (H�B
_scope_nameB
_collection_nameB
_nameB
_num_replicasB
	_deferredB
_ignore_if_exists"
CreatePrimaryIndexResponse"�
CreateIndexRequest
bucket_name (	

scope_name (	H �
collection_name (	H�
name (	
num_replicas (H�
fields (	
deferred (H�
ignore_if_exists (H�B
_scope_nameB
_collection_nameB
_num_replicasB
	_deferredB
_ignore_if_exists"
CreateIndexResponse"�
DropPrimaryIndexRequest
bucket_name (	

scope_name (	H �
collection_name (	H�
name (	H�
ignore_if_missing (H�B
_scope_nameB
_collection_nameB
_nameB
_ignore_if_missing"
DropPrimaryIndexResponse"�
DropIndexRequest
bucket_name (	

scope_name (	H �
collection_name (	H�
name (	
ignore_if_missing (H�B
_scope_nameB
_collection_nameB
_ignore_if_missing"
DropIndexResponse"�
BuildDeferredIndexesRequest
bucket_name (	

scope_name (	H �
collection_name (	H�B
_scope_nameB
_collection_name"�
BuildDeferredIndexesResponseM
indexes (2<.couchbase.admin.query.v1.BuildDeferredIndexesResponse.Index�
Index
bucket_name (	

scope_name (	H �
collection_name (	H�
name (	B
_scope_nameB
_collection_name*4
	IndexType
INDEX_TYPE_VIEW 
INDEX_TYPE_GSI*�

IndexState
INDEX_STATE_DEFERRED 
INDEX_STATE_BUILDING
INDEX_STATE_PENDING
INDEX_STATE_ONLINE
INDEX_STATE_OFFLINE
INDEX_STATE_ABRIDGED
INDEX_STATE_SCHEDULED2�
QueryAdminServicer
GetAllIndexes..couchbase.admin.query.v1.GetAllIndexesRequest/.couchbase.admin.query.v1.GetAllIndexesResponse" �
CreatePrimaryIndex3.couchbase.admin.query.v1.CreatePrimaryIndexRequest4.couchbase.admin.query.v1.CreatePrimaryIndexResponse" l
CreateIndex,.couchbase.admin.query.v1.CreateIndexRequest-.couchbase.admin.query.v1.CreateIndexResponse" {
DropPrimaryIndex1.couchbase.admin.query.v1.DropPrimaryIndexRequest2.couchbase.admin.query.v1.DropPrimaryIndexResponse" f
	DropIndex*.couchbase.admin.query.v1.DropIndexRequest+.couchbase.admin.query.v1.DropIndexResponse" �
BuildDeferredIndexes5.couchbase.admin.query.v1.BuildDeferredIndexesRequest6.couchbase.admin.query.v1.BuildDeferredIndexesResponse" B�
0com.couchbase.client.protostellar.admin.query.v1PZJgithub.com/couchbase/goprotostellar/genproto/admin_query_v1;admin_query_v1�%Couchbase.Protostellar.Admin.Query.V1�/Couchbase\\Protostellar\\Generated\\Admin\\Query\\V1�4Couchbase::Protostellar::Generated::Admin::Query::V1bproto3'
        , true);

        static::$is_initialized = true;
    }
}

