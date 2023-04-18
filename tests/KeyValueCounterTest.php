<?php

use Couchbase\DecrementOptions;
use Couchbase\DurabilityLevel;
use Couchbase\Exception\DocumentNotFoundException;
use Couchbase\IncrementOptions;
use Couchbase\RawJsonTranscoder;
use Couchbase\UpsertOptions;
use Helpers\CouchbaseTestCaseProtostellar;

include_once __DIR__ . "/Helpers/CouchbaseTestCaseProtostellar.php";

class KeyValueCounterTest extends CouchbaseTestCaseProtostellar
{
    public function testIncrementThrowsExceptionIfDocumentDoesNotExist()
    {
        $collection = $this->defaultCollection();
        $this->expectException(DocumentNotFoundException::class);
        $collection->binary()->increment($this->uniqueId());
    }

    public function testIncrementInitializesValueIfRequested()
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

    public function testDecrementInitializesValueIfRequested()
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

    public function testIncrementAllowsToOverrideDeltaValue()
    {
        $collection = $this->defaultCollection();
        $id = $this->uniqueId();

        $collection->upsert($id, "42", UpsertOptions::build()->transcoder(RawJsonTranscoder::getInstance()));

        $res = $collection->binary()->increment($id, IncrementOptions::build()->delta(10));
        $this->assertEquals(52, $res->content());

        $res = $collection->get($id);
        $this->assertEquals(52, $res->content());
    }

    public function testDecrementAllowsToOverrideDeltaValue()
    {
        $collection = $this->defaultCollection();
        $id = $this->uniqueId();

        $collection->upsert($id, "42", UpsertOptions::build()->transcoder(RawJsonTranscoder::getInstance()));

        $res = $collection->binary()->decrement($id, DecrementOptions::build()->delta(10));
        $this->assertEquals(32, $res->content());

        $res = $collection->get($id);
        $this->assertEquals(32, $res->content());
    }

    public function testIncrementDurabilityMajority()
    {
        $key = $this->uniqueId("increment-durability-majority");
        $collection = $this->defaultCollection();
        $opts = IncrementOptions::build()->durabilityLevel(DurabilityLevel::MAJORITY)->initial(42);
        $res = $collection->binary()->increment($key, $opts);
        $this->assertNotNull($res->cas());
    }

    public function testIncrementDurabilityMajorityAndPersist()
    {
        $key = $this->uniqueId("increment-durability-majority-and-persist");
        $collection = $this->defaultCollection();
        $opts = IncrementOptions::build()->durabilityLevel(DurabilityLevel::MAJORITY_AND_PERSIST_TO_ACTIVE, 5)->initial(42);
        $res = $collection->binary()->increment($key, $opts);
        $this->assertNotNull($res->cas());
    }

    public function testIncrementDurabilityPersistToMajority()
    {
        $key = $this->uniqueId("increment-durability-persist-majority");
        $collection = $this->defaultCollection();
        $opts = IncrementOptions::build()->durabilityLevel(DurabilityLevel::PERSIST_TO_MAJORITY)->initial(42);
        $res = $collection->binary()->increment($key, $opts);
        $this->assertNotNull($res->cas());
    }
}
