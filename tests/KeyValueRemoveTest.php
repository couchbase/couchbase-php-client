<?php

use Couchbase\DurabilityLevel;
use Couchbase\Exception\CasMismatchException;
use Couchbase\Exception\DocumentNotFoundException;
use Couchbase\RemoveOptions;
use Helpers\CouchbaseTestCaseProtostellar;

include_once __DIR__ . "/Helpers/CouchbaseTestCaseProtostellar.php";

class KeyValueRemoveTest extends CouchbaseTestCaseProtostellar
{
    public function testRemoveThrowsCasMismatchForWrongCas()
    {
        $collection = $this->defaultCollection();
        $id = $this->uniqueId();

        $collection->upsert($id, ["answer" => 42]);

        $this->expectException(CasMismatchException::class);
        $collection->remove($id, RemoveOptions::build()->cas("6543653463"));
    }

    public function testRemoveThrowsDocumentNotFoundForUnknownId()
    {
        $collection = $this->defaultCollection();

        $this->expectException(DocumentNotFoundException::class);
        $collection->remove($this->uniqueId());
    }

    public function testRemoveChecksCas()
    {
        $collection = $this->defaultCollection();
        $id = $this->uniqueId();

        $res = $collection->upsert($id, ["answer" => 42]);
        $originalCas = $res->cas();
        $this->assertNotNull($originalCas);

        $res = $collection->remove($id, RemoveOptions::build()->cas($originalCas));
        $this->assertNotEquals($originalCas, $res->cas());
    }

    public function testRemoveDurabilityMajority()
    {
        $key = $this->uniqueId("remove-durability-majority");
        $collection = $this->defaultCollection();
        $collection->upsert($key, ["answer" => 42]);
        $opts = RemoveOptions::build()->durabilityLevel(DurabilityLevel::MAJORITY);
        $res = $collection->remove($key, $opts);
        $this->assertNotNull($res->cas());
    }

    public function testRemoveDurabilityMajorityAndPersist()
    {
        $key = $this->uniqueId("remove-durability-majority-and-persist");
        $collection = $this->defaultCollection();
        $collection->upsert($key, ["answer" => 42]);
        $opts = RemoveOptions::build()->durabilityLevel(DurabilityLevel::MAJORITY_AND_PERSIST_TO_ACTIVE);
        $res = $collection->remove($key, $opts);
        $this->assertNotNull($res->cas());
    }

    public function testRemoveDurabilityPersistToMajority()
    {
        $key = $this->uniqueId("remove-durability-persist-majority");
        $collection = $this->defaultCollection();
        $collection->upsert($key, ["answer" => 42]);
        $opts = RemoveOptions::build()->durabilityLevel(DurabilityLevel::PERSIST_TO_MAJORITY);
        $res = $collection->remove($key, $opts);
        $this->assertNotNull($res->cas());
    }
}
