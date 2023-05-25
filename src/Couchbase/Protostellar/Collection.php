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
use Couchbase\Protostellar\Internal\TimeoutHandler;
use Couchbase\Protostellar\Management\CollectionQueryIndexManager;
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
use Google\Protobuf\Timestamp;

class Collection implements CollectionInterface
{
    public const DEFAULT_NAME = "_default";

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
            "content_flags" => $contentType,
        ];
        $timeout = $this->client->timeoutHandler()->getTimeout(TimeoutHandler::KV, $exportedOptions);
        $request = array_merge($request, KVConverter::convertUpsertOptions($exportedOptions));
        $res = ProtostellarOperationRunner::runUnary(
            SharedUtils::createProtostellarRequest(new UpsertRequest($request), false, $timeout),
            [$this->client->kv(), 'Upsert']
        );
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
        $exportedOptions = InsertOptions::export($options);
        $request = [
            "bucket_name" => $this->bucketName,
            "scope_name" => $this->scopeName,
            "collection_name" => $this->name,
            "key" => $key,
            "content" => $encodedDocument,
            "content_flags" => $contentType
        ];
        $timeout = $this->client->timeoutHandler()->getTimeout(TimeoutHandler::KV, $exportedOptions);
        $request = array_merge($request, KVConverter::convertInsertOptions($exportedOptions));
        $res = ProtostellarOperationRunner::runUnary(
            SharedUtils::createProtostellarRequest(new InsertRequest($request), false, $timeout),
            [$this->client->kv(), 'Insert']
        );
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
        $exportedOptions = ReplaceOptions::export($options);
        $request = [
            "bucket_name" => $this->bucketName,
            "scope_name" => $this->scopeName,
            "collection_name" => $this->name,
            "key" => $key,
            "content" => $encodedDocument,
            "content_flags" => $contentType
        ];
        $timeout = $this->client->timeoutHandler()->getTimeout(TimeoutHandler::KV, $exportedOptions);
        $request = array_merge($request, KVConverter::convertReplaceOptions($exportedOptions));
        $res = ProtostellarOperationRunner::runUnary(
            SharedUtils::createProtostellarRequest(new ReplaceRequest($request), false, $timeout),
            [$this->client->kv(), 'Replace']
        );
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
        $timeout = $this->client->timeoutHandler()->getTimeout(TimeoutHandler::KV, $exportedOptions);
        $request = array_merge($request, KVConverter::convertRemoveOptions($exportedOptions));
        $res = ProtostellarOperationRunner::runUnary(
            SharedUtils::createProtostellarRequest(new RemoveRequest($request), false, $timeout),
            [$this->client->kv(), 'Remove']
        );
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

    public function get(string $key, GetOptions $options = null): GetResult
    {
        $exportedOptions = GetOptions::export($options);
        $request = [
            "bucket_name" => $this->bucketName,
            "scope_name" => $this->scopeName,
            "collection_name" => $this->name,
            "key" => $key,
        ];
        $timeout = $this->client->timeoutHandler()->getTimeout(TimeoutHandler::KV, $exportedOptions);
        $res = ProtostellarOperationRunner::runUnary(
            SharedUtils::createProtostellarRequest(new GetRequest($request), true, $timeout),
            [$this->client->kv(), 'Get']
        );
        return new GetResult(
            [
                "id" => $key,
                "cas" => strval($res->getCas()),
                "value" => $res->getContent(),
                "flags" => $res->getContentFlags(),
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
        $timeout = $this->client->timeoutHandler()->getTimeout(TimeoutHandler::KV, $exportedOptions);
        $res = ProtostellarOperationRunner::runUnary(
            SharedUtils::createProtostellarRequest(new ExistsRequest($request), true, $timeout),
            [$this->client->kv(), 'Exists']
        );
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
        $exportedOptions = GetAndTouchOptions::export($options);
        $request = [
            "bucket_name" => $this->bucketName,
            "scope_name" => $this->scopeName,
            "collection_name" => $this->name,
            "key" => $key,
        ];
        if ($expiry instanceof DateTimeInterface) {
            $expirySeconds = $expiry->getTimestamp();
            $request["expiry_time"] = new Timestamp(["seconds" => $expirySeconds]);
        } else {
            $expirySeconds = (int)$expiry;
            $request["expiry_secs"] = $expirySeconds;
        }
        $timeout = $this->client->timeoutHandler()->getTimeout(TimeoutHandler::KV, $exportedOptions);
        $res = ProtostellarOperationRunner::runUnary(
            SharedUtils::createProtostellarRequest(new GetAndTouchRequest($request), false, $timeout),
            [$this->client->kv(), 'GetAndTouch']
        );
        return new GetResult(
            [
                "id" => $key,
                "cas" => strval($res->getCas()),
                "value" => $res->getContent(),
                "flags" => $res->getContentFlags(),
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
        $timeout = $this->client->timeoutHandler()->getTimeout(TimeoutHandler::KV, $exportedOptions);
        $res = ProtostellarOperationRunner::runUnary(
            SharedUtils::createProtostellarRequest(new GetAndLockRequest($request), false, $timeout),
            [$this->client->kv(), 'GetAndLock']
        );
        return new GetResult(
            [
                "id" => $key,
                "cas" => strval($res->getCas()),
                "value" => $res->getContent(),
                "flags" => $res->getContentFlags(),
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
        $timeout = $this->client->timeoutHandler()->getTimeout(TimeoutHandler::KV, $exportedOptions);
        ProtostellarOperationRunner::runUnary(
            SharedUtils::createProtostellarRequest(new UnlockRequest($request), false, $timeout),
            [$this->client->kv(), 'Unlock']
        );
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
        $exportedOptions = TouchOptions::export($options);
        $request = [
            "bucket_name" => $this->bucketName,
            "scope_name" => $this->scopeName,
            "collection_name" => $this->name,
            "key" => $key,
        ];
        if ($expiry instanceof DateTimeInterface) {
            $expirySeconds = $expiry->getTimestamp();
            $request["expiry_time"] = new Timestamp(["seconds" => $expirySeconds]);
        } else {
            $expirySeconds = (int)$expiry;
            $request["expiry_secs"] = $expirySeconds;
        }
        $timeout = $this->client->timeoutHandler()->getTimeout(TimeoutHandler::KV, $exportedOptions);
        $res = ProtostellarOperationRunner::runUnary(
            SharedUtils::createProtostellarRequest(new TouchRequest($request), false, $timeout),
            [$this->client->kv(), 'Touch']
        );
        $resArr = KVConverter::convertTouchRes($key, $res);
        return new MutationResult($resArr);
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
        [$specsReq, $order] = KVConverter::getLookupInSpec($encoded);
        $request = [
            "bucket_name" => $this->bucketName,
            "scope_name" => $this->scopeName,
            "collection_name" => $this->name,
            "key" => $key,
            "specs" => $specsReq
        ];
        $timeout = $this->client->timeoutHandler()->getTimeout(TimeoutHandler::KV, $exportedOptions);
        $request = array_merge($request, KVConverter::convertLookupInOptions($exportedOptions));
        $res = ProtostellarOperationRunner::runUnary(
            SharedUtils::createProtostellarRequest(new LookupInRequest($request), true, $timeout),
            [$this->client->kv(), 'LookupIn']
        );

        $fields = KVConverter::convertLookupInRes(SharedUtils::toArray($res->getSpecs()), $specsReq, $order);
        return new LookupInResult(
            [
                "id" => $key,
                "cas" => strval($res->getCas()),
                "fields" => $fields
            ],
            LookupInOptions::getTranscoder($options)
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
        [$specsReq, $order] = KVConverter::getMutateInSpec($encoded);
        $request = [
            "bucket_name" => $this->bucketName,
            "scope_name" => $this->scopeName,
            "collection_name" => $this->name,
            "key" => $key,
            "specs" => $specsReq
        ];
        $timeout = $this->client->timeoutHandler()->getTimeout(TimeoutHandler::KV, $exportedOptions);
        $request = array_merge($request, KVConverter::convertMutateInOptions($exportedOptions));
        $res = ProtostellarOperationRunner::runUnary(
            SharedUtils::createProtostellarRequest(new MutateInRequest($request), false, $timeout),
            [$this->client->kv(), 'MutateIn']
        );
        $fields = KVConverter::convertMutateInRes(SharedUtils::toArray($res->getSpecs()), $specsReq, $order);
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
        return new GetReplicaResult();
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

    public function queryIndexes(): CollectionQueryIndexManager
    {
        return new CollectionQueryIndexManager($this->name, $this->scopeName, $this->bucketName, $this->client);
    }
}
