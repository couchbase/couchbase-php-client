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

use Couchbase\Exception\InvalidArgumentException;
use Couchbase\Exception\UnsupportedOperationException;
use Couchbase\Management\CollectionManagerInterface;
use Couchbase\Management\CollectionSpec;
use Couchbase\Management\CreateCollectionOptions;
use Couchbase\Management\CreateCollectionSettings;
use Couchbase\Management\CreateScopeOptions;
use Couchbase\Management\DropCollectionOptions;
use Couchbase\Management\DropScopeOptions;
use Couchbase\Management\GetAllScopesOptions;
use Couchbase\Management\UpdateCollectionOptions;
use Couchbase\Management\UpdateCollectionSettings;
use Couchbase\Protostellar\Internal\Client;
use Couchbase\Protostellar\Internal\Management\CollectionManagementResponseConverter;
use Couchbase\Protostellar\Internal\RequestFactory;
use Couchbase\Protostellar\Internal\SharedUtils;
use Couchbase\Protostellar\Internal\TimeoutHandler;
use Couchbase\Protostellar\ProtostellarOperationRunner;

class CollectionManager implements CollectionManagerInterface
{
    private string $bucketName;
    private Client $client;

    public function __construct(string $bucketName, Client $client)
    {
        $this->bucketName = $bucketName;
        $this->client = $client;
    }

    public function getAllScopes(GetAllScopesOptions $options = null): array
    {
        $exportedOptions = GetAllScopesOptions::export($options);
        $request = RequestFactory::makeRequest(
            ['Couchbase\Protostellar\Internal\Management\CollectionManagementRequestConverter', 'getGetAllScopesRequest'],
            [$this->bucketName]
        );
        $timeout = $this->client->timeoutHandler()->getTimeout(TimeoutHandler::MANAGEMENT, $exportedOptions);
        $res = ProtostellarOperationRunner::runUnary(
            SharedUtils::createProtostellarRequest($request, true, $timeout),
            [$this->client->collectionAdmin(), 'ListCollections']
        );
        return CollectionManagementResponseConverter::convertGetAllScopesResult($res);
    }

    public function createScope(string $name, CreateScopeOptions $options = null)
    {
        $exportedOptions = CreateScopeOptions::export($options);
        $request = RequestFactory::makeRequest(
            ['Couchbase\Protostellar\Internal\Management\CollectionManagementRequestConverter', 'getCreateScopeRequest'],
            [$this->bucketName, $name]
        );
        $timeout = $this->client->timeoutHandler()->getTimeout(TimeoutHandler::MANAGEMENT, $exportedOptions);
        ProtostellarOperationRunner::runUnary(
            SharedUtils::createProtostellarRequest($request, false, $timeout),
            [$this->client->collectionAdmin(), 'CreateScope']
        );
    }

    public function dropScope(string $name, DropScopeOptions $options = null)
    {
        $exportedOptions = DropScopeOptions::export($options);
        $request = RequestFactory::makeRequest(
            ['Couchbase\Protostellar\Internal\Management\CollectionManagementRequestConverter', 'getDropScopeRequest'],
            [$this->bucketName, $name]
        );
        $timeout = $this->client->timeoutHandler()->getTimeout(TimeoutHandler::MANAGEMENT, $exportedOptions);
        ProtostellarOperationRunner::runUnary(
            SharedUtils::createProtostellarRequest($request, false, $timeout),
            [$this->client->collectionAdmin(), 'DeleteScope']
        );
    }

    /**
     * @throws InvalidArgumentException
     */
    public function createCollection($scopeName, $collectionName = null, $settings = null, $options = null)
    {
        if (is_string($scopeName) && is_null($collectionName)) {
            throw new InvalidArgumentException(
                "Collection name cannot be null if using the (scopeName, collectionName, settings, options) API"
            );
        }
        // Deprecated usage conversion for (CollectionSpec, CreateCollectionOptions)
        if ($scopeName instanceof  CollectionSpec) {
            $options = $collectionName;
            $collectionName = $scopeName->name();
            $settings = new CreateCollectionSettings($scopeName->maxExpiry(), $scopeName->history());
            $scopeName = $scopeName->scopeName();
        }

        $exportedOptions = CreateCollectionOptions::export($options);
        $request = RequestFactory::makeRequest(
            ['Couchbase\Protostellar\Internal\Management\CollectionManagementRequestConverter', 'getCreateCollectionRequest'],
            [$this->bucketName, $scopeName, $collectionName, $settings]
        );
        $timeout = $this->client->timeoutHandler()->getTimeout(TimeoutHandler::MANAGEMENT, $exportedOptions);
        ProtostellarOperationRunner::runUnary(
            SharedUtils::createProtostellarRequest($request, false, $timeout),
            [$this->client->collectionAdmin(), 'CreateCollection']
        );
    }

    public function dropCollection($scopeName, $collectionName = null, $options = null)
    {
        if (is_string($scopeName) && is_null($collectionName)) {
            throw new InvalidArgumentException("Collection name cannot be null if using the (scopeName, collectionName, options) API");
        }

        // Deprecated usage conversion for (CollectionSpec, DropCollectionOptions)
        if ($scopeName instanceof CollectionSpec) {
            $options = $collectionName;
            $collectionName = $scopeName->name();
            $scopeName = $scopeName->scopeName();
        }

        $exportedOptions = DropCollectionOptions::export($options);
        $request = RequestFactory::makeRequest(
            ['Couchbase\Protostellar\Internal\Management\CollectionManagementRequestConverter', 'getDropCollectionRequest'],
            [$this->bucketName, $scopeName, $collectionName]
        );
        $timeout = $this->client->timeoutHandler()->getTimeout(TimeoutHandler::MANAGEMENT, $exportedOptions);
        ProtostellarOperationRunner::runUnary(
            SharedUtils::createProtostellarRequest($request, false, $timeout),
            [$this->client->collectionAdmin(), 'DeleteCollection']
        );
    }

    /**
     * @throws UnsupportedOperationException
     */
    public function updateCollection(string $scopeName, string $collectionName, UpdateCollectionSettings $settings, UpdateCollectionOptions $options = null)
    {
        throw new UnsupportedOperationException("Update collection is not yet supported in CNG");
    }
}
