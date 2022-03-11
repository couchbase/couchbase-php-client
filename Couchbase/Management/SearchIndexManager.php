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

class SearchIndexManager
{
    public function getIndex(string $name): SearchIndex
    {
    }

    public function getAllIndexes(): array
    {
    }

    public function upsertIndex(SearchIndex $indexDefinition)
    {
    }

    public function dropIndex(string $name)
    {
    }

    public function getIndexedDocumentsCount(string $indexName): int
    {
    }

    public function pauseIngest(string $indexName)
    {
    }

    public function resumeIngest(string $indexName)
    {
    }

    public function allowQuerying(string $indexName)
    {
    }

    public function disallowQuerying(string $indexName)
    {
    }

    public function freezePlan(string $indexName)
    {
    }

    public function unfreezePlan(string $indexName)
    {
    }

    public function analyzeDocument(string $indexName, $document)
    {
    }
}
