<?php

namespace Couchbase\Management;

interface QueryIndexManagerInterface
{
    public function getAllIndexes(string $bucketName, GetAllQueryIndexesOptions $options = null): array;

    public function createIndex(string $bucketName, string $indexName, array $fields, CreateQueryIndexOptions $options = null);

    public function createPrimaryIndex(string $bucketName, CreateQueryPrimaryIndexOptions $options = null);

    public function dropIndex(string $bucketName, string $indexName, DropQueryIndexOptions $options = null);

    public function dropPrimaryIndex(string $bucketName, DropQueryPrimaryIndexOptions $options = null);

    public function buildDeferredIndexes(string $bucketName, BuildQueryIndexesOptions $options = null);

    public function watchIndexes(string $bucketName, array $indexNames, int $timeoutMilliseconds, WatchQueryIndexesOptions $options = null);
}
