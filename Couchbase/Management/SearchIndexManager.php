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

class SearchIndexManager implements SearchIndexManagerInterface
{
    /**
     * @var resource
     */
    private $core;

    /**
     * @param $core
     *
     * @internal
     * @since 4.1.5
     */
    public function __construct($core)
    {
        $this->core = $core;
    }

    /**
     * Fetches index with the specified name from the server
     *
     * @param string $indexName
     * @param GetSearchIndexOptions|null $options
     * @return SearchIndex
     *
     * @throws Couchbase\Exception\IndexNotFoundException
     *
     * @since 4.1.5
     */
    public function getIndex(string $indexName, GetSearchIndexOptions $options = null): SearchIndex
    {
        $result = Extension\searchIndexGet($this->core, $indexName, GetSearchIndexOptions::export($options));

        return SearchIndex::import($result);
    }

    /**
     * Fetches all indexes from the server
     *
     * @param GetAllSearchIndexesOptions|null $options
     * @return array
     *
     * @since 4.1.5
     */
    public function getAllIndexes(GetAllSearchIndexesOptions $options = null): array
    {
        $result = Extension\searchIndexGetAll($this->core, GetAllSearchIndexesOptions::export($options));
        $indexes = [];
        foreach ($result as $index) {
            $indexes[] = SearchIndex::import($index);
        }
        return $indexes;
    }

    /**
     * Upserts index to the server
     *
     * @param SearchIndex $indexDefinition
     * @param UpsertSearchIndexOptions|null $options
     *
     * @since 4.1.5
     */
    public function upsertIndex(SearchIndex $indexDefinition, UpsertSearchIndexOptions $options = null)
    {
        Extension\searchIndexUpsert($this->core, SearchIndex::export($indexDefinition), UpsertSearchIndexOptions::export($options));
    }

    /**
     * Drops an index from the server
     *
     * @param string $name
     * @param DropSearchIndexOptions|null $options
     *
     * @since 4.1.5
     */
    public function dropIndex(string $name, DropSearchIndexOptions $options = null)
    {
        Extension\searchIndexDrop($this->core, $name, DropSearchIndexOptions::export($options));
    }

    /**
     * Retrieves the number of documents that have been indexed for the index
     *
     * @param string $indexName
     * @param GetIndexedSearchIndexOptions|null $options
     * @return int
     *
     * @since 4.1.5
     */
    public function getIndexedDocumentsCount(string $indexName, GetIndexedSearchIndexOptions $options = null): int
    {
        $result = Extension\searchIndexGetDocumentsCount($this->core, $indexName, GetIndexedSearchIndexOptions::export($options));
        return $result['count'];
    }

    /**
     * Pauses updates and maintenance for the index
     *
     * @param string $indexName
     * @param PauseIngestSearchIndexOptions|null $options
     *
     * @since 4.1.5
     */
    public function pauseIngest(string $indexName, PauseIngestSearchIndexOptions $options = null)
    {
        Extension\searchIndexIngestPause($this->core, $indexName, PauseIngestSearchIndexOptions::export($options));
    }

    /**
     * Resumes updates and maintenance for the index
     *
     * @param string $indexName
     * @param ResumeIngestSearchIndexOptions|null $options
     *
     * @since 4.1.5
     */
    public function resumeIngest(string $indexName, ResumeIngestSearchIndexOptions $options = null)
    {
        Extension\searchIndexIngestResume($this->core, $indexName, ResumeIngestSearchIndexOptions::export($options));
    }

    /**
     * Allows querying against the index
     *
     * @param string $indexName
     * @param AllowQueryingSearchIndexOptions|null $options
     *
     * @since 4.1.5
     */
    public function allowQuerying(string $indexName, AllowQueryingSearchIndexOptions $options = null)
    {
        Extension\searchIndexQueryingAllow($this->core, $indexName, AllowQueryingSearchIndexOptions::export($options));
    }

    /**
     * Disallows querying against the index
     *
     * @param string $indexName
     * @param DisallowQueryingSearchIndexOptions|null $options
     *
     * @since 4.1.5
     */
    public function disallowQuerying(string $indexName, DisallowQueryingSearchIndexOptions $options = null)
    {
        Extension\searchIndexQueryingDisallow($this->core, $indexName, DisallowQueryingSearchIndexOptions::export($options));
    }

    /**
     * Freezes the assigment of index partitions to nodes
     *
     * @param string $indexName
     * @param FreezePlanSearchIndexOptions|null $options
     *
     * @since 4.1.5
     */
    public function freezePlan(string $indexName, FreezePlanSearchIndexOptions $options = null)
    {
        Extension\searchIndexPlanFreeze($this->core, $indexName, FreezePlanSearchIndexOptions::export($options));
    }

    /**
     * Unfreezes the assignment of index partitions to nodes
     * @param string $indexName
     * @param UnfreezePlanSearchIndexOptions|null $options
     *
     * @since 4.1.5
     */
    public function unfreezePlan(string $indexName, UnfreezePlanSearchIndexOptions $options = null)
    {
        Extension\searchIndexPlanUnfreeze($this->core, $indexName, UnfreezePlanSearchIndexOptions::export($options));
    }

    /**
     * Fetches the analysis of a document against a specific index
     *
     * @param string $indexName
     * @param $document
     * @param AnalyzeDocumentOptions|null $options
     * @return array
     *
     * @since 4.1.5
     */
    public function analyzeDocument(string $indexName, $document, AnalyzeDocumentOptions $options = null): array
    {
        $result = Extension\searchIndexDocumentAnalyze($this->core, $indexName, json_encode($document), AnalyzeDocumentOptions::export($options));
        return json_decode($result["analysis"], true);
    }
}
