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

namespace Couchbase\Protostellar\Internal\Management;

use Couchbase\Protostellar\Generated\Admin\Query\V1\BuildDeferredIndexesRequest;
use Couchbase\Protostellar\Generated\Admin\Query\V1\CreateIndexRequest;
use Couchbase\Protostellar\Generated\Admin\Query\V1\CreatePrimaryIndexRequest;
use Couchbase\Protostellar\Generated\Admin\Query\V1\DropIndexRequest;
use Couchbase\Protostellar\Generated\Admin\Query\V1\DropPrimaryIndexRequest;
use Couchbase\Protostellar\Generated\Admin\Query\V1\GetAllIndexesRequest;

class QueryIndexManagementRequestConverter
{
    public static function getGetAllIndexesRequest(string $bucketName, ?string $scopeName = null, ?string $collectionName = null): GetAllIndexesRequest
    {
        $request = [
            "bucket_name" => $bucketName,
        ];
        if (!is_null($scopeName)) {
            $request["scope_name"] = $scopeName;
        }
        if (!is_null($collectionName)) {
            $request["collection_name"] = $collectionName;
        }
        return new GetAllIndexesRequest($request);
    }

    public static function getCreatePrimaryIndexRequest(string $bucketName, array $exportedOptions, ?string $scopeName = null, ?string $collectionName = null): CreatePrimaryIndexRequest
    {
        $request = [
            "bucket_name" => $bucketName,
        ];
        if (!is_null($scopeName)) {
            $request["scope_name"] = $scopeName;
        }
        if (!is_null($collectionName)) {
            $request["collection_name"] = $collectionName;
        }
        if (isset($exportedOptions["indexName"])) {
            $request["name"] = $exportedOptions["indexName"];
        }
        if (isset($exportedOptions["numberOfReplicas"])) {
            $request["num_replicas"] = $exportedOptions["numberOfReplicas"];
        }
        if (isset($exportedOptions["deferred"])) {
            $request["deferred"] = $exportedOptions["deferred"];
        }
        if (isset($exportedOptions["ignoreIfExists"])) {
            $request["ignore_if_exists"] = $exportedOptions["ignoreIfExists"];
        }
        return new CreatePrimaryIndexRequest($request);
    }

    public static function getCreateIndexRequest(string $bucketName, string $indexName, array $fields, array $exportedOptions, ?string $scopeName = null, ?string $collectionName = null): CreateIndexRequest
    {
        $request = [
            "bucket_name" => $bucketName,
            "name" => $indexName,
            "fields" => $fields
        ];
        if (!is_null($scopeName)) {
            $request["scope_name"] = $scopeName;
        }
        if (!is_null($collectionName)) {
            $request["collection_name"] = $collectionName;
        }
        if (isset($exportedOptions["numberOfReplicas"])) {
            $request["num_replicas"] = $exportedOptions["numberOfReplicas"];
        }
        if (isset($exportedOptions["deferred"])) {
            $request["deferred"] = $exportedOptions["deferred"];
        }
        if (isset($exportedOptions["ignoreIfExists"])) {
            $request["ignore_if_exists"] = $exportedOptions["ignoreIfExists"];
        }
        return new CreateIndexRequest($request);
    }

    public static function getDropIndexRequest(string $bucketName, string $indexName, array $exportedOptions, ?string $scopeName = null, ?string $collectionName = null): DropIndexRequest
    {
        $request = [
            "bucket_name" => $bucketName,
            "name" => $indexName
        ];
        if (!is_null($scopeName)) {
            $request["scope_name"] = $scopeName;
        }
        if (!is_null($collectionName)) {
            $request["collection_name"] = $collectionName;
        }
        if (isset($exportedOptions["ignoreIfDoesNotExist"])) {
            $request["ignore_if_missing"] = $exportedOptions["ignoreIfDoesNotExist"];
        }
        return new DropIndexRequest($request);
    }

    public static function getDropPrimaryIndexRequest(string $bucketName, array $exportedOptions, ?string $scopeName = null, ?string $collectionName = null): DropPrimaryIndexRequest
    {
        $request = [
            "bucket_name" => $bucketName
        ];
        if (!is_null($scopeName)) {
            $request["scope_name"] = $scopeName;
        }
        if (!is_null($collectionName)) {
            $request["collection_name"] = $collectionName;
        }
        if (isset($exportedOptions["indexName"])) {
            $request["name"] = $exportedOptions["indexName"];
        }
        if (isset($exportedOptions["ignoreIfDoesNotExist"])) {
            $request["ignore_if_missing"] = $exportedOptions["ignoreIfDoesNotExist"];
        }
        return new DropPrimaryIndexRequest($request);
    }

    public static function getBuildDeferredIndexesRequest(string $bucketName, ?string $scopeName = null, ?string $collectionName = null): BuildDeferredIndexesRequest
    {
        $request = [
            "bucket_name" => $bucketName
        ];
        if (!is_null($scopeName)) {
            $request["scope_name"] = $scopeName;
        }
        if (!is_null($collectionName)) {
            $request["collection_name"] = $collectionName;
        }
        return new BuildDeferredIndexesRequest($request);
    }
}
