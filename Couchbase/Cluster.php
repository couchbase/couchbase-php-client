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
use Couchbase\Exception\FeatureNotAvailableException;
use Couchbase\Exception\InvalidArgumentException;
use Couchbase\Exception\TimeoutException;
use Couchbase\Exception\UnsupportedOperationException;
use Couchbase\Management\AnalyticsIndexManager;
use Couchbase\Management\BucketManager;
use Couchbase\Management\QueryIndexManager;
use Couchbase\Management\SearchIndexManager;
use Couchbase\Management\User;
use Couchbase\Management\UserManager;

/**
 * Cluster is an object containing functionality for performing cluster level operations
 * against a cluster and for access to buckets.
 *
 * @since 4.0.0
 */
class Cluster implements ClusterInterface
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
        if (
            preg_match("/^protostellar:\/\//", $connectionString) ||
            preg_match("/^couchbase2:\/\//", $connectionString)
        ) {
            throw new InvalidArgumentException("Please use Cluster::connect() to connect to CNG.");
        }
        $this->connectionHash = hash("sha256", sprintf("--%s--%s--", $connectionString, $options->authenticatorHash()));
        $this->core = Extension\createConnection($this->connectionHash, $connectionString, $options->export());
        $this->options = $options;
    }

    /**
     * @throws InvalidArgumentException
     * @throws FeatureNotAvailableException
     *
     * Note: The couchbase2:// scheme has stability @UNCOMMITTED
     */
    public static function connect(string $connectionString, ClusterOptions $options): ClusterInterface
    {
        if (preg_match("/^couchbases?:\/\//", $connectionString)) {
            return new Cluster($connectionString, $options);
        }

        if (
            preg_match("/^protostellar:\/\//", $connectionString) ||
            preg_match("/^couchbase2:\/\//", $connectionString)
        ) {
            Cluster::enableProtostellar();
        }
        return ClusterRegistry::connect($connectionString, $options);
    }

    /**
     * @throws InvalidArgumentException if runtime dependencies are missing ("protobuf" and "grpc" modules)
     */
    private static function enableProtostellar(): void
    {
        if (!extension_loaded("protobuf")) {
            throw new InvalidArgumentException("couchbase2:// protocol requires protobuf extension");
        }
        if (!extension_loaded("grpc")) {
            throw new InvalidArgumentException("couchbase2:// protocol requires grpc extension");
        }
        ClusterRegistry::registerConnectionHandler(
            "/^protostellar:\/\//",
            function (string $connectionString, ClusterOptions $options) {
                return new Protostellar\Cluster($connectionString, $options);
            }
        );

        ClusterRegistry::registerConnectionHandler(
            "/^couchbase2:\/\//",
            function (string $connectionString, ClusterOptions $options) {
                return new Protostellar\Cluster($connectionString, $options);
            }
        );
    }

    /**
     * Notifies the SDK about usage of `fork(2)` syscall. Typically PHP exposes it using `pcntl_fork()` function, but
     * the library should have chance to properly close descriptors and reach safe point to allow forking the process.
     * This is not a problem in case of `proc_open()` as in this case the memory and descriptors are not inherited by
     * the child process.
     *
     * Allowed values for `$event` are:
     *
     * * ForkEvent::PREPARE - must be used before `fork()` to ensure the SDK reaches safe point
     * * ForkEvent::CHILD - must be used in the child process, the branch where `pcntl_fork()` returns zero
     * * ForkEvent::PARENT - must be used in the parent process, the branch where `pcntl_fork()` returns pid of the child process
     *
     * In case `pcntl_fork()` returns negative value, and the application decides to continue, `notifyFork(ForkEvent::PARENT)`
     * must be invoked to resume the SDK.
     *
     * @see https://www.php.net/manual/en/function.pcntl-fork.php
     * @see https://www.php.net/manual/en/function.proc-open.php
     *
     * @param string $event type of the event to send to the library (one of the constants in ForkEvent).
     * @return void
     *
     * @since 4.2.1
     */
    public static function notifyFork(string $event)
    {
        return Extension\notifyFork($event);
    }

    /**
     * Returns a new bucket object.
     *
     * @param string $name the name of the bucket
     *
     * @return Bucket
     * @since 4.0.0
     */
    public function bucket(string $name): BucketInterface
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
     * Executes a search query against the full text search services.
     *
     * This can be used to perform a traditional FTS query, and/or a vector search.
     *
     * @param string $indexName the cluster-level FTS index to use for the search request
     * @param SearchRequest $request The search request to run
     * @param SearchOptions|null $options The options to use when executing the search request
     *
     * @return SearchResult
     * @throws InvalidArgumentException
     * @since 4.1.7
     */
    public function search(string $indexName, SearchRequest $request, SearchOptions $options = null): SearchResult
    {
        $exportedRequest = SearchRequest::export($request);
        $exportedOptions = SearchOptions::export($options);
        $exportedOptions["showRequest"] = false;
        $query = $exportedRequest['searchQuery'];

        if (!$exportedRequest['vectorSearch']) {
            $result = Extension\searchQuery($this->core, $indexName, json_encode($query), $exportedOptions);
            return new SearchResult($result);
        }

        $vectorSearch = $exportedRequest['vectorSearch'];
        $result = Extension\vectorSearch($this->core, $indexName, json_encode($query), json_encode($vectorSearch), $exportedOptions, VectorSearchOptions::export($vectorSearch->options()));
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
     * @since 4.0.0
     */
    public function users(): UserManager
    {
        return new UserManager($this->core);
    }

    /**
     * Creates a new manager object for managing analytics query indexes.
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
     * Creates a new manager object for managing N1QL query indexes.
     *
     * @return QueryIndexManager
     * @since 4.0.0
     */
    public function queryIndexes(): QueryIndexManager
    {
        return new QueryIndexManager($this->core);
    }

    /**
     * Creates a new search index manager object for managing search query indexes.
     *
     * @return SearchIndexManager
     */
    public function searchIndexes(): SearchIndexManager
    {
        return new SearchIndexManager($this->core);
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
     * @param TransactionsConfiguration|null $config
     *
     * @return Transactions
     * @since 4.0.0
     */
    public function transactions(?TransactionsConfiguration $config = null): Transactions
    {
        return new Transactions($this->core, $config ?: $this->options->getTransactionsConfiguration());
    }

    /**
     * @param string $bucketName
     *
     * @return string|null
     * @internal
     *
     */
    public function version(string $bucketName): ?string
    {
        return Extension\clusterVersion($this->core, $bucketName);
    }

    /**
     * @param string $bucketName
     *
     * @return bool if replicas enabled, and cluster has enough nodes to use them
     * @internal
     *
     */
    public function replicasConfiguredFor(string $bucketName): bool
    {
        return Extension\replicasConfiguredForBucket($this->core, $bucketName);
    }

    /**
     * @internal
     * @deprecated will be removed once all managers will be implemented
     */
    public function core()
    {
        return $this->core;
    }
}
