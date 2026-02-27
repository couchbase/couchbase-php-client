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

namespace Helpers;

include_once __DIR__ . "/CouchbaseTestCase.php";
include_once __DIR__ . "/Tracing/TestTracer.php";
include_once __DIR__ . "/Tracing/TestSpan.php";
include_once __DIR__ . "/Tracing/ParentSpanRequirement.php";
include_once __DIR__ . "/Metrics/TestMeter.php";
include_once __DIR__ . "/Metrics/TestValueRecorder.php";

use Couchbase\ClusterInterface;
use Couchbase\ClusterOptions;
use Couchbase\DurabilityLevel;
use Couchbase\RequestSpan;
use Helpers\Tracing\TestTracer;
use Helpers\Tracing\TestSpan;
use Helpers\Metrics\TestMeter;
use RuntimeException;

class CouchbaseObservabilityTestCase extends CouchbaseTestCase
{
    private TestTracer $tracer;
    private TestSpan $parentSpan;
    private TestMeter $meter;

    private static ?array $clusterLabels;

    protected function parentSpan(): TestSpan
    {
        if (!isset($this->parentSpan)) {
            $span = $this->tracer()->requestSpan("outer");
            if ($span instanceof TestSpan) {
                $this->parentSpan = $span;
            } else {
                throw new \RuntimeException("TestTracer did not return a TestSpan");
            }
        }
        return $this->parentSpan;
    }

    protected static function clusterLabels(): array
    {
        if (!isset(self::$clusterLabels)) {
            self::$clusterLabels = self::fetchClusterLabels();
        }
        return self::$clusterLabels;
    }

    protected function tracer(): TestTracer
    {
        if (!isset($this->tracer)) {
            $this->tracer = new TestTracer();
        }
        return $this->tracer;
    }

    protected function meter(): TestMeter
    {
        if (!isset($this->meter)) {
            $this->meter = new TestMeter();
        }
        return $this->meter;
    }

    public function connectCluster(?ClusterOptions $options = null): ClusterInterface
    {
        if ($options == null) {
            $options = new ClusterOptions();
        }
        $options->tracer($this->tracer());
        $options->meter($this->meter());

        return parent::connectCluster($options);
    }

    public function assertSpan(TestSpan $span, string $expectedName, ?RequestSpan $expectedParent = null): void
    {
        fprintf(STDERR, "Asserting span %s\n", $span->getName());
        fprintf(STDERR, "  Parent: %s\n", is_null($span->getParent()) ? "null" : $span->getParent()->getName());
        fprintf(STDERR, "  Tags: %s\n", json_encode($span->getTags()));

        $this->assertEquals($expectedName, $span->getName());
        if (is_null($expectedParent)) {
            $this->assertNull($span->getParent());
        } else {
            $this->assertEquals($expectedParent, $span->getParent());
        }
        $this->assertNotNull($span->getEndTimestampNanoseconds(), "Expected span to be finished and have an end timestamp");
        $this->assertTrue($span->getStartTimestampNanoseconds() <= $span->getEndTimestampNanoseconds(), "Span start time should be before end time");
        $this->assertSpanHasTag($span, "db.system.name", "couchbase");
        $clusterLabels = self::clusterLabels();
        if (is_null($clusterLabels["clusterName"])) {
            $this->assertSpanNotHasTag($span, "couchbase.cluster.name");
        } else {
            $this->assertSpanHasTag($span, "couchbase.cluster.name", $clusterLabels["clusterName"]);
        }
        if (is_null($clusterLabels["clusterUuid"])) {
            $this->assertSpanNotHasTag($span, "couchbase.cluster.uuid");
        } else {
            $this->assertSpanHasTag($span, "couchbase.cluster.uuid", $clusterLabels["clusterUuid"]);
        }
    }

    public function assertKvOperationSpan(
        TestSpan $span,
        string $name,
        ?RequestSpan $parent = null,
        ?string $bucket = null,
        ?string $scope = null,
        ?string $collection = null,
        ?string $durability = null,
    )
    {
        if (is_null($bucket)) {
            $bucket = $this->env()->bucketName();
        }
        if (is_null($scope)) {
            $scope = "_default";
        }
        if (is_null($collection)) {
            $collection = "_default";
        }
        $this->assertSpan($span, $name, $parent);
        $this->assertSpanHasTag($span, "db.operation.name", $name);
        $this->assertKeyspaceTags($span, $bucket, $scope, $collection);
        $this->assertSpanHasTag($span, "couchbase.service", "kv");
        if (is_null($durability) || $durability == DurabilityLevel::NONE) {
            $this->assertSpanNotHasTag($span, "couchbase.durability");
        } else {
            $this->assertSpanHasTag(
                $span,
                "couchbase.durability",
                match ($durability) {
                DurabilityLevel::PERSIST_TO_MAJORITY => "persist_majority",
                DurabilityLevel::MAJORITY_AND_PERSIST_TO_ACTIVE => "majority_and_persist_active",
                DurabilityLevel::MAJORITY => "majority",
                default => throw new \InvalidArgumentException("Unexpected durability level: " . $durability),
                }
            );
        }
    }

    public function assertHasRequestEncodingSpan(TestSpan $operationSpan)
    {
        $childSpans = $this->tracer()->getSpans(null, $operationSpan);
        $this->assertGreaterThanOrEqual(1, count($childSpans), "Expected at least one child span under the operation span");

        // The request_encoding span should be the first child of the operation span
        $requestEncodingSpan = $childSpans[0];

        $this->assertSpan($requestEncodingSpan, "request_encoding", $operationSpan);
    }

    public function assertHttpOperationSpan(
        TestSpan $span,
        string $expectedName,
        ?string $expectedService = null,
        ?RequestSpan $expectedParent = null,
        ?string $expectedBucketName = null,
        ?string $expectedScopeName = null,
        ?string $expectedCollectionName = null
    )
    {
        $this->assertSpan($span, $expectedName, $expectedParent);
        $this->assertSpanHasTag($span, "db.operation.name", $expectedName);
        $this->assertKeyspaceTags($span, $expectedBucketName, $expectedScopeName, $expectedCollectionName);
        if (is_null($expectedService)) {
            $this->assertSpanNotHasTag($span, "couchbase.service");
        } else {
            $this->assertSpanHasTag($span, "couchbase.service", $expectedService);
        }
    }

    public function assertKeyspaceTags(
        TestSpan $span,
        ?string $expectedBucketName = null,
        ?string $expectedScopeName = null,
        ?string $expectedCollectionName = null
    )
    {
        if (is_null($expectedBucketName)) {
            $this->assertSpanNotHasTag($span, "db.namespace");
        } else {
            $this->assertSpanHasTag($span, "db.namespace", $expectedBucketName);
        }

        if (is_null($expectedScopeName)) {
            $this->assertSpanNotHasTag($span, "couchbase.scope.name");
        } else {
            $this->assertSpanHasTag($span, "couchbase.scope.name", $expectedScopeName);
        }

        if (is_null($expectedCollectionName)) {
            $this->assertSpanNotHasTag($span, "couchbase.collection.name");
        } else {
            $this->assertSpanHasTag($span, "couchbase.collection.name", $expectedCollectionName);
        }
    }

    public function assertSpanHasTag(TestSpan $span, string $key, int|string|null $expectedValue): void
    {
        $tags = $span->getTags();
        $this->assertArrayHasKey($key, $tags);
        if (!is_null($expectedValue)) {
            $this->assertEquals($expectedValue, $tags[$key]);
        }
    }

    public function assertSpanNotHasTag(TestSpan $span, string $key): void
    {
        $this->assertArrayNotHasKey($key, $span->getTags());
    }

    public function assertKvOperationMetrics(
        int $count,
        string $operationName,
        ?string $bucket = null,
        ?string $scope = null,
        ?string $collection = null,
        ?string $error = null,
    )
    {
        if (is_null($bucket)) {
            $bucket = $this->env()->bucketName();
        }
        if (is_null($scope)) {
            $scope = "_default";
        }
        if (is_null($collection)) {
            $collection = "_default";
        }
        $this->assertOperationMetrics($count, $operationName, "kv", $bucket, $scope, $collection, $error);
    }

    public function assertOperationMetrics(
        int $count,
        string $operationName,
        ?string $service = null,
        ?string $bucket = null,
        ?string $scope = null,
        ?string $collection = null,
        ?string $error = null,
    )
    {
        $tags = [
            "db.system.name" => "couchbase",
            "db.operation.name" => $operationName,
            "__unit" => "s",
        ];

        $clusterLabels = self::clusterLabels();
        if (!is_null($clusterLabels["clusterName"])) {
            $tags["couchbase.cluster.name"] = $clusterLabels["clusterName"];
        }
        if (!is_null($clusterLabels["clusterUuid"])) {
            $tags["couchbase.cluster.uuid"] = $clusterLabels["clusterUuid"];
        }
        if (!is_null($service)) {
            $tags["couchbase.service"] = $service;
        }
        if (!is_null($bucket)) {
            $tags["db.namespace"] = $bucket;
        }
        if (!is_null($scope)) {
            $tags["couchbase.scope.name"] = $scope;
        }
        if (!is_null($collection)) {
            $tags["couchbase.collection.name"] = $collection;
        }
        if (!is_null($error)) {
            $tags["error.type"] = $error;
        }

        $values = $this->meter()->getValues("db.client.operation.duration", $tags);
        $this->assertCount($count, $values, "Expected $count operation metric values with tags " . json_encode($tags));
    }

    private static function extractStatusCode($statusLine): int
    {
        preg_match('{HTTP\/\S*\s(\d{3})}', $statusLine, $match);
        $status = $match[1];
        return intval($status);
    }

    private static function fetchClusterLabels(): array
    {
        $labels = [
            "clusterName" => null,
            "clusterUuid" => null,
        ];

        if (self::env()->useCaves()) {
            return $labels;
        }

        $username = self::env()->username();
        $password = self::env()->password();
        $auth = "$username:$password";
        $hostname = parse_url(self::env()->connectionString())["host"];
        if (str_contains($hostname, ",")) {
            $hostname = explode(",", $hostname)[0];
        }

        $url = "http://" . $hostname . ":8091/pools/default/nodeServices";
        $opts = array('http' =>
            array(
                'method' => 'GET',
                'header' => "Authorization: Basic " . base64_encode($auth),
                'ignore_errors' => true,
            )
        );
        $context = stream_context_create($opts);
        $response = file_get_contents($url, false, $context);
        $statusCode = self::extractStatusCode($http_response_header[0]);
        if ($statusCode != 200) {
            throw new RuntimeException(sprintf("Error fetching cluster name/uuid. Status code: %s", $statusCode));
        }
        $body = json_decode($response, true);

        if (array_key_exists("clusterName", $body)) {
            $labels["clusterName"] = $body["clusterName"];
        }
        if (array_key_exists("clusterUUID", $body)) {
            $labels["clusterUuid"] = $body["clusterUUID"];
        }
        return $labels;
    }
}
