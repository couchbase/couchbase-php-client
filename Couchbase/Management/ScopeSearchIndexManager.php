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

class ScopeSearchIndexManager implements ScopeSearchIndexManagerInterface
{
    private $core;
    private string $bucketName;
    private string $scopeName;

    public function __construct($core, string $bucketName, string $scopeName)
    {
        $this->core = $core;
        $this->bucketName = $bucketName;
        $this->scopeName = $scopeName;
    }

    /**
     * Fetches scope-level index with the specified name from the server
     *
     * @param string $indexName
     * @param GetSearchIndexOptions|null $options
     * @return SearchIndex
     *
     * @since 4.1.7
     */
    public function getIndex(string $indexName, GetSearchIndexOptions $options = null): SearchIndex
    {
        $result = Extension\scopeSearchIndexGet($this->core, $this->bucketName, $this->scopeName, $indexName, GetSearchIndexOptions::export($options));

        return SearchIndex::import($result);
    }

    /**
     * Fetches all scope-level indexes from the server
     *
     * @param GetAllSearchIndexesOptions|null $options
     * @return array
     *
     * @since 4.1.7
     */
    public function getAllIndexes(GetAllSearchIndexesOptions $options = null): array
    {
        $result = Extension\scopeSearchIndexGetAll($this->core, $this->bucketName, $this->scopeName, GetAllSearchIndexesOptions::export($options));
        $indexes = [];
        foreach ($result as $index) {
            $indexes[] = SearchIndex::import($index);
        }
        return $indexes;
    }

    /**
     * Upserts scope-level index to the server
     *
     * @param SearchIndex $indexDefinition
     * @param UpsertSearchIndexOptions|null $options
     *
     * @since 4.1.7
     */
    public function upsertIndex(SearchIndex $indexDefinition, UpsertSearchIndexOptions $options = null)
    {
        Extension\scopeSearchIndexUpsert($this->core, $this->bucketName, $this->scopeName, SearchIndex::export($indexDefinition), UpsertSearchIndexOptions::export($options));
    }

    /**
     * Drops a scope-level index from the server
     *
     * @param string $name
     * @param DropSearchIndexOptions|null $options
     *
     * @since 4.1.7
     */
    public function dropIndex(string $name, DropSearchIndexOptions $options = null)
    {
        Extension\scopeSearchIndexDrop($this->core, $this->bucketName, $this->scopeName, $name, DropSearchIndexOptions::export($options));
    }

    /**
     * Retrieves the number of documents that have been indexed for the scope-level index
     *
     * @param string $indexName
     * @param GetIndexedSearchIndexOptions|null $options
     *
     * @return int
     * @since 4.1.7
     */
    public function getIndexedDocumentsCount(string $indexName, GetIndexedSearchIndexOptions $options = null): int
    {
        $result = Extension\scopeSearchIndexGetDocumentsCount($this->core, $this->bucketName, $this->scopeName, $indexName, GetIndexedSearchIndexOptions::export($options));
        return $result['count'];
    }

    /**
     * Pauses updates and maintenance for the scope-level index
     *
     * @param string $indexName
     * @param PauseIngestSearchIndexOptions|null $options
     *
     * @since 4.1.7
     */
    public function pauseIngest(string $indexName, PauseIngestSearchIndexOptions $options = null)
    {
        Extension\scopeSearchIndexIngestPause($this->core, $this->bucketName, $this->scopeName, $indexName, PauseIngestSearchIndexOptions::export($options));
    }

    /**
     * Resumes updates and maintenance for the scope-level index
     *
     * @param string $indexName
     * @param ResumeIngestSearchIndexOptions|null $options
     *
     * @since 4.1.7
     */
    public function resumeIngest(string $indexName, ResumeIngestSearchIndexOptions $options = null)
    {
        Extension\scopeSearchIndexIngestResume($this->core, $this->bucketName, $this->scopeName, $indexName, ResumeIngestSearchIndexOptions::export($options));
    }

    /**
     * Allows querying against the scope-level index
     *
     * @param string $indexName
     * @param AllowQueryingSearchIndexOptions|null $options
     *
     * @since 4.1.7
     */
    public function allowQuerying(string $indexName, AllowQueryingSearchIndexOptions $options = null)
    {
        Extension\scopeSearchIndexQueryingAllow($this->core, $this->bucketName, $this->scopeName, $indexName, AllowQueryingSearchIndexOptions::export($options));
    }

    /**
     * Disallows querying against the scope-level index
     *
     * @param string $indexName
     * @param DisallowQueryingSearchIndexOptions|null $options
     *
     * @since 4.1.7
     */
    public function disallowQuerying(string $indexName, DisallowQueryingSearchIndexOptions $options = null)
    {
        Extension\scopeSearchIndexQueryingDisallow($this->core, $this->bucketName, $this->scopeName, $indexName, DisallowQueryingSearchIndexOptions::export($options));
    }

    /**
     * Freezes the assigment of scope-level index partitions to nodes
     *
     * @param string $indexName
     * @param FreezePlanSearchIndexOptions|null $options
     *
     * @since 4.1.7
     */
    public function freezePlan(string $indexName, FreezePlanSearchIndexOptions $options = null)
    {
        Extension\scopeSearchIndexPlanFreeze($this->core, $this->bucketName, $this->scopeName, $indexName, FreezePlanSearchIndexOptions::export($options));
    }

    /**
     * Unfreezes the assignment of index partitions to nodes
     *
     * @param string $indexName
     * @param UnfreezePlanSearchIndexOptions|null $options
     *
     * @since 4.1.7
     */
    public function unfreezePlan(string $indexName, UnfreezePlanSearchIndexOptions $options = null)
    {
        Extension\scopeSearchIndexPlanUnfreeze($this->core, $this->bucketName, $this->scopeName, $indexName, UnfreezePlanSearchIndexOptions::export($options));
    }

    /**
     * Fetches the analysis of a document against a specific scope-level index
     *
     * @param string $indexName
     * @param $document
     * @param AnalyzeDocumentOptions|null $options
     *
     * @return array
     * @since 4.1.7
     */
    public function analyzeDocument(string $indexName, $document, AnalyzeDocumentOptions $options = null): array
    {
        $result = Extension\scopeSearchIndexDocumentAnalyze($this->core, $this->bucketName, $this->scopeName, $indexName, json_encode($document), AnalyzeDocumentOptions::export($options));
        return json_decode($result["analysis"], true);
    }
}
