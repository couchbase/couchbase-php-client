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

use Couchbase\GetOptions;
use Couchbase\UpsertOptions;
use Couchbase\InsertOptions;
use Couchbase\ReplaceOptions;
use Couchbase\RemoveOptions;
use Couchbase\ExistsOptions;
use Couchbase\TouchOptions;
use Couchbase\GetAndTouchOptions;
use Couchbase\GetAndLockOptions;
use Couchbase\UnlockOptions;
use Couchbase\LookupInOptions;
use Couchbase\LookupGetSpec;
use Couchbase\MutateInOptions;
use Couchbase\MutateUpsertSpec;
use Couchbase\DurabilityLevel;
use Helpers\Tracing\ParentSpanRequirement;


class ObservabilityKeyValueOperationsTest extends Helpers\CouchbaseObservabilityTestCase
{
    public const EXISTING_DOC_ID = "observability-kv-test";

    public function setUp(): void
    {
        parent::setUp();
        $this->defaultCollection()->upsert(self::EXISTING_DOC_ID, [ "foo" => "bar" ]);
        $this->tracer()->reset();
        $this->meter()->reset();
    }

    public function testGet()
    {
        $collection = $this->defaultCollection();
        $result = $collection->get(
            self::EXISTING_DOC_ID,
            GetOptions::build()
                ->parentSpan($this->parentSpan())
        );
        $this->assertEquals("bar", $result->content()["foo"]);

        $getSpan = $this->tracer()->getSpans(null, $this->parentSpan())[0];
        $this->assertKvOperationSpan($getSpan, "get", $this->parentSpan());
        $this->assertKvOperationMetrics(1, "get");
    }

    public function testGetDocumentNotFound()
    {
        $collection = $this->defaultCollection();
        $this->wrapException(
            function () use ($collection) {
                $collection->get(
                    "non-existing-id",
                    GetOptions::build()
                    ->parentSpan($this->parentSpan())
                );
            },
            Couchbase\Exception\DocumentNotFoundException::class
        );

        $getSpan = $this->tracer()->getSpans(null, $this->parentSpan())[0];
        $this->assertKvOperationSpan($getSpan, "get", $this->parentSpan());
        $this->assertKvOperationMetrics(1, "get", error: "DocumentNotFound");
    }

    public function testGetNoParentSpan()
    {
        $collection = $this->defaultCollection();
        $result = $collection->get(self::EXISTING_DOC_ID);
        $this->assertEquals("bar", $result->content()["foo"]);

        $getSpan = $this->tracer()->getSpans(null, ParentSpanRequirement::ROOT)[0];
        $this->assertKvOperationSpan($getSpan, "get");
        $this->assertKvOperationMetrics(1, "get");
    }

    public function testExists()
    {
        $collection = $this->defaultCollection();
        $result = $collection->exists(
            self::EXISTING_DOC_ID,
            ExistsOptions::build()
                ->parentSpan($this->parentSpan())
        );
        $this->assertTrue($result->exists());

        $existsSpan = $this->tracer()->getSpans(null, $this->parentSpan())[0];
        $this->assertKvOperationSpan($existsSpan, "exists", $this->parentSpan());
        $this->assertKvOperationMetrics(1, "exists");
    }

    public function testRemove()
    {
        $collection = $this->defaultCollection();
        $collection->remove(
            self::EXISTING_DOC_ID,
            RemoveOptions::build()
                ->parentSpan($this->parentSpan())
        );

        $removeSpan = $this->tracer()->getSpans(null, $this->parentSpan())[0];
        $this->assertKvOperationSpan($removeSpan, "remove", $this->parentSpan());
        $this->assertKvOperationMetrics(1, "remove");
    }

    public function testUpsert()
    {
        $collection = $this->defaultCollection();
        $collection->upsert(
            self::EXISTING_DOC_ID,
            ["foo" => "baz"],
            UpsertOptions::build()
                ->parentSpan($this->parentSpan())
        );

        $upsertSpan = $this->tracer()->getSpans(null, $this->parentSpan())[0];
        $this->assertKvOperationSpan($upsertSpan, "upsert", $this->parentSpan());
        $this->assertHasRequestEncodingSpan($upsertSpan);
        $this->assertKvOperationMetrics(1, "upsert");
    }

    public function testInsert()
    {
        $collection = $this->defaultCollection();
        $id = $this->uniqueId();
        $collection->insert(
            $id,
            ["foo" => "bar"],
            InsertOptions::build()
                ->parentSpan($this->parentSpan())
        );

        $insertSpan = $this->tracer()->getSpans(null, $this->parentSpan())[0];
        $this->assertKvOperationSpan($insertSpan, "insert", $this->parentSpan());
        $this->assertHasRequestEncodingSpan($insertSpan);
        $this->assertKvOperationMetrics(1, "insert");
    }

    public function testReplace()
    {
        $collection = $this->defaultCollection();
        $collection->replace(
            self::EXISTING_DOC_ID,
            ["foo" => "baz"],
            ReplaceOptions::build()
                ->parentSpan($this->parentSpan())
        );

        $replaceSpan = $this->tracer()->getSpans(null, $this->parentSpan())[0];
        $this->assertKvOperationSpan($replaceSpan, "replace", $this->parentSpan());
        $this->assertHasRequestEncodingSpan($replaceSpan);
        $this->assertKvOperationMetrics(1, "replace");
    }

    public function testTouch()
    {
        $collection = $this->defaultCollection();
        $collection->touch(
            self::EXISTING_DOC_ID,
            10,
            TouchOptions::build()
                ->parentSpan($this->parentSpan())
        );

        $touchSpan = $this->tracer()->getSpans(null, $this->parentSpan())[0];
        $this->assertKvOperationSpan($touchSpan, "touch", $this->parentSpan());
        $this->assertKvOperationMetrics(1, "touch");
    }

    public function testGetAndTouch()
    {
        $collection = $this->defaultCollection();
        $result = $collection->getAndTouch(
            self::EXISTING_DOC_ID,
            10,
            GetAndTouchOptions::build()
                ->parentSpan($this->parentSpan())
        );
        $this->assertEquals("bar", $result->content()["foo"]);

        $getAndTouchSpan = $this->tracer()->getSpans(null, $this->parentSpan())[0];
        $this->assertKvOperationSpan($getAndTouchSpan, "get_and_touch", $this->parentSpan());
        $this->assertKvOperationMetrics(1, "get_and_touch");
    }

    public function testGetAndLockUnlock()
    {
        $collection = $this->defaultCollection();
        $result = $collection->getAndLock(
            self::EXISTING_DOC_ID,
            5,
            GetAndLockOptions::build()
                ->parentSpan($this->parentSpan())
        );
        $this->assertEquals("bar", $result->content()["foo"]);

        $cas = $result->cas();

        $collection->unlock(
            self::EXISTING_DOC_ID,
            $cas,
            UnlockOptions::build()
                ->parentSpan($this->parentSpan())
        );

        $getAndLockSpan = $this->tracer()->getSpans(null, $this->parentSpan())[0];
        $this->assertKvOperationSpan($getAndLockSpan, "get_and_lock", $this->parentSpan());
        $this->assertKvOperationMetrics(1, "get_and_lock");

        $unlockSpan = $this->tracer()->getSpans(null, $this->parentSpan())[1];
        $this->assertKvOperationSpan($unlockSpan, "unlock", $this->parentSpan());
        $this->assertKvOperationMetrics(1, "unlock");
    }

    public function testLookupIn()
    {
        $collection = $this->defaultCollection();
        $result = $collection->lookupIn(
            self::EXISTING_DOC_ID,
            [LookupGetSpec::build("foo")],
            LookupInOptions::build()
                ->parentSpan($this->parentSpan())
        );
        $this->assertEquals("bar", $result->content(0));

        $lookupInSpan = $this->tracer()->getSpans(null, $this->parentSpan())[0];
        $this->assertKvOperationSpan($lookupInSpan, "lookup_in", $this->parentSpan());
        $this->assertKvOperationMetrics(1, "lookup_in");
    }

    public function testMutateIn()
    {
        $collection = $this->defaultCollection();
        $collection->mutateIn(
            self::EXISTING_DOC_ID,
            [MutateUpsertSpec::build("baz", "qux")],
            MutateInOptions::build()
                ->parentSpan($this->parentSpan())
        );

        $mutateInSpan = $this->tracer()->getSpans(null, $this->parentSpan())[0];
        $this->assertKvOperationSpan($mutateInSpan, "mutate_in", $this->parentSpan());
        $this->assertHasRequestEncodingSpan($mutateInSpan);
        $this->assertKvOperationMetrics(1, "mutate_in");
    }

    public function testInsertWithDurabilityMajority()
    {
        $this->skipIfCaves();
        $this->skipIfReplicasAreNotConfigured();

        $collection = $this->defaultCollection();
        $id = $this->uniqueId();
        $collection->insert(
            $id,
            ["foo" => "bar"],
            InsertOptions::build()
                ->durabilityLevel(DurabilityLevel::MAJORITY, null)
                ->parentSpan($this->parentSpan())
        );

        $insertSpan = $this->tracer()->getSpans(null, $this->parentSpan())[0];
        $this->assertKvOperationSpan($insertSpan, "insert", $this->parentSpan(), durability: DurabilityLevel::MAJORITY);
        $this->assertHasRequestEncodingSpan($insertSpan);
        $this->assertKvOperationMetrics(1, "insert");
    }

    public function testReplaceWithDurabilityMajorityAndPersistToActive()
    {
        $this->skipIfCaves();
        $this->skipIfReplicasAreNotConfigured();

        $collection = $this->defaultCollection();
        $collection->replace(
            self::EXISTING_DOC_ID,
            ["foo" => "baz"],
            ReplaceOptions::build()
                ->durabilityLevel(DurabilityLevel::MAJORITY_AND_PERSIST_TO_ACTIVE, null)
                ->parentSpan($this->parentSpan())
        );

        $replaceSpan = $this->tracer()->getSpans(null, $this->parentSpan())[0];
        $this->assertKvOperationSpan($replaceSpan, "replace", $this->parentSpan(), durability: DurabilityLevel::MAJORITY_AND_PERSIST_TO_ACTIVE);
        $this->assertHasRequestEncodingSpan($replaceSpan);
        $this->assertKvOperationMetrics(1, "replace");
    }

    public function testRemoveWithDurabilityPersistToMajority()
    {
        $this->skipIfCaves();
        $this->skipIfReplicasAreNotConfigured();

        $collection = $this->defaultCollection();
        $collection->remove(
            self::EXISTING_DOC_ID,
            RemoveOptions::build()
                ->durabilityLevel(DurabilityLevel::PERSIST_TO_MAJORITY, null)
                ->parentSpan($this->parentSpan())
        );

        $removeSpan = $this->tracer()->getSpans(null, $this->parentSpan())[0];
        $this->assertKvOperationSpan($removeSpan, "remove", $this->parentSpan(), durability: DurabilityLevel::PERSIST_TO_MAJORITY);
        $this->assertKvOperationMetrics(1, "remove");
    }
}
