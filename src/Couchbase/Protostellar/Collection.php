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
use Couchbase\Exception\InvalidArgumentException;
use Couchbase\ExistsOptions;
use Couchbase\ExistsResult;
use Couchbase\GetAllReplicasOptions;
use Couchbase\GetAndLockOptions;
use Couchbase\GetAndTouchOptions;
use Couchbase\GetOptions;
use Couchbase\GetReplicaResult;
use Couchbase\GetResult;
use Couchbase\InsertOptions;
use Couchbase\LookupInOptions;
use Couchbase\LookupInResult;
use Couchbase\MutateInOptions;
use Couchbase\MutateInResult;
use Couchbase\MutationResult;
use Couchbase\Protostellar\Generated\KV\V1\LookupInRequest;
use Couchbase\Protostellar\Generated\KV\V1\MutateInRequest;
use Couchbase\Protostellar\Internal\KVRequestConverter;
use Couchbase\Protostellar\Internal\KVResponseConverter;
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
use Couchbase\TouchOptions;
use Couchbase\UnlockOptions;
use Couchbase\UpsertOptions;

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

    /**
     * @throws InvalidArgumentException
     */
    public function upsert(string $key, $document, UpsertOptions $options = null): MutationResult
    {
        $exportedOptions = UpsertOptions::export($options);
        $request = KVRequestConverter::getUpsertRequest(
            $key,
            $document,
            $options,
            KVRequestConverter::getLocation($this->bucketName, $this->scopeName, $this->name)
        );
        $timeout = $this->client->timeoutHandler()->getTimeout(TimeoutHandler::KV, $exportedOptions);
        $res = ProtostellarOperationRunner::runUnary(
            SharedUtils::createProtostellarRequest(new UpsertRequest($request), false, $timeout),
            [$this->client->kv(), 'Upsert']
        );
        return KVResponseConverter::convertMutationResult($key, $res);
    }

    /**
     * @throws InvalidArgumentException
     */
    public function insert(string $key, $document, InsertOptions $options = null): MutationResult
    {
        $exportedOptions = InsertOptions::export($options);
        $request = KVRequestConverter::getInsertRequest(
            $key,
            $document,
            $options,
            KVRequestConverter::getLocation($this->bucketName, $this->scopeName, $this->name)
        );
        $timeout = $this->client->timeoutHandler()->getTimeout(TimeoutHandler::KV, $exportedOptions);
        $res = ProtostellarOperationRunner::runUnary(
            SharedUtils::createProtostellarRequest(new InsertRequest($request), false, $timeout),
            [$this->client->kv(), 'Insert']
        );
        return KVResponseConverter::convertMutationResult($key, $res);
    }

    /**
     * @throws InvalidArgumentException
     */
    public function replace(string $key, $document, ReplaceOptions $options = null): MutationResult
    {
        $exportedOptions = ReplaceOptions::export($options);
        $request = KVRequestConverter::getReplaceRequest(
            $key,
            $document,
            $options,
            KVRequestConverter::getLocation($this->bucketName, $this->scopeName, $this->name)
        );
        $timeout = $this->client->timeoutHandler()->getTimeout(TimeoutHandler::KV, $exportedOptions);
        $res = ProtostellarOperationRunner::runUnary(
            SharedUtils::createProtostellarRequest(new ReplaceRequest($request), false, $timeout),
            [$this->client->kv(), 'Replace']
        );
        return KVResponseConverter::convertMutationResult($key, $res);
    }

    /**
     * @throws InvalidArgumentException
     */
    public function remove(string $key, RemoveOptions $options = null): MutationResult
    {
        $exportedOptions = RemoveOptions::export($options);
        $request = KVRequestConverter::getRemoveRequest(
            $key,
            $exportedOptions,
            KVRequestConverter::getLocation($this->bucketName, $this->scopeName, $this->name)
        );
        $timeout = $this->client->timeoutHandler()->getTimeout(TimeoutHandler::KV, $exportedOptions);
        $res = ProtostellarOperationRunner::runUnary(
            SharedUtils::createProtostellarRequest(new RemoveRequest($request), false, $timeout),
            [$this->client->kv(), 'Remove']
        );
        return KVResponseConverter::convertMutationResult($key, $res);
    }

    public function get(string $key, GetOptions $options = null): GetResult
    {
        $exportedOptions = GetOptions::export($options);
        $request = KVRequestConverter::getGetRequest(
            $key,
            $exportedOptions,
            KVRequestConverter::getLocation($this->bucketName, $this->scopeName, $this->name)
        );
        $timeout = $this->client->timeoutHandler()->getTimeout(TimeoutHandler::KV, $exportedOptions);
        $res = ProtostellarOperationRunner::runUnary(
            SharedUtils::createProtostellarRequest(new GetRequest($request), true, $timeout),
            [$this->client->kv(), 'Get']
        );
        return KVResponseConverter::convertGetResult($key, $options, $res);
    }

    public function exists(string $key, ExistsOptions $options = null): ExistsResult
    {
        $exportedOptions = ExistsOptions::export($options);
        $request = KVRequestConverter::getExistsRequest(
            $key,
            KVRequestConverter::getLocation($this->bucketName, $this->scopeName, $this->name)
        );
        $timeout = $this->client->timeoutHandler()->getTimeout(TimeoutHandler::KV, $exportedOptions);
        $res = ProtostellarOperationRunner::runUnary(
            SharedUtils::createProtostellarRequest(new ExistsRequest($request), true, $timeout),
            [$this->client->kv(), 'Exists']
        );
        return KVResponseConverter::convertExistsResult($key, $res);
    }

    /**
     * @throws ProtocolException
     * @throws InvalidArgumentException
     */
    public function getAndTouch(string $key, $expiry, GetAndTouchOptions $options = null): GetResult
    {
        $exportedOptions = GetAndTouchOptions::export($options);
        $request = KVRequestConverter::getGetAndTouchRequest(
            $key,
            $expiry,
            KVRequestConverter::getLocation($this->bucketName, $this->scopeName, $this->name)
        );
        $timeout = $this->client->timeoutHandler()->getTimeout(TimeoutHandler::KV, $exportedOptions);
        $res = ProtostellarOperationRunner::runUnary(
            SharedUtils::createProtostellarRequest(new GetAndTouchRequest($request), false, $timeout),
            [$this->client->kv(), 'GetAndTouch']
        );
        return KVResponseConverter::convertGetAndTouchResult($key, $options, $res);
    }

    /**
     * @throws ProtocolException
     * @throws InvalidArgumentException
     */
    public function getAndLock(string $key, int $lockTimeSeconds, GetAndLockOptions $options = null): GetResult
    {
        $exportedOptions = GetAndLockOptions::export($options);
        $request = KVRequestConverter::getGetAndLockRequest(
            $key,
            $lockTimeSeconds,
            KVRequestConverter::getLocation($this->bucketName, $this->scopeName, $this->name)
        );
        $timeout = $this->client->timeoutHandler()->getTimeout(TimeoutHandler::KV, $exportedOptions);
        $res = ProtostellarOperationRunner::runUnary(
            SharedUtils::createProtostellarRequest(new GetAndLockRequest($request), false, $timeout),
            [$this->client->kv(), 'GetAndLock']
        );
        return KVResponseConverter::convertGetAndLockResult($key, $res, $options);
    }

    /**
     * @throws ProtocolException
     */
    public function unlock(string $key, string $cas, UnlockOptions $options = null): Result
    {
        $exportedOptions = UnlockOptions::export($options);
        $request = KVRequestConverter::getUnlockRequest(
            $key,
            $cas,
            KVRequestConverter::getLocation($this->bucketName, $this->scopeName, $this->name)
        );
        $timeout = $this->client->timeoutHandler()->getTimeout(TimeoutHandler::KV, $exportedOptions);
        ProtostellarOperationRunner::runUnary(
            SharedUtils::createProtostellarRequest(new UnlockRequest($request), false, $timeout),
            [$this->client->kv(), 'Unlock']
        );
        return KVResponseConverter::convertUnlockResult($key, $cas);
    }

    public function touch(string $key, $expiry, TouchOptions $options = null): MutationResult
    {
        $exportedOptions = TouchOptions::export($options);
        $request = KVRequestConverter::getTouchRequest(
            $key,
            $expiry,
            KVRequestConverter::getLocation($this->bucketName, $this->scopeName, $this->name)
        );
        $timeout = $this->client->timeoutHandler()->getTimeout(TimeoutHandler::KV, $exportedOptions);
        $res = ProtostellarOperationRunner::runUnary(
            SharedUtils::createProtostellarRequest(new TouchRequest($request), false, $timeout),
            [$this->client->kv(), 'Touch']
        );
        return KVResponseConverter::convertMutationResult($key, $res);
    }

    public function lookupIn(string $key, array $specs, LookupInOptions $options = null): LookupInResult
    {
        $exportedOptions = LookupInOptions::export($options);
        [$request, $order] = KVRequestConverter::getLookupInRequest(
            $key,
            $specs,
            KVRequestConverter::getLocation($this->bucketName, $this->scopeName, $this->name),
            $options
        );
        $timeout = $this->client->timeoutHandler()->getTimeout(TimeoutHandler::KV, $exportedOptions);
        $res = ProtostellarOperationRunner::runUnary(
            SharedUtils::createProtostellarRequest(new LookupInRequest($request), true, $timeout),
            [$this->client->kv(), 'LookupIn']
        );
        return KVResponseConverter::convertLookupInResult($key, $res, $request['specs'], $order, $options);
    }

    /**
     * @throws InvalidArgumentException
     */
    public function mutateIn(string $key, array $specs, MutateInOptions $options = null): MutateInResult
    {
        $exportedOptions = MutateInOptions::export($options);
        [$request, $order] = KVRequestConverter::getMutateInRequest(
            $key,
            $specs,
            KVRequestConverter::getLocation($this->bucketName, $this->scopeName, $this->name),
            $options
        );
        $timeout = $this->client->timeoutHandler()->getTimeout(TimeoutHandler::KV, $exportedOptions);
        $res = ProtostellarOperationRunner::runUnary(
            SharedUtils::createProtostellarRequest(new MutateInRequest($request), false, $timeout),
            [$this->client->kv(), 'MutateIn']
        );
        return KVResponseConverter::convertMutateInResult($key, $res, $request['specs'], $order);
    }

    public function getAnyReplica(string $id, \Couchbase\GetAnyReplicaOptions $options = null): GetReplicaResult
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
