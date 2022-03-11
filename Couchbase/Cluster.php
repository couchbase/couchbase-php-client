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

/**
 * Cluster is an object containing functionality for performing cluster level operations
 * against a cluster and for access to buckets.
 */

class Cluster
{
    public function __construct(string $connstr, ClusterOptions $options)
    {
    }

    /**
     * Returns a new bucket object.
     *
     * @param string $name the name of the bucket
     * @return Bucket
     */
    public function bucket(string $name): Bucket
    {
    }

    /**
     * Executes a N1QL query against the cluster.
     * Note: On Couchbase Server versions < 6.5 a bucket must be opened before using query.
     *
     * @param string $statement the N1QL query statement to execute
     * @param QueryOptions $options the options to use when executing the query
     * @return QueryResult
     */
    public function query(string $statement, QueryOptions $options = null): QueryResult
    {
    }

    /**
     * Executes an analytics query against the cluster.
     * Note: On Couchbase Server versions < 6.5 a bucket must be opened before using analyticsQuery.
     *
     * @param string $statement the analytics query statement to execute
     * @param AnalyticsOptions $options the options to use when executing the query
     * @return AnalyticsResult
     */
    public function analyticsQuery(string $statement, AnalyticsOptions $options = null): AnalyticsResult
    {
    }

    /**
     * Executes a full text search query against the cluster.
     * Note: On Couchbase Server versions < 6.5 a bucket must be opened before using searchQuery.
     *
     * @param string $indexName the fts index to use for the query
     * @param SearchQuery $query the search query to execute
     * @param SearchOptions $options the options to use when executing the query
     * @return SearchResult
     */
    public function searchQuery(string $indexName, SearchQuery $query, SearchOptions $options = null): SearchResult
    {
    }

    /**
     * Creates a new bucket manager object for managing buckets.
     *
     * @return BucketManager
     */
    public function buckets(): BucketManager
    {
    }

    /**
     * Creates a new user manager object for managing users and groups.
     *
     * @return UserManager
     */
    public function users(): UserManager
    {
    }

    /**
     * Creates a new query index manager object for managing analytics query indexes.
     *
     * @return AnalyticsIndexManager
     */
    public function analyticsIndexes(): AnalyticsIndexManager
    {
    }

    /**
     * Creates a new query index manager object for managing N1QL query indexes.
     *
     * @return QueryIndexManager
     */
    public function queryIndexes(): QueryIndexManager
    {
    }

    /**
     * Creates a new search index manager object for managing search query indexes.
     *
     * @return SearchIndexManager
     */
    public function searchIndexes(): SearchIndexManager
    {
    }
}
