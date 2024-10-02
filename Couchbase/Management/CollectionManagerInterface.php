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

namespace Couchbase\Management;

interface CollectionManagerInterface
{
    public function getAllScopes(?GetAllScopesOptions $options = null): array;

    public function createScope(string $name, ?CreateScopeOptions $options = null);

    public function dropScope(string $name, ?DropScopeOptions $options = null);

    public function createCollection($scopeName, $collectionName = null, $settings = null, $options = null);

    public function dropCollection($scopeName, $collectionName = null, $options = null);

    public function updateCollection(string $scopeName, string $collectionName, UpdateCollectionSettings $settings, ?UpdateCollectionOptions $options = null);
}
