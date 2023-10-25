<?php

namespace Couchbase\Management;

interface CollectionQueryIndexManagerInterface
{
    public function getAllIndexes(GetAllQueryIndexesOptions $options = null): array;

    public function createIndex(string $indexName, array $fields, CreateQueryIndexOptions $options = null);

    public function createPrimaryIndex(CreateQueryPrimaryIndexOptions $options = null);

    public function dropIndex(string $indexName, DropQueryIndexOptions $options = null);

    public function dropPrimaryIndex(DropQueryPrimaryIndexOptions $options = null);

    public function buildDeferredIndexes(BuildQueryIndexesOptions $options = null);

    public function watchIndexes(array $indexNames, int $timeoutMilliseconds, WatchQueryIndexesOptions $options = null);
}
