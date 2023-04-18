<?php

use Couchbase\Exception\PathNotFoundException;
use Couchbase\LookupExistsSpec;
use Couchbase\LookupGetFullSpec;
use Couchbase\LookupGetSpec;
use Couchbase\LookupInOptions;
use Couchbase\UpsertOptions;
use Helpers\CouchbaseTestCaseProtostellar;

include_once __DIR__ . "/Helpers/CouchbaseTestCaseProtostellar.php";

class KeyValueLookupInTest extends CouchbaseTestCaseProtostellar
{
    public function testSubdocumentLookupCanFetchExpiry()
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

    public function testSubdocumentLookupExists()
    {
        $id = $this->uniqueId("foo");
        $collection = $this->defaultCollection();

        $collection->upsert($id, ["foo" => "bar"]);

        $res = $collection->lookupIn(
            $id,
            [
                LookupExistsSpec::build("foo"),
                LookupExistsSpec::build("doesnotexist"),
            ]
        );
        $this->assertTrue($res->exists(0));
        $this->assertFalse($res->exists(1));
    }
}
