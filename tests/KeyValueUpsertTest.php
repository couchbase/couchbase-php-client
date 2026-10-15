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

use Couchbase\DurabilityLevel;
use Couchbase\Exception\DocumentNotFoundException;
use Couchbase\Exception\InvalidArgumentException;
use Couchbase\PersistTo;
use Couchbase\ReplicateTo;
use Couchbase\UpsertOptions;

include_once __DIR__ . "/Helpers/CouchbaseTestCase.php";

class KeyValueUpsertTest extends Helpers\CouchbaseTestCase
{
    public function testUpsertReturnsCas()
    {
        $collection = $this->defaultCollection();
        $res = $collection->upsert($this->uniqueId("foo"), ["answer" => 42]);
        $this->assertNotNull($res->cas());
    }

    public function testUpsertDurabilityMajority()
    {
        $this->skipIfUnsupported($this->version()->supportsEnhancedDurability());
        $collection = $this->defaultCollection();
        $opts = UpsertOptions::build()->durabilityLevel(DurabilityLevel::MAJORITY);
        $res = $collection->upsert($this->uniqueId("upsert-durability-majority"), ["answer" => 42], $opts);
        $this->assertNotNull($res->cas());
    }

    public function testUpsertDurabilityMajorityAndPersist()
    {
        $this->skipIfUnsupported($this->version()->supportsEnhancedDurability());
        $collection = $this->defaultCollection();
        $opts = UpsertOptions::build()->durabilityLevel(DurabilityLevel::MAJORITY_AND_PERSIST_TO_ACTIVE);
        $res = $collection->upsert($this->uniqueId("upsert-durability-majority-and-persist"), ["answer" => 42], $opts);
        $this->assertNotNull($res->cas());
    }

    public function testUpsertDurabilityPersistToMajority()
    {
        $this->skipIfUnsupported($this->version()->supportsEnhancedDurability());
        $collection = $this->defaultCollection();
        $opts = UpsertOptions::build()->durabilityLevel(DurabilityLevel::PERSIST_TO_MAJORITY);
        $res = $collection->upsert($this->uniqueId("upsert-durability-persist-majority"), ["answer" => 42], $opts);
        $this->assertNotNull($res->cas());
    }

    private function upsertWithLegacyDurability(string $id, ?array $options): void
    {
        $this->skipIfProtostellar();
        $core = $this->connectCluster()->core();
        $function = COUCHBASE_EXTENSION_NAMESPACE . '\\documentUpsert';
        $function(
            $core,
            self::env()->bucketName(),
            "_default",
            "_default",
            $id,
            '{"answer":42}',
            0,
            $options
        );
    }

    private function assertUpsertRejected(array $options, string $message): void
    {
        $id = $this->uniqueId("upsert-legacy-durability");
        try {
            $this->upsertWithLegacyDurability($id, $options);
            $this->fail("expected InvalidArgumentException");
        } catch (InvalidArgumentException $e) {
            $this->assertStringContainsString($message, $e->getMessage());
        }
        $this->expectException(DocumentNotFoundException::class);
        $this->defaultCollection()->get($id);
    }

    public function testUpsertRejectsUnknownPersistTo()
    {
        $this->assertUpsertRejected(["persistTo" => "won"], "unknown persistTo: won");
    }

    public function testUpsertRejectsUnknownReplicateTo()
    {
        $this->assertUpsertRejected(["replicateTo" => "won"], "unknown replicateTo: won");
    }

    public function testUpsertAcceptsLegacyDurabilityWithoutConstraints()
    {
        foreach (
            [
                null,
                [],
                ["persistTo" => null, "replicateTo" => null],
                ["persistTo" => PersistTo::NONE, "replicateTo" => ReplicateTo::NONE],
            ] as $options
        ) {
            $id = $this->uniqueId("upsert-legacy-durability-none");
            $this->upsertWithLegacyDurability($id, $options);
            $this->assertNotNull($this->defaultCollection()->get($id)->cas());
        }
    }

    public function testUpsertAcceptsLegacyDurabilityPersistToActive()
    {
        $this->skipIfCaves();
        $id = $this->uniqueId("upsert-legacy-durability-active");
        $this->upsertWithLegacyDurability($id, ["persistTo" => PersistTo::ACTIVE]);
        $this->assertNotNull($this->defaultCollection()->get($id)->cas());
    }
}
