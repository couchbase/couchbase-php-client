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

interface CollectionQueryIndexManagerInterface
{
    public function getAllIndexes(?GetAllQueryIndexesOptions $options = null): array;

    public function createIndex(string $indexName, array $fields, ?CreateQueryIndexOptions $options = null);

    public function createPrimaryIndex(?CreateQueryPrimaryIndexOptions $options = null);

    public function dropIndex(string $indexName, ?DropQueryIndexOptions $options = null);

    public function dropPrimaryIndex(?DropQueryPrimaryIndexOptions $options = null);

    public function buildDeferredIndexes(?BuildQueryIndexesOptions $options = null);

    public function watchIndexes(array $indexNames, int $timeoutMilliseconds, ?WatchQueryIndexesOptions $options = null);
}
