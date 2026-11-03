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

use Couchbase\AppendOptions;
use Couchbase\DecrementOptions;
use Couchbase\Exception\AmbiguousTimeoutException;
use Couchbase\Exception\DocumentLockedException;
use Couchbase\Exception\DocumentNotLockedException;
use Couchbase\Exception\InvalidArgumentException;
use Couchbase\Exception\RequestCanceledException;
use Couchbase\ExistsOptions;
use Couchbase\GetAndLockOptions;
use Couchbase\GetAndTouchOptions;
use Couchbase\GetOptions;
use Couchbase\IncrementOptions;
use Couchbase\InsertOptions;
use Couchbase\LookupGetFullSpec;
use Couchbase\LookupInOptions;
use Couchbase\MutateInOptions;
use Couchbase\MutateUpsertSpec;
use Couchbase\PrependOptions;
use Couchbase\RemoveOptions;
use Couchbase\ReplaceOptions;
use Couchbase\RetryStrategy;
use Couchbase\TouchOptions;
use Couchbase\UnlockOptions;
use Couchbase\Protostellar\Internal\SharedUtils;
use Couchbase\Protostellar\Retries\BestEffortRetryStrategy;
use Couchbase\Protostellar\Retries\FailFastRetryStrategy;
use Couchbase\Protostellar\Retries\RetryOrchestrator;
use Couchbase\Protostellar\Retries\RetryReason;
use Couchbase\UpsertOptions;

include_once __DIR__ . "/Helpers/CouchbaseTestCase.php";

class KeyValueLockingTest extends Helpers\CouchbaseTestCase
{
    public function testPessimisticLockingWorkflow()
    {
        $this->skipIfProtostellar(); //Skipping due to lookupin locked doc get issue
        $id = $this->uniqueId("foo");
        $collection = $this->defaultCollection();

        $res = $collection->upsert($id, ["foo" => "bar"]);
        $originalCas = $res->cas();

        $res = $collection->getAndLock($id, 5);
        $lockedCas = $res->cas();
        $this->assertNotEquals($originalCas, $lockedCas);
        $this->assertEquals(["foo" => "bar"], $res->content());

        $res = $collection->get($id);
        $this->assertNotEquals($originalCas, $res->cas());
        $this->assertNotEquals($lockedCas, $res->cas());

        $collection->unlock($id, $lockedCas);
    }

    public function testUnlockingUnlockedDocumentThrowsDocNotLocked()
    {
        $this->skipIfProtostellar();
        $this->skipIfUnsupported($this->version()->supportsDocNotLockedException());
        $id = $this->uniqueId("foo");
        $collection = $this->defaultCollection();

        $collection->upsert($id, ["foo" => "bar"]);
        $cas = $collection->get($id)->cas();

        $this->expectException(DocumentNotLockedException::class);
        $collection->unlock($id, $cas);
    }

    /**
     * The default strategy retries a mutation on a locked document until the timeout. Fail-fast
     * surfaces the lock at once, for single and multi mutations alike.
     */
    public function testFailFastRetryStrategyDoesNotRetryLockedDocument()
    {
        $this->skipIfProtostellar();
        $this->skipIfCaves();
        $id = $this->uniqueId("foo");
        $other = $this->uniqueId("bar");
        $collection = $this->defaultCollection();

        $collection->upsertMulti([[$id, ["foo" => "bar"]], [$other, ["foo" => "bar"]]]);
        $cas = $collection->getAndLock($id, 15)->cas();

        $this->wrapException(
            fn() => $collection->upsert($id, ["foo" => "baz"], UpsertOptions::build()->timeout(1_000)),
            AmbiguousTimeoutException::class
        );

        $failFast = UpsertOptions::build()->timeout(10_000)->retryStrategy(RetryStrategy::FAIL_FAST);
        $start = microtime(true);
        $this->wrapException(
            fn() => $collection->upsert($id, ["foo" => "baz"], $failFast),
            DocumentLockedException::class
        );
        $this->wrapException(
            fn() => $collection->replace($id, ["foo" => "baz"], ReplaceOptions::build()->retryStrategy(RetryStrategy::FAIL_FAST)),
            DocumentLockedException::class
        );
        $this->wrapException(
            fn() => $collection->remove($id, RemoveOptions::build()->retryStrategy(RetryStrategy::FAIL_FAST)),
            DocumentLockedException::class
        );
        $this->wrapException(
            fn() => $collection->mutateIn(
                $id,
                [new MutateUpsertSpec("foo", "baz")],
                MutateInOptions::build()->retryStrategy(RetryStrategy::FAIL_FAST)
            ),
            DocumentLockedException::class
        );
        $results = $collection->upsertMulti([[$other, ["foo" => "baz"]], [$id, ["foo" => "baz"]]], $failFast);
        $this->assertLessThan(5, microtime(true) - $start);
        $this->assertNull($results[0]->error());
        $this->assertInstanceOf(DocumentLockedException::class, $results[1]->error());

        $collection->unlock($id, $cas);
    }

    public function testUnknownRetryStrategyThrowsInvalidArgument()
    {
        $this->skipIfProtostellar();
        $this->wrapException(
            fn() => $this->defaultCollection()->get($this->uniqueId("foo"), GetOptions::build()->retryStrategy("sometimes")),
            InvalidArgumentException::class
        );
    }

    public function testKeyValueOptionsExportRetryStrategy()
    {
        $classes = [
            GetOptions::class, ExistsOptions::class, GetAndLockOptions::class, GetAndTouchOptions::class,
            TouchOptions::class, UnlockOptions::class, InsertOptions::class, UpsertOptions::class,
            ReplaceOptions::class, RemoveOptions::class, AppendOptions::class, PrependOptions::class,
            IncrementOptions::class, DecrementOptions::class, LookupInOptions::class, MutateInOptions::class,
        ];
        foreach ($classes as $class) {
            $options = new $class();
            $this->assertNull($class::export($options)["retryStrategy"], $class);
            $this->assertSame(
                RetryStrategy::FAIL_FAST,
                $class::export($options->retryStrategy(RetryStrategy::FAIL_FAST))["retryStrategy"],
                $class
            );
        }
    }

    public function testProtostellarRequestTakesRetryStrategy()
    {
        $this->assertInstanceOf(BestEffortRetryStrategy::class, SharedUtils::createProtostellarRequest(null, false, 1e6)->retryStrategy());
        $this->assertInstanceOf(
            BestEffortRetryStrategy::class,
            SharedUtils::createProtostellarRequest(null, false, 1e6, RetryStrategy::BEST_EFFORT)->retryStrategy()
        );
        $this->assertInstanceOf(
            FailFastRetryStrategy::class,
            SharedUtils::createProtostellarRequest(null, false, 1e6, RetryStrategy::FAIL_FAST)->retryStrategy()
        );
        $this->wrapException(
            fn() => SharedUtils::createProtostellarRequest(null, false, 1e6, "sometimes"),
            InvalidArgumentException::class
        );
    }

    /**
     * Over couchbase2://, a lock that the strategy declines to retry must surface as the lock, and
     * any other declined reason as RequestCanceledException.
     */
    public function testProtostellarFailFastReportsLockedDocument()
    {
        $locked = new RetryReason(RetryReason::KV_LOCKED);
        $failFast = SharedUtils::createProtostellarRequest(null, false, 1e6, RetryStrategy::FAIL_FAST);
        $this->assertInstanceOf(DocumentLockedException::class, RetryOrchestrator::maybeRetry($failFast, $locked)->exception());
        $this->assertInstanceOf(
            RequestCanceledException::class,
            RetryOrchestrator::maybeRetry($failFast, new RetryReason(RetryReason::KV_TEMPORARY_FAILURE))->exception()
        );
        $bestEffort = SharedUtils::createProtostellarRequest(null, false, 1e6);
        $this->assertNotNull(RetryOrchestrator::maybeRetry($bestEffort, $locked)->retryDuration());
    }
}
