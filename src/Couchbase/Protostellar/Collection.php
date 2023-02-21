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

namespace Couchbase\Protostellar;

use Couchbase\BinaryCollectionInterface;
use Couchbase\CollectionInterface;
use Couchbase\Exception\CouchbaseException;
use Couchbase\Exception\InvalidArgumentException;
use Couchbase\ExistsOptions;
use Couchbase\ExistsResult;
use Couchbase\GetAllReplicasOptions;
use Couchbase\GetAndLockOptions;
use Couchbase\GetAndTouchOptions;
use Couchbase\GetOptions;
use Couchbase\GetResult;
use Couchbase\InsertOptions;
use Couchbase\LookupInMacro;
use Couchbase\LookupInOptions;
use Couchbase\LookupInResult;
use Couchbase\LookupInSpec;
use Couchbase\MutateInOptions;
use Couchbase\MutateInResult;
use Couchbase\MutateInSpec;
use Couchbase\MutationResult;
use Couchbase\Protostellar\Generated\KV\V1\LookupInRequest;
use Couchbase\Protostellar\Generated\KV\V1\MutateInRequest;
use Couchbase\Protostellar\Internal\SharedUtils;
use Couchbase\RemoveOptions;
use Couchbase\ReplaceOptions;
use Couchbase\Result;
use Couchbase\Protostellar\Generated\KV\V1\ExistsRequest;
use Couchbase\Protostellar\Generated\KV\V1\GetAndLockRequest;
use Couchbase\Protostellar\Generated\KV\V1\GetAndTouchRequest;
use Couchbase\Protostellar\Generated\KV\V1\GetRequest;
use Couchbase\Protostellar\Generated\KV\V1\InsertRequest;
use Couchbase\Protostellar\Generated\KV\V1\RemoveRequest;
use Couchbase\Protostellar\Generated\KV\V1\ReplaceRequest;
use Couchbase\Protostellar\Generated\KV\V1\TouchRequest;
use Couchbase\Protostellar\Generated\KV\V1\UnlockRequest;
use Couchbase\Protostellar\Generated\KV\V1\UpsertRequest;
use Couchbase\Protostellar\Internal\Client;
use Couchbase\Protostellar\Internal\KVConverter;
use Couchbase\TouchOptions;
use Couchbase\TranscoderFlags;
use Couchbase\UnlockOptions;
use Couchbase\UpsertOptions;
use DateTimeInterface;

use const Grpc\STATUS_OK;

class Collection implements CollectionInterface
{
    public const DEFAULT_NAME = "_default";

    public const DEFAULT_KV_TIMEOUT = 2.5e6;

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
     * @throws InvalidArgumentException
     */
    public function upsert(string $key, $document, UpsertOptions $options = null): MutationResult
    {
        [$encodedDocument, $contentType] = UpsertOptions::encodeDocument($options, $document);
        $contentType = KVConverter::convertTranscoderFlagsToGRPC((TranscoderFlags::decode($contentType))->dataFormat());
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
        $request = array_merge($request, KVConverter::convertUpsertOptions($exportedOptions));
        $pendingCall = $this->client->kv()->Upsert(new UpsertRequest($request), [], ['timeout' => $timeout]);
        [$res, $status] = $pendingCall->wait();
        if ($status->code !== STATUS_OK) {
            throw new ProtocolException("unable to upsert the key", $status);
        }
        return new MutationResult(
            [
            "id" => $key,
            "cas" => strval($res->getCas()),
            "mutationToken" =>
                [
                "bucketName" => $res->getMutationToken()->getBucketName(),
                "partitionId" => $res->getMutationToken()->getVbucketId(),
                "partitionUuid" => strval($res->getMutationToken()->getVbucketUuid()),
                "sequenceNumber" => strval($res->getMutationToken()->getSeqNo())
                ]
            ]
        );
    }

    /**
     * @throws ProtocolException
     * @throws InvalidArgumentException
     */
    public function insert(string $key, $document, InsertOptions $options = null): MutationResult
    {
        [$encodedDocument, $contentType] = InsertOptions::encodeDocument($options, $document);
        $contentType = KVConverter::convertTranscoderFlagsToGRPC((TranscoderFlags::decode($contentType))->dataFormat());
        $exportedOptions = InsertOptions::export($options);
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
        $request = array_merge($request, KVConverter::convertInsertOptions($exportedOptions));
        $pendingCall = $this->client->kv()->Insert(new InsertRequest($request), [], ['timeout' => $timeout]);
        [$res, $status] = $pendingCall->wait();
        if ($status->code !== STATUS_OK) {
            throw new ProtocolException("unable to insert the key", $status);
        }
        return new MutationResult(
            [
                "id" => $key,
                "cas" => strval($res->getCas()),
                "mutationToken" =>
                    [
                        "bucketName" => $res->getMutationToken()->getBucketName(),
                        "partitionId" => $res->getMutationToken()->getVbucketId(),
                        "partitionUuid" => strval($res->getMutationToken()->getVbucketUuid()),
                        "sequenceNumber" => strval($res->getMutationToken()->getSeqNo())
                    ]
            ]
        );
    }

    /**
     * @throws ProtocolException
     */
    public function replace(string $key, $document, ReplaceOptions $options = null): MutationResult
    {
        [$encodedDocument, $contentType] = ReplaceOptions::encodeDocument($options, $document);
        $contentType = KVConverter::convertTranscoderFlagsToGRPC((TranscoderFlags::decode($contentType))->dataFormat());
        $exportedOptions = ReplaceOptions::export($options);
        $request = [
            "bucket_name" => $this->bucketName,
            "scope_name" => $this->scopeName,
            "collection_name" => $this->name,
            "key" => $key,
            "content" => $encodedDocument,
            "content_type" => $contentType
        ];
        $timeout = isset($exportedOptions["timeoutMilliseconds"])
            ? $exportedOptions["timeoutMilliseconds"] * 1000
            : self::DEFAULT_KV_TIMEOUT;
        $request = array_merge($request, KVConverter::convertReplaceOptions($exportedOptions));
        $pendingCall = $this->client->kv()->Replace(new ReplaceRequest($request), [], ['timeout' => $timeout]);
        [$res, $status] = $pendingCall->wait();
        if ($status->code !== STATUS_OK) {
            throw new ProtocolException("unable to replace the key", $status);
        }
        return new MutationResult(
            [
                "id" => $key,
                "cas" => strval($res->getCas()),
                "mutationToken" =>
                    [
                        "bucketName" => $res->getMutationToken()->getBucketName(),
                        "partitionId" => $res->getMutationToken()->getVbucketId(),
                        "partitionUuid" => strval($res->getMutationToken()->getVbucketUuid()),
                        "sequenceNumber" => strval($res->getMutationToken()->getSeqNo())
                    ]
            ]
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
        $timeout = isset($exportedOptions["timeoutMilliseconds"])
            ? $exportedOptions["timeoutMilliseconds"] * 1000
            : self::DEFAULT_KV_TIMEOUT;
        $request = array_merge($request, KVConverter::convertRemoveOptions($exportedOptions));
        $pendingCall = $this->client->kv()->Remove(new RemoveRequest($request), [], ['timeout' => $timeout]);
        [$res, $status] = $pendingCall->wait();
        if ($status->code !== STATUS_OK) {
            throw new ProtocolException("unable to remove the key", $status);
        }
        return new MutationResult(
            [
                "id" => $key,
                "cas" => strval($res->getCas()),
                "mutationToken" =>
                    [
                        "bucketName" => $res->getMutationToken()->getBucketName(),
                        "partitionId" => $res->getMutationToken()->getVbucketId(),
                        "partitionUuid" => strval($res->getMutationToken()->getVbucketUuid()),
                        "sequenceNumber" => strval($res->getMutationToken()->getSeqNo())
                    ]
            ]
        );
    }

    /**
     * @throws ProtocolException
     * @throws InvalidArgumentException
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
        $timeout = isset($exportedOptions["timeoutMilliseconds"])
            ? $exportedOptions["timeoutMilliseconds"] * 1000
            : self::DEFAULT_KV_TIMEOUT;
        $pendingCall = $this->client->kv()->Get(new GetRequest($request), [], ['timeout' => $timeout]);
        [$res, $status] = $pendingCall->wait();
        if ($status->code !== STATUS_OK) {
            throw new ProtocolException("unable to get the key", $status);
        }
        $contentType = (new TranscoderFlags(
            KVConverter::convertTranscoderFlagsToClassic($res->getContentType()),
            $res->getCompressionType()
        ))->encode();
        return new GetResult(
            [
                "id" => $key,
                "cas" => strval($res->getCas()),
                "value" => $res->getContent(),
                "flags" => $contentType,
                "expiry" => $res->getExpiry()?->getSeconds(),
            ],
            GetOptions::getTranscoder($options)
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
        $timeout = isset($exportedOptions["timeoutMilliseconds"])
            ? $exportedOptions["timeoutMilliseconds"] * 1000
            : self::DEFAULT_KV_TIMEOUT;
        $pendingCall = $this->client->kv()->Exists(new ExistsRequest($request), [], ['timeout' => $timeout]);
        [$res, $status] = $pendingCall->wait();
        if ($status->code !== STATUS_OK) {
            throw new ProtocolException("unable to check if the key exists", $status);
        }
        return new ExistsResult( //TODO Other options in ExistsResult not returned by GRPC
            [
                "id" => $key,
                "cas" => strval($res->getCas()),
                "exists" => $res->getResult()
            ]
        );
    }

    /**
     * @throws ProtocolException
     * @throws InvalidArgumentException
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
        $timeout = isset($exportedOptions["timeoutMilliseconds"])
            ? $exportedOptions["timeoutMilliseconds"] * 1000
            : self::DEFAULT_KV_TIMEOUT;
        $pendingCall = $this->client->kv()->GetAndTouch(new GetAndTouchRequest($request), [], ['timeout' => $timeout]);
        [$res, $status] = $pendingCall->wait();
        if ($status->code !== STATUS_OK) {
            throw new ProtocolException("unable to getAndTouch the key", $status);
        }
        $contentType = (new TranscoderFlags(
            KVConverter::convertTranscoderFlagsToClassic($res->getContentType()),
            $res->getCompressionType()
        ))->encode();
        return new GetResult(
            [
                "id" => $key,
                "cas" => strval($res->getCas()),
                "value" => $res->getContent(),
                "flags" => $contentType,
                "compression_type" => $res->getCompressionType(),
                "expiry" => $res->getExpiry()?->getSeconds(),
            ],
            GetAndTouchOptions::getTranscoder($options)
        );
    }

    /**
     * @throws ProtocolException
     * @throws InvalidArgumentException
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
        $timeout = isset($exportedOptions["timeoutMilliseconds"])
            ? $exportedOptions["timeoutMilliseconds"] * 1000
            : self::DEFAULT_KV_TIMEOUT;
        $pendingCall = $this->client->kv()->GetAndLock(new GetAndLockRequest($request), [], ['timeout' => $timeout]);
        [$res, $status] = $pendingCall->wait();
        if ($status->code !== STATUS_OK) {
            throw new ProtocolException("unable to getAndLock the key", $status);
        }
        $contentType = (new TranscoderFlags(
            KVConverter::convertTranscoderFlagsToClassic($res->getContentType()),
            $res->getCompressionType()
        ))->encode();
        return new GetResult(
            [
                "id" => $key,
                "cas" => strval($res->getCas()),
                "value" => $res->getContent(),
                "flags" => $res->getContentType(),
                "compression_type" => $contentType,
                "expiry" => $res->getExpiry()?->getSeconds(),
            ],
            GetAndLockOptions::getTranscoder($options)
        );
    }

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
        $timeout = isset($exportedOptions["timeoutMilliseconds"])
            ? $exportedOptions["timeoutMilliseconds"] * 1000
            : self::DEFAULT_KV_TIMEOUT;
        $pendingCall = $this->client->kv()->Unlock(new UnlockRequest($request), [], ['timeout' => $timeout]);
        [, $status] = $pendingCall->wait();
        if ($status->code !== STATUS_OK) {
            throw new ProtocolException("unable to unlock the document", $status);
        }
        return new Result(
            [
                "id" => $key,
                "cas" => $cas,
            ]
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
        $timeout = isset($exportedOptions["timeoutMilliseconds"])
            ? $exportedOptions["timeoutMilliseconds"] * 1000
            : self::DEFAULT_KV_TIMEOUT;
        $pendingCall = $this->client->kv()->Touch(new TouchRequest($request), [], ['timeout' => $timeout]);
        [$res, $status] = $pendingCall->wait();
        if ($status->code !== STATUS_OK) {
            throw new ProtocolException("unable to touch the document", $status);
        }
        return new MutationResult(
            [
                "id" => $key,
                "cas" => strval($res->getCas()),
                "mutationToken" =>
                    [
                        "bucketName" => $res->getMutationToken()->getBucketName(),
                        "partitionId" => $res->getMutationToken()->getVbucketId(),
                        "partitionUuid" => strval($res->getMutationToken()->getVbucketUuid()),
                        "sequenceNumber" => strval($res->getMutationToken()->getSeqNo())
                    ]
            ]
        );
    }
    public function lookupIn(string $key, array $specs, LookupInOptions $options = null): LookupInResult
    {
        $encoded = array_map(
            function (LookupInSpec $item) {
                return $item->export();
            },
            $specs
        );
        if ($options != null && $options->needToFetchExpiry()) {
            $encoded[] = ['opcode' => 'get', 'isXattr' => true, 'path' => LookupInMacro::EXPIRY_TIME];
        }
        $exportedOptions = LookupInOptions::export($options);
        $specsReq = KVConverter::getLookupInSpec($encoded);
        $request = [
            "bucket_name" => $this->bucketName,
            "scope_name" => $this->scopeName,
            "collection_name" => $this->name,
            "key" => $key,
            "specs" => $specsReq
        ];
        $timeout = isset($exportedOptions["timeoutMilliseconds"])
            ? $exportedOptions["timeoutMilliseconds"] * 1000
            : self::DEFAULT_KV_TIMEOUT;
        $request = array_merge($request, KVConverter::convertLookupInOptions($exportedOptions));
        $pendingCall = $this->client->kv()->LookupIn(new LookupInRequest($request), [], ['timeout' => $timeout]);
        [$res, $status] = $pendingCall->wait();
        if ($status->code !== STATUS_OK) {
            throw new ProtocolException("unable to LookupIn the document", $status);
        }
        $fields = KVConverter::convertLookupInRes(SharedUtils::toArray($res->getSpecs()), $specsReq);
        return new LookupInResult(
            [
                "id" => $key,
                "cas" => strval($res->getCas()),
                "fields" => $fields
            ]
        );
    }

    /**
     * @throws InvalidArgumentException
     */
    public function mutateIn(string $key, array $specs, MutateInOptions $options = null): MutateInResult
    {
        $encoded = array_map(
            function (MutateInSpec $item) use ($options) {
                return $item->export($options);
            },
            $specs
        );
        $exportedOptions = MutateInOptions::export($options);
        $specsReq = KVConverter::getMutateInSpec($encoded);
        $request = [
            "bucket_name" => $this->bucketName,
            "scope_name" => $this->scopeName,
            "collection_name" => $this->name,
            "key" => $key,
            "specs" => $specsReq
        ];
        $timeout = isset($exportedOptions["timeoutMilliseconds"])
            ? $exportedOptions["timeoutMilliseconds"] * 1000
            : self::DEFAULT_KV_TIMEOUT;
        $request = array_merge($request, KVConverter::convertMutateInOptions($exportedOptions));
        $pendingCall = $this->client->kv()->MutateIn(new MutateInRequest($request), [], ['timeout' => $timeout]);
        [$res, $status] = $pendingCall->wait();
        if ($status->code !== STATUS_OK) {
            throw new ProtocolException("unable to mutateIn the document", $status);
        }
        $fields = KVConverter::ConvertMutateInRes(SharedUtils::toArray($res->getSpecs()), $specsReq);
        return new MutateInResult(
            [
                "id" => $key,
                "cas" => strval($res->getCas()),
                "mutationToken" =>
                    [
                        "bucketName" => $res->getMutationToken()->getBucketName(),
                        "partitionId" => $res->getMutationToken()->getVbucketId(),
                        "partitionUuid" => strval($res->getMutationToken()->getVbucketUuid()),
                        "sequenceNumber" => strval($res->getMutationToken()->getSeqNo())
                    ],
                "fields" => $fields,
                "deleted" => false //TODO: No Deleted flag from grpc response
            ]
        );
    }

    public function getAnyReplica(string $id, \Couchbase\GetAnyReplicaOptions $options = null): \Couchbase\GetReplicaResult
    {
        // TODO: Implement getAnyReplica() method.
        return new \Couchbase\GetReplicaResult();
    }

    public function getAllReplicas(string $id, GetAllReplicasOptions $options = null): array
    {
        // TODO: Implement getAllReplicas() method.
        return [];
    }

    public function bucketName(): string
    {
        return $this->bucketName;
    }

    public function scopeName(): string
    {
        return $this->scopeName;
    }

    public function binary(): BinaryCollectionInterface
    {
        return new BinaryCollection($this->client, $this->bucketName, $this->scopeName, $this->name);
    }
}
