<?php

namespace Couchbase\Management;

interface ScopeSearchIndexManagerInterface
{
    public function getIndex(string $indexName, GetSearchIndexOptions $options = null): SearchIndex;

    public function getAllIndexes(GetAllSearchIndexesOptions $options = null): array;

    public function upsertIndex(SearchIndex $indexDefinition, UpsertSearchIndexOptions $options = null);

    public function dropIndex(string $name, DropSearchIndexOptions $options = null);

    public function getIndexedDocumentsCount(string $indexName, GetIndexedSearchIndexOptions $options = null): int;

    public function pauseIngest(string $indexName, PauseIngestSearchIndexOptions $options = null);

    public function resumeIngest(string $indexName, ResumeIngestSearchIndexOptions $options = null);

    public function allowQuerying(string $indexName, AllowQueryingSearchIndexOptions $options = null);

    public function disallowQuerying(string $indexName, DisallowQueryingSearchIndexOptions $options = null);

    public function freezePlan(string $indexName, FreezePlanSearchIndexOptions $options = null);

    public function unfreezePlan(string $indexName, UnfreezePlanSearchIndexOptions $options = null);

    public function analyzeDocument(string $indexName, $document, AnalyzeDocumentOptions $options = null): array;
}
