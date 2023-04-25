<?php

use Couchbase\Exception\DocumentExistsException;
use Couchbase\GetOptions;
use Couchbase\InsertOptions;
use Helpers\CouchbaseTestCaseProtostellar;

include_once __DIR__ . "/Helpers/CouchbaseTestCaseProtostellar.php";

class KeyValueInsertTest extends CouchbaseTestCaseProtostellar
{
    public function testInsertFailsIfDocumentExistsAlready()
    {
        $collection = $this->defaultCollection();
        $id = $this->uniqueId();

        $collection->insert($id, ["answer" => 42]);

        $this->expectException(DocumentExistsException::class);
        $collection->insert($id, ["answer" => "foo"]);
    }

    public function testCanInsertWithExpiry()
    {
        $expiry = 300;

        $options = new InsertOptions();
        $expiryDate = (new \DateTimeImmutable())->modify('+' . $expiry . ' seconds');
        $options->expiry($expiryDate);

        $collection = $this->defaultCollection();
        $id = $this->uniqueId();

        $collection->insert($id, ["answer" => 42], $options);

        $opts = (GetOptions::build())->withExpiry(true);
        $res = $collection->get($id, $opts);
        $this->assertGreaterThan((new DateTime())->getTimestamp(), $res->expiryTime()->getTimestamp());
    }
}
