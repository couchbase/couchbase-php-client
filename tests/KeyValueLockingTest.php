<?php
//
//use Helpers\
//
//;
//
//include_once __DIR__ . "/Helpers/CouchbaseTestCaseProtostellar.php";
//
//class KeyValueLockingTest extends CouchbaseTestCaseProtostellar
//{
//    TODO: STG Returns Document Locked when running A get of a locked document
//    public function testPessimisticLockingWorkflow()
//    {
//        $id = $this->uniqueId("foo");
//        $collection = $this->defaultCollection();
//
//        $res = $collection->upsert($id, ["foo" => "bar"]);
//        $originalCas = $res->cas();
//
//        $res = $collection->getAndLock($id, 5);
//        $lockedCas = $res->cas();
//        $this->assertNotEquals($originalCas, $lockedCas);
//        $this->assertEquals(["foo" => "bar"], $res->content());
//        print_r($res->content());
//
//        $res = $collection->get($id);
//        $this->assertNotEquals($originalCas, $res->cas());
//        $this->assertNotEquals($lockedCas, $res->cas());
//
//        $collection->unlock($id, $lockedCas);
//
//        $res = $collection->get($id);
//        $this->assertEquals($lockedCas, $res->cas());
//    }
//}