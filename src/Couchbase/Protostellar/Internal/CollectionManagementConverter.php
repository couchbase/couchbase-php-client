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

namespace Couchbase\Protostellar\Internal;

use Couchbase\Management\CollectionSpec;
use Couchbase\Management\ScopeSpec;
use Couchbase\Protostellar\Generated\Admin\Collection\V1\ListCollectionsResponse;

class CollectionManagementConverter
{
    public static function convertGetAllScopesResult(ListCollectionsResponse $response): array
    {
        $scopeSpecs = [];
        foreach ($response->getScopes() as $scope) {
            $scopeSpecs[] = self::convertScopeToScopeSpec($scope);
        }
        return $scopeSpecs;
    }

    public static function getCreateCollectionRequest(string $bucketName, CollectionSpec $collectionSpec): array
    {
        $exportedSpec = CollectionSpec::export($collectionSpec);
        $request = [
            "bucket_name" => $bucketName,
            "scope_name" => $exportedSpec['scopeName'],
            "collection_name" => $exportedSpec['collectionName'],
        ];
        if (isset($exportedSpec['maxExpiry'])) {
            $request['max_expiry_secs'] = $exportedSpec['maxExpiry'];
        }
        return $request;
    }

    public static function getDropCollectionRequest(string $bucketName, CollectionSpec $collectionSpec): array
    {
        $exportedSpec = CollectionSpec::export($collectionSpec);
        return [
            "bucket_name" => $bucketName,
            "scope_name" => $exportedSpec['scopeName'],
            "collection_name" => $exportedSpec["name"]
        ];
    }

    private static function convertScopeToScopeSpec(ListCollectionsResponse\Scope $scope): ScopeSpec
    {
        return ScopeSpec::build(
            $scope->getName(),
            self::convertCollectionsToCollectionSpecs($scope->getName(), Sharedutils::toArray($scope->getCollections()))
        );
    }

    private static function convertCollectionsToCollectionSpecs(string $scopeName, array $collections): array
    {
        $collectionSpecs = [];
        foreach ($collections as $collection) {
            $collectionSpec = new CollectionSpec($collection['name'], $scopeName);
            if ($collection->hasMaxExpirySecs()) {
                $collectionSpec->setMaxExpiry($collection->getMaxExpirySecs());
            }
            $collectionSpecs[] = $collectionSpec;
        }
        return $collectionSpecs;
    }
}
