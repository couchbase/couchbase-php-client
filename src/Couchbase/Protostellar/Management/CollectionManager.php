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

use Couchbase\Management\CollectionSpec;
use Couchbase\Management\CreateCollectionOptions;
use Couchbase\Management\CreateScopeOptions;
use Couchbase\Management\DropCollectionOptions;
use Couchbase\Management\DropScopeOptions;
use Couchbase\Management\GetAllScopesOptions;
use Couchbase\Protostellar\Generated\Admin\Collection\V1\CreateCollectionRequest;
use Couchbase\Protostellar\Generated\Admin\Collection\V1\CreateScopeRequest;
use Couchbase\Protostellar\Generated\Admin\Collection\V1\DeleteCollectionRequest;
use Couchbase\Protostellar\Generated\Admin\Collection\V1\DeleteScopeRequest;
use Couchbase\Protostellar\Generated\Admin\Collection\V1\ListCollectionsRequest;
use Couchbase\Protostellar\Internal\Client;
use Couchbase\Protostellar\Internal\CollectionManagementConverter;
use Couchbase\Protostellar\Internal\SharedUtils;
use Couchbase\Protostellar\ProtostellarOperationRunner;

class CollectionManager
{
    const DEFAULT_MANAGEMENT_TIMEOUT = 7.5e7;
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
        $request = [
            "bucket_name" => $this->bucketName
        ];
        $timeout = isset($exportedOptions["timeoutMilliseconds"])
            ? $exportedOptions["timeoutMilliseconds"] * 1000
            : self::DEFAULT_MANAGEMENT_TIMEOUT;
        $res = ProtostellarOperationRunner::runUnary(
            SharedUtils::createProtostellarRequest(new ListCollectionsRequest($request), true, $timeout),
            [$this->client->collectionAdmin(), 'ListCollections']
        );
        return CollectionManagementConverter::convertGetAllScopesResult($res);
    }

    public function createScope(string $name, CreateScopeOptions $options = null)
    {
        $exportedOptions = CreateScopeOptions::export($options);
        $request = [
            "bucket_name" => $this->bucketName,
            "scope_name" => $name
        ];
        $timeout = isset($exportedOptions["timeoutMilliseconds"])
            ? $exportedOptions["timeoutMilliseconds"] * 1000
            : self::DEFAULT_MANAGEMENT_TIMEOUT;
        ProtostellarOperationRunner::runUnary(
            SharedUtils::createProtostellarRequest(new CreateScopeRequest($request), false, $timeout),
            [$this->client->collectionAdmin(), 'CreateScope']
        );
    }

    public function dropScope(string $name, DropScopeOptions $options = null)
    {
        $exportedOptions = DropScopeOptions::export($options);
        $request = [
            "bucket_name" => $this->bucketName,
            "scope_name" => $name
        ];
        $timeout = isset($exportedOptions["timeoutMilliseconds"])
            ? $exportedOptions["timeoutMilliseconds"] * 1000
            : self::DEFAULT_MANAGEMENT_TIMEOUT;
        ProtostellarOperationRunner::runUnary(
            SharedUtils::createProtostellarRequest(new DeleteScopeRequest($request), false, $timeout),
            [$this->client->collectionAdmin(), 'DeleteScope']
        );
    }

    public function createCollection(CollectionSpec $collection, CreateCollectionOptions $options = null)
    {
        $exportedOptions = CreateCollectionOptions::export($options);
        $request = CollectionManagementConverter::getCreateCollectionRequest($this->bucketName, $collection);
        $timeout = isset($exportedOptions["timeoutMilliseconds"])
            ? $exportedOptions["timeoutMilliseconds"] * 1000
            : self::DEFAULT_MANAGEMENT_TIMEOUT;
        ProtostellarOperationRunner::runUnary(
            SharedUtils::createProtostellarRequest(new CreateCollectionRequest($request), false, $timeout),
            [$this->client->collectionAdmin(), 'CreateCollection']
        );
    }

    public function dropCollection(CollectionSpec $collection, DropCollectionOptions $options = null)
    {
        $exportedOptions = DropCollectionOptions::export($options);
        $request = CollectionManagementConverter::getDropCollectionRequest($this->bucketName, $collection);
        $timeout = isset($exportedOptions["timeoutMilliseconds"])
            ? $exportedOptions["timeoutMilliseconds"] * 1000
            : self::DEFAULT_MANAGEMENT_TIMEOUT;
        ProtostellarOperationRunner::runUnary(
            SharedUtils::createProtostellarRequest(new DeleteCollectionRequest($request), false, $timeout),
            [$this->client->collectionAdmin(), 'DeleteCollection']
        );
    }
}
