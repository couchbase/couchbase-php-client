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
use Couchbase\Exception\DecodingFailureException;
use Couchbase\Exception\DocumentIrretrievableException;
use Couchbase\Exception\DocumentNotFoundException;
use Couchbase\Exception\InvalidArgumentException;
use Couchbase\ExistsOptions;
use Couchbase\ExistsResult;
use Couchbase\GetAllReplicasOptions;
use Couchbase\GetAndLockOptions;
use Couchbase\GetAndTouchOptions;
use Couchbase\GetAnyReplicaOptions;
use Couchbase\GetOptions;
use Couchbase\GetReplicaResult;
use Couchbase\GetResult;
use Couchbase\InsertOptions;
use Couchbase\LookupInOptions;
use Couchbase\LookupInResult;
use Couchbase\MutateInOptions;
use Couchbase\MutateInResult;
use Couchbase\MutationResult;
use Couchbase\Protostellar\Internal\Client;
use Couchbase\Protostellar\Internal\KV\KVRequestConverter;
use Couchbase\Protostellar\Internal\KV\KVResponseConverter;
use Couchbase\Protostellar\Internal\RequestFactory;
use Couchbase\Protostellar\Internal\SharedUtils;
use Couchbase\Protostellar\Internal\TimeoutHandler;
use Couchbase\Protostellar\Management\CollectionQueryIndexManager;
use Couchbase\RemoveOptions;
use Couchbase\ReplaceOptions;
use Couchbase\Result;
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
        $request = RequestFactory::makeRequest(
            ['Couchbase\Protostellar\Internal\KV\KVRequestConverter', 'getUpsertRequest'],
            [$key, $document, KVRequestConverter::getLocation($this->bucketName, $this->scopeName, $this->name), $options]
        );
        $timeout = $this->client->timeoutHandler()->getTimeout(TimeoutHandler::KV, $exportedOptions);
        $res = ProtostellarOperationRunner::runUnary(
            SharedUtils::createProtostellarRequest($request, false, $timeout),
            [$this->client->kv(), 'Upsert']
        );
        return KVResponseConverter::convertMutationResult($key, $res);
    }

    /**
     * @throws InvalidArgumentException|DecodingFailureException
     */
    public function insert(string $key, $document, InsertOptions $options = null): MutationResult
    {
        $exportedOptions = InsertOptions::export($options);
        $request = RequestFactory::makeRequest(
            ['Couchbase\Protostellar\Internal\KV\KVRequestConverter', 'getInsertRequest'],
            [$key, $document, KVRequestConverter::getLocation($this->bucketName, $this->scopeName, $this->name), $options]
        );
        $timeout = $this->client->timeoutHandler()->getTimeout(TimeoutHandler::KV, $exportedOptions);
        $res = ProtostellarOperationRunner::runUnary(
            SharedUtils::createProtostellarRequest($request, false, $timeout),
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
        $request = RequestFactory::makeRequest(
            ['Couchbase\Protostellar\Internal\KV\KVRequestConverter', 'getReplaceRequest'],
            [$key, $document, KVRequestConverter::getLocation($this->bucketName, $this->scopeName, $this->name), $options]
        );
        $timeout = $this->client->timeoutHandler()->getTimeout(TimeoutHandler::KV, $exportedOptions);
        $res = ProtostellarOperationRunner::runUnary(
            SharedUtils::createProtostellarRequest($request, false, $timeout),
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
        $request = RequestFactory::makeRequest(
            ['Couchbase\Protostellar\Internal\KV\KVRequestConverter', 'getRemoveRequest'],
            [$key, $exportedOptions, KVRequestConverter::getLocation($this->bucketName, $this->scopeName, $this->name)]
        );
        $timeout = $this->client->timeoutHandler()->getTimeout(TimeoutHandler::KV, $exportedOptions);
        $res = ProtostellarOperationRunner::runUnary(
            SharedUtils::createProtostellarRequest($request, false, $timeout),
            [$this->client->kv(), 'Remove']
        );
        return KVResponseConverter::convertMutationResult($key, $res);
    }

    public function get(string $key, GetOptions $options = null): GetResult
    {
        $exportedOptions = GetOptions::export($options);
        $request = RequestFactory::makeRequest(
            ['Couchbase\Protostellar\Internal\KV\KVRequestConverter', 'getGetRequest'],
            [$key, $exportedOptions, KVRequestConverter::getLocation($this->bucketName, $this->scopeName, $this->name)]
        );
        $timeout = $this->client->timeoutHandler()->getTimeout(TimeoutHandler::KV, $exportedOptions);
        $res = ProtostellarOperationRunner::runUnary(
            SharedUtils::createProtostellarRequest($request, true, $timeout),
            [$this->client->kv(), 'Get']
        );
        return KVResponseConverter::convertGetResult($key, $res, $options);
    }

    public function exists(string $key, ExistsOptions $options = null): ExistsResult
    {
        $exportedOptions = ExistsOptions::export($options);
        $request = RequestFactory::makeRequest(
            ['Couchbase\Protostellar\Internal\KV\KVRequestConverter', 'getExistsRequest'],
            [$key, KVRequestConverter::getLocation($this->bucketName, $this->scopeName, $this->name)]
        );
        $timeout = $this->client->timeoutHandler()->getTimeout(TimeoutHandler::KV, $exportedOptions);
        $res = ProtostellarOperationRunner::runUnary(
            SharedUtils::createProtostellarRequest($request, true, $timeout),
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
        $request = RequestFactory::makeRequest(
            ['Couchbase\Protostellar\Internal\KV\KVRequestConverter', 'getGetAndTouchRequest'],
            [$key, $expiry, KVRequestConverter::getLocation($this->bucketName, $this->scopeName, $this->name)]
        );
        $timeout = $this->client->timeoutHandler()->getTimeout(TimeoutHandler::KV, $exportedOptions);
        $res = ProtostellarOperationRunner::runUnary(
            SharedUtils::createProtostellarRequest($request, false, $timeout),
            [$this->client->kv(), 'GetAndTouch']
        );
        return KVResponseConverter::convertGetAndTouchResult($key, $res, $options);
    }

    /**
     * @throws ProtocolException
     * @throws InvalidArgumentException
     */
    public function getAndLock(string $key, int $lockTimeSeconds, GetAndLockOptions $options = null): GetResult
    {
        $exportedOptions = GetAndLockOptions::export($options);
        $request = RequestFactory::makeRequest(
            ['Couchbase\Protostellar\Internal\KV\KVRequestConverter', 'getGetAndLockRequest'],
            [$key, $lockTimeSeconds, KVRequestConverter::getLocation($this->bucketName, $this->scopeName, $this->name)]
        );
        $timeout = $this->client->timeoutHandler()->getTimeout(TimeoutHandler::KV, $exportedOptions);
        $res = ProtostellarOperationRunner::runUnary(
            SharedUtils::createProtostellarRequest($request, false, $timeout),
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
        $request = RequestFactory::makeRequest(
            ['Couchbase\Protostellar\Internal\KV\KVRequestConverter', 'getUnlockRequest'],
            [$key, $cas, KVRequestConverter::getLocation($this->bucketName, $this->scopeName, $this->name)]
        );
        $timeout = $this->client->timeoutHandler()->getTimeout(TimeoutHandler::KV, $exportedOptions);
        ProtostellarOperationRunner::runUnary(
            SharedUtils::createProtostellarRequest($request, false, $timeout),
            [$this->client->kv(), 'Unlock']
        );
        return KVResponseConverter::convertUnlockResult($key, $cas);
    }

    public function touch(string $key, $expiry, TouchOptions $options = null): MutationResult
    {
        $exportedOptions = TouchOptions::export($options);
        $request = RequestFactory::makeRequest(
            ['Couchbase\Protostellar\Internal\KV\KVRequestConverter', 'getTouchRequest'],
            [$key, $expiry, KVRequestConverter::getLocation($this->bucketName, $this->scopeName, $this->name)]
        );
        $timeout = $this->client->timeoutHandler()->getTimeout(TimeoutHandler::KV, $exportedOptions);
        $res = ProtostellarOperationRunner::runUnary(
            SharedUtils::createProtostellarRequest($request, false, $timeout),
            [$this->client->kv(), 'Touch']
        );
        return KVResponseConverter::convertTouchResult($key, $res);
    }

    public function lookupIn(string $key, array $specs, LookupInOptions $options = null): LookupInResult
    {
        $exportedOptions = LookupInOptions::export($options);
        [$request, $order] = RequestFactory::makeRequest(
            ['Couchbase\Protostellar\Internal\KV\KVRequestConverter', 'getLookupInRequest'],
            [$key, $specs, KVRequestConverter::getLocation($this->bucketName, $this->scopeName, $this->name), $options]
        );
        $timeout = $this->client->timeoutHandler()->getTimeout(TimeoutHandler::KV, $exportedOptions);
        $res = ProtostellarOperationRunner::runUnary(
            SharedUtils::createProtostellarRequest($request, true, $timeout),
            [$this->client->kv(), 'LookupIn']
        );
        return KVResponseConverter::convertLookupInResult($key, $res, SharedUtils::toArray($request->getSpecs()), $order, $options);
    }

    /**
     * @throws InvalidArgumentException
     */
    public function mutateIn(string $key, array $specs, MutateInOptions $options = null): MutateInResult
    {
        $exportedOptions = MutateInOptions::export($options);
        [$request, $order] = RequestFactory::makeRequest(
            ['Couchbase\Protostellar\Internal\KV\KVRequestConverter', 'getMutateInRequest'],
            [$key, $specs, KVRequestConverter::getLocation($this->bucketName, $this->scopeName, $this->name), $options]
        );
        $timeout = $this->client->timeoutHandler()->getTimeout(TimeoutHandler::KV, $exportedOptions);
        $res = ProtostellarOperationRunner::runUnary(
            SharedUtils::createProtostellarRequest($request, false, $timeout),
            [$this->client->kv(), 'MutateIn']
        );
        return KVResponseConverter::convertMutateInResult($key, $res, SharedUtils::toArray($request->getSpecs()), $order);
    }

    /**
     * @throws DocumentIrretrievableException
     */
    public function getAnyReplica(string $key, GetAnyReplicaOptions $options = null): GetReplicaResult
    {
        $exportedOptions = GetAnyReplicaOptions::export($options);
        $request = RequestFactory::makeRequest(
            ['Couchbase\Protostellar\Internal\KV\KVRequestConverter', 'getGetAllReplicasRequest'],
            [$key, KVRequestConverter::getLocation($this->bucketName, $this->scopeName, $this->name)]
        );
        $timeout = $this->client->timeoutHandler()->getTimeout(TimeoutHandler::KV, $exportedOptions);
        $response = ProtostellarOperationRunner::runStreaming(
            SharedUtils::createProtostellarRequest($request, true, $timeout),
            [$this->client->kv(), 'GetAllReplicas']
        );
        return KVResponseConverter::convertGetAnyReplicaResult($key, $response, $options)[0];
    }

    /**
     * @throws DocumentNotFoundException
     */
    public function getAllReplicas(string $key, GetAllReplicasOptions $options = null): array
    {
        $exportedOptions = GetAllReplicasOptions::export($options);
        $request = RequestFactory::makeRequest(
            ['Couchbase\Protostellar\Internal\KV\KVRequestConverter', 'getGetAllReplicasRequest'],
            [$key, KVRequestConverter::getLocation($this->bucketName, $this->scopeName, $this->name)]
        );
        $timeout = $this->client->timeoutHandler()->getTimeout(TimeoutHandler::KV, $exportedOptions);
        $response = ProtostellarOperationRunner::runStreaming(
            SharedUtils::createProtostellarRequest($request, true, $timeout),
            [$this->client->kv(), 'GetAllReplicas']
        );
        return KVResponseConverter::convertGetAllReplicasResult($key, $response, $options);
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
