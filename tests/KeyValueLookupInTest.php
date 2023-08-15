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

use Couchbase\Exception\PathNotFoundException;
use Couchbase\Exception\DocumentIrretrievableException;
use Couchbase\Exception\DocumentNotFoundException;
use Couchbase\LookupGetFullSpec;
use Couchbase\LookupGetSpec;
use Couchbase\LookupInOptions;
use Couchbase\UpsertOptions;
use Couchbase\LookupInAnyReplicaOptions;

include_once __DIR__ . "/Helpers/CouchbaseTestCase.php";

class KeyValueLookupInTest extends Helpers\CouchbaseTestCase
{
    public function testSubdocumenLookupCanFetchExpiry()
    {
        $id = $this->uniqueId("foo");
        $collection = $this->defaultCollection();

        $res = $collection->upsert($id, ["foo" => "bar"]);
        $cas = $res->cas();

        $res = $collection->lookupIn(
            $id,
            [
                LookupGetFullSpec::build(),
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
                LookupGetFullSpec::build(),
            ],
            LookupInOptions::build()->withExpiry(true)
        );
        $this->assertEquals($birthday, $res->expiryTime());
    }

    public function testSubdocumentLookupRaisesExceptionsOnlyOnAccessResultFields()
    {
        $id = $this->uniqueId("foo");
        $collection = $this->defaultCollection();

        $collection->upsert($id, ["foo" => ["value" => 3.14]]);

        $res = $collection->lookupIn(
            $id,
            [
                LookupGetSpec::build("foo.value"),
                LookupGetSpec::build("foo.bar"),
            ]
        );
        $this->assertEquals(3.14, $res->content(0));
        $this->assertEquals(3.14, $res->contentByPath("foo.value"));
        $this->expectException(PathNotFoundException::class);
        $this->assertEquals(3.14, $res->content(1));
    }

    public function testLookupInAllReplicasThrowsDocumentNotFoundExceptionForMissingId()
    {
        $this->skipIfUnsupported($this->version()->supportsSubdocReadReplica());
        $this->skipIfReplicasAreNotConfigured();

        $id = $this->uniqueId();
        $collection = $this->defaultCollection();
        $this->expectException(DocumentNotFoundException::class);
        $collection->lookupInAllReplicas(
            $id,
            [
                LookupGetSpec::build("not.exist")
            ]
        );
    }

    public function testLookupInAnyReplicaThrowsIrretrievableExceptionForMissingId()
    {
        $this->skipIfUnsupported($this->version()->supportsSubdocReadReplica());
        $this->skipIfReplicasAreNotConfigured();

        $id = $this->uniqueId();
        $collection = $this->defaultCollection();
        $this->expectException(DocumentIrretrievableException::class);
        $collection->lookupInAnyReplica(
            $id,
            [
            LookupGetSpec::build("not.exist")
            ]
        );
    }

    public function testSubdocumentLookupAnyReplicaCanFetchExpiry()
    {
        $this->skipIfUnsupported($this->version()->supportsSubdocReadReplica());
        $this->skipIfReplicasAreNotConfigured();

        $id = $this->uniqueId("foo");
        $collection = $this->defaultCollection();

        $res = $collection->upsert($id, ["foo" => "bar"]);
        $cas = $res->cas();

        $res = $collection->lookupInAnyReplica(
            $id,
            [
                LookupGetFullSpec::build(),
            ],
            LookupInAnyReplicaOptions::build()->withExpiry(true)
        );
        $this->assertNotNull($res->cas());
        $this->assertEquals($cas, $res->cas());
        $this->assertEquals(["foo" => "bar"], $res->content(0));
        $this->assertNull($res->expiryTime());

        $birthday = DateTime::createFromFormat(DateTimeInterface::ISO8601, "2027-04-07T00:00:00UTC");
        $collection->upsert($id, ["foo" => "bar"], UpsertOptions::build()->expiry($birthday));

        $res = $collection->lookupInAnyReplica(
            $id,
            [
                LookupGetFullSpec::build(),
            ],
            LookupInAnyReplicaOptions::build()->withExpiry(true)
        );
        $this->assertEquals($birthday, $res->expiryTime());
    }

    public function testSubdocumentLookupAllReplicas()
    {
        $this->skipIfUnsupported($this->version()->supportsSubdocReadReplica());
        $this->skipIfReplicasAreNotConfigured();

        $id = $this->uniqueId();
        $collection = $this->defaultCollection();
        $res = $collection->upsert($id, ["answer" => 42]);
        $cas = $res->cas();
        $this->assertNotNull($cas);
        $results = $collection->lookupInAllReplicas(
            $id,
            [
                LookupGetSpec::build("answer")
            ],
        );
        $this->assertGreaterThanOrEqual(1, count($results));
        $seenActiveVersion = false;
        foreach ($results as $res) {
            $this->assertEquals(42, $res->contentByPath("answer"));
            if (!$res->isReplica()) {
                $seenActiveVersion = true;
            }
        }
        $this->assertTrue($seenActiveVersion);
    }
}
