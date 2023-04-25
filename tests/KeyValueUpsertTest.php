<?php

use Couchbase\DurabilityLevel;
use Couchbase\UpsertOptions;
use Helpers\CouchbaseTestCaseProtostellar;

include_once __DIR__ . "/Helpers/CouchbaseTestCaseProtostellar.php";

class KeyValueUpsertTest extends CouchbaseTestCaseProtostellar
{
    public function testUpsertReturnsCas()
    {
        $collection = $this->defaultCollection();
        $res = $collection->upsert($this->uniqueId("foo"), ["answer" => 42]);
        $this->assertNotNull($res->cas());
    }

    public function testUpsertDurabilityMajority()
    {
        $collection = $this->defaultCollection();
        $opts = UpsertOptions::build()->durabilityLevel(DurabilityLevel::MAJORITY);
        $res = $collection->upsert($this->uniqueId("upsert-durability-majority"), ["answer" => 42], $opts);
        $this->assertNotNull($res->cas());
    }

    public function testUpsertDurabilityMajorityAndPersist()
    {
        $collection = $this->defaultCollection();
        $opts = UpsertOptions::build()->durabilityLevel(DurabilityLevel::MAJORITY_AND_PERSIST_TO_ACTIVE);
        $res = $collection->upsert($this->uniqueId("upsert-durability-majority-and-persist"), ["answer" => 42], $opts);
        $this->assertNotNull($res->cas());
    }

    public function testUpsertDurabilityPersistToMajority()
    {
        $collection = $this->defaultCollection();
        $opts = UpsertOptions::build()->durabilityLevel(DurabilityLevel::PERSIST_TO_MAJORITY);
        $res = $collection->upsert($this->uniqueId("upsert-durability-persist-majority"), ["answer" => 42], $opts);
        $this->assertNotNull($res->cas());
    }
}
