<?php

use Helpers\CouchbaseTestCaseProtostellar;

include_once __DIR__ . "/Helpers/CouchbaseTestCaseProtostellar.php";

class KeyValueExistsTest extends CouchbaseTestCaseProtostellar
{
    public function testExistsReturnsFalseForMissingDocuments()
    {
        $collection = $this->defaultCollection();
        $res = $collection->exists($this->uniqueId("foo"));
        $this->assertFalse($res->exists(), "expected document to not exist");
    }

    public function testGetReturnsCorrectCas()
    {
        $id = $this->uniqueId();
        $collection = $this->defaultCollection();
        $res = $collection->upsert($id, ["answer" => 42]);
        $cas = $res->cas();
        $this->assertNotNull($cas);
        $res = $collection->exists($id);
        $this->assertTrue($res->exists(), "expected document to exist");
        $this->assertEquals($cas, $res->cas());
    }
}
