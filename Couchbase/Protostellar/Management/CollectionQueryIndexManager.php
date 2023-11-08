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

use Couchbase\Exception\DecodingFailureException;
use Couchbase\Exception\InvalidArgumentException;
use Couchbase\Exception\UnambiguousTimeoutException;
use Couchbase\Management\BuildQueryIndexesOptions;
use Couchbase\Management\CollectionQueryIndexManagerInterface;
use Couchbase\Management\CreateQueryIndexOptions;
use Couchbase\Management\CreateQueryPrimaryIndexOptions;
use Couchbase\Management\DropQueryIndexOptions;
use Couchbase\Management\DropQueryPrimaryIndexOptions;
use Couchbase\Management\GetAllQueryIndexesOptions;
use Couchbase\Management\QueryIndex;
use Couchbase\Management\WatchQueryIndexesOptions;
use Couchbase\Protostellar\Internal\Client;
use Couchbase\Protostellar\Internal\Management\QueryIndexManagementResponseConverter;
use Couchbase\Protostellar\Internal\RequestFactory;
use Couchbase\Protostellar\Internal\SharedUtils;
use Couchbase\Protostellar\Internal\TimeoutHandler;
use Couchbase\Protostellar\ProtostellarOperationRunner;

class CollectionQueryIndexManager implements CollectionQueryIndexManagerInterface
{
    private string $collectionName;
    private string $scopeName;
    private string $bucketName;
    private Client $client;
    public function __construct(string $collectionName, string $scopeName, string $bucketName, Client $client)
    {
        $this->collectionName = $collectionName;
        $this->scopeName = $scopeName;
        $this->bucketName = $bucketName;
        $this->client = $client;
    }

    /**
     * @throws DecodingFailureException
     * @throws InvalidArgumentException
     */
    public function getAllIndexes(GetAllQueryIndexesOptions $options = null): array
    {
        $exportedOptions = GetAllQueryIndexesOptions::export($options);
        $this->checkOptions($exportedOptions);
        $request = RequestFactory::makeRequest(
            ['Couchbase\Protostellar\Internal\Management\QueryIndexManagementRequestConverter', 'getGetAllIndexesRequest'],
            [$this->bucketName, $this->scopeName, $this->collectionName]
        );
        $timeout = $this->client->timeoutHandler()->getTimeout(TimeoutHandler::MANAGEMENT, $exportedOptions);
        $response = ProtostellarOperationRunner::runUnary(
            SharedUtils::createProtostellarRequest($request, true, $timeout),
            [$this->client->queryAdmin(), 'GetAllIndexes']
        );
        return QueryIndexManagementResponseConverter::convertGetAllIndexesResult($response);
    }

    /**
     * @throws InvalidArgumentException
     */
    public function createPrimaryIndex(CreateQueryPrimaryIndexOptions $options = null)
    {
        $exportedOptions = CreateQueryPrimaryIndexOptions::export($options);
        $this->checkOptions($exportedOptions);
        $request = RequestFactory::makeRequest(
            ['Couchbase\Protostellar\Internal\Management\QueryIndexManagementRequestConverter', 'getCreatePrimaryIndexRequest'],
            [$this->bucketName, $exportedOptions, $this->scopeName, $this->collectionName]
        );
        $timeout = $this->client->timeoutHandler()->getTimeout(TimeoutHandler::MANAGEMENT, $exportedOptions);
        ProtostellarOperationRunner::runUnary(
            SharedUtils::createProtostellarRequest($request, false, $timeout),
            [$this->client->queryAdmin(), 'CreatePrimaryIndex']
        );
    }

    /**
     * @throws InvalidArgumentException
     */
    public function createIndex(string $indexName, array $fields, CreateQueryIndexOptions $options = null)
    {
        $exportedOptions = CreateQueryIndexOptions::export($options);
        $this->checkOptions($exportedOptions);
        $request = RequestFactory::makeRequest(
            ['Couchbase\Protostellar\Internal\Management\QueryIndexManagementRequestConverter', 'getCreateIndexRequest'],
            [$this->bucketName, $indexName, $fields, $exportedOptions, $this->scopeName, $this->collectionName]
        );
        $timeout = $this->client->timeoutHandler()->getTimeout(TimeoutHandler::MANAGEMENT, $exportedOptions);
        ProtostellarOperationRunner::runUnary(
            SharedUtils::createProtostellarRequest($request, false, $timeout),
            [$this->client->queryAdmin(), 'CreateIndex']
        );
    }

    /**
     * @throws InvalidArgumentException
     */
    public function dropIndex(string $indexName, DropQueryIndexOptions $options = null)
    {
        $exportedOptions = DropQueryIndexOptions::export($options);
        $this->checkOptions($exportedOptions);
        $request = RequestFactory::makeRequest(
            ['Couchbase\Protostellar\Internal\Management\QueryIndexManagementRequestConverter', 'getDropIndexRequest'],
            [$this->bucketName, $indexName, $exportedOptions, $this->scopeName, $this->collectionName]
        );
        $timeout = $this->client->timeoutHandler()->getTimeout(TimeoutHandler::MANAGEMENT, $exportedOptions);
        ProtostellarOperationRunner::runUnary(
            SharedUtils::createProtostellarRequest($request, false, $timeout),
            [$this->client->queryAdmin(), 'DropIndex']
        );
    }

    /**
     * @throws InvalidArgumentException
     */
    public function dropPrimaryIndex(DropQueryPrimaryIndexOptions $options = null)
    {
        $exportedOptions = DropQueryPrimaryIndexOptions::export($options);
        $this->checkOptions($exportedOptions);
        $request = RequestFactory::makeRequest(
            ['Couchbase\Protostellar\Internal\Management\QueryIndexManagementRequestConverter', 'getDropPrimaryIndexRequest'],
            [$this->bucketName, $exportedOptions, $this->scopeName, $this->collectionName]
        );
        $timeout = $this->client->timeoutHandler()->getTimeout(TimeoutHandler::MANAGEMENT, $exportedOptions);
        ProtostellarOperationRunner::runUnary(
            SharedUtils::createProtostellarRequest($request, false, $timeout),
            [$this->client->queryAdmin(), 'DropPrimaryIndex']
        );
    }

    /**
     * @throws InvalidArgumentException
     */
    public function buildDeferredIndexes(BuildQueryIndexesOptions $options = null)
    {
        $exportedOptions = BuildQueryIndexesOptions::export($options);
        $this->checkOptions($exportedOptions);
        $request = RequestFactory::makeRequest(
            ['Couchbase\Protostellar\Internal\Management\QueryIndexManagementRequestConverter', 'getBuildDeferredIndexesRequest'],
            [$this->bucketName, $this->scopeName, $this->collectionName]
        );
        $timeout = $this->client->timeoutHandler()->getTimeout(TimeoutHandler::MANAGEMENT, $exportedOptions);
        ProtostellarOperationRunner::runUnary(
            SharedUtils::createProtostellarRequest($request, false, $timeout),
            [$this->client->queryAdmin(), 'BuildDeferredIndexes']
        );
    }

    /**
     * @throws UnambiguousTimeoutException
     * @throws InvalidArgumentException
     */
    public function watchIndexes(array $indexNames, int $timeoutMilliseconds, WatchQueryIndexesOptions $options = null)
    {
        $exported = WatchQueryIndexesOptions::export($options);
        $this->checkOptions($exported);
        if (array_key_exists("watchPrimary", $exported) && $exported["watchPrimary"]) {
            $indexNames [] = "#primary";
        }
        $deadline = (int)(microtime(true) * 1000) + $timeoutMilliseconds;

        while (true) {
            $now = (int)(microtime(true) * 1000);
            if ($now >= $deadline) {
                throw new UnambiguousTimeoutException(
                    sprintf(
                        "Failed to find all indexes online within the allotted time (%dms)",
                        $timeoutMilliseconds
                    )
                );
            }
            $options = GetAllQueryIndexesOptions::build()->timeout($deadline - $now);
            $foundIndexes = $this->getAllIndexes($options);
            $onlineIndexes = [];
            /**
             * @var QueryIndex $index
             */
            foreach ($foundIndexes as $index) {
                if ($index->state() == "online") {
                    $onlineIndexes[$index->name()] = true;
                }
            }
            $allOnline = true;
            /**
             * @var string $name
             */
            foreach ($indexNames as $name) {
                if (!array_key_exists($name, $onlineIndexes)) {
                    $allOnline = false;
                    break;
                }
            }
            if ($allOnline) {
                break;
            }

            usleep(100_000); /* wait for 100ms */
        }
    }

    /**
     * @throws InvalidArgumentException
     */
    private function checkOptions(array $exportedOpts)
    {
        if (isset($exportedOpts['scopeName']) || isset($exportedOpts['collectionName'])) {
            throw new InvalidArgumentException("Scope and Collection options cannot be set when using the Query Index Manager at the collection level");
        }
    }
}
