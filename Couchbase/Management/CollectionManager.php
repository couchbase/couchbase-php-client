<?php

/**
 * Copyright 2014-Present Couchbase, Inc.
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

namespace Couchbase\Management;

use Couchbase\Exception\InvalidArgumentException;
use Couchbase\Extension;

class CollectionManager implements CollectionManagerInterface
{
    /**
     * @var resource
     */
    private $core;
    private string $bucketName;
    public function __construct($core, string $bucketName)
    {
        $this->core = $core;
        $this->bucketName = $bucketName;
    }

    /**
     * Retrieves all scopes within the bucket
     *
     * @param GetAllScopesOptions|null $options The options to use when retrieving the scopes
     *
     * @see ScopeSpec
     * @return array array of scopes within the bucket
     */
    public function getAllScopes(GetAllScopesOptions $options = null): array
    {
        $result = Extension\scopeGetAll($this->core, $this->bucketName, GetAllScopesOptions::export($options));
        $scopes = [];
        foreach ($result['scopes'] as $scope) {
            $scopes[] = ScopeSpec::import($scope);
        }
        return $scopes;
    }

    /**
     * Create a new scope
     *
     * @param string $name name of the scope
     * @param CreateScopeOptions|null $options the options to use when creating a scope
     * @since 4.1.3
     */
    public function createScope(string $name, CreateScopeOptions $options = null)
    {
        Extension\scopeCreate($this->core, $this->bucketName, $name, CreateScopeOptions::export($options));
    }

    /**
     * Drops an existing scope
     *
     * @param string $name of the scope to drop
     * @param DropScopeOptions|null $options the options to use when dropping a scope
     * @since 4.1.3
     */
    public function dropScope(string $name, DropScopeOptions $options = null)
    {
        Extension\scopeDrop($this->core, $this->bucketName, $name, DropScopeOptions::export($options));
    }

    /**
     * Creates a new collection
     *
     * Note: The (CollectionSpec, CreateCollectionOptions) API is now deprecated.
     *
     * @param string|CollectionSpec $scopeName The name of the scope on which to create the collection. Deprecated: CollectionSpec
     * @param string|CreateCollectionOptions $collectionName The name of the collection. Deprecated: CreateCollectionOptions
     * @param CreateCollectionSettings|null $settings The settings to apply on the collection
     * @param CreateCollectionOptions|null $options The options to use when creating a collection
     *
     * @throws InvalidArgumentException
     * @since 4.1.6
     */
    public function createCollection($scopeName, $collectionName = null, $settings = null, $options = null)
    {
        if (is_string($scopeName) && is_null($collectionName)) {
            throw new InvalidArgumentException("Collection name cannot be null if using the (scopeName, collectionName, settings, options) API");
        }
        // Deprecated usage conversion for (CollectionSpec, CreateCollectionOptions)
        if ($scopeName instanceof  CollectionSpec) {
            $options = $collectionName;
            $collectionName = $scopeName->name();
            $settings = new CreateCollectionSettings($scopeName->maxExpiry(), $scopeName->history());
            $scopeName = $scopeName->scopeName();
        }

        Extension\collectionCreate($this->core, $this->bucketName, $scopeName, $collectionName, CreateCollectionSettings::export($settings), CreateCollectionOptions::export($options));
    }

    /**
     * Drops an existing collection
     *
     * Note: The (CollectionSpec, DropCollectionOptions) API is now deprecated.
     *
     * @param string|CollectionSpec $scopeName The name of the scope on which the collection exists.
     * @param string|DropCollectionOptions|null $collectionName The name of the collection. Only nullable to support the deprecated API.
     * @param DropcollectionOptions|null $options The options to use when dropping a collection
     *
     * @throws InvalidArgumentException
     * @since 4.1.3
     */
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
        Extension\collectionDrop($this->core, $this->bucketName, $scopeName, $collectionName, DropCollectionOptions::export($options));
    }

    /**
     * Updates an existing collection
     *
     * @param string $scopeName name of the scope on which the collection exists
     * @param string $collectionName collection name
     * @param UpdateCollectionSettings $settings Settings to update on the collection
     * @param UpdateCollectionOptions|null $options The options to use when updating the collection
     * @since 4.1.6
     */
    public function updateCollection(string $scopeName, string $collectionName, UpdateCollectionSettings $settings, UpdateCollectionOptions $options = null)
    {
        Extension\collectionUpdate($this->core, $this->bucketName, $scopeName, $collectionName, UpdateCollectionSettings::export($settings), UpdateBucketOptions::export($options));
    }
}
