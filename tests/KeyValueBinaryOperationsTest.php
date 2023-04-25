<?php

use Couchbase\AppendOptions;
use Couchbase\DurabilityLevel;
use Couchbase\Exception\DocumentNotFoundException;
use Couchbase\GetOptions;
use Couchbase\PrependOptions;
use Couchbase\RawBinaryTranscoder;
use Couchbase\UpsertOptions;
use Helpers\CouchbaseTestCaseProtostellar;

include_once __DIR__ . "/Helpers/CouchbaseTestCaseProtostellar.php";


class KeyValueBinaryOperationsTest extends CouchbaseTestCaseProtostellar
{
    public function testAppendAddsBytesToTheEndOfTheDocument()
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

    public function testPrependAddsBytesToTheBeginningOfTheDocument()
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
        $key = $this->uniqueId("append-durability-majority");
        $collection = $this->defaultCollection();
        $collection->upsert($key, "foo", UpsertOptions::build()->transcoder(RawBinaryTranscoder::getInstance()));
        $opts = AppendOptions::build()->durabilityLevel(DurabilityLevel::MAJORITY);
        $res = $collection->binary()->append($key, "bar", $opts);
        $this->assertNotNull($res->cas());
    }

    public function testAppendDurabilityMajorityAndPersist()
    {
        $key = $this->uniqueId("append-durability-majority-and-persist");
        $collection = $this->defaultCollection();
        $collection->upsert($key, "foo", UpsertOptions::build()->transcoder(RawBinaryTranscoder::getInstance()));
        $opts = AppendOptions::build()->durabilityLevel(DurabilityLevel::MAJORITY_AND_PERSIST_TO_ACTIVE, 5);
        $res = $collection->binary()->append($key, "bar", $opts);
        $this->assertNotNull($res->cas());
    }

    public function testAppendDurabilityPersistToMajority()
    {
        $key = $this->uniqueId("append-durability-persist-majority");
        $collection = $this->defaultCollection();
        $collection->upsert($key, "foo", UpsertOptions::build()->transcoder(RawBinaryTranscoder::getInstance()));
        $opts = AppendOptions::build()->durabilityLevel(DurabilityLevel::PERSIST_TO_MAJORITY);
        $res = $collection->binary()->append($key, "bar", $opts);
        $this->assertNotNull($res->cas());
    }

    public function testPrependDurabilityMajority()
    {
        $key = $this->uniqueId("prepend-durability-majority");
        $collection = $this->defaultCollection();
        $collection->upsert($key, "foo", UpsertOptions::build()->transcoder(RawBinaryTranscoder::getInstance()));
        $opts = PrependOptions::build()->durabilityLevel(DurabilityLevel::MAJORITY);
        $res = $collection->binary()->prepend($key, "bar", $opts);
        $this->assertNotNull($res->cas());
    }

    public function testPrependDurabilityMajorityAndPersist()
    {
        $key = $this->uniqueId("prepend-durability-majority-and-persist");
        $collection = $this->defaultCollection();
        $collection->upsert($key, "foo", UpsertOptions::build()->transcoder(RawBinaryTranscoder::getInstance()));
        $opts = PrependOptions::build()->durabilityLevel(DurabilityLevel::MAJORITY_AND_PERSIST_TO_ACTIVE);
        $res = $collection->binary()->prepend($key, "bar", $opts);
        $this->assertNotNull($res->cas());
    }

    public function testPrependDurabilityPersistToMajority()
    {
        $key = $this->uniqueId("prepend-durability-persist-majority");
        $collection = $this->defaultCollection();
        $collection->upsert($key, "foo", UpsertOptions::build()->transcoder(RawBinaryTranscoder::getInstance()));
        $opts = PrependOptions::build()->durabilityLevel(DurabilityLevel::PERSIST_TO_MAJORITY);
        $res = $collection->binary()->prepend($key, "bar", $opts);
        $this->assertNotNull($res->cas());
    }
}
