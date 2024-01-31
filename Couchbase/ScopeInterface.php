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

namespace Couchbase;

use Couchbase\Management\ScopeSearchIndexManagerInterface;

/**
 * Scope is an object for providing access to collections.
 */
interface ScopeInterface
{
    public function name(): string;

    public function collection(string $name): CollectionInterface;

    public function query(string $statement, QueryOptions $options = null): QueryResult;

    public function search(string $indexName, SearchRequest $request, SearchOptions $options = null): SearchResult;

    public function analyticsQuery(string $statement, AnalyticsOptions $options = null): AnalyticsResult;

    public function searchIndexes(): ScopeSearchIndexManagerInterface;
}
