<?php

declare(strict_types=1);

/*
 *   Copyright 2020 Couchbase, Inc.
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
use Couchbase\Datastructures\CouchbaseList;
use OutOfBoundsException;

class CouchbaseListTest extends CouchbaseTestCase
{
    /**
     * @covers CouchbaseList::count
     * @covers CouchbaseList::empty
     * @return void
     */
    public function testNewListEmpty()
    {
        $list = new CouchbaseList($this->uniqueId('new_list'), $this->collection);
        $this->assertEquals(0, $list->count());
        $this->assertEquals(0, count($list));
        $this->assertTrue($list->empty());
    }

    /**
     * @covers CouchbaseList::getIterator
     * @return void
     */
    public function testNewListYieldsNoElements()
    {
        $list = new CouchbaseList($this->uniqueId('empty_list'), $this->collection);
        $values = [];
        foreach ($list as $value) {
            $values[] = $value;
        }
        $this->assertEquals(0, count($values));
    }

    /**
     * @covers CouchbaseList::offsetGet
     * @covers CouchbaseList::at
     * @return void
     */
    public function testIndexOperatorReturnsNullWhenOffsetDoesNotExist()
    {
        $list = new CouchbaseList($this->uniqueId('offset_does_not_exist'), $this->collection);
        $this->assertNull($list[0]);
        $this->assertNull($list[42]);
    }

    /**
     * @covers CouchbaseList::offsetSet
     * @covers CouchbaseList::replaceAt
     * @return void
     */
    public function testSetReplacesEntry()
    {
        $id = $this->uniqueId('set_replace');
        $this->collection->upsert($id, [0, 1, 2]);

        $list = new CouchbaseList($id, $this->collection);
        $list[1] = "foo";
        $values = [];
        foreach ($list as $value) {
            $values[] = $value;
        }
        $this->assertEquals([0, "foo", 2], $values);
    }

    /**
     * @covers CouchbaseList::offsetUnset
     * @covers CouchbaseList::removeAt
     * @return void
     */
    public function testUnsetRemovesEntry()
    {
        $id = $this->uniqueId('unset_remove');
        $this->collection->upsert($id, [0, 1, 2]);

        $list = new CouchbaseList($id, $this->collection);
        unset($list[1]);
        $values = [];
        foreach ($list as $value) {
            $values[] = $value;
        }
        $this->assertEquals([0, 2], $values);
    }

    /**
     * @covers CouchbaseList::insertAt
     * @return void
     */
    public function testInsertExpandsArray()
    {
        $id = $this->uniqueId('insert_expand');
        $this->collection->upsert($id, [0, 1, 2]);

        $list = new CouchbaseList($id, $this->collection);
        $list->insertAt(1, "foo");
        $values = [];
        foreach ($list as $value) {
            $values[] = $value;
        }
        $this->assertEquals([0, "foo", 1, 2], $values);
    }

    /**
     * @covers CouchbaseList::insertAt
     * @return void
     */
    public function testInsertChecksBounds()
    {
        $id = $this->uniqueId('insert_expand');
        $this->collection->upsert($id, [0, 1, 2]);

        $list = new CouchbaseList($id, $this->collection);
        $this->expectException(OutOfBoundsException::class);
        $list->insertAt(42, "foo");
    }

    /**
     * @covers CouchbaseList::append
     * @return void
     */
    public function testAppendCreatesList()
    {
        $id = $this->uniqueId('append');
        $list = new CouchbaseList($id, $this->collection);
        $list->append("foo", "bar");

        $res = $this->collection->get($id);
        $this->assertEquals(["foo", "bar"], $res->content());
    }

    /**
     * @covers CouchbaseList::prepend
     * @return void
     */
    public function testPrependCreatesList()
    {
        $id = $this->uniqueId('prepend');
        $list = new CouchbaseList($id, $this->collection);
        $list->prepend("foo", "bar");

        $res = $this->collection->get($id);
        $this->assertEquals(["foo", "bar"], $res->content());
    }

    /**
     * @covers CouchbaseList::clear
     * @return void
     */
    public function testClearRemovesDocument()
    {
        $id = $this->uniqueId('prepend');
        $list = new CouchbaseList($id, $this->collection);
        $list->append("foo");
        $list->clear();

        $this->assertFalse($this->collection->exists($id)->exists());

        $this->expectException(DocumentNotFoundException::class);
        $this->collection->get($id);
    }
}
