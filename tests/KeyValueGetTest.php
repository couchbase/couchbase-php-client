<?php

use Couchbase\Exception\DocumentNotFoundException;
use Couchbase\GetOptions;
use Couchbase\RawJsonTranscoder;
use Couchbase\UpsertOptions;
use Helpers\CouchbaseTestCaseProtostellar;

include_once __DIR__ . "/Helpers/CouchbaseTestCaseProtostellar.php";


class KeyValueGetTest extends CouchbaseTestCaseProtostellar
{
    public function testGetThrowsDocumentNotFoundException()
    {
        $collection = $this->defaultCollection();
        $this->expectException(DocumentNotFoundException::class);
        $collection->get($this->uniqueId("foo"));
    }

    public function testGetReturnsCorrectCas()
    {
        $id = $this->uniqueId();
        $collection = $this->defaultCollection();
        $res = $collection->upsert($id, ["answer" => 42]);
        $cas = $res->cas();
        $this->assertNotNull($cas);
        $res = $collection->get($id);
        $this->assertEquals($cas, $res->cas());
    }

    public function testGetReturnsCorrectValue()
    {
        $id = $this->uniqueId();
        $collection = $this->defaultCollection();
        $res = $collection->upsert($id, ["answer" => 42]);
        $cas = $res->cas();
        $this->assertNotNull($cas);
        $res = $collection->get($id);
        $this->assertEquals(["answer" => 42], $res->content());
    }

    public function testGetWithExpiry()
    {
        $id = $this->uniqueId();
        $collection = $this->defaultCollection();
        $now = (new DateTime())->getTimestamp();
        $opts = (UpsertOptions::build())->expiry(10);
        $res = $collection->upsert($id, ["answer" => 42], $opts);
        $cas = $res->cas();
        $this->assertNotNull($cas);

        $opts = (GetOptions::build())->withExpiry(true);
        $res = $collection->get($id, $opts);
        // Allow a bit of extra time for server edges.
        $this->assertGreaterThan($now + 8, $res->expiryTime()->getTimestamp());
    }

    public function testGetWithProjections()
    {
        $id = $this->uniqueId();
        $collection = $this->defaultCollection();
        $opts = (UpsertOptions::build())->expiry(10);
        $res = $collection->upsert(
            $id,
            [
                "answer" => 42,
                "name" => "james",
                "hobbies" => [
                    "activity" => "biking",
                    "frequency" => "weekly"
                ]
            ],
            $opts
        );
        $cas = $res->cas();
        $this->assertNotNull($cas);

        $opts = (GetOptions::build())->project(["name", "hobbies.activity"]);
        $res = $collection->get($id, $opts);
        $this->assertEquals(
            [
                "name" => "james",
                "hobbies" => [
                    "activity" => "biking"
                ]
            ],
            $res->content()
        );
    }

    public function testGetContentAsReturnsCorrectValue()
    {
        $id = $this->uniqueId();
        $collection = $this->defaultCollection();
        $res = $collection->upsert($id, ["answer" => 42]);
        $cas = $res->cas();
        $this->assertNotNull($cas);
        $res = $collection->get($id);
        $this->assertEquals('{"answer":42}', $res->contentAs(RawJsonTranscoder::getInstance()));
    }

    public function testGetContentAsWithFlagsReturnsCorrectValue()
    {
        $id = $this->uniqueId();
        $collection = $this->defaultCollection();
        $res = $collection->upsert($id, ["answer" => 42]);
        $cas = $res->cas();
        $this->assertNotNull($cas);
        $res = $collection->get($id);
        $this->assertEquals('{"answer":42}', $res->contentAs(RawJsonTranscoder::getInstance(), 33554432));
    }
}
