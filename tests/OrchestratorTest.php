<?php

/*
 * Copyright 2022-Present Couchbase, Inc.
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

use Couchbase\BucketInterface;
use Couchbase\ClusterInterface;
use Couchbase\ClusterOptions;
use Couchbase\CollectionInterface;
use Couchbase\Integration;
use Couchbase\Protostellar\Collection;
use Couchbase\Protostellar\Generated\KV\V1\GetRequest;
use Couchbase\Protostellar\Generated\KV\V1\UpsertRequest;
use Couchbase\Protostellar\Internal\SharedUtils;
use Couchbase\Protostellar\Retries\RetryOrchestrator;
use Couchbase\Protostellar\Retries\RetryReason;
use PHPUnit\Framework\TestCase;

final class OrchestratorTest extends TestCase
{
    private const CONNECTION_STRING_ENV = "TEST_CONNECTION_STRING";
    private const BUCKET_NAME_ENV = "TEST_BUCKET";

    private const DEFAULT_CONNECTION_STRING = "localhost";
    private const DEFAULT_BUCKET_NAME = "default";

    private ClusterInterface $cluster;
    private CollectionInterface $defaultCollection;
    private BucketInterface $bucket;


    protected function setUp(): void
    {
        parent::setUp();
        Integration::enableProtostellar();
        $options = new ClusterOptions();
        $this->cluster = Couchbase\Cluster::connect(
            getenv(self::CONNECTION_STRING_ENV)
                ?: self::DEFAULT_CONNECTION_STRING,
            $options
        );
        $this->bucket = $this->cluster->bucket(getenv(self::BUCKET_NAME_ENV) ?: self::DEFAULT_BUCKET_NAME);
        $this->defaultCollection = $this->bucket->defaultCollection();
    }

    protected function tearDown(): void
    {
        $this->cluster->close();
        parent::tearDown();
    }


    public function testNonIdempotentRetries(): void
    {
        $request = SharedUtils::createProtostellarRequest(new UpsertRequest(), false, Collection::DEFAULT_KV_TIMEOUT);
        $behaviour = RetryOrchestrator::maybeRetry($request, new RetryReason(RetryReason::KV_LOCKED));
        $this->assertNull($behaviour->exception());
        $this->assertEquals(1_500, $behaviour->retryDuration()); //1500 per ExponentialBackoff Calculator
    }

    public function testIdempotentRetries(): void
    {
        $request = SharedUtils::createProtostellarRequest(new GetRequest(), true, Collection::DEFAULT_KV_TIMEOUT);
        $behaviour = RetryOrchestrator::maybeRetry($request, new RetryReason(RetryReason::UNKNOWN));
        $this->assertNull($behaviour->exception());
        $this->assertEquals(1_500, $behaviour->retryDuration());
    }

    public function testIdempotentRetriesWithTwoPriorRetries(): void
    {
        $request = SharedUtils::createProtostellarRequest(new GetRequest(), true, Collection::DEFAULT_KV_TIMEOUT);
        $request->incrementRetryAttempts(new RetryReason(RetryReason::UNKNOWN));
        $request->incrementRetryAttempts(new RetryReason(RetryReason::UNKNOWN));
        $this->assertEquals(2, $request->retryAttempts());
        $behaviour = RetryOrchestrator::maybeRetry($request, RetryReason::build(RetryReason::UNKNOWN));
        $this->assertNull($behaviour->exception());
        $this->assertEquals(7_500, $behaviour->retryDuration());
    }

    public function testAlwaysRetry(): void
    {
        $request = SharedUtils::createProtostellarRequest(new GetRequest(), true, Collection::DEFAULT_KV_TIMEOUT);
        $behaviour = RetryOrchestrator::maybeRetry($request, RetryReason::build(RetryReason::KV_COLLECTION_OUTDATED));
        $this->assertNull($behaviour->exception());
        $this->assertEquals(1_000, $behaviour->retryDuration()); //Controlled backoff duration
    }
}
