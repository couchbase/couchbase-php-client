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

use Couchbase\LookupGetFullSpec;
use Couchbase\LookupInOptions;
use Couchbase\UpsertOptions;

include_once __DIR__ . "/Helpers/CouchbaseTestCase.php";

class KeyValueLookupInTest extends Helpers\CouchbaseTestCase
{
    function testSubdocumenLookupCanFetchExpiry()
    {
        $id = $this->uniqueId("foo");
        $collection = $this->defaultCollection();

        $res = $collection->upsert($id, ["foo" => "bar"]);
        $cas = $res->cas();

        $res = $collection->lookupIn(
            $id,
            [
                LookupGetFullSpec::build()
            ],
            LookupInOptions::build()->withExpiry(true)
        );
        $this->assertNotNull($res->cas());
        $this->assertEquals($cas, $res->cas());
        $this->assertEquals(["foo" => "bar"], $res->content(0));
        $this->assertNull($res->expiryTime());

        $birthday = DateTime::createFromFormat(DateTimeInterface::ISO8601, "2027-04-07T00:00:00UTC");
        $collection->upsert($id, ["foo" => "bar"], UpsertOptions::build()->expiry($birthday));

        $res = $collection->lookupIn(
            $id,
            [
                LookupGetFullSpec::build()
            ],
            LookupInOptions::build()->withExpiry(true)
        );
        $this->assertEquals($birthday, $res->expiryTime());
    }
}
