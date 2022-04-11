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

use Couchbase\GetOptions;
use Couchbase\RawJsonTranscoder;
use Couchbase\UpsertOptions;

include_once __DIR__ . "/Helpers/CouchbaseTestCase.php";

class TranscodersTest extends Helpers\CouchbaseTestCase
{
    public function testDefaultTranscoderUsesJson()
    {
        $id = $this->uniqueId();
        $collection = $this->defaultCollection();

        $collection->upsert($id, ["answer" => 42]);
        $res = $collection->get($id);
        $this->assertEquals(["answer" => 42], $res->content());

        /* this violates open/closed principle, and should not be ever used in production, but for tests it is okay */
        $reflector = new ReflectionClass($res);
        $property = $reflector->getProperty("value");
        $property->setAccessible(true);
        $this->assertEquals('{"answer":42}', $property->getValue($res));
    }

    public function testRawJsonTranscoderDoesNotRecodeJsonOnEncode()
    {
        $id = $this->uniqueId();
        $collection = $this->defaultCollection();

        $collection->upsert($id, '{"answer":42}', UpsertOptions::build()->transcoder(RawJsonTranscoder::getInstance()));
        $res = $collection->get($id);
        $this->assertEquals(["answer" => 42], $res->content());
    }

    public function testRawJsonTranscoderReturnsRawDataOnDecode()
    {
        $id = $this->uniqueId();
        $collection = $this->defaultCollection();

        $collection->upsert($id, ["answer" => 42]);
        $res = $collection->get($id, GetOptions::build()->transcoder(RawJsonTranscoder::getInstance()));
        $this->assertEquals('{"answer":42}', $res->content());
    }
}
