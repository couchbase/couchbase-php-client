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

use Couchbase\Protostellar\Generated\KV\V1\GetRequest;
use Couchbase\Protostellar\Generated\KV\V1\UpsertRequest;
use Couchbase\Protostellar\Internal\SharedUtils;
use Couchbase\Protostellar\Internal\TimeoutHandler;
use Couchbase\Protostellar\Retries\RetryOrchestrator;
use Couchbase\Protostellar\Retries\RetryReason;
use Couchbase\UpsertOptions;

include_once __DIR__ . "/Helpers/CouchbaseTestCaseProtostellar.php";
final class OrchestratorTest extends \Helpers\CouchbaseTestCaseProtostellar
{
    public function testNonIdempotentRetries(): void
    {
        $timeout = $this->getDefaultClient()->timeoutHandler()->getTimeout(TimeoutHandler::KV, UpsertOptions::export(new UpsertOptions()));
        $request = SharedUtils::createProtostellarRequest(new UpsertRequest(), false, $timeout);
        $behaviour = RetryOrchestrator::maybeRetry($request, new RetryReason(RetryReason::KV_LOCKED));
        $this->assertNull($behaviour->exception());
        $this->assertEquals(1_500, $behaviour->retryDuration()); //1500 per ExponentialBackoff Calculator
    }

    public function testIdempotentRetries(): void
    {
        $timeout = $this->getDefaultClient()->timeoutHandler()->getTimeout(TimeoutHandler::KV, UpsertOptions::export(new UpsertOptions()));
        $request = SharedUtils::createProtostellarRequest(new GetRequest(), true, $timeout);
        $behaviour = RetryOrchestrator::maybeRetry($request, new RetryReason(RetryReason::UNKNOWN));
        $this->assertNull($behaviour->exception());
        $this->assertEquals(1_500, $behaviour->retryDuration());
    }

    public function testIdempotentRetriesWithTwoPriorRetries(): void
    {
        $timeout = $this->getDefaultClient()->timeoutHandler()->getTimeout(TimeoutHandler::KV, UpsertOptions::export(new UpsertOptions()));
        $request = SharedUtils::createProtostellarRequest(new GetRequest(), true, $timeout);
        $request->incrementRetryAttempts(new RetryReason(RetryReason::UNKNOWN));
        $request->incrementRetryAttempts(new RetryReason(RetryReason::UNKNOWN));
        $this->assertEquals(2, $request->retryAttempts());
        $behaviour = RetryOrchestrator::maybeRetry($request, RetryReason::build(RetryReason::UNKNOWN));
        $this->assertNull($behaviour->exception());
        $this->assertEquals(7_500, $behaviour->retryDuration());
    }

    public function testAlwaysRetry(): void
    {
        $timeout = $this->getDefaultClient()->timeoutHandler()->getTimeout(TimeoutHandler::KV, UpsertOptions::export(new UpsertOptions()));
        $request = SharedUtils::createProtostellarRequest(new GetRequest(), true, $timeout);
        $behaviour = RetryOrchestrator::maybeRetry($request, RetryReason::build(RetryReason::KV_COLLECTION_OUTDATED));
        $this->assertNull($behaviour->exception());
        $this->assertEquals(1_000, $behaviour->retryDuration()); //Controlled backoff duration
    }
}
