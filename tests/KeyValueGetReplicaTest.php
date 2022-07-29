<?php

/**
 * Copyright 2014-Present Couchbase, Inc.
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *     http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */

declare(strict_types=1);

use Couchbase\Exception\DocumentIrretrievableException;
use Couchbase\Exception\DocumentNotFoundException;

include_once __DIR__ . "/Helpers/CouchbaseTestCase.php";

class KeyValueGetReplicaTest extends Helpers\CouchbaseTestCase
{
    public function testGetAnyReplicaReturnsCorrectValue()
    {
        $this->skipIfReplicasAreNotConfigured();

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
        $this->skipIfReplicasAreNotConfigured();

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
        $this->skipIfReplicasAreNotConfigured();

        $id = $this->uniqueId();
        $collection = $this->defaultCollection();
        $this->expectException(DocumentNotFoundException::class);
        $results = $collection->getAllReplicas($id);
    }

    public function testGetAnyReplicaThrowsIrretrievableExceptionForMissingId()
    {
        $this->skipIfReplicasAreNotConfigured();

        $id = $this->uniqueId();
        $collection = $this->defaultCollection();
        $this->expectException(DocumentIrretrievableException::class);
        $collection->getAnyReplica($id);
    }
}
