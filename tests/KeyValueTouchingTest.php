<?php

use Couchbase\GetOptions;
use Helpers\CouchbaseTestCaseProtostellar;

include_once __DIR__ . "/Helpers/CouchbaseTestCaseProtostellar.php";

class KeyValueTouchingTest extends CouchbaseTestCaseProtostellar
{
    public function testGetAndTouchChangesExpiry()
    {
        $id = $this->uniqueId("foo");
        $collection = $this->defaultCollection();

        $res = $collection->upsert($id, ["foo" => "bar"]);
        $originalCas = $res->cas();
        $this->assertNotNull($originalCas);

        $res = $collection->get($id, GetOptions::build()->withExpiry(true));
        $this->assertNull($res->expiryTime());

        $res = $collection->getAndTouch($id, 5);
        $gatCas = $res->cas();
        $this->assertNotNull($gatCas);
        $this->assertNotEquals($originalCas, $gatCas);
        $this->assertEquals(["foo" => "bar"], $res->content());

        $res = $collection->get($id, GetOptions::build()->withExpiry(true));
        $this->assertGreaterThan(
            date_add(date_create(), date_interval_create_from_date_string('3 seconds')),
            $res->expiryTime()
        );

        $birthday = DateTime::createFromFormat(DateTimeInterface::ISO8601, "2027-04-07T00:00:00UTC");
        $res = $collection->touch($id, $birthday);
        $this->assertNotNull($res->cas());

        $res = $collection->get($id, GetOptions::build()->withExpiry(true));
        $this->assertEquals($birthday, $res->expiryTime());
        $this->assertNotEquals($gatCas, $res->cas());
        $this->assertNotEquals($originalCas, $res->cas());
    }
}
