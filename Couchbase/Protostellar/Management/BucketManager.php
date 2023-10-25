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

namespace Couchbase\Protostellar\Management;

use Couchbase\Exception\BucketNotFoundException;
use Couchbase\Exception\DecodingFailureException;
use Couchbase\Exception\InvalidArgumentException;
use Couchbase\Exception\UnsupportedOperationException;
use Couchbase\Management\BucketManagerInterface;
use Couchbase\Management\BucketSettings;
use Couchbase\Management\CreateBucketOptions;
use Couchbase\Management\DropBucketOptions;
use Couchbase\Management\FlushBucketOptions;
use Couchbase\Management\GetAllBucketsOptions;
use Couchbase\Management\GetBucketOptions;
use Couchbase\Management\UpdateBucketOptions;
use Couchbase\Protostellar\Internal\Client;
use Couchbase\Protostellar\Internal\Management\BucketManagementResponseConverter;
use Couchbase\Protostellar\Internal\RequestFactory;
use Couchbase\Protostellar\Internal\SharedUtils;
use Couchbase\Protostellar\Internal\TimeoutHandler;
use Couchbase\Protostellar\ProtostellarOperationRunner;

class BucketManager implements BucketManagerInterface
{
    private Client $client;

    public function __construct(Client $client)
    {
        $this->client = $client;
    }

    /**
     * @throws InvalidArgumentException
     */
    public function createBucket(BucketSettings $settings, CreateBucketOptions $options = null)
    {
        $exportedSettings = BucketSettings::export($settings);
        $exportedOptions = CreateBucketOptions::export($options);
        $request = RequestFactory::makeRequest(
            ['Couchbase\Protostellar\Internal\Management\BucketManagementRequestConverter', 'getCreateBucketRequest'],
            [$exportedSettings]
        );
        $timeout = $this->client->timeoutHandler()->getTimeout(TimeoutHandler::MANAGEMENT, $exportedOptions);
        ProtostellarOperationRunner::runUnary(
            SharedUtils::createProtostellarRequest($request, false, $timeout),
            [$this->client->bucketAdmin(), 'CreateBucket']
        );
    }

    /**
     * @throws InvalidArgumentException
     */
    public function updateBucket(BucketSettings $settings, UpdateBucketOptions $options = null)
    {
        $exportedSettings = BucketSettings::export($settings);
        $exportedOptions = UpdateBucketOptions::export($options);
        $request = RequestFactory::makeRequest(
            ['Couchbase\Protostellar\Internal\Management\BucketManagementRequestConverter', 'getUpdateBucketRequest'],
            [$exportedSettings]
        );
        $timeout = $this->client->timeoutHandler()->getTimeout(TimeoutHandler::MANAGEMENT, $exportedOptions);
        ProtostellarOperationRunner::runUnary(
            SharedUtils::createProtostellarRequest($request, false, $timeout),
            [$this->client->bucketAdmin(), 'UpdateBucket']
        );
    }

    public function dropBucket(string $name, DropBucketOptions $options = null)
    {
        $exportedOptions = DropBucketOptions::export($options);
        $request = RequestFactory::makeRequest(
            ['Couchbase\Protostellar\Internal\Management\BucketManagementRequestConverter', 'getDropBucketRequest'],
            [$name]
        );
        $timeout = $this->client->timeoutHandler()->getTimeout(TimeoutHandler::MANAGEMENT, $exportedOptions);
        ProtostellarOperationRunner::runUnary(
            SharedUtils::createProtostellarRequest($request, false, $timeout),
            [$this->client->bucketAdmin(), 'DeleteBucket']
        );
    }

    /**
     * @throws BucketNotFoundException
     */
    public function getBucket(string $name, GetBucketOptions $options = null): BucketSettings
    {
        $exportedOptions = GetBucketOptions::export($options);
        $getAllBucketOptions = isset($exportedOptions['timeoutMilliseconds'])
            ? GetAllBucketsOptions::build()->timeout($exportedOptions['timeoutMilliseconds'])
            : null;
        foreach ($this->getAllBuckets($getAllBucketOptions) as $bucket) {
            if ($bucket->name() == $name) {
                return $bucket;
            }
        }
        throw new BucketNotFoundException("Bucket " . $name . " was not found");
    }

    /**
     * @throws DecodingFailureException|InvalidArgumentException
     */
    public function getAllBuckets(GetAllBucketsOptions $options = null): array
    {
        $exportedOptions = GetAllBucketsOptions::export($options);
        $timeout = $this->client->timeoutHandler()->getTimeout(TimeoutHandler::MANAGEMENT, $exportedOptions);
        $request = RequestFactory::makeRequest(
            ['Couchbase\Protostellar\Internal\Management\BucketManagementRequestConverter', 'getGetAllBucketsRequest'],
            []
        );
        $res = ProtostellarOperationRunner::runUnary(
            SharedUtils::createProtostellarRequest($request, true, $timeout),
            [$this->client->bucketAdmin(), 'ListBuckets']
        );
        $buckets = [];
        foreach ($res->getBuckets() as $bucket) {
            $buckets[] = BucketSettings::import(BucketManagementResponseConverter::convertGetBucketResponse($bucket));
        }
        return $buckets;
    }

    /**
     * @throws UnsupportedOperationException
     */
    public function flush(string $name, FlushBucketOptions $options = null)
    {
        throw new UnsupportedOperationException("Flush is not available in CNG");
    }
}
