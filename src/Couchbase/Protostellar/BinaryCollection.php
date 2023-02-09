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
        $timeout = isset($exportedOptions["timeoutMilliseconds"])
            ? $exportedOptions["timeoutMilliseconds"] * 1000
            : Collection::DEFAULT_KV_TIMEOUT;
        $request = array_merge($request, KVConverter::convertAppendOptions($exportedOptions));
        $pendingCall = $this->client->kv()->Append(new AppendRequest($request), [], ['timeout' => $timeout]);
        [$res, $status] = $pendingCall->wait();
        if ($status->code !== STATUS_OK) {
            throw new ProtocolException("unable to append the key", $status);
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
        $timeout = isset($exportedOptions["timeoutMilliseconds"])
            ? $exportedOptions["timeoutMilliseconds"] * 1000
            : Collection::DEFAULT_KV_TIMEOUT;
        $request = array_merge($request, KVConverter::convertPrependOptions($exportedOptions));
        $pendingCall = $this->client->kv()->Prepend(new PrependRequest($request), [], ['timeout' => $timeout]);
        [$res, $status] = $pendingCall->wait();
        if ($status->code !== STATUS_OK) {
            throw new ProtocolException("unable to prepend the key", $status);
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
    public function increment(string $key, IncrementOptions $options = null): CounterResult
    {
        $exportedOptions = IncrementOptions::export($options);
        $request = [
            "bucket_name" => $this->bucketName,
            "scope_name" => $this->scopeName,
            "collection_name" => $this->name,
            "key" => $key,
        ];
        $timeout = isset($exportedOptions["timeoutMilliseconds"])
            ? $exportedOptions["timeoutMilliseconds"] * 1000
            : Collection::DEFAULT_KV_TIMEOUT;
        $request = array_merge($request, KVConverter::convertIncrementOptions($exportedOptions));
        $pendingCall = $this->client->kv()->Increment(new IncrementRequest($request), [], ['timeout' => $timeout]);
        [$res, $status] = $pendingCall->wait();
        if ($status->code !== STATUS_OK) {
            throw new ProtocolException("unable to increment the key", $status);
        }
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
        $timeout = isset($exportedOptions["timeoutMilliseconds"])
            ? $exportedOptions["timeoutMilliseconds"] * 1000
            : Collection::DEFAULT_KV_TIMEOUT;
        $request = array_merge($request, KVConverter::convertDecrementOptions($exportedOptions));
        $pendingCall = $this->client->kv()->Decrement(new DecrementRequest($request), [], ['timeout' => $timeout]);
        [$res, $status] = $pendingCall->wait();
        if ($status->code !== STATUS_OK) {
            throw new ProtocolException("unable to decrement the key", $status);
        }
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
