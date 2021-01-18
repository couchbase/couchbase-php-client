<?php

declare(strict_types=1);

/*
 *   Copyright 2020-2021 Couchbase, Inc.
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

namespace Couchbase\Tests\Datastructures;

use Couchbase\DocumentNotFoundException;
use Couchbase\Tests\CouchbaseTestCase;
use Couchbase\Datastructures\CouchbaseMap;

class CouchbaseMapTest extends CouchbaseTestCase
{
    /**
     * @covers CouchbaseMap::count
     * @covers CouchbaseMap::empty
     * @return void
     */
    public function testNewMapEmpty()
    {
        $map = new CouchbaseMap($this->uniqueId('new_map'), $this->collection);
        $this->assertEquals(0, $map->count());
        $this->assertEquals(0, count($map));
        $this->assertTrue($map->empty());
    }

    /**
     * @covers CouchbaseMap::getIterator
     * @return void
     */
    public function testNewMapYieldsNoElements()
    {
        $map = new CouchbaseMap($this->uniqueId('empty_map'), $this->collection);
        $values = [];
        foreach ($map as $key => $value) {
            $values[$key] = $value;
        }
        $this->assertEquals(0, count($values));
    }

    /**
     * @covers CouchbaseMap::offsetGet
     * @covers CouchbaseMap::get
     * @return void
     */
    public function testIndexOperatorReturnsNullWhenOffsetDoesNotExist()
    {
        $map = new CouchbaseMap($this->uniqueId('key_does_not_exist'), $this->collection);
        $this->assertNull($map->get("foo"));
        $this->assertNull($map["bar"]);
    }

    /**
     * @covers CouchbaseMap::offsetSet
     * @covers CouchbaseMap::replaceAt
     * @return void
     */
    public function testSetReplacesEntry()
    {
        $id = $this->uniqueId('set_replace');
        $this->collection->upsert($id, ["foo" => "bar"]);

        $map = new CouchbaseMap($id, $this->collection);
        $map["foo"] = "baz";
        $values = [];
        foreach ($map as $key => $value) {
            $values[$key] = $value;
        }
        $this->assertEquals(["foo" => "baz"], $values);
    }

    /**
     * @covers CouchbaseMap::offsetUnset
     * @covers CouchbaseMap::removeAt
     * @return void
     */
    public function testUnsetRemovesEntry()
    {
        $id = $this->uniqueId('unset_remove');
        $this->collection->upsert($id, ["foo" => "bar", "ans" => 42]);

        $map = new CouchbaseMap($id, $this->collection);
        unset($map["foo"]);
        $values = [];
        foreach ($map as $key => $value) {
            $values[$key] = $value;
        }
        $this->assertEquals(["ans" => 42], $values);
    }

    /**
     * @covers CouchbaseMap::set
     * @return void
     */
    public function testSetCreatesMap()
    {
        $id = $this->uniqueId('set_creates');
        $map = new CouchbaseMap($id, $this->collection);
        $map->set("foo", "bar");

        $res = $this->collection->get($id);
        $this->assertEquals(["foo" => "bar"], $res->content());
    }


    /**
     * @covers CouchbaseMap::clear
     * @return void
     */
    public function testClearRemovesDocument()
    {
        $id = $this->uniqueId('clear');
        $set = new CouchbaseMap($id, $this->collection);
        $set->set("foo", "bar");
        $set->clear();

        $this->assertFalse($this->collection->exists($id)->exists());

        $this->expectException(DocumentNotFoundException::class);
        $this->collection->get($id);
    }
}
