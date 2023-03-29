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

use Couchbase\Extension;

class CollectionManager
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
     * @param CollectionSpec $collection The spec of the collection
     * @param CreateCollectionOptions|null $options The options to use when creating a collection
     * @since 4.1.3
     */
    public function createCollection(CollectionSpec $collection, CreateCollectionOptions $options = null)
    {
        Extension\collectionCreate($this->core, $this->bucketName, CollectionSpec::export($collection), CreateCollectionOptions::export($options));
    }

    /**
     * Drops an existing collection
     *
     * @param CollectionSpec $collection The spec of the collection to drop
     * @param DropCollectionOptions|null $options The options to use when dropping a collection
     * @since 4.1.3
     */
    public function dropCollection(CollectionSpec $collection, DropCollectionOptions $options = null)
    {
        Extension\collectionDrop($this->core, $this->bucketName, CollectionSpec::export($collection), DropCollectionOptions::export($options));
    }
}
