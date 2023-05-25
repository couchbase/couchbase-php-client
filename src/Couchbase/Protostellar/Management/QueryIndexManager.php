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

use Couchbase\Exception\UnambiguousTimeoutException;
use Couchbase\Management\BuildQueryIndexesOptions;
use Couchbase\Management\CreateQueryIndexOptions;
use Couchbase\Management\CreateQueryPrimaryIndexOptions;
use Couchbase\Management\DropQueryIndexOptions;
use Couchbase\Management\DropQueryPrimaryIndexOptions;
use Couchbase\Management\GetAllQueryIndexesOptions;
use Couchbase\Management\QueryIndex;
use Couchbase\Management\WatchQueryIndexesOptions;
use Couchbase\Protostellar\Generated\Admin\Query\V1\BuildDeferredIndexesRequest;
use Couchbase\Protostellar\Generated\Admin\Query\V1\CreateIndexRequest;
use Couchbase\Protostellar\Generated\Admin\Query\V1\CreatePrimaryIndexRequest;
use Couchbase\Protostellar\Generated\Admin\Query\V1\DropIndexRequest;
use Couchbase\Protostellar\Generated\Admin\Query\V1\DropPrimaryIndexRequest;
use Couchbase\Protostellar\Generated\Admin\Query\V1\GetAllIndexesRequest;
use Couchbase\Protostellar\Internal\Client;
use Couchbase\Protostellar\Internal\QueryIndexManagementConverter;
use Couchbase\Protostellar\Internal\SharedUtils;
use Couchbase\Protostellar\Internal\TimeoutHandler;
use Couchbase\Protostellar\ProtostellarOperationRunner;

class QueryIndexManager
{
    private Client $client;

    public function __construct(Client $client)
    {
        $this->client = $client;
    }

    public function getAllIndexes(string $bucketName, GetAllQueryIndexesOptions $options = null): array
    {
        $exportedOptions = GetAllQueryIndexesOptions::export($options);
        $request = QueryIndexManagementConverter::getGetAllIndexesRequest($bucketName, $exportedOptions["scopeName"] ?? null, $exportedOptions["collectionName"] ?? null);
        $timeout = $this->client->timeoutHandler()->getTimeout(TimeoutHandler::MANAGEMENT, $exportedOptions);
        $response = ProtostellarOperationRunner::runUnary(
            SharedUtils::createProtostellarRequest(new GetAllIndexesRequest($request), true, $timeout),
            [$this->client->queryAdmin(), 'GetAllIndexes']
        );
        return QueryIndexManagementConverter::convertGetAllIndexesResult($response);
    }

    public function createPrimaryIndex(string $bucketName, CreateQueryPrimaryIndexOptions $options = null)
    {
        $exportedOptions = CreateQueryPrimaryIndexOptions::export($options);
        $request = QueryIndexManagementConverter::getCreatePrimaryIndexRequest($bucketName, $exportedOptions, $exportedOptions["scopeName"] ?? null, $exportedOptions["collectionName"] ?? null);
        $timeout = $this->client->timeoutHandler()->getTimeout(TimeoutHandler::MANAGEMENT, $exportedOptions);
        ProtostellarOperationRunner::runUnary(
            SharedUtils::createProtostellarRequest(new CreatePrimaryIndexRequest($request), false, $timeout),
            [$this->client->queryAdmin(), 'CreatePrimaryIndex']
        );
    }

    public function createIndex(string $bucketName, string $indexName, array $fields, CreateQueryIndexOptions $options = null)
    {
        $exportedOptions = CreateQueryIndexOptions::export($options);
        $request = QueryIndexManagementConverter::getCreateIndexRequest($bucketName, $indexName, $fields,  $exportedOptions, $exportedOptions["scopeName"] ?? null, $exportedOptions["collectionName"] ?? null);
        $timeout = $this->client->timeoutHandler()->getTimeout(TimeoutHandler::MANAGEMENT, $exportedOptions);
        ProtostellarOperationRunner::runUnary(
            SharedUtils::createProtostellarRequest(new CreateIndexRequest($request), false, $timeout),
            [$this->client->queryAdmin(), 'CreateIndex']
        );
    }

    public function dropIndex(string $bucketName, string $indexName, DropQueryIndexOptions $options = null)
    {
        $exportedOptions = DropQueryIndexOptions::export($options);
        $request = QueryIndexManagementConverter::getDropIndexRequest($bucketName, $indexName, $exportedOptions["scopeName"] ?? null, $exportedOptions["collectionName"] ?? null);
        $timeout = $this->client->timeoutHandler()->getTimeout(TimeoutHandler::MANAGEMENT, $exportedOptions);
        ProtostellarOperationRunner::runUnary(
            SharedUtils::createProtostellarRequest(new DropIndexRequest($request), false, $timeout),
            [$this->client->queryAdmin(), 'DropIndex']
        );
    }

    public function dropPrimaryIndex(string $bucketName, DropQueryPrimaryIndexOptions $options = null)
    {
        $exportedOptions = DropQueryPrimaryIndexOptions::export($options);
        $request = QueryIndexManagementConverter::getDropPrimaryIndexRequest($bucketName, $exportedOptions, $exportedOptions["scopeName"] ?? null, $exportedOptions["collectionName"] ?? null);
        $timeout = $this->client->timeoutHandler()->getTimeout(TimeoutHandler::MANAGEMENT, $exportedOptions);
        ProtostellarOperationRunner::runUnary(
            SharedUtils::createProtostellarRequest(new DropPrimaryIndexRequest($request), false, $timeout),
            [$this->client->queryAdmin(), 'DropPrimaryIndex']
        );
    }

    public function buildDeferredIndexes(string $bucketName, BuildQueryIndexesOptions $options = null)
    {
        $exportedOptions = BuildQueryIndexesOptions::export($options);
        $request = QueryIndexManagementConverter::getBuildDeferredIndexesRequest($bucketName, $exportedOptions["scopeName"] ?? null, $exportedOptions["collectionName"] ?? null);
        $timeout = $this->client->timeoutHandler()->getTimeout(TimeoutHandler::MANAGEMENT, $exportedOptions);
        ProtostellarOperationRunner::runUnary(
            SharedUtils::createProtostellarRequest(new BuildDeferredIndexesRequest($request), false, $timeout),
            [$this->client->queryAdmin(), 'BuildDeferredIndexes']
        );
    }

    /**
     * @throws UnambiguousTimeoutException
     */
    public function watchIndexes(string $bucketName, array $indexNames, int $timeoutMilliseconds, WatchQueryIndexesOptions $options = null)
    {
        $exported = WatchQueryIndexesOptions::export($options);
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
            if (array_key_exists("scopeName", $exported) && $exported["scopeName"]) {
                $options->scopeName($exported["scopeName"]);
            }
            if (array_key_exists("collectionName", $exported) && $exported["collectionName"]) {
                $options->collectionName($exported["collectionName"]);
            }
            $foundIndexes = $this->getAllIndexes($bucketName, $options);
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
}
