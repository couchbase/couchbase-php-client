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

include_once __DIR__ . "/Helpers/CouchbaseTestCase.php";

class KeyValueTouchingTest extends Helpers\CouchbaseTestCase
{
    public function testGetAndTouchChangesExpiry()
    {
        $id = $this->uniqueId("foo");
        $collection = $this->defaultCollection();

        $res = $collection->upsert($id, ["foo" => "bar"]);
        $originalCas = $res->cas();
        $this->assertNotNull($originalCas);

        $res = $collection->get($id, GetOptions::build()->withExpiry(true));
        $this->assertNull($res->expiryTime());

        $res = $collection->getAndTouch($id, 10);
        $gatCas = $res->cas();
        $this->assertNotNull($gatCas);
        $this->assertNotEquals($originalCas, $gatCas);
        $this->assertEquals(["foo" => "bar"], $res->content());

        $res = $collection->get($id, GetOptions::build()->withExpiry(true));
        $this->assertGreaterThan(
            date_add(date_create(), date_interval_create_from_date_string('3 seconds')),
            $res->expiryTime()
        );

        $birthday = DateTime::createFromFormat(DateTimeInterface::ISO8601, "2027-04-07T00:00:00UTC");
        $res = $collection->touch($id, $birthday);
        $this->assertNotNull($res->cas());

        $res = $collection->get($id, GetOptions::build()->withExpiry(true));
        $this->assertEquals($birthday, $res->expiryTime());
        $this->assertNotEquals($gatCas, $res->cas());
        $this->assertNotEquals($originalCas, $res->cas());
    }
}
