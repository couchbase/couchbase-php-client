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
use Couchbase\Observability\ObservabilityContext;
use Couchbase\Observability\ObservabilityConstants;

class SearchIndexManager implements SearchIndexManagerInterface
{
    /**
     * @var resource
     */
    private $core;

    private ObservabilityContext $observability;

    /**
     * @param $core
     * @param ObservabilityContext $observability
     *
     * @internal
     * @since 4.1.5
     */
    public function __construct($core, ObservabilityContext $observability)
    {
        $this->core = $core;
        $this->observability = ObservabilityContext::from(
            $observability,
            service: ObservabilityConstants::ATTR_VALUE_SERVICE_SEARCH
        );
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
    public function getIndex(string $indexName, ?GetSearchIndexOptions $options = null): SearchIndex
    {
        return $this->observability->recordOperation(
            ObservabilityConstants::OP_SM_GET_INDEX,
            GetSearchIndexOptions::getParentSpan($options),
            function ($obsHandler) use ($indexName, $options) {
                $function = COUCHBASE_EXTENSION_NAMESPACE . '\\searchIndexGet';
                $result = $function($this->core, $indexName, GetSearchIndexOptions::export($options));

                return SearchIndex::import($result);
            }
        );
    }

    /**
     * Fetches all indexes from the server
     *
     * @param GetAllSearchIndexesOptions|null $options
     * @return array
     *
     * @since 4.1.5
     */
    public function getAllIndexes(?GetAllSearchIndexesOptions $options = null): array
    {
        return $this->observability->recordOperation(
            ObservabilityConstants::OP_SM_GET_ALL_INDEXES,
            GetAllSearchIndexesOptions::getParentSpan($options),
            function ($obsHandler) use ($options) {
                $function = COUCHBASE_EXTENSION_NAMESPACE . '\\searchIndexGetAll';
                $result = $function($this->core, GetAllSearchIndexesOptions::export($options));
                $indexes = [];
                foreach ($result as $index) {
                    $indexes[] = SearchIndex::import($index);
                }
                return $indexes;
            }
        );
    }

    /**
     * Upserts index to the server
     *
     * @param SearchIndex $indexDefinition
     * @param UpsertSearchIndexOptions|null $options
     *
     * @since 4.1.5
     */
    public function upsertIndex(SearchIndex $indexDefinition, ?UpsertSearchIndexOptions $options = null)
    {
        $this->observability->recordOperation(
            ObservabilityConstants::OP_SM_UPSERT_INDEX,
            UpsertSearchIndexOptions::getParentSpan($options),
            function ($obsHandler) use ($indexDefinition, $options) {
                $function = COUCHBASE_EXTENSION_NAMESPACE . '\\searchIndexUpsert';
                $function($this->core, SearchIndex::export($indexDefinition), UpsertSearchIndexOptions::export($options));
            }
        );
    }

    /**
     * Drops an index from the server
     *
     * @param string $name
     * @param DropSearchIndexOptions|null $options
     *
     * @since 4.1.5
     */
    public function dropIndex(string $name, ?DropSearchIndexOptions $options = null)
    {
        $this->observability->recordOperation(
            ObservabilityConstants::OP_SM_DROP_INDEX,
            DropSearchIndexOptions::getParentSpan($options),
            function ($obsHandler) use ($name, $options) {
                $function = COUCHBASE_EXTENSION_NAMESPACE . '\\searchIndexDrop';
                $function($this->core, $name, DropSearchIndexOptions::export($options));
            }
        );
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
    public function getIndexedDocumentsCount(string $indexName, ?GetIndexedSearchIndexOptions $options = null): int
    {
        return $this->observability->recordOperation(
            ObservabilityConstants::OP_SM_GET_INDEXED_DOCUMENTS_COUNT,
            GetIndexedSearchIndexOptions::getParentSpan($options),
            function ($obsHandler) use ($indexName, $options) {
                $function = COUCHBASE_EXTENSION_NAMESPACE . '\\searchIndexGetDocumentsCount';
                $result = $function($this->core, $indexName, GetIndexedSearchIndexOptions::export($options));
                return $result['count'];
            }
        );
    }

    /**
     * Pauses updates and maintenance for the index
     *
     * @param string $indexName
     * @param PauseIngestSearchIndexOptions|null $options
     *
     * @since 4.1.5
     */
    public function pauseIngest(string $indexName, ?PauseIngestSearchIndexOptions $options = null)
    {
        $this->observability->recordOperation(
            ObservabilityConstants::OP_SM_PAUSE_INGEST,
            PauseIngestSearchIndexOptions::getParentSpan($options),
            function ($obsHandler) use ($indexName, $options) {
                $function = COUCHBASE_EXTENSION_NAMESPACE . '\\searchIndexIngestPause';
                $function($this->core, $indexName, PauseIngestSearchIndexOptions::export($options));
            }
        );
    }

    /**
     * Resumes updates and maintenance for the index
     *
     * @param string $indexName
     * @param ResumeIngestSearchIndexOptions|null $options
     *
     * @since 4.1.5
     */
    public function resumeIngest(string $indexName, ?ResumeIngestSearchIndexOptions $options = null)
    {
        $this->observability->recordOperation(
            ObservabilityConstants::OP_SM_RESUME_INGEST,
            ResumeIngestSearchIndexOptions::getParentSpan($options),
            function ($obsHandler) use ($indexName, $options) {
                $function = COUCHBASE_EXTENSION_NAMESPACE . '\\searchIndexIngestResume';
                $function($this->core, $indexName, ResumeIngestSearchIndexOptions::export($options));
            }
        );
    }

    /**
     * Allows querying against the index
     *
     * @param string $indexName
     * @param AllowQueryingSearchIndexOptions|null $options
     *
     * @since 4.1.5
     */
    public function allowQuerying(string $indexName, ?AllowQueryingSearchIndexOptions $options = null)
    {
        $this->observability->recordOperation(
            ObservabilityConstants::OP_SM_ALLOW_QUERYING,
            AllowQueryingSearchIndexOptions::getParentSpan($options),
            function ($obsHandler) use ($indexName, $options) {
                $function = COUCHBASE_EXTENSION_NAMESPACE . '\\searchIndexQueryingAllow';
                $function($this->core, $indexName, AllowQueryingSearchIndexOptions::export($options));
            }
        );
    }

    /**
     * Disallows querying against the index
     *
     * @param string $indexName
     * @param DisallowQueryingSearchIndexOptions|null $options
     *
     * @since 4.1.5
     */
    public function disallowQuerying(string $indexName, ?DisallowQueryingSearchIndexOptions $options = null)
    {
        $this->observability->recordOperation(
            ObservabilityConstants::OP_SM_DISALLOW_QUERYING,
            DisallowQueryingSearchIndexOptions::getParentSpan($options),
            function ($obsHandler) use ($indexName, $options) {
                $function = COUCHBASE_EXTENSION_NAMESPACE . '\\searchIndexQueryingDisallow';
                $function($this->core, $indexName, DisallowQueryingSearchIndexOptions::export($options));
            }
        );
    }

    /**
     * Freezes the assigment of index partitions to nodes
     *
     * @param string $indexName
     * @param FreezePlanSearchIndexOptions|null $options
     *
     * @since 4.1.5
     */
    public function freezePlan(string $indexName, ?FreezePlanSearchIndexOptions $options = null)
    {
        $this->observability->recordOperation(
            ObservabilityConstants::OP_SM_FREEZE_PLAN,
            FreezePlanSearchIndexOptions::getParentSpan($options),
            function ($obsHandler) use ($indexName, $options) {
                $function = COUCHBASE_EXTENSION_NAMESPACE . '\\searchIndexPlanFreeze';
                $function($this->core, $indexName, FreezePlanSearchIndexOptions::export($options));
            }
        );
    }

    /**
     * Unfreezes the assignment of index partitions to nodes
     * @param string $indexName
     * @param UnfreezePlanSearchIndexOptions|null $options
     *
     * @since 4.1.5
     */
    public function unfreezePlan(string $indexName, ?UnfreezePlanSearchIndexOptions $options = null)
    {
        $this->observability->recordOperation(
            ObservabilityConstants::OP_SM_UNFREEZE_PLAN,
            UnfreezePlanSearchIndexOptions::getParentSpan($options),
            function ($obsHandler) use ($indexName, $options) {
                $function = COUCHBASE_EXTENSION_NAMESPACE . '\\searchIndexPlanUnfreeze';
                $function($this->core, $indexName, UnfreezePlanSearchIndexOptions::export($options));
            }
        );
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
    public function analyzeDocument(string $indexName, $document, ?AnalyzeDocumentOptions $options = null): array
    {
        return $this->observability->recordOperation(
            ObservabilityConstants::OP_SM_ANALYZE_DOCUMENT,
            AnalyzeDocumentOptions::getParentSpan($options),
            function ($obsHandler) use ($indexName, $document, $options) {
                $function = COUCHBASE_EXTENSION_NAMESPACE . '\\searchIndexDocumentAnalyze';
                $result = $function($this->core, $indexName, json_encode($document), AnalyzeDocumentOptions::export($options));
                return json_decode($result["analysis"], true);
            }
        );
    }
}
