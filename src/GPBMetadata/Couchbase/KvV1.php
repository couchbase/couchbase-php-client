<?php
# Generated by the protocol buffer compiler.  DO NOT EDIT!
# source: couchbase/kv.v1.proto

namespace GPBMetadata\Couchbase;

class KvV1
{
    public static $is_initialized = false;

    public static function initOnce() {
        $pool = \Google\Protobuf\Internal\DescriptorPool::getGeneratedPool();

        if (static::$is_initialized == true) {
          return;
        }
        \GPBMetadata\Google\Rpc\Status::initOnce();
        \GPBMetadata\Google\Protobuf\Timestamp::initOnce();
        $pool->internalAddGeneratedFile(
            '
øK
couchbase/kv.v1.protocouchbase.kv.v1google/protobuf/timestamp.proto"E
LegacyDurabilitySpec
num_replicated (
num_persisted ("^
MutationToken
bucket_name (	

vbucket_id (
vbucket_uuid (
seq_no ("[

GetRequest
bucket_name (	

scope_name (	
collection_name (	
key (	"
GetAndTouchRequest
bucket_name (	

scope_name (	
collection_name (	
key (	*
expiry (2.google.protobuf.Timestamp"u
GetAndLockRequest
bucket_name (	

scope_name (	
collection_name (	
key (	
	lock_time ("y
GetReplicaRequest
bucket_name (	

scope_name (	
collection_name (	
key (	
replica_index ("ç
GetResponse
content (:
content_type (2$.couchbase.kv.v1.DocumentContentTypeB
compression_type (2(.couchbase.kv.v1.DocumentCompressionType
cas (/
expiry (2.google.protobuf.TimestampH B	
_expiry"k
UnlockRequest
bucket_name (	

scope_name (	
collection_name (	
key (	
cas ("
UnlockResponse"
TouchRequest
bucket_name (	

scope_name (	
collection_name (	
key (	*
expiry (2.google.protobuf.Timestamp"T
TouchResponse
cas (6
mutation_token (2.couchbase.kv.v1.MutationToken"^
ExistsRequest
bucket_name (	

scope_name (	
collection_name (	
key (	"-
ExistsResponse
result (
cas ("
InsertRequest
bucket_name (	

scope_name (	
collection_name (	
key (	
content (:
content_type (2$.couchbase.kv.v1.DocumentContentType/
expiry (2.google.protobuf.TimestampHG
legacy_durability_spec (2%.couchbase.kv.v1.LegacyDurabilitySpecH <
durability_level	 (2 .couchbase.kv.v1.DurabilityLevelH B
durability_specB	
_expiry"U
InsertResponse
cas (6
mutation_token (2.couchbase.kv.v1.MutationToken"
UpsertRequest
bucket_name (	

scope_name (	
collection_name (	
key (	
content (:
content_type (2$.couchbase.kv.v1.DocumentContentType/
expiry (2.google.protobuf.TimestampHG
legacy_durability_spec (2%.couchbase.kv.v1.LegacyDurabilitySpecH <
durability_level	 (2 .couchbase.kv.v1.DurabilityLevelH B
durability_specB	
_expiry"U
UpsertResponse
cas (6
mutation_token (2.couchbase.kv.v1.MutationToken"
ReplaceRequest
bucket_name (	

scope_name (	
collection_name (	
key (	
content (:
content_type (2$.couchbase.kv.v1.DocumentContentType
cas (H/
expiry (2.google.protobuf.TimestampHG
legacy_durability_spec	 (2%.couchbase.kv.v1.LegacyDurabilitySpecH <
durability_level
 (2 .couchbase.kv.v1.DurabilityLevelH B
durability_specB
_casB	
_expiry"V
ReplaceResponse
cas (6
mutation_token (2.couchbase.kv.v1.MutationToken"
RemoveRequest
bucket_name (	

scope_name (	
collection_name (	
key (	
cas (HG
legacy_durability_spec (2%.couchbase.kv.v1.LegacyDurabilitySpecH <
durability_level (2 .couchbase.kv.v1.DurabilityLevelH B
durability_specB
_cas"U
RemoveResponse
cas (6
mutation_token (2.couchbase.kv.v1.MutationToken"è
IncrementRequest
bucket_name (	

scope_name (	
collection_name (	
key (	
delta (/
expiry (2.google.protobuf.TimestampH
initial (HG
legacy_durability_spec (2%.couchbase.kv.v1.LegacyDurabilitySpecH <
durability_level	 (2 .couchbase.kv.v1.DurabilityLevelH B
durability_specB	
_expiryB

_initial"i
IncrementResponse
cas (
content (6
mutation_token (2.couchbase.kv.v1.MutationToken"è
DecrementRequest
bucket_name (	

scope_name (	
collection_name (	
key (	
delta (/
expiry (2.google.protobuf.TimestampH
initial (HG
legacy_durability_spec (2%.couchbase.kv.v1.LegacyDurabilitySpecH <
durability_level	 (2 .couchbase.kv.v1.DurabilityLevelH B
durability_specB	
_expiryB

_initial"i
DecrementResponse
cas (
content (6
mutation_token (2.couchbase.kv.v1.MutationToken"£
AppendRequest
bucket_name (	

scope_name (	
collection_name (	
key (	
content (
cas (HG
legacy_durability_spec (2%.couchbase.kv.v1.LegacyDurabilitySpecH <
durability_level (2 .couchbase.kv.v1.DurabilityLevelH B
durability_specB
_cas"U
AppendResponse
cas (6
mutation_token (2.couchbase.kv.v1.MutationToken"€
PrependRequest
bucket_name (	

scope_name (	
collection_name (	
key (	
content (
cas (HG
legacy_durability_spec (2%.couchbase.kv.v1.LegacyDurabilitySpecH <
durability_level (2 .couchbase.kv.v1.DurabilityLevelH B
durability_specB
_cas"V
PrependResponse
cas (6
mutation_token (2.couchbase.kv.v1.MutationToken"
LookupInRequest
bucket_name (	

scope_name (	
collection_name (	
key (	4
specs (2%.couchbase.kv.v1.LookupInRequest.Spec:
flags (2&.couchbase.kv.v1.LookupInRequest.FlagsH ÷
SpecB
	operation (2/.couchbase.kv.v1.LookupInRequest.Spec.Operation
path (	?
flags (2+.couchbase.kv.v1.LookupInRequest.Spec.FlagsH %
Flags
xattr (H B
_xattr"+
	Operation
GET 

EXISTS	
COUNTB
_flags7
Flags
access_deleted (H B
_access_deletedB
_flags"
LookupInResponse5
specs (2&.couchbase.kv.v1.LookupInResponse.Spec
cas (;
Spec"
status (2.google.rpc.Status
content ("
MutateInRequest
bucket_name (	

scope_name (	
collection_name (	
key (	4
specs (2%.couchbase.kv.v1.MutateInRequest.SpecK
store_semantic (2..couchbase.kv.v1.MutateInRequest.StoreSemanticHG
legacy_durability_spec (2%.couchbase.kv.v1.LegacyDurabilitySpecH <
durability_level (2 .couchbase.kv.v1.DurabilityLevelH 
cas	 (H:
flags
 (2&.couchbase.kv.v1.MutateInRequest.FlagsH
SpecB
	operation (2/.couchbase.kv.v1.MutateInRequest.Spec.Operation
path (	
content (?
flags (2+.couchbase.kv.v1.MutateInRequest.Spec.FlagsH O
Flags
create_path (H 
xattr (HB
_create_pathB
_xattr"
	Operation

INSERT 

UPSERT
REPLACE

REMOVE
ARRAY_APPEND
ARRAY_PREPEND
ARRAY_INSERT
ARRAY_ADD_UNIQUE
COUNTERB
_flags7
Flags
access_deleted (H B
_access_deleted"4
StoreSemantic
REPLACE 

UPSERT

INSERTB
durability_specB
_store_semanticB
_casB
_flags"ž
MutateInResponse5
specs (2&.couchbase.kv.v1.MutateInResponse.Spec
cas (6
mutation_token (2.couchbase.kv.v1.MutationToken(
Spec
content (H B

_content"
RangeScanRequest
bucket_name (	

scope_name (	
collection_name (	
key_only	 (6
range (2\'.couchbase.kv.v1.RangeScanRequest.Range<
sampling (2*.couchbase.kv.v1.RangeScanRequest.SamplingU
snapshot_requirements (26.couchbase.kv.v1.RangeScanRequest.SnapshotRequirements[
Range
	start_key (	
end_key (	
inclusive_start (
inclusive_end ()
Sampling
seed (
samples (R
SnapshotRequirements
vb_uuid (
seqno (
check_seqno_exists ("ß
RangeScanResponse>
	documents (2+.couchbase.kv.v1.RangeScanResponse.Document
Document
key (	
content (H L
	meta_data (24.couchbase.kv.v1.RangeScanResponse.Document.MetaDataHñ
MetaData
flags (/
expiry (2.google.protobuf.TimestampH 
seqno (
cas (:
content_type (2$.couchbase.kv.v1.DocumentContentTypeB
compression_type (2(.couchbase.kv.v1.DocumentCompressionTypeB	
_expiryB

_contentB

_meta_data*\\
DurabilityLevel
MAJORITY "
MAJORITY_AND_PERSIST_TO_ACTIVE
PERSIST_TO_MAJORITY*8
DocumentContentType
UNKNOWN 

BINARY
JSON*/
DocumentCompressionType
NONE 

SNAPPY2 
KvB
Get.couchbase.kv.v1.GetRequest.couchbase.kv.v1.GetResponse" R
GetAndTouch#.couchbase.kv.v1.GetAndTouchRequest.couchbase.kv.v1.GetResponse" P

GetAndLock".couchbase.kv.v1.GetAndLockRequest.couchbase.kv.v1.GetResponse" K
Unlock.couchbase.kv.v1.UnlockRequest.couchbase.kv.v1.UnlockResponse" P

GetReplica".couchbase.kv.v1.GetReplicaRequest.couchbase.kv.v1.GetResponse" H
Touch.couchbase.kv.v1.TouchRequest.couchbase.kv.v1.TouchResponse" K
Exists.couchbase.kv.v1.ExistsRequest.couchbase.kv.v1.ExistsResponse" K
Insert.couchbase.kv.v1.InsertRequest.couchbase.kv.v1.InsertResponse" K
Upsert.couchbase.kv.v1.UpsertRequest.couchbase.kv.v1.UpsertResponse" N
Replace.couchbase.kv.v1.ReplaceRequest .couchbase.kv.v1.ReplaceResponse" K
Remove.couchbase.kv.v1.RemoveRequest.couchbase.kv.v1.RemoveResponse" T
	Increment!.couchbase.kv.v1.IncrementRequest".couchbase.kv.v1.IncrementResponse" T
	Decrement!.couchbase.kv.v1.DecrementRequest".couchbase.kv.v1.DecrementResponse" K
Append.couchbase.kv.v1.AppendRequest.couchbase.kv.v1.AppendResponse" N
Prepend.couchbase.kv.v1.PrependRequest .couchbase.kv.v1.PrependResponse" Q
LookupIn .couchbase.kv.v1.LookupInRequest!.couchbase.kv.v1.LookupInResponse" Q
MutateIn .couchbase.kv.v1.MutateInRequest!.couchbase.kv.v1.MutateInResponse" T
	RangeScan!.couchbase.kv.v1.RangeScanRequest".couchbase.kv.v1.RangeScanResponse" B»
\'com.couchbase.client.protostellar.kv.v1PZ8github.com/couchbase/goprotostellar/genproto/kv_v1;kv_v1Ê&Couchbase\\Protostellar\\Generated\\KV\\V1ê*Couchbase::Protostellar::Generated::KV::V1bproto3'
        , true);

        static::$is_initialized = true;
    }
}

