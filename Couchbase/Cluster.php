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

use Couchbase\Exception\UnsupportedOperationException;
use Couchbase\Management\AnalyticsIndexManager;
use Couchbase\Management\BucketManager;
use Couchbase\Management\QueryIndexManager;
use Couchbase\Management\SearchIndexManager;
use Couchbase\Management\UserManager;

/**
 * Cluster is an object containing functionality for performing cluster level operations
 * against a cluster and for access to buckets.
 *
 * @since 4.0.0
 */
class Cluster
{
    private string $connectionHash;
    private $core;

    public function __construct(string $connectionString, ClusterOptions $options)
    {
        $this->connectionHash = hash("sha256", sprintf("--%s--%s--%s--", $connectionString, $options->username(), $options->password()));
        $this->core = Extension\createConnection($this->connectionHash, $connectionString, $options->export());
    }

    /**
     * Returns a new bucket object.
     *
     * @param string $name the name of the bucket
     * @return Bucket
     * @throws UnsupportedOperationException
     * @since 4.0.0
     */
    public function bucket(string $name): Bucket
    {
        throw new UnsupportedOperationException();
    }

    /**
     * Executes a N1QL query against the cluster.
     * Note: On Couchbase Server versions < 6.5 a bucket must be opened before using query.
     *
     * @param string $statement the N1QL query statement to execute
     * @param QueryOptions|null $options the options to use when executing the query
     * @return QueryResult
     * @throws UnsupportedOperationException
     * @since 4.0.0
     */
    public function query(string $statement, QueryOptions $options = null): QueryResult
    {
        throw new UnsupportedOperationException();
    }

    /**
     * Executes an analytics query against the cluster.
     * Note: On Couchbase Server versions < 6.5 a bucket must be opened before using analyticsQuery.
     *
     * @param string $statement the analytics query statement to execute
     * @param AnalyticsOptions|null $options the options to use when executing the query
     * @return AnalyticsResult
     * @throws UnsupportedOperationException
     * @since 4.0.0
     */
    public function analyticsQuery(string $statement, AnalyticsOptions $options = null): AnalyticsResult
    {
        throw new UnsupportedOperationException();
    }

    /**
     * Executes a full text search query against the cluster.
     * Note: On Couchbase Server versions < 6.5 a bucket must be opened before using searchQuery.
     *
     * @param string $indexName the fts index to use for the query
     * @param SearchQuery $query the search query to execute
     * @param SearchOptions|null $options the options to use when executing the query
     * @return SearchResult
     * @throws UnsupportedOperationException
     * @since 4.0.0
     */
    public function searchQuery(string $indexName, SearchQuery $query, SearchOptions $options = null): SearchResult
    {
        throw new UnsupportedOperationException();
    }

    /**
     * Creates a new bucket manager object for managing buckets.
     *
     * @return BucketManager
     * @throws UnsupportedOperationException
     * @since 4.0.0
     */
    public function buckets(): BucketManager
    {
        throw new UnsupportedOperationException();
    }

    /**
     * Creates a new user manager object for managing users and groups.
     *
     * @return UserManager
     * @throws UnsupportedOperationException
     * @since 4.0.0
     */
    public function users(): UserManager
    {
        throw new UnsupportedOperationException();
    }

    /**
     * Creates a new query index manager object for managing analytics query indexes.
     *
     * @return AnalyticsIndexManager
     * @throws UnsupportedOperationException
     * @since 4.0.0
     */
    public function analyticsIndexes(): AnalyticsIndexManager
    {
        throw new UnsupportedOperationException();
    }

    /**
     * Creates a new query index manager object for managing N1QL query indexes.
     *
     * @return QueryIndexManager
     * @throws UnsupportedOperationException
     * @since 4.0.0
     */
    public function queryIndexes(): QueryIndexManager
    {
        throw new UnsupportedOperationException();
    }

    /**
     * Creates a new search index manager object for managing search query indexes.
     *
     * @return SearchIndexManager
     * @throws UnsupportedOperationException
     */
    public function searchIndexes(): SearchIndexManager
    {
        throw new UnsupportedOperationException();
    }
}
