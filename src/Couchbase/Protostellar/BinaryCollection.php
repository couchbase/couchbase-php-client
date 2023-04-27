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
use Couchbase\IncrementOptions;
use Couchbase\MutationResult;
use Couchbase\PrependOptions;
use Couchbase\Protostellar\Generated\KV\V1\AppendRequest;
use Couchbase\Protostellar\Generated\KV\V1\DecrementRequest;
use Couchbase\Protostellar\Generated\KV\V1\IncrementRequest;
use Couchbase\Protostellar\Generated\KV\V1\PrependRequest;
use Couchbase\Protostellar\Internal\Client;
use Couchbase\Protostellar\Internal\KVConverter;
use Couchbase\Protostellar\Internal\SharedUtils;
use Couchbase\Protostellar\Internal\TimeoutHandler;

use const Grpc\STATUS_OK;

class BinaryCollection implements BinaryCollectionInterface
{
    private Client $client;
    private string $bucketName;
    private string $scopeName;
    private string $name;

    /**
     * @param string $name
     * @param string $scopeName
     * @param string $bucketName
     * @param resource $core
     *
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
     * @throws ProtocolException
     */
    public function append(string $key, string $value, AppendOptions $options = null): MutationResult
    {
        $exportedOptions = AppendOptions::export($options);
        $request = [
            "bucket_name" => $this->bucketName,
            "scope_name" => $this->scopeName,
            "collection_name" => $this->name,
            "key" => $key,
            "content" => $value,
        ];
        $timeout = $this->client->timeoutHandler()->getTimeout(TimeoutHandler::KV, $exportedOptions);
        $request = array_merge($request, KVConverter::convertAppendOptions($exportedOptions));
        $res = ProtostellarOperationRunner::runUnary(
            SharedUtils::createProtostellarRequest(new AppendRequest($request), false, $timeout),
            [$this->client->kv(), 'Append']
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
    public function prepend(string $key, string $value, PrependOptions $options = null): MutationResult
    {
        $exportedOptions = PrependOptions::export($options);
        $request = [
            "bucket_name" => $this->bucketName,
            "scope_name" => $this->scopeName,
            "collection_name" => $this->name,
            "key" => $key,
            "content" => $value,
        ];
        $timeout = $this->client->timeoutHandler()->getTimeout(TimeoutHandler::KV, $exportedOptions);
        $request = array_merge($request, KVConverter::convertPrependOptions($exportedOptions));
        $res = ProtostellarOperationRunner::runUnary(
            SharedUtils::createProtostellarRequest(new PrependRequest($request), false, $timeout),
            [$this->client->kv(), 'Prepend']
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
    public function increment(string $key, IncrementOptions $options = null): CounterResult
    {
        $exportedOptions = IncrementOptions::export($options);
        $request = [
            "bucket_name" => $this->bucketName,
            "scope_name" => $this->scopeName,
            "collection_name" => $this->name,
            "key" => $key,
        ];
        $timeout = $this->client->timeoutHandler()->getTimeout(TimeoutHandler::KV, $exportedOptions);
        $request = array_merge($request, KVConverter::convertIncrementOptions($exportedOptions));
        $res = ProtostellarOperationRunner::runUnary(
            SharedUtils::createProtostellarRequest(new IncrementRequest($request), false, $timeout),
            [$this->client->kv(), 'Increment']
        );
        return new CounterResult(
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
                "value" => $res->getContent()
            ]
        );
    }

    /**
     * @throws ProtocolException
     */
    public function decrement(string $key, DecrementOptions $options = null): CounterResult
    {
        $exportedOptions = DecrementOptions::export($options);
        $request = [
            "bucket_name" => $this->bucketName,
            "scope_name" => $this->scopeName,
            "collection_name" => $this->name,
            "key" => $key,
        ];
        $timeout = $this->client->timeoutHandler()->getTimeout(TimeoutHandler::KV, $exportedOptions);
        $request = array_merge($request, KVConverter::convertDecrementOptions($exportedOptions));
        $res = ProtostellarOperationRunner::runUnary(
            SharedUtils::createProtostellarRequest(new DecrementRequest($request), false, $timeout),
            [$this->client->kv(), 'Decrement']
        );
        return new CounterResult(
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
                "value" => $res->getContent()
            ]
        );
    }
}
