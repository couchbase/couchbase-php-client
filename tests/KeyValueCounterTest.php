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

use Couchbase\DecrementOptions;
use Couchbase\DurabilityLevel;
use Couchbase\Exception\DocumentNotFoundException;
use Couchbase\IncrementOptions;
use Couchbase\RawBinaryTranscoder;
use Couchbase\RawJsonTranscoder;
use Couchbase\UpsertOptions;

include_once __DIR__ . "/Helpers/CouchbaseTestCase.php";

class KeyValueCounterTest extends Helpers\CouchbaseTestCase
{
    public function testIncrementThrowsExceptionIfDocumentDoesNotExist()
    {
        $collection = $this->defaultCollection();
        $this->expectException(DocumentNotFoundException::class);
        $collection->binary()->increment($this->uniqueId());
    }

    public function testIncrementInitializesValueIfRequested()
    {
        $collection = $this->defaultCollection();
        $id = $this->uniqueId();

        $res = $collection->binary()->increment($id, IncrementOptions::build()->initial(42));
        $initialCas = $res->cas();
        $this->assertEquals(42, $res->content());

        $res = $collection->get($id);
        $this->assertEquals($initialCas, $res->cas());
        $this->assertEquals(42, $res->content());

        $res = $collection->binary()->increment($id);
        $incrementedCas = $res->cas();
        $this->assertEquals(43, $res->content());

        $res = $collection->get($id);
        $this->assertEquals($incrementedCas, $res->cas());
        $this->assertEquals(43, $res->content());
    }

    public function testDecrementInitializesValueIfRequested()
    {
        $collection = $this->defaultCollection();
        $id = $this->uniqueId();

        $res = $collection->binary()->decrement($id, DecrementOptions::build()->initial(42));
        $initialCas = $res->cas();
        $this->assertEquals(42, $res->content());

        $res = $collection->get($id);
        $this->assertEquals($initialCas, $res->cas());
        $this->assertEquals(42, $res->content());

        $res = $collection->binary()->decrement($id);
        $decrementedCas = $res->cas();
        $this->assertEquals(41, $res->content());

        $res = $collection->get($id);
        $this->assertEquals($decrementedCas, $res->cas());
        $this->assertEquals(41, $res->content());
    }

    public function testIncrementAllowsToOverrideDeltaValue()
    {
        $collection = $this->defaultCollection();
        $id = $this->uniqueId();

        $collection->upsert($id, "42", UpsertOptions::build()->transcoder(RawJsonTranscoder::getInstance()));

        $res = $collection->binary()->increment($id, IncrementOptions::build()->delta(10));
        $this->assertEquals(52, $res->content());

        $res = $collection->get($id);
        $this->assertEquals(52, $res->content());
    }

    public function testDecrementAllowsToOverrideDeltaValue()
    {
        $collection = $this->defaultCollection();
        $id = $this->uniqueId();

        $collection->upsert($id, "42", UpsertOptions::build()->transcoder(RawJsonTranscoder::getInstance()));

        $res = $collection->binary()->decrement($id, DecrementOptions::build()->delta(10));
        $this->assertEquals(32, $res->content());

        $res = $collection->get($id);
        $this->assertEquals(32, $res->content());
    }

    // CXXCBC-167
    public function testIncrementDurabilityMajority()
    {
        $this->skipIfUnsupported($this->version()->supportsEnhancedDurability());

        $key = $this->uniqueId("increment-durability-majority");
        $collection = $this->defaultCollection();
        $opts = IncrementOptions::build()->durabilityLevel(DurabilityLevel::MAJORITY)->initial(42);
        $res = $collection->binary()->increment($key, $opts);
        $this->assertNotNull($res->cas());
    }

    public function testIncrementDurabilityMajorityAndPersist()
    {
        $this->skipIfUnsupported($this->version()->supportsEnhancedDurability());

        $key = $this->uniqueId("increment-durability-majority-and-persist");
        $collection = $this->defaultCollection();
        $opts = IncrementOptions::build()->durabilityLevel(DurabilityLevel::MAJORITY_AND_PERSIST_TO_ACTIVE, 5)->initial(42);
        $res = $collection->binary()->increment($key, $opts);
        $this->assertNotNull($res->cas());
    }

    public function testIncrementDurabilityPersistToMajority()
    {
        $this->skipIfUnsupported($this->version()->supportsEnhancedDurability());

        $key = $this->uniqueId("increment-durability-persist-majority");
        $collection = $this->defaultCollection();
        $opts = IncrementOptions::build()->durabilityLevel(DurabilityLevel::PERSIST_TO_MAJORITY)->initial(42);
        $res = $collection->binary()->increment($key, $opts);
        $this->assertNotNull($res->cas());
    }
}
