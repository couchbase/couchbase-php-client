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

use Couchbase\Protostellar\Generated\Admin\Search\V1\AllowIndexQueryingRequest;
use Couchbase\Protostellar\Generated\Admin\Search\V1\AnalyzeDocumentRequest;
use Couchbase\Protostellar\Generated\Admin\Search\V1\CreateIndexRequest;
use Couchbase\Protostellar\Generated\Admin\Search\V1\DeleteIndexRequest;
use Couchbase\Protostellar\Generated\Admin\Search\V1\DisallowIndexQueryingRequest;
use Couchbase\Protostellar\Generated\Admin\Search\V1\FreezeIndexPlanRequest;
use Couchbase\Protostellar\Generated\Admin\Search\V1\GetIndexedDocumentsCountRequest;
use Couchbase\Protostellar\Generated\Admin\Search\V1\GetIndexRequest;
use Couchbase\Protostellar\Generated\Admin\Search\V1\ListIndexesRequest;
use Couchbase\Protostellar\Generated\Admin\Search\V1\PauseIndexIngestRequest;
use Couchbase\Protostellar\Generated\Admin\Search\V1\ResumeIndexIngestRequest;
use Couchbase\Protostellar\Generated\Admin\Search\V1\UnfreezeIndexPlanRequest;
use Couchbase\Protostellar\Generated\Admin\Search\V1\UpdateIndexRequest;

class SearchIndexManagementRequestConverter
{
    public static function getGetIndexRequest(string $indexName, string $bucketName = null, string $scopeName = null): GetIndexRequest
    {
        $request = [
            'name' => $indexName
        ];
        if (!is_null($bucketName)) {
            $request["bucket_name"] = $bucketName;
        }
        if (!is_null($scopeName)) {
            $request["scope_name"] = $scopeName;
        }
        return new GetIndexRequest($request);
    }

    public static function getGetAllIndexesRequest(string $bucketName = null, string $scopeName = null): ListIndexesRequest
    {
        $request = [];
        if (!is_null($bucketName)) {
            $request["bucket_name"] = $bucketName;
        }
        if (!is_null($scopeName)) {
            $request["scope_name"] = $scopeName;
        }
        return new ListIndexesRequest($request);
    }

    public static function getCreateIndexRequest(array $index, string $bucketName = null, string $scopeName = null): CreateIndexRequest
    {
        $request = [
            "name" => $index["name"],
            "type" => $index["type"],
            "source_name" => $index["sourceName"],
            "source_type" => $index["sourceType"]
        ];
        if (isset($index["params"])) {
            $request["params"] = json_decode($index["params"], true);
        }
        if (isset($index["planParams"])) {
            $request["plan_params"] = json_decode($index["planParams"], true);
        }
        if (isset($index["sourceParams"])) {
            $request["source_params"] = json_decode($index["sourceParams"], true);
        }
        if (isset($index["sourceUuid"])) {
            $request["source_uuid"] = $index["sourceUuid"];
        }
        if (!is_null($bucketName)) {
            $request["bucket_name"] = $bucketName;
        }
        if (!is_null($scopeName)) {
            $request["scope_name"] = $scopeName;
        }
        return new CreateIndexRequest($request);
    }

    public static function getUpdateIndexRequest(array $index, string $bucketName = null, string $scopeName = null): UpdateIndexRequest
    {
        $request = [
            "index" => self::getIndex($index)
        ];
        if (!is_null($bucketName)) {
            $request["bucket_name"] = $bucketName;
        }
        if (!is_null($scopeName)) {
            $request["scope_name"] = $scopeName;
        }
        return new UpdateIndexRequest($request);
    }

    public static function getDropIndexRequest(string $indexName, string $bucketName = null, string $scopeName = null): DeleteIndexRequest
    {
        $request = [
            "name" => $indexName
        ];
        if (!is_null($bucketName)) {
            $request["bucket_name"] = $bucketName;
        }
        if (!is_null($scopeName)) {
            $request["scope_name"] = $scopeName;
        }
        return new DeleteIndexRequest($request);
    }

    public static function getGetIndexedDocumentCountRequest(string $indexName, string $bucketName = null, string $scopeName = null): GetIndexedDocumentsCountRequest
    {
        $request = [
            "name" => $indexName
        ];
        if (!is_null($bucketName)) {
            $request["bucket_name"] = $bucketName;
        }
        if (!is_null($scopeName)) {
            $request["scope_name"] = $scopeName;
        }
        return new GetIndexedDocumentsCountRequest($request);
    }

    public static function getPauseIngestRequest(string $indexName, string $bucketName = null, string $scopeName = null): PauseIndexIngestRequest
    {
        $request = [
            "name" => $indexName
        ];
        if (!is_null($bucketName)) {
            $request["bucket_name"] = $bucketName;
        }
        if (!is_null($scopeName)) {
            $request["scope_name"] = $scopeName;
        }
        return new PauseIndexIngestRequest($request);
    }

    public static function getResumeIngestRequest(string $indexName, string $bucketName = null, string $scopeName = null): ResumeIndexIngestRequest
    {
        $request = [
            "name" => $indexName
        ];
        if (!is_null($bucketName)) {
            $request["bucket_name"] = $bucketName;
        }
        if (!is_null($scopeName)) {
            $request["scope_name"] = $scopeName;
        }
        return new ResumeIndexIngestRequest($request);
    }

    public static function getAllowQueryingRequest(string $indexName, string $bucketName = null, string $scopeName = null): AllowIndexQueryingRequest
    {
        $request = [
            "name" => $indexName
        ];
        if (!is_null($bucketName)) {
            $request["bucket_name"] = $bucketName;
        }
        if (!is_null($scopeName)) {
            $request["scope_name"] = $scopeName;
        }
        return new AllowIndexQueryingRequest($request);
    }

    public static function getDisallowQueryingRequest(string $indexName, string $bucketName = null, string $scopeName = null): DisallowIndexQueryingRequest
    {
        $request = [
            "name" => $indexName
        ];
        if (!is_null($bucketName)) {
            $request["bucket_name"] = $bucketName;
        }
        if (!is_null($scopeName)) {
            $request["scope_name"] = $scopeName;
        }
        return new DisallowIndexQueryingRequest($request);
    }

    public static function getFreezePlanRequest(string $indexName, string $bucketName = null, string $scopeName = null): FreezeIndexPlanRequest
    {
        $request = [
            "name" => $indexName
        ];
        if (!is_null($bucketName)) {
            $request["bucket_name"] = $bucketName;
        }
        if (!is_null($scopeName)) {
            $request["scope_name"] = $scopeName;
        }
        return new FreezeIndexPlanRequest($request);
    }

    public static function getUnfreezePlanRequest(string $indexName, string $bucketName = null, string $scopeName = null): UnfreezeIndexPlanRequest
    {
        $request = [
            "name" => $indexName
        ];
        if (!is_null($bucketName)) {
            $request["bucket_name"] = $bucketName;
        }
        if (!is_null($scopeName)) {
            $request["scope_name"] = $scopeName;
        }
        return new UnfreezeIndexPlanRequest($request);
    }

    public static function getAnalyzeDocumentRequest(string $indexName, $document, string $bucketName = null, string $scopeName = null): AnalyzeDocumentRequest
    {
        $request = [
            "name" => $indexName,
            "doc" => json_encode($document)
        ];
        if (!is_null($bucketName)) {
            $request["bucket_name"] = $bucketName;
        }
        if (!is_null($scopeName)) {
            $request["scope_name"] = $scopeName;
        }
        return new AnalyzeDocumentRequest($request);
    }
    private static function getIndex(array $indexDefinition): array
    {
        $index = [
            "name" => $indexDefinition["name"],
            "type" => $indexDefinition["type"],
            "source_name" => $indexDefinition["sourceName"],
            "source_type" => $indexDefinition["sourceType"],
            "uuid" => $indexDefinition["uuid"],
        ];
        if (isset($indexDefinition["params"])) {
            $index["params"] = json_decode($indexDefinition["params"], true);
        }
        if (isset($indexDefinition["planParams"])) {
            $index["plan_params"] = json_decode($indexDefinition["planParams"], true);
        }
        if (isset($indexDefinition["sourceParams"])) {
            $index["source_params"] = json_decode($indexDefinition["sourceParams"], true);
        }
        if (isset($indexDefinition["sourceUuid"])) {
            $index["source_uuid"] = $indexDefinition["sourceUuid"];
        }
        return $index;
    }
}
