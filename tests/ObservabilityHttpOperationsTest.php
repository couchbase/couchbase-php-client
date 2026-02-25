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

include_once __DIR__ . "/Helpers/CouchbaseObservabilityTestCase.php";

use Helpers\Tracing\ParentSpanRequirement;
use Couchbase\QueryOptions;
use Couchbase\AnalyticsOptions;
use Couchbase\Exception\DataverseNotFoundException;
use Couchbase\Exception\IndexNotFoundException;
use Couchbase\MatchAllSearchQuery;
use Couchbase\SearchOptions;
use Couchbase\SearchRequest;

class ObservabilityHttpOperationsTest extends Helpers\CouchbaseObservabilityTestCase
{
    public function testClusterLevelQuery()
    {
        $this->skipIfCaves();

        $cluster = $this->connectCluster();
        $result = $cluster->query(
            "SELECT 1=1",
            QueryOptions::build()
            ->parentSpan($this->parentSpan())
        );
        $this->assertCount(1, $result->rows());

        $querySpan = $this->tracer()->getSpans(null, $this->parentSpan())[0];
        $this->assertNotNull($querySpan);
        $this->assertHttpOperationSpan($querySpan, "query", "query", $this->parentSpan());
        $this->assertSpanNotHasTag($querySpan, "db.query.text");
        $this->assertOperationMetrics(1, "query", "query");
    }

    public function testClusterLevelQueryNoParentSpan()
    {
        $this->skipIfCaves();

        $cluster = $this->connectCluster();
        $result = $cluster->query("SELECT 1=1");
        $this->assertCount(1, $result->rows());

        $querySpan = $this->tracer()->getSpans(null, ParentSpanRequirement::ROOT)[0];
        $this->assertHttpOperationSpan($querySpan, "query", "query");
        $this->assertSpanNotHasTag($querySpan, "db.query.text");
        $this->assertOperationMetrics(1, "query", "query");
    }

    public function testClusterLevelQueryWithNamedParameters()
    {
        $this->skipIfCaves();

        $cluster = $this->connectCluster();
        $result = $cluster->query(
            "SELECT 1=\$num",
            QueryOptions::build()
            ->namedParameters(["num" => 1])
            ->parentSpan($this->parentSpan())
        );
        $this->assertCount(1, $result->rows());

        $querySpan = $this->tracer()->getSpans(null, $this->parentSpan())[0];
        $this->assertHttpOperationSpan($querySpan, "query", "query", $this->parentSpan());
        $this->assertSpanHasTag($querySpan, "db.query.text", "SELECT 1=\$num");
        $this->assertOperationMetrics(1, "query", "query");
    }

    public function testClusterLevelQueryWithPositionalParameters()
    {
        $this->skipIfCaves();

        $cluster = $this->connectCluster();
        $result = $cluster->query(
            "SELECT 1=$1",
            QueryOptions::build()
            ->positionalParameters([1])
            ->parentSpan($this->parentSpan())
        );
        $this->assertCount(1, $result->rows());

        $querySpan = $this->tracer()->getSpans(null, $this->parentSpan())[0];
        $this->assertHttpOperationSpan($querySpan, "query", "query", $this->parentSpan());
        $this->assertSpanHasTag($querySpan, "db.query.text", "SELECT 1=$1");
        $this->assertOperationMetrics(1, "query", "query");
    }

    public function testClusterLevelAnalyticsQuery()
    {
        $this->skipIfCaves();

        $cluster = $this->connectCluster();
        $result = $cluster->analyticsQuery(
            "SELECT 1=1",
            AnalyticsOptions::build()
            ->parentSpan($this->parentSpan())
        );
        $this->assertCount(1, $result->rows());

        $analyticsSpan = $this->tracer()->getSpans(null, $this->parentSpan())[0];
        $this->assertHttpOperationSpan($analyticsSpan, "analytics", "analytics", $this->parentSpan());
        $this->assertSpanNotHasTag($analyticsSpan, "db.query.text");
        $this->assertOperationMetrics(1, "analytics", "analytics");
    }

    public function testClusterLevelAnalyticsQueryWithNamedParameters()
    {
        $this->skipIfCaves();

        $cluster = $this->connectCluster();
        $result = $cluster->analyticsQuery(
            "SELECT 1=\$num",
            AnalyticsOptions::build()
            ->namedParameters(["num" => 1])
            ->parentSpan($this->parentSpan())
        );
        $this->assertCount(1, $result->rows());

        $analyticsSpan = $this->tracer()->getSpans(null, $this->parentSpan())[0];
        $this->assertHttpOperationSpan($analyticsSpan, "analytics", "analytics", $this->parentSpan());
        $this->assertSpanHasTag($analyticsSpan, "db.query.text", "SELECT 1=\$num");
        $this->assertOperationMetrics(1, "analytics", "analytics");
    }

    public function testClusterLevelAnalyticsQueryWithPositionalParameters()
    {
        $this->skipIfCaves();

        $cluster = $this->connectCluster();
        $result = $cluster->analyticsQuery(
            "SELECT 1=\$1",
            AnalyticsOptions::build()
            ->positionalParameters([1])
            ->parentSpan($this->parentSpan())
        );
        $this->assertCount(1, $result->rows());

        $analyticsSpan = $this->tracer()->getSpans(null, $this->parentSpan())[0];
        $this->assertHttpOperationSpan($analyticsSpan, "analytics", "analytics", $this->parentSpan());
        $this->assertSpanHasTag($analyticsSpan, "db.query.text", "SELECT 1=\$1");
        $this->assertOperationMetrics(1, "analytics", "analytics");
    }

    public function testScopeLevelQuery()
    {
        $this->skipIfCaves();

        $bucket = $this->openBucket();
        $scope = $bucket->defaultScope();
        $result = $scope->query(
            "SELECT 1=1",
            QueryOptions::build()
            ->parentSpan($this->parentSpan())
        );
        $this->assertCount(1, $result->rows());

        $querySpan = $this->tracer()->getSpans(null, $this->parentSpan())[0];
        $this->assertHttpOperationSpan($querySpan, "query", "query", $this->parentSpan(), $bucket->name(), "_default");
        $this->assertSpanNotHasTag($querySpan, "db.query.text");
        $this->assertOperationMetrics(1, "query", "query", $bucket->name(), "_default");
    }

    public function testScopeLevelQueryNoParentSpan()
    {
        $this->skipIfCaves();

        $bucket = $this->openBucket();
        $scope = $bucket->defaultScope();
        $result = $scope->query("SELECT 1=1");
        $this->assertCount(1, $result->rows());

        $querySpan = $this->tracer()->getSpans(null, ParentSpanRequirement::ROOT)[0];
        $this->assertHttpOperationSpan($querySpan, "query", "query", null, $bucket->name(), "_default");
        $this->assertSpanNotHasTag($querySpan, "db.query.text");
        $this->assertOperationMetrics(1, "query", "query", $bucket->name(), "_default");
    }

    public function testScopeLevelQueryWithNamedParameters()
    {
        $this->skipIfCaves();

        $bucket = $this->openBucket();
        $scope = $bucket->defaultScope();
        $result = $scope->query(
            "SELECT 1=\$num",
            QueryOptions::build()
            ->namedParameters(["num" => 1])
            ->parentSpan($this->parentSpan())
        );
        $this->assertCount(1, $result->rows());

        $querySpan = $this->tracer()->getSpans(null, $this->parentSpan())[0];
        $this->assertHttpOperationSpan($querySpan, "query", "query", $this->parentSpan(), $bucket->name(), "_default");
        $this->assertSpanHasTag($querySpan, "db.query.text", "SELECT 1=\$num");
        $this->assertOperationMetrics(1, "query", "query", $bucket->name(), "_default");
    }

    public function testScopeLevelQueryWithPositionalParameters()
    {
        $this->skipIfCaves();

        $bucket = $this->openBucket();
        $scope = $bucket->defaultScope();
        $result = $scope->query(
            "SELECT 1=$1",
            QueryOptions::build()
            ->positionalParameters([1])
            ->parentSpan($this->parentSpan())
        );
        $this->assertCount(1, $result->rows());

        $querySpan = $this->tracer()->getSpans(null, $this->parentSpan())[0];
        $this->assertHttpOperationSpan($querySpan, "query", "query", $this->parentSpan(), $bucket->name(), "_default");
        $this->assertSpanHasTag($querySpan, "db.query.text", "SELECT 1=$1");
        $this->assertOperationMetrics(1, "query", "query", $bucket->name(), "_default");
    }

    public function testScopeLevelAnalyticsQuery()
    {
        $this->skipIfCaves();

        $bucket = $this->openBucket();
        $scope = $bucket->scope("does-not-exist");
        $this->wrapException(
            function () use ($scope) {
                $scope->analyticsQuery(
                    "SELECT 1=1",
                    AnalyticsOptions::build()
                        ->parentSpan($this->parentSpan())
                );
            },
            DataverseNotFoundException::class
        );

        $analyticsSpan = $this->tracer()->getSpans(null, $this->parentSpan())[0];
        $this->assertHttpOperationSpan($analyticsSpan, "analytics", "analytics", $this->parentSpan(), $bucket->name(), "does-not-exist");
        $this->assertOperationMetrics(1, "analytics", "analytics", $bucket->name(), "does-not-exist", null, "DataverseNotFound");
        $this->assertSpanNotHasTag($analyticsSpan, "db.query.text");
    }

    public function testClusterLevelSearchQuery()
    {
        $this->skipIfCaves();

        $cluster = $this->connectCluster();
        $this->wrapException(
            function () use ($cluster) {
                $cluster->searchQuery(
                    "non_existent_index",
                    new MatchAllSearchQuery(),
                    SearchOptions::build()
                        ->parentSpan($this->parentSpan())
                );
            },
            IndexNotFoundException::class
        );

        $searchSpan = $this->tracer()->getSpans(null, $this->parentSpan())[0];
        $this->assertHttpOperationSpan($searchSpan, "search", "search", $this->parentSpan());
        $this->assertOperationMetrics(1, "search", "search", null, null, null, "IndexNotFound");
    }

    public function testClusterLevelSearch()
    {
        $this->skipIfCaves();

        $cluster = $this->connectCluster();
        $this->wrapException(
            function () use ($cluster) {
                $cluster->search(
                    "non_existent_index",
                    SearchRequest::build(new MatchAllSearchQuery()),
                    SearchOptions::build()
                        ->parentSpan($this->parentSpan())
                );
            },
            IndexNotFoundException::class
        );

        $searchSpan = $this->tracer()->getSpans(null, $this->parentSpan())[0];
        $this->assertHttpOperationSpan($searchSpan, "search", "search", $this->parentSpan());
        $this->assertOperationMetrics(1, "search", "search", null, null, null, "IndexNotFound");
    }

    public function testScopeLevelSearch()
    {
        $this->skipIfCaves();
        $this->skipIfUnsupported($this->version()->supportsScopeSearchIndexes());

        $bucket = $this->openBucket();
        $scope = $bucket->defaultScope();
        $this->wrapException(
            function () use ($scope) {
                $scope->search(
                    "non_existent_index",
                    SearchRequest::build(new MatchAllSearchQuery()),
                    SearchOptions::build()
                        ->parentSpan($this->parentSpan())
                );
            },
            IndexNotFoundException::class
        );

        $searchSpan = $this->tracer()->getSpans(null, $this->parentSpan())[0];
        $this->assertHttpOperationSpan($searchSpan, "search", "search", $this->parentSpan(), $bucket->name(), "_default");
        $this->assertOperationMetrics(1, "search", "search", $bucket->name(), "_default", null, "IndexNotFound");
    }
}
