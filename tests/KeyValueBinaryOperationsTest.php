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
use Couchbase\DurabilityLevel;
use Couchbase\Exception\DocumentNotFoundException;
use Couchbase\GetOptions;
use Couchbase\PrependOptions;
use Couchbase\RawBinaryTranscoder;
use Couchbase\UpsertOptions;

include_once __DIR__ . "/Helpers/CouchbaseTestCase.php";

class KeyValueBinaryOperationsTest extends Helpers\CouchbaseTestCase
{
    public function testAppendAddsBytesToTheEndOfTheDocument()
    {
        $collection = $this->defaultCollection();
        $id = $this->uniqueId();

        $res = $collection->upsert($id, "foo", UpsertOptions::build()->transcoder(RawBinaryTranscoder::getInstance()));
        $originalCas = $res->cas();

        $this->fixCavesTimeResolutionOnWindows();
        $res = $collection->binary()->append($id, "bar");
        $appendedCas = $res->cas();
        $this->assertNotEquals($appendedCas, $originalCas);

        $res = $collection->get($id, GetOptions::build()->transcoder(RawBinaryTranscoder::getInstance()));
        $this->assertEquals($appendedCas, $res->cas());
        $this->assertEquals("foobar", $res->content());
    }

    public function testPrependAddsBytesToTheBeginningOfTheDocument()
    {
        $collection = $this->defaultCollection();
        $id = $this->uniqueId();

        $res = $collection->upsert($id, "foo", UpsertOptions::build()->transcoder(RawBinaryTranscoder::getInstance()));
        $originalCas = $res->cas();

        $this->fixCavesTimeResolutionOnWindows();
        $res = $collection->binary()->prepend($id, "bar");
        $prependedCas = $res->cas();
        $this->assertNotEquals($prependedCas, $originalCas);

        $res = $collection->get($id, GetOptions::build()->transcoder(RawBinaryTranscoder::getInstance()));
        $this->assertEquals($prependedCas, $res->cas());
        $this->assertEquals("barfoo", $res->content());
    }

    public function testAppendThrowsExceptionIfDocumentDoesNotExist()
    {
        $collection = $this->defaultCollection();
        $this->expectException(DocumentNotFoundException::class);
        $collection->binary()->append($this->uniqueId(), "foo");
    }


    public function testPrependThrowsExceptionIfDocumentDoesNotExist()
    {
        $collection = $this->defaultCollection();
        $this->expectException(DocumentNotFoundException::class);
        $collection->binary()->append($this->uniqueId(), "foo");
    }

    public function testAppendDurabilityMajority()
    {
        $this->skipIfUnsupported($this->version()->supportsEnhancedDurability());

        $key = $this->uniqueId("append-durability-majority");
        $collection = $this->defaultCollection();
        $collection->upsert($key, "foo", UpsertOptions::build()->transcoder(RawBinaryTranscoder::getInstance()));
        $opts = AppendOptions::build()->durabilityLevel(DurabilityLevel::MAJORITY);
        $res = $collection->binary()->append($key, "bar", $opts);
        $this->assertNotNull($res->cas());
    }

    public function testAppendDurabilityMajorityAndPersist()
    {
        $this->skipIfUnsupported($this->version()->supportsEnhancedDurability());

        $key = $this->uniqueId("append-durability-majority-and-persist");
        $collection = $this->defaultCollection();
        $collection->upsert($key, "foo", UpsertOptions::build()->transcoder(RawBinaryTranscoder::getInstance()));
        $opts = AppendOptions::build()->durabilityLevel(DurabilityLevel::MAJORITY_AND_PERSIST_TO_ACTIVE, 5);
        $res = $collection->binary()->append($key, "bar", $opts);
        $this->assertNotNull($res->cas());
    }

    public function testAppendDurabilityPersistToMajority()
    {
        $this->skipIfUnsupported($this->version()->supportsEnhancedDurability());

        $key = $this->uniqueId("append-durability-persist-majority");
        $collection = $this->defaultCollection();
        $collection->upsert($key, "foo", UpsertOptions::build()->transcoder(RawBinaryTranscoder::getInstance()));
        $opts = AppendOptions::build()->durabilityLevel(DurabilityLevel::PERSIST_TO_MAJORITY);
        $res = $collection->binary()->append($key, "bar", $opts);
        $this->assertNotNull($res->cas());
    }

    public function testPrependDurabilityMajority()
    {
        $this->skipIfUnsupported($this->version()->supportsEnhancedDurability());

        $key = $this->uniqueId("prepend-durability-majority");
        $collection = $this->defaultCollection();
        $collection->upsert($key, "foo", UpsertOptions::build()->transcoder(RawBinaryTranscoder::getInstance()));
        $opts = PrependOptions::build()->durabilityLevel(DurabilityLevel::MAJORITY);
        $res = $collection->binary()->prepend($key, "bar", $opts);
        $this->assertNotNull($res->cas());
    }

    public function testPrependDurabilityMajorityAndPersist()
    {
        $this->skipIfUnsupported($this->version()->supportsEnhancedDurability());

        $key = $this->uniqueId("prepend-durability-majority-and-persist");
        $collection = $this->defaultCollection();
        $collection->upsert($key, "foo", UpsertOptions::build()->transcoder(RawBinaryTranscoder::getInstance()));
        $opts = PrependOptions::build()->durabilityLevel(DurabilityLevel::MAJORITY_AND_PERSIST_TO_ACTIVE);
        $res = $collection->binary()->prepend($key, "bar", $opts);
        $this->assertNotNull($res->cas());
    }

    public function testPrependDurabilityPersistToMajority()
    {
        $this->skipIfUnsupported($this->version()->supportsEnhancedDurability());

        $key = $this->uniqueId("prepend-durability-persist-majority");
        $collection = $this->defaultCollection();
        $collection->upsert($key, "foo", UpsertOptions::build()->transcoder(RawBinaryTranscoder::getInstance()));
        $opts = PrependOptions::build()->durabilityLevel(DurabilityLevel::PERSIST_TO_MAJORITY);
        $res = $collection->binary()->prepend($key, "bar", $opts);
        $this->assertNotNull($res->cas());
    }
}
