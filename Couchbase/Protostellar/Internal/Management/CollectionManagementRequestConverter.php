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

use Couchbase\Exception\InvalidArgumentException;
use Couchbase\Management\CreateCollectionSettings;
use Couchbase\Protostellar\Generated\Admin\Collection\V1\CreateCollectionRequest;
use Couchbase\Protostellar\Generated\Admin\Collection\V1\CreateScopeRequest;
use Couchbase\Protostellar\Generated\Admin\Collection\V1\DeleteCollectionRequest;
use Couchbase\Protostellar\Generated\Admin\Collection\V1\DeleteScopeRequest;
use Couchbase\Protostellar\Generated\Admin\Collection\V1\ListCollectionsRequest;

class CollectionManagementRequestConverter
{
    /**
     * @throws InvalidArgumentException
     */
    public static function getCreateCollectionRequest(string $bucketName, string $scopeName, string $collectionName, CreateCollectionSettings $settings = null): CreateCollectionRequest
    {
        $exportedSettings = CreateCollectionSettings::export($settings);
        $request = [
            "bucket_name" => $bucketName,
            "scope_name" => $scopeName,
            "collection_name" => $collectionName,
        ];
        if (isset($exportedSettings['maxExpiry'])) {
            $request['max_expiry_secs'] = $exportedSettings['maxExpiry'];
        }
        if (isset($exportedSettings['history'])) {
            throw new InvalidArgumentException("History is not yet supported in CNG");
        }
        return new CreateCollectionRequest($request);
    }

    public static function getDropCollectionRequest(string $bucketName, string $scopeName, string $collectionName): DeleteCollectionRequest
    {
        $request = [
            "bucket_name" => $bucketName,
            "scope_name" => $scopeName,
            "collection_name" => $collectionName
        ];
        return new DeleteCollectionRequest($request);
    }

    public static function getGetAllScopesRequest(string $bucketName): ListCollectionsRequest
    {
        $request = [
            'bucket_name' => $bucketName
        ];
        return new ListCollectionsRequest($request);
    }

    public static function getCreateScopeRequest(string $bucketName, string $scopeName): CreateScopeRequest
    {
        $request = [
            'bucket_name' => $bucketName,
            'scope_name' => $scopeName
        ];
        return new CreateScopeRequest($request);
    }

    public static function getDropScopeRequest(string $bucketName, string $scopeName): DeleteScopeRequest
    {
        $request = [
            'bucket_name' => $bucketName,
            'scope_name' => $scopeName
        ];
        return new DeleteScopeRequest($request);
    }
}
