<?php

use Couchbase\DurabilityLevel;
use Couchbase\Exception\DocumentNotFoundException;
use Couchbase\ReplaceOptions;
use Helpers\CouchbaseTestCaseProtostellar;

include_once __DIR__ . "/Helpers/CouchbaseTestCaseProtostellar.php";

class KeyValueReplaceTest extends CouchbaseTestCaseProtostellar
{
    public function testReplaceFailsIfDocumentDoesNotExist()
    {
        $collection = $this->defaultCollection();
        $id = $this->uniqueId();

        $this->expectException(DocumentNotFoundException::class);
        $collection->replace($id, ["answer" => "foo"]);
    }

    public function testReplaceCompletesIfDocumentExists()
    {
        $collection = $this->defaultCollection();
        $id = $this->uniqueId();

        $res = $collection->insert($id, ["answer" => 42]);
        $originalCas = $res->cas();

        $res = $collection->replace($id, ["answer" => "foo"]);
        $replacedCas = $res->cas();
        $this->assertNotEquals($originalCas, $replacedCas);

        $res = $collection->get($id);
        $this->assertEquals($replacedCas, $res->cas());
        $this->assertEquals(["answer" => "foo"], $res->content());
    }

    public function testReplaceDurabilityMajority()
    {
        $key = $this->uniqueId("replace-durability-majority");
        $collection = $this->defaultCollection();
        $collection->upsert($key, ["answer" => 42]);
        $opts = ReplaceOptions::build()->durabilityLevel(DurabilityLevel::MAJORITY);
        $res = $collection->replace($key, ["answer" => 42], $opts);
        $this->assertNotNull($res->cas());
    }

    public function testReplaceDurabilityMajorityAndPersist()
    {
        $key = $this->uniqueId("replace-durability-majority-and-persist");
        $collection = $this->defaultCollection();
        $collection->upsert($key, ["answer" => 42]);
        $opts = ReplaceOptions::build()->durabilityLevel(DurabilityLevel::MAJORITY_AND_PERSIST_TO_ACTIVE);
        $res = $collection->replace($key, ["answer" => 45], $opts);
        $this->assertNotNull($res->cas());
    }

    public function testReplaceDurabilityPersistToMajority()
    {
        $key = $this->uniqueId("replace-durability-persist-majority");
        $collection = $this->defaultCollection();
        $collection->upsert($key, ["answer" => 42]);
        $opts = ReplaceOptions::build()->durabilityLevel(DurabilityLevel::PERSIST_TO_MAJORITY);
        $res = $collection->replace($key, ["answer" => 42], $opts);
        $this->assertNotNull($res->cas());
    }
}
