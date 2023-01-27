<?php

/*
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

declare(strict_types=1);

namespace Couchbase\StellarNebula;

use Couchbase\StellarNebula\Generated\KV\V1\ExistsRequest;
use Couchbase\StellarNebula\Generated\KV\V1\GetAndLockRequest;
use Couchbase\StellarNebula\Generated\KV\V1\GetAndTouchRequest;
use Couchbase\StellarNebula\Generated\KV\V1\GetRequest;
use Couchbase\StellarNebula\Generated\KV\V1\InsertRequest;
use Couchbase\StellarNebula\Generated\KV\V1\InsertResponse;
use Couchbase\StellarNebula\Generated\KV\V1\LegacyDurabilitySpec;
use Couchbase\StellarNebula\Generated\KV\V1\LookupInRequest;
use Couchbase\StellarNebula\Generated\KV\V1\MutateInRequest;
use Couchbase\StellarNebula\Generated\KV\V1\RemoveRequest;
use Couchbase\StellarNebula\Generated\KV\V1\RemoveResponse;
use Couchbase\StellarNebula\Generated\KV\V1\ReplaceRequest;
use Couchbase\StellarNebula\Generated\KV\V1\TouchRequest;
use Couchbase\StellarNebula\Generated\KV\V1\UnlockRequest;
use Couchbase\StellarNebula\Generated\KV\V1\UpsertRequest;
use Couchbase\StellarNebula\Internal\Client;
use DateTimeInterface;
use Google\Protobuf\Timestamp;

use const Grpc\STATUS_OK;

class Collection
{
    public const DEFAULT_NAME = "_default";
    public const DEFAULT_KV_TIMEOUT = 30000000;

    private Client $client;
    private string $bucketName;
    private string $scopeName;
    private string $name;

    public function __construct(Client $client, string $bucketName, string $scopeName, string $name)
    {
        $this->client = $client;
        $this->bucketName = $bucketName;
        $this->scopeName = $scopeName;
        $this->name = $name;
    }

    public function name(): string
    {
        return $this->name;
    }

    /**
     * @throws ProtocolException
     */
    public function upsert(string $key, $document, UpsertOptions $options = null): MutationResult
    {
        [$encodedDocument, $contentType] = UpsertOptions::encodeDocument($options, $document);
        $exportedOptions = UpsertOptions::export($options);
        $request = [
            "bucket_name" => $this->bucketName,
            "scope_name" => $this->scopeName,
            "collection_name" => $this->name,
            "key" => $key,
            "content" => $encodedDocument,
            "content_type" => $contentType,
        ];
        $timeout = isset($exportedOptions["timeoutMilliseconds"])
            ? $exportedOptions["timeoutMilliseconds"] * 1000
            : self::DEFAULT_KV_TIMEOUT;
        if (isset($exportedOptions["expirySeconds"])) {
            $request["expiry"] = new Timestamp(["seconds" => $exportedOptions["expirySeconds"]]);
        }
        if (isset($exportedOptions["durabilityLevel"])) {
            $request["durability_level"] = $this->convertDurabilityLevel($exportedOptions["durabilityLevel"]);
        }
        if (isset($exportedOptions["legacyDurability"])) {
            $request["legacy_durability_spec"] = $this->convertLegacyDurability($exportedOptions["legacyDurability"]);
        }
        $pendingCall = $this->client->kv()->Upsert(new UpsertRequest($request), [], ['timeout' => $timeout]);
        [$res, $status] = $pendingCall->wait();
        if ($status->code !== STATUS_OK) {
            throw new ProtocolException("unable to upsert the key", $status);
        }
        return new MutationResult(
            $this->bucketName,
            $this->scopeName,
            $this->name,
            $key,
            $res->getCas(),
            new MutationToken(
                $res->getMutationToken()->getBucketName(),
                $res->getMutationToken()->getVbucketId(),
                $res->getMutationToken()->getVbucketUuid(),
                $res->getMutationToken()->getSeqNo()
            )
        );
    }

    public function getRequest(string $key, $document, UpsertOptions $options = null): array
    {
        [$encodedDocument, $contentType] = UpsertOptions::encodeDocument($options, $document);
        $exportedOptions = UpsertOptions::export($options);
        $request = [
            "bucket_name" => $this->bucketName,
            "scope_name" => $this->scopeName,
            "collection_name" => $this->name,
            "key" => $key,
            "content" => $encodedDocument,
            "content_type" => $contentType,
        ];
        $timeout = array_key_exists("timeoutMilliseconds", $exportedOptions)
            ? $exportedOptions["timeoutMilliseconds"] * 1000
            : self::DEFAULT_KV_TIMEOUT;
        if (array_key_exists("expirySeconds", $exportedOptions)) {
            $request["expiry"] = new Timestamp(["seconds" => $exportedOptions["expirySeconds"]]);
        }
        if (array_key_exists("durabilityLevel", $exportedOptions)) {
            $request["durability_level"] = $this->convertDurabilityLevel($exportedOptions["durabilityLevel"]);
        }
        if (array_key_exists("legacyDurability", $exportedOptions)) {
            $request["legacy_durability_spec"] = $this->convertLegacyDurability($exportedOptions["legacyDurability"]);
        }
        return $request;
    }

    /**
     * @throws ProtocolException
     */
    public function insert(string $key, $document, InsertOptions $options = null): MutationResult
    {
        [$encodedDocument, $contentType] = InsertOptions::encodeDocument($options, $document);
        $exportedOptions = InsertOptions::export($options);
        $request = [
            "bucket_name" => $this->bucketName,
            "scope_name" => $this->scopeName,
            "collection_name" => $this->name,
            "key" => $key,
            "content" => $encodedDocument,
            "content_type" => $contentType,
        ];
        $timeout = array_key_exists("timeoutMilliseconds", $exportedOptions)
            ? $exportedOptions["timeoutMilliseconds"] * 1000
            : self::DEFAULT_KV_TIMEOUT;
        if (array_key_exists("expirySeconds", $exportedOptions)) {
            $request["expiry"] = new Timestamp(["seconds" => $exportedOptions["expirySeconds"]]);
        }
        if (array_key_exists("durabilityLevel", $exportedOptions)) {
            $request["durability_level"] = $this->convertDurabilityLevel($exportedOptions["durabilityLevel"]);
        }
        if (array_key_exists("legacyDurability", $exportedOptions)) {
            $request["legacy_durability_spec"] = $this->convertLegacyDurability($exportedOptions["legacyDurability"]);
        }
        $pendingCall = $this->client->kv()->Insert(new InsertRequest($request), [], ['timeout' => $timeout]);
        [$res, $status] = $pendingCall->wait();
        if ($status->code !== STATUS_OK) {
            throw new ProtocolException("unable to insert the key", $status);
        }
        return new MutationResult(
            $this->bucketName,
            $this->scopeName,
            $this->name,
            $key,
            $res->getCas(),
            new MutationToken(
                $res->getMutationToken()->getBucketName(),
                $res->getMutationToken()->getVbucketId(),
                $res->getMutationToken()->getVbucketUuid(),
                $res->getMutationToken()->getSeqNo()
            )
        );
    }

    /**
     * @throws ProtocolException
     */
    public function replace(string $key, $document, ReplaceOptions $options = null): MutationResult
    {
        [$encodedDocument, $contentType] = ReplaceOptions::encodeDocument($options, $document);
        $exportedOptions = InsertOptions::export($options);
        $request = [
            "bucket_name" => $this->bucketName,
            "scope_name" => $this->scopeName,
            "collection_name" => $this->name,
            "key" => $key,
            "content" => $encodedDocument,
            "content_type" => $contentType,
        ];
        $timeout = array_key_exists("timeoutMilliseconds", $exportedOptions)
            ? $exportedOptions["timeoutMilliseconds"] * 1000
            : self::DEFAULT_KV_TIMEOUT;
        if (array_key_exists("expirySeconds", $exportedOptions)) {
            $request["expiry"] = new Timestamp(["seconds" => $exportedOptions["expirySeconds"]]);
        }
        if (array_key_exists("cas", $exportedOptions)) {
            $request["cas"] = $exportedOptions["cas"];
        }
        if (array_key_exists("durabilityLevel", $exportedOptions)) {
            $request["durability_level"] = $this->convertDurabilityLevel($exportedOptions["durabilityLevel"]);
        }
        if (array_key_exists("legacyDurability", $exportedOptions)) {
            $request["legacy_durability_spec"] = $this->convertLegacyDurability($exportedOptions["legacyDurability"]);
        }
        $pendingCall = $this->client->kv()->Replace(new ReplaceRequest($request), [], ['timeout' => $timeout]);
        [$res, $status] = $pendingCall->wait();
        if ($status->code !== STATUS_OK) {
            throw new ProtocolException("unable to replace the key", $status);
        }
        return new MutationResult(
            $this->bucketName,
            $this->scopeName,
            $this->name,
            $key,
            $res->getCas(),
            new MutationToken(
                $res->getMutationToken()->getBucketName(),
                $res->getMutationToken()->getVbucketId(),
                $res->getMutationToken()->getVbucketUuid(),
                $res->getMutationToken()->getSeqNo()
            )
        );
    }

    /**
     * @throws ProtocolException
     */
    public function remove(string $key, RemoveOptions $options = null): MutationResult
    {
        $exportedOptions = RemoveOptions::export($options);
        $request = [
            "bucket_name" => $this->bucketName,
            "scope_name" => $this->scopeName,
            "collection_name" => $this->name,
            "key" => $key,
        ];
        $timeout = array_key_exists("timeoutMilliseconds", $exportedOptions)
            ? $exportedOptions["timeoutMilliseconds"] * 1000
            : self::DEFAULT_KV_TIMEOUT;
        if (array_key_exists("cas", $exportedOptions)) {
            $request["cas"] = $exportedOptions["cas"];
        }
        if (array_key_exists("durabilityLevel", $exportedOptions)) {
            $request["durability_level"] = $this->convertDurabilityLevel($exportedOptions["durabilityLevel"]);
        }
        if (array_key_exists("legacyDurability", $exportedOptions)) {
            $request["legacy_durability_spec"] = $this->convertLegacyDurability($exportedOptions["legacyDurability"]);
        }
        $pendingCall = $this->client->kv()->Remove(new RemoveRequest($request), [], ['timeout' => $timeout]);
        [$res, $status] = $pendingCall->wait();
        if ($status->code !== STATUS_OK) {
            throw new ProtocolException("unable to remove the key", $status);
        }
        return new MutationResult(
            $this->bucketName,
            $this->scopeName,
            $this->name,
            $key,
            $res->getCas(),
            new MutationToken(
                $res->getMutationToken()->getBucketName(),
                $res->getMutationToken()->getVbucketId(),
                $res->getMutationToken()->getVbucketUuid(),
                $res->getMutationToken()->getSeqNo()
            )
        );
    }

    /**
     * @throws ProtocolException
     */
    public function get(string $key, GetOptions $options = null): GetResult
    {
        $exportedOptions = GetOptions::export($options);
        $request = [
            "bucket_name" => $this->bucketName,
            "scope_name" => $this->scopeName,
            "collection_name" => $this->name,
            "key" => $key,
        ];
        $timeout = array_key_exists("timeoutMilliseconds", $exportedOptions)
            ? $exportedOptions["timeoutMilliseconds"] * 1000
            : self::DEFAULT_KV_TIMEOUT;
        $pendingCall = $this->client->kv()->Get(new GetRequest($request), [], ['timeout' => $timeout]);
        [$res, $status] = $pendingCall->wait();
        if ($status->code !== STATUS_OK) {
            throw new ProtocolException("unable to get the key", $status);
        }
        return new GetResult(
            $this->bucketName,
            $this->scopeName,
            $this->name,
            $key,
            $res->getCas(),
            GetOptions::getTranscoder($options),
            $res->getContent(),
            $res->getContentType(),
            $res->getCompressionType(),
            $res->getExpiry()?->getSeconds(),
        );
    }

    /**
     * @throws ProtocolException
     */
    public function exists(string $key, ExistsOptions $options = null): ExistsResult
    {
        $exportedOptions = ExistsOptions::export($options);
        $request = [
            "bucket_name" => $this->bucketName,
            "scope_name" => $this->scopeName,
            "collection_name" => $this->name,
            "key" => $key,
        ];
        $timeout = array_key_exists("timeoutMilliseconds", $exportedOptions)
            ? $exportedOptions["timeoutMilliseconds"] * 1000
            : self::DEFAULT_KV_TIMEOUT;
        $pendingCall = $this->client->kv()->Exists(new ExistsRequest($request), [], ['timeout' => $timeout]);
        [$res, $status] = $pendingCall->wait();
        if ($status->code !== STATUS_OK) {
            throw new ProtocolException("unable to check if the key exists", $status);
        }
        return new ExistsResult(
            $this->bucketName,
            $this->scopeName,
            $this->name,
            $key,
            $res->getCas(),
            $res->getResult(),
        );
    }

    /**
     * @throws ProtocolException
     */
    public function getAndTouch(string $key, $expiry, GetAndTouchOptions $options = null): GetResult
    {
        if ($expiry instanceof DateTimeInterface) {
            $expirySeconds = $expiry->getTimestamp();
        } else {
            $expirySeconds = (int)$expiry;
        }
        $exportedOptions = GetAndTouchOptions::export($options);
        $request = [
            "bucket_name" => $this->bucketName,
            "scope_name" => $this->scopeName,
            "collection_name" => $this->name,
            "key" => $key,
            "expiry" => $expirySeconds
        ];
        $timeout = array_key_exists("timeoutMilliseconds", $exportedOptions)
            ? $exportedOptions["timeoutMilliseconds"] * 1000
            : self::DEFAULT_KV_TIMEOUT;
        $pendingCall = $this->client->kv()->GetAndTouch(new GetAndTouchRequest($request), [], ['timeout' => $timeout]);
        [$res, $status] = $pendingCall->wait();
        if ($status->code !== STATUS_OK) {
            throw new ProtocolException("unable to getAndTouch the key", $status);
        }
        return new GetResult(
            $this->bucketName,
            $this->scopeName,
            $this->name,
            $key,
            $res->getCas(),
            GetOptions::getTranscoder($options),
            $res->getContent(),
            $res->getContentType(),
            $res->getCompressionType(),
            $res->getExpiry()?->getSeconds(),
        );
    }

    /**
     * @throws ProtocolException
     */
    public function getAndLock(string $key, int $lockTimeSeconds, GetAndLockOptions $options = null): GetResult
    {
        $exportedOptions = GetAndLockOptions::export($options);
        $request = [
            "bucket_name" => $this->bucketName,
            "scope_name" => $this->scopeName,
            "collection_name" => $this->name,
            "key" => $key,
            "lock_time" => $lockTimeSeconds
        ];
        $timeout = array_key_exists("timeoutMilliseconds", $exportedOptions)
            ? $exportedOptions["timeoutMilliseconds"] * 1000
            : self::DEFAULT_KV_TIMEOUT;
        $pendingCall = $this->client->kv()->GetAndLock(new GetAndLockRequest($request), [], ['timeout' => $timeout]);
        [$res, $status] = $pendingCall->wait();
        if ($status->code !== STATUS_OK) {
            throw new ProtocolException("unable to getAndLock the key", $status);
        }
        return new GetResult(
            $this->bucketName,
            $this->scopeName,
            $this->name,
            $key,
            $res->getCas(),
            GetOptions::getTranscoder($options),
            $res->getContent(),
            $res->getContentType(),
            $res->getCompressionType(),
            $res->getExpiry()?->getSeconds(),
        );
    }

//    public function getAnyReplica(string $key, GetAnyReplicaOptions $options = null): GetReplicaResult
//    {
//        $exportedOptions = GetAnyReplicaOptions::export($options);
//        $request = [
//            "bucket_name" => $this->bucketName,
//            "scope_name" => $this->scopeName,
//            "collection_name" => $this->name,
//            "key" => $key
//        ];
//        $timeout = array_key_exists("timeoutMilliseconds", $exportedOptions)
//            ? $exportedOptions["timeoutMilliseconds"] * 1000
//            : self::DEFAULT_KV_TIMEOUT;
//
//
//    }
//
//    public function getAllReplicas(string $key, GetAllReplicasOptions $options = null): array
//    {
//
//    }

    /**
     * @throws ProtocolException
     */
    public function unlock(string $key, string $cas, UnlockOptions $options = null): Result
    {
        $exportedOptions = UnlockOptions::export($options);
        $request = [
            "bucket_name" => $this->bucketName,
            "scope_name" => $this->scopeName,
            "collection_name" => $this->name,
            "key" => $key,
            "cas" => $cas,
        ];
        $timeout = array_key_exists("timeoutMilliseconds", $exportedOptions)
            ? $exportedOptions["timeoutMilliseconds"] * 1000
            : self::DEFAULT_KV_TIMEOUT;
        $pendingCall = $this->client->kv()->Unlock(new UnlockRequest($request), [], ['timeout' => $timeout]);
        [$res, $status] = $pendingCall->wait();
        if ($status->code !== STATUS_OK) {
            throw new ProtocolException("unable to unlock the document", $status);
        }
        return new Result(
            $this->bucketName,
            $this->scopeName,
            $this->name,
            $key,
            $cas,
        );
    }

    /**
     * @throws ProtocolException
     */
    public function touch(string $key, $expiry, TouchOptions $options = null): MutationResult
    {
        if ($expiry instanceof DateTimeInterface) {
            $expirySeconds = $expiry->getTimestamp();
        } else {
            $expirySeconds = (int)$expiry;
        }
        $exportedOptions = TouchOptions::export($options);
        $request = [
            "bucket_name" => $this->bucketName,
            "scope_name" => $this->scopeName,
            "collection_name" => $this->name,
            "key" => $key,
            "expiry" => $expirySeconds
        ];
        $timeout = array_key_exists("timeoutMilliseconds", $exportedOptions)
            ? $exportedOptions["timeoutMilliseconds"] * 1000
            : self::DEFAULT_KV_TIMEOUT;
        $pendingCall = $this->client->kv()->Touch(new TouchRequest($request), [], ['timeout' => $timeout]);
        [$res, $status] = $pendingCall->wait();
        if ($status->code !== STATUS_OK) {
            throw new ProtocolException("unable to unlock the document", $status);
        }
        return new MutationResult(
            $this->bucketName,
            $this->scopeName,
            $this->name,
            $key,
            $res->getCas(),
            new MutationToken(
                $res->getMutationToken()->getBucketName(),
                $res->getMutationToken()->getVbucketId(),
                $res->getMutationToken()->getVbucketUuid(),
                $res->getMutationToken()->getSeqNo()
            )
        );
    }

    /**
     * @throws ProtocolException
     */
    public function lookupIn(string $key, array $specs, LookupInOptions $options = null): LookupInResult
    {
        //TODO: Proto does not take expiry unlike old API, and no option for GetFullSpecs, access deleted flag?
        //TODO: Proto response is very different to old API, need to see how to map it
        $encoded = array_map(
            function (LookupInSpec $item) {
                return $item->export();
            },
            $specs
        );
        $exportedOptions = LookupInOptions::export($options);
        $request = [
            "bucket_name" => $this->bucketName,
            "scope_name" => $this->scopeName,
            "collection_name" => $this->name,
            "key" => $key,
            "specs" => $encoded,
        ];
        $timeout = array_key_exists("timeoutMilliseconds", $exportedOptions)
            ? $exportedOptions["timeoutMilliseconds"] * 1000
            : self::DEFAULT_KV_TIMEOUT;
        $pendingCall = $this->client->kv()->LookupIn(new LookupInRequest($request), [], ['timeout' => $timeout]);
        [$res, $status] = $pendingCall->wait();
        if ($status->code !== STATUS_OK) {
            throw new ProtocolException("unable to unlock the document", $status);
        }
        return new LookupInResult(
            $this->bucketName,
            $this->scopeName,
            $this->name,
            $key,
            $res->getCas(),
            $res->getSpecs()->getContent(),
            LookupInOptions::getTranscoder($options)
        );
    }

    /**
     * @throws ProtocolException
     */
    public function mutateIn(string $key, array $specs, MutateInOptions $options = null): MutateInResult
    {
        //TODO; Expiry/preserveExpiry in Options?
        $encoded = array_map(
            function (MutateInSpec $item) use ($options) {
                return $item->export($options);
            },
            $specs
        );
        $exportedOptions = LookupInOptions::export($options);
        $request = [
            "bucket_name" => $this->bucketName,
            "scope_name" => $this->scopeName,
            "collection_name" => $this->name,
            "key" => $key,
            "specs" => $encoded,
        ];
        $timeout = array_key_exists("timeoutMilliseconds", $exportedOptions)
            ? $exportedOptions["timeoutMilliseconds"] * 1000
            : self::DEFAULT_KV_TIMEOUT;
        if (array_key_exists("cas", $exportedOptions)) {
            $request["cas"] = $exportedOptions["cas"];
        }
        if (array_key_exists("durabilityLevel", $exportedOptions)) {
            $request["durability_level"] = $this->convertDurabilityLevel($exportedOptions["durabilityLevel"]);
        }
        if (array_key_exists("legacyDurability", $exportedOptions)) {
            $request["legacy_durability_spec"] = $this->convertLegacyDurability($exportedOptions["legacyDurability"]);
        }
        if (array_key_exists("store_semantic", $exportedOptions)) {
            $request["store_semantic"] = $this->convertLegacyDurability($exportedOptions["store_semantic"]);
        }
        $pendingCall = $this->client->kv()->MutateIn(new MutateInRequest($request), [], ['timeout' => $timeout]);
        [$res, $status] = $pendingCall->wait();
        if ($status->code !== STATUS_OK) {
            throw new ProtocolException("unable to MutateIn the document", $status);
        }
        return new MutateInResult(
            $this->bucketName,
            $this->scopeName,
            $this->name,
            $key,
            $res->getCas(),
            new MutationToken(
                $res->getMutationToken()->getBucketName(),
                $res->getMutationToken()->getVbucketId(),
                $res->getMutationToken()->getVbucketUuid(),
                $res->getMutationToken()->getSeqNo()
            ),
            $res->getSpecs()->getContent()
        );
    }

//    public function scan(ScanType $scanType, ScanOptions $options): ScanResult
//    {
//        $exportedOptions = ScanOptions::export($options);
//        $request = [
//            "bucket_name" => $this->bucketName,
//            "scope_name" => $this->scopeName,
//            "collection_name" => $this->name,
//        ];
//        if (array_key_exists("idsOnly", $exportedOptions)) {
//            $request["key_only"] = $exportedOptions["idsOnly"];
//        }
//        if ($scanType instanceof RangeScan) {
//            $range = [];
//            $range["start_key"] = $scanType->getStart()->getTerm();
//            $range["end_key"] = $scanType->getEnd()->getTerm();
//            $range["inclusive_start"] = !$scanType->getStart()->getExclusive();
//            $range["inclusive_end"] = !$scanType->getEnd()->getExclusive();
//            $request['range'] = $range;
//        } elseif ($scanType instanceof SamplingScan) {
//            $sampling = [];
//            $sampling["seed"] = $scanType->getSeed();
//            $sampling["samples"] = $scanType->getLimit();
//            $request['sampling'] = $sampling;
//        }
//
//
//    }

    public static function convertDurabilityLevel(string $durabilityLevel): ?int
    {
        if ($durabilityLevel === DurabilityLevel::MAJORITY) {
            return \Couchbase\StellarNebula\Generated\KV\V1\DurabilityLevel::MAJORITY;
        }
        if ($durabilityLevel === DurabilityLevel::MAJORITY_AND_PERSIST_TO_ACTIVE) {
            return \Couchbase\StellarNebula\Generated\KV\V1\DurabilityLevel::MAJORITY_AND_PERSIST_TO_ACTIVE;
        }
        if ($durabilityLevel === DurabilityLevel::PERSIST_TO_MAJORITY) {
            return \Couchbase\StellarNebula\Generated\KV\V1\DurabilityLevel::PERSIST_TO_MAJORITY;
        }
        return null;
    }

    public static function convertLegacyDurability(?array $legacyDurability): ?LegacyDurabilitySpec
    {
        if ($legacyDurability == null) {
            return null;
        }
        return new LegacyDurabilitySpec(
            [
                "num_replicated" => $legacyDurability["replicateTo"],
                "num_persisted" => $legacyDurability["persistTo"],
            ]
        );
    }
}
