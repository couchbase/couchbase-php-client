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
use Couchbase\Exception\DocumentNotFoundException;
use Couchbase\IncrementOptions;
use Couchbase\RawJsonTranscoder;
use Couchbase\UpsertOptions;

include_once __DIR__ . "/Helpers/CouchbaseTestCase.php";

class KeyValueCounterTest extends Helpers\CouchbaseTestCase
{
    function testIncrementThrowsExceptionIfDocumentDoesNotExist()
    {
        $collection = $this->defaultCollection();
        $this->expectException(DocumentNotFoundException::class);
        $collection->binary()->increment($this->uniqueId());
    }

    function testIncrementInitializesValueIfRequested()
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

    function testDecrementInitializesValueIfRequested()
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

    function testIncrementAllowsToOverrideDeltaValue()
    {
        $collection = $this->defaultCollection();
        $id = $this->uniqueId();

        $collection->upsert($id, "42", UpsertOptions::build()->transcoder(RawJsonTranscoder::getInstance()));

        $res = $collection->binary()->increment($id, IncrementOptions::build()->delta(10));
        $this->assertEquals(52, $res->content());

        $res = $collection->get($id);
        $this->assertEquals(52, $res->content());
    }

    function testDecrementAllowsToOverrideDeltaValue()
    {
        $collection = $this->defaultCollection();
        $id = $this->uniqueId();

        $collection->upsert($id, "42", UpsertOptions::build()->transcoder(RawJsonTranscoder::getInstance()));

        $res = $collection->binary()->decrement($id, DecrementOptions::build()->delta(10));
        $this->assertEquals(32, $res->content());

        $res = $collection->get($id);
        $this->assertEquals(32, $res->content());
    }
}
