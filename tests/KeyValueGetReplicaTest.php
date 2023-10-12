<?php

use Couchbase\Exception\DocumentIrretrievableException;
use Couchbase\Exception\DocumentNotFoundException;
use Helpers\CouchbaseTestCaseProtostellar;

include_once __DIR__ . "/Helpers/CouchbaseTestCaseProtostellar.php";

class KeyValueGetReplicaTest extends CouchbaseTestCaseProtostellar
{
    public function testGetAnyReplicaReturnsCorrectValue()
    {
        $id = $this->uniqueId();
        $collection = $this->defaultCollection();
        $res = $collection->upsert($id, ["answer" => 42]);
        $cas = $res->cas();
        $this->assertNotNull($cas);
        $res = $collection->getAnyReplica($id);
        $this->assertEquals(["answer" => 42], $res->content());
    }

    public function testGetAllReplicasReturnCorrectValue()
    {
        $id = $this->uniqueId();
        $collection = $this->defaultCollection();
        $res = $collection->upsert($id, ["answer" => 42]);
        $cas = $res->cas();
        $this->assertNotNull($cas);
        $results = $collection->getAllReplicas($id);
        $this->assertGreaterThanOrEqual(1, count($results));
        $seenActiveVersion = false;
        foreach ($results as $res) {
            $this->assertEquals(["answer" => 42], $res->content());
            if (!$res->isReplica()) {
                $seenActiveVersion = true;
            }
        }
        $this->assertTrue($seenActiveVersion);
    }

    public function testGetAllReplicasThrowsDocumentNotFoundExceptionForMissingId()
    {
        $id = $this->uniqueId();
        $collection = $this->defaultCollection();
        $this->expectException(DocumentNotFoundException::class);
        $results = $collection->getAllReplicas($id);
    }

    public function testGetAnyReplicaThrowsIrretrievableExceptionForMissingId()
    {
        $id = $this->uniqueId();
        $collection = $this->defaultCollection();
        $this->expectException(DocumentIrretrievableException::class);
        $collection->getAnyReplica($id);
    }
}
