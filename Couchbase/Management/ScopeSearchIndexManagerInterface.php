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

interface ScopeSearchIndexManagerInterface
{
    public function getIndex(string $indexName, ?GetSearchIndexOptions $options = null): SearchIndex;

    public function getAllIndexes(?GetAllSearchIndexesOptions $options = null): array;

    public function upsertIndex(SearchIndex $indexDefinition, ?UpsertSearchIndexOptions $options = null);

    public function dropIndex(string $name, ?DropSearchIndexOptions $options = null);

    public function getIndexedDocumentsCount(string $indexName, ?GetIndexedSearchIndexOptions $options = null): int;

    public function pauseIngest(string $indexName, ?PauseIngestSearchIndexOptions $options = null);

    public function resumeIngest(string $indexName, ?ResumeIngestSearchIndexOptions $options = null);

    public function allowQuerying(string $indexName, ?AllowQueryingSearchIndexOptions $options = null);

    public function disallowQuerying(string $indexName, ?DisallowQueryingSearchIndexOptions $options = null);

    public function freezePlan(string $indexName, ?FreezePlanSearchIndexOptions $options = null);

    public function unfreezePlan(string $indexName, ?UnfreezePlanSearchIndexOptions $options = null);

    public function analyzeDocument(string $indexName, $document, ?AnalyzeDocumentOptions $options = null): array;
}
