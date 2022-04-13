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

use Couchbase\Exception\CouchbaseException;
use Couchbase\Exception\InvalidArgumentException;
use Couchbase\Exception\TimeoutException;
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
    private ClusterOptions $options;

    private string $connectionHash;
    /**
     * @var resource
     */
    private $core;

    /**
     * @throws InvalidArgumentException
     */
    public function __construct(string $connectionString, ClusterOptions $options)
    {
        $this->connectionHash = hash("sha256", sprintf("--%s--%s--", $connectionString, $options->authenticatorHash()));
        $this->core = Extension\createConnection($this->connectionHash, $connectionString, $options->export());
        $this->options = $options;
    }

    /**
     * Returns a new bucket object.
     *
     * @param string $name the name of the bucket
     *
     * @return Bucket
     * @since 4.0.0
     */
    public function bucket(string $name): Bucket
    {
        return new Bucket($name, $this->core);
    }

    /**
     * Executes a N1QL query against the cluster.
     * Note: On Couchbase Server versions < 6.5 a bucket must be opened before using query.
     *
     * @param string $statement the N1QL query statement to execute
     * @param QueryOptions|null $options the options to use when executing the query
     *
     * @return QueryResult
     * @throws InvalidArgumentException
     * @throws TimeoutException
     * @throws CouchbaseException
     * @since 4.0.0
     */
    public function query(string $statement, ?QueryOptions $options = null): QueryResult
    {
        $result = Extension\query($this->core, $statement, QueryOptions::export($options));

        return new QueryResult($result, QueryOptions::getTranscoder($options));
    }

    /**
     * Executes an analytics query against the cluster.
     * Note: On Couchbase Server versions < 6.5 a bucket must be opened before using analyticsQuery.
     *
     * @param string $statement the analytics query statement to execute
     * @param AnalyticsOptions|null $options the options to use when executing the query
     *
     * @return AnalyticsResult
     * @throws TimeoutException
     * @throws CouchbaseException
     * @since 4.0.0
     */
    public function analyticsQuery(string $statement, AnalyticsOptions $options = null): AnalyticsResult
    {
        $result = Extension\analyticsQuery($this->core, $statement, AnalyticsOptions::export($options));

        return new AnalyticsResult($result, AnalyticsOptions::getTranscoder($options));
    }

    /**
     * Executes a full text search query against the cluster.
     * Note: On Couchbase Server versions < 6.5 a bucket must be opened before using searchQuery.
     *
     * @param string $indexName the fts index to use for the query
     * @param SearchQuery $query the search query to execute
     * @param SearchOptions|null $options the options to use when executing the query
     *
     * @return SearchResult
     * @since 4.0.0
     */
    public function searchQuery(string $indexName, SearchQuery $query, SearchOptions $options = null): SearchResult
    {
        $result = Extension\searchQuery($this->core, $indexName, json_encode($query), SearchOptions::export($options));

        return new SearchResult($result);
    }

    /**
     * Creates a new bucket manager object for managing buckets.
     *
     * @return BucketManager
     * @since 4.0.0
     */
    public function buckets(): BucketManager
    {
        return new BucketManager($this->core);
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

    /**
     * Executes a ping for each service against each node in the cluster. This can be used for determining
     * the current health of the cluster.
     *
     * @param mixed|null $services the services to ping against
     * @param mixed|null $reportId a name which will be included within the ping result
     *
     * @see ServiceType::KEY_VALUE
     * @see ServiceType::QUERY
     * @see ServiceType::ANALYTICS
     * @see ServiceType::SEARCH
     * @see ServiceType::VIEWS
     * @see ServiceType::MANAGEMENT
     * @see ServiceType::EVENTING
     * @since 4.0.0
     */
    public function ping($services = null, $reportId = null)
    {
        $options = [];
        if ($services != null) {
            $options['serviceTypes'] = $services;
        }
        if ($reportId != null) {
            $options['reportId'] = $reportId;
        }
        return Extension\ping($this->core, $options);
    }

    /**
     * Returns diagnostics information about connections that the SDK has to the cluster. This does not perform
     * any operations.
     *
     * @param string|null $reportId a name which will be included within the ping result
     *
     * @since 4.0.0
     */
    public function diagnostics(string $reportId = null)
    {
        if ($reportId == null) {
            $reportId = uniqid();
        }
        return Extension\diagnostics($this->core, $reportId);
    }

    /**
     * Creates a new bucket manager object for managing buckets.
     *
     * @return Transactions
     * @since 4.0.0
     */
    public function transactions(): Transactions
    {
        return new Transactions($this->core, $this->options->getTransactionsConfiguration());
    }

    /**
     * @internal
     *
     * @param string $bucketName
     *
     * @return string|null
     */
    public function version(string $bucketName): ?string
    {
        return Extension\clusterVersion($this->core, $bucketName);
    }

    /**
     * @internal
     */
    public function core()
    {
        return $this->core;
    }
}
