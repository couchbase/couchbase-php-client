<?php
# Generated by the protocol buffer compiler.  DO NOT EDIT!
# source: couchbase/admin/collection/v1/collection.proto

namespace GPBMetadata\Couchbase\Admin\Collection\V1;

class Collection
{
    public static $is_initialized = false;

    public static function initOnce() {
        $pool = \Google\Protobuf\Internal\DescriptorPool::getGeneratedPool();

        if (static::$is_initialized == true) {
          return;
        }
        $pool->internalAddGeneratedFile(
            '
�
.couchbase/admin/collection/v1/collection.protocouchbase.admin.collection.v1"-
ListCollectionsRequest
bucket_name (	"�
ListCollectionsResponseL
scopes (2<.couchbase.admin.collection.v1.ListCollectionsResponse.ScopeL

Collection
name (	
max_expiry_secs (H �B
_max_expiry_secsm
Scope
name (	V
collections (2A.couchbase.admin.collection.v1.ListCollectionsResponse.Collection"=
CreateScopeRequest
bucket_name (	

scope_name (	"
CreateScopeResponse"=
DeleteScopeRequest
bucket_name (	

scope_name (	"
DeleteScopeResponse"�
CreateCollectionRequest
bucket_name (	

scope_name (	
collection_name (	
max_expiry_secs (H �B
_max_expiry_secs"
CreateCollectionResponse"[
DeleteCollectionRequest
bucket_name (	

scope_name (	
collection_name (	"
DeleteCollectionResponse2�
CollectionAdminService�
ListCollections5.couchbase.admin.collection.v1.ListCollectionsRequest6.couchbase.admin.collection.v1.ListCollectionsResponse" v
CreateScope1.couchbase.admin.collection.v1.CreateScopeRequest2.couchbase.admin.collection.v1.CreateScopeResponse" v
DeleteScope1.couchbase.admin.collection.v1.DeleteScopeRequest2.couchbase.admin.collection.v1.DeleteScopeResponse" �
CreateCollection6.couchbase.admin.collection.v1.CreateCollectionRequest7.couchbase.admin.collection.v1.CreateCollectionResponse" �
DeleteCollection6.couchbase.admin.collection.v1.DeleteCollectionRequest7.couchbase.admin.collection.v1.DeleteCollectionResponse" B�
5com.couchbase.client.protostellar.admin.collection.v1PZTgithub.com/couchbase/goprotostellar/genproto/admin_collection_v1;admin_collection_v1�*Couchbase.Protostellar.Admin.Collection.V1�4Couchbase\\Protostellar\\Generated\\Admin\\Collection\\V1�9Couchbase::Protostellar::Generated::Admin::Collection::V1bproto3'
        , true);

        static::$is_initialized = true;
    }
}

