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
use Couchbase\Datastructures\CouchbaseSet;

class CouchbaseSetTest extends CouchbaseTestCase
{
    /**
     * @covers CouchbaseSet::count
     * @covers CouchbaseSet::empty
     * @return void
     */
    public function testNewSetEmpty()
    {
        $set = new CouchbaseSet($this->uniqueId('new_set'), $this->collection);
        $this->assertEquals(0, $set->count());
        $this->assertEquals(0, count($set));
        $this->assertTrue($set->empty());
    }

    /**
     * @covers CouchbaseSet::getIterator
     * @return void
     */
    public function testNewSetYieldsNoElements()
    {
        $set = new CouchbaseSet($this->uniqueId('empty_set'), $this->collection);
        $values = [];
        foreach ($set as $value) {
            $values[] = $value;
        }
        $this->assertEquals(0, count($values));
    }

    /**
     * @covers CouchbaseSet::add
     * @return void
     */
    public function testAddDoesNotCreateDuplicates()
    {
        $id = $this->uniqueId('set_add');
        $this->collection->upsert($id, [42, "foo"]);

        $set = new CouchbaseSet($id, $this->collection);
        $set->add("foo");

        $values = [];
        foreach ($set as $value) {
            $values[] = $value;
        }
        $this->assertEquals([42, "foo"], $values);
    }

    /**
     * @covers CouchbaseSet::remove
     * @return void
     */
    public function testRemovesEntry()
    {
        $id = $this->uniqueId('set_remove');
        $this->collection->upsert($id, [42, "foo", "bar"]);

        $set = new CouchbaseSet($id, $this->collection);
        $set->remove("foo");
        foreach ($set as $value) {
            $values[] = $value;
        }
        $this->assertEquals([42, "bar"], $values);
    }

    /**
     * @covers CouchbaseSet::add
     * @return void
     */
    public function testAddCreatesSet()
    {
        $id = $this->uniqueId('set_add_create');
        $set = new CouchbaseSet($id, $this->collection);
        $set->add("foo");

        $res = $this->collection->get($id);
        $this->assertEquals(["foo"], $res->content());
    }

    /**
     * @covers CouchbaseSet::clear
     * @return void
     */
    public function testClearRemovesDocument()
    {
        $id = $this->uniqueId('clear');
        $set = new CouchbaseSet($id, $this->collection);
        $set->add("foo");
        $set->clear();

        $this->assertFalse($this->collection->exists($id)->exists());

        $this->expectException(DocumentNotFoundException::class);
        $this->collection->get($id);
    }
}
