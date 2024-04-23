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

use Couchbase\Exception\DocumentNotFoundException;

include_once __DIR__ . "/Helpers/CouchbaseTestCase.php";

class KeyValueMultiOperationsTest extends Helpers\CouchbaseTestCase
{
    public function setUp(): void
    {
        parent::setUp();
        $this->skipIfProtostellar();
    }

    public function testGetMulti()
    {
        $collection = $this->defaultCollection();

        $idFoo = $this->uniqueId("foo");
        $idBar = $this->uniqueId("bar");
        $idMiss = $this->uniqueId("miss");

        $resFoo = $collection->upsert($idFoo, ["value" => "foo"]);
        $resBar = $collection->upsert($idBar, ["value" => "bar"]);

        $res = $collection->getMulti([$idFoo, $idMiss, $idBar]);
        $this->assertCount(3, $res);

        $this->assertEquals($idFoo, $res[0]->id());
        $this->assertNull($res[0]->error());
        $this->assertEquals(["value" => "foo"], $res[0]->content());
        $this->assertEquals($resFoo->cas(), $res[0]->cas());

        $this->assertEquals($idMiss, $res[1]->id());
        $this->assertNotNull($res[1]->error());
        $this->assertInstanceOf(DocumentNotFoundException::class, $res[1]->error());

        $this->assertEquals($idBar, $res[2]->id());
        $this->assertNull($res[2]->error());
        $this->assertEquals(["value" => "bar"], $res[2]->content());
        $this->assertEquals($resBar->cas(), $res[2]->cas());
    }

    public function testRemoveMulti()
    {
        $collection = $this->defaultCollection();

        $idFoo = $this->uniqueId("foo");
        $idBar = $this->uniqueId("bar");
        $idMiss = $this->uniqueId("miss");

        $resFoo = $collection->upsert($idFoo, ["value" => "foo"]);
        $resBar = $collection->upsert($idBar, ["value" => "bar"]);

        $this->fixCavesTimeResolutionOnWindows();
        $res = $collection->removeMulti([$idFoo, $idMiss, $idBar]);
        $this->assertCount(3, $res);

        $this->assertEquals($idFoo, $res[0]->id());
        $this->assertNull($res[0]->error());
        $this->assertNotEquals($resFoo->cas(), $res[0]->cas());

        $this->assertEquals($idMiss, $res[1]->id());
        $this->assertNotNull($res[1]->error());
        $this->assertInstanceOf(DocumentNotFoundException::class, $res[1]->error());

        $this->assertEquals($idBar, $res[2]->id());
        $this->assertNull($res[2]->error());
        $this->assertNotEquals($resBar->cas(), $res[2]->cas());
    }

    public function testUpsertMulti()
    {
        $collection = $this->defaultCollection();

        $idFoo = $this->uniqueId("foo");
        $idBar = $this->uniqueId("bar");

        $results = $collection->upsertMulti(
            [
                [$idFoo, ["value" => "foo"]],
                [$idBar, ["value" => "bar"]],
            ]
        );
        $this->assertCount(2, $results);
        $this->assertNull($results[0]->error());
        $this->assertNull($results[1]->error());

        $res = $collection->get($idFoo);
        $this->assertEquals($results[0]->cas(), $res->cas());
        $this->assertEquals(["value" => "foo"], $res->content());

        $res = $collection->get($idBar);
        $this->assertEquals($results[1]->cas(), $res->cas());
        $this->assertEquals(["value" => "bar"], $res->content());
    }
}
