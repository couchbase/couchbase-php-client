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

use Couchbase\Exception\DocumentNotFoundException;
use Couchbase\GetOptions;
use Couchbase\RawBinaryTranscoder;
use Couchbase\UpsertOptions;

include_once __DIR__ . "/Helpers/CouchbaseTestCase.php";

class KeyValueBinaryOperationsTest extends Helpers\CouchbaseTestCase
{
    function testAppendAddsBytesToTheEndOfTheDocument()
    {
        $collection = $this->defaultCollection();
        $id = $this->uniqueId();

        $res = $collection->upsert($id, "foo", UpsertOptions::build()->transcoder(RawBinaryTranscoder::getInstance()));
        $originalCas = $res->cas();

        $res = $collection->binary()->append($id, "bar");
        $appendedCas = $res->cas();
        $this->assertNotEquals($appendedCas, $originalCas);

        $res = $collection->get($id, GetOptions::build()->transcoder(RawBinaryTranscoder::getInstance()));
        $this->assertEquals($appendedCas, $res->cas());
        $this->assertEquals("foobar", $res->content());
    }

    function testPrependAddsBytesToTheBeginningOfTheDocument()
    {
        $collection = $this->defaultCollection();
        $id = $this->uniqueId();

        $res = $collection->upsert($id, "foo", UpsertOptions::build()->transcoder(RawBinaryTranscoder::getInstance()));
        $originalCas = $res->cas();

        $res = $collection->binary()->prepend($id, "bar");
        $prependedCas = $res->cas();
        $this->assertNotEquals($prependedCas, $originalCas);

        $res = $collection->get($id, GetOptions::build()->transcoder(RawBinaryTranscoder::getInstance()));
        $this->assertEquals($prependedCas, $res->cas());
        $this->assertEquals("barfoo", $res->content());
    }

    function testAppendThrowsExceptionIfDocumentDoesNotExist()
    {
        $collection = $this->defaultCollection();
        $this->expectException(DocumentNotFoundException::class);
        $collection->binary()->append($this->uniqueId(), "foo");
    }


    function testPrependThrowsExceptionIfDocumentDoesNotExist()
    {
        $collection = $this->defaultCollection();
        $this->expectException(DocumentNotFoundException::class);
        $collection->binary()->append($this->uniqueId(), "foo");
    }
}
