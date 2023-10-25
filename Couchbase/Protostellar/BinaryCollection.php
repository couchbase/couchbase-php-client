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

use Couchbase\AppendOptions;
use Couchbase\BinaryCollectionInterface;
use Couchbase\CounterResult;
use Couchbase\DecrementOptions;
use Couchbase\Exception\InvalidArgumentException;
use Couchbase\IncrementOptions;
use Couchbase\MutationResult;
use Couchbase\PrependOptions;
use Couchbase\Protostellar\Internal\Client;
use Couchbase\Protostellar\Internal\KV\KVRequestConverter;
use Couchbase\Protostellar\Internal\KV\KVResponseConverter;
use Couchbase\Protostellar\Internal\RequestFactory;
use Couchbase\Protostellar\Internal\SharedUtils;
use Couchbase\Protostellar\Internal\TimeoutHandler;

class BinaryCollection implements BinaryCollectionInterface
{
    private Client $client;
    private string $bucketName;
    private string $scopeName;
    private string $name;

    /**
     * @param Client $client
     * @param string $bucketName
     * @param string $scopeName
     * @param string $name
     * @internal
     */
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
    public function append(string $key, string $value, AppendOptions $options = null): MutationResult
    {
        $exportedOptions = AppendOptions::export($options);
        $request = RequestFactory::makeRequest(
            ['Couchbase\Protostellar\Internal\KV\KVRequestConverter', 'getAppendRequest'],
            [$key, $value, $exportedOptions, KVRequestConverter::getLocation($this->bucketName, $this->scopeName, $this->name)]
        );
        $timeout = $this->client->timeoutHandler()->getTimeout(TimeoutHandler::KV, $exportedOptions);
        $res = ProtostellarOperationRunner::runUnary(
            SharedUtils::createProtostellarRequest($request, false, $timeout),
            [$this->client->kv(), 'Append']
        );
        return KVResponseConverter::convertMutationResult($key, $res);
    }

    /**
     * @throws InvalidArgumentException
     */
    public function prepend(string $key, string $value, PrependOptions $options = null): MutationResult
    {
        $exportedOptions = PrependOptions::export($options);
        $request = RequestFactory::makeRequest(
            ['Couchbase\Protostellar\Internal\KV\KVRequestConverter', 'getPrependRequest'],
            [$key, $value, $exportedOptions, KVRequestConverter::getLocation($this->bucketName, $this->scopeName, $this->name)]
        );
        $timeout = $this->client->timeoutHandler()->getTimeout(TimeoutHandler::KV, $exportedOptions);
        $res = ProtostellarOperationRunner::runUnary(
            SharedUtils::createProtostellarRequest($request, false, $timeout),
            [$this->client->kv(), 'Prepend']
        );
        return KVResponseConverter::convertMutationResult($key, $res);
    }

    /**
     * @throws InvalidArgumentException
     */
    public function increment(string $key, IncrementOptions $options = null): CounterResult
    {
        $exportedOptions = IncrementOptions::export($options);
        $request = RequestFactory::makeRequest(
            ['Couchbase\Protostellar\Internal\KV\KVRequestConverter', 'getIncrementRequest'],
            [$key, $exportedOptions, KVRequestConverter::getLocation($this->bucketName, $this->scopeName, $this->name)]
        );
        $timeout = $this->client->timeoutHandler()->getTimeout(TimeoutHandler::KV, $exportedOptions);
        $res = ProtostellarOperationRunner::runUnary(
            SharedUtils::createProtostellarRequest($request, false, $timeout),
            [$this->client->kv(), 'Increment']
        );
        return KVResponseConverter::convertCounterResult($key, $res);
    }

    /**
     * @throws InvalidArgumentException
     */
    public function decrement(string $key, DecrementOptions $options = null): CounterResult
    {
        $exportedOptions = DecrementOptions::export($options);
        $request = RequestFactory::makeRequest(
            ['Couchbase\Protostellar\Internal\KV\KVRequestConverter', 'getDecrementRequest'],
            [$key, $exportedOptions, KVRequestConverter::getLocation($this->bucketName, $this->scopeName, $this->name)]
        );
        $timeout = $this->client->timeoutHandler()->getTimeout(TimeoutHandler::KV, $exportedOptions);
        $res = ProtostellarOperationRunner::runUnary(
            SharedUtils::createProtostellarRequest($request, false, $timeout),
            [$this->client->kv(), 'Decrement']
        );
        return KVResponseConverter::convertCounterResult($key, $res);
    }
}
