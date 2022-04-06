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

use Couchbase\Datastructures\CouchbaseQueue;
use Couchbase\Exception\DocumentNotFoundException;

include_once __DIR__ . "/Helpers/CouchbaseTestCase.php";

class CouchbaseQueueTest extends Helpers\CouchbaseTestCase
{
    /**
     * @covers CouchbaseQueue::count
     * @covers CouchbaseQueue::empty
     * @return void
     */
    public function testNewQueueEmpty()
    {
        $collection = $this->defaultCollection();
        $set = new CouchbaseQueue($this->uniqueId('new_set'), $collection);
        $this->assertEquals(0, $set->count());
        $this->assertEquals(0, count($set));
        $this->assertTrue($set->empty());
    }

    /**
     * @covers CouchbaseQueue::getIterator
     * @return void
     */
    public function testNewQueueYieldsNoElements()
    {
        $collection = $this->defaultCollection();
        $set = new CouchbaseQueue($this->uniqueId('empty_set'), $collection);
        $values = [];
        foreach ($set as $value) {
            $values[] = $value;
        }
        $this->assertEquals(0, count($values));
    }

    /**
     * @covers CouchbaseQueue::pop
     * @return void
     */
    public function testPopEntry()
    {
        $collection = $this->defaultCollection();
        $id = $this->uniqueId('set_remove');
        $collection->upsert($id, ["foo", 42, "bar"]);

        $set = new CouchbaseQueue($id, $collection);
        $value = $set->pop();
        $this->assertEquals("bar", $value);

        $values = [];
        foreach ($set as $value) {
            $values[] = $value;
        }
        $this->assertEquals(["foo", 42], $values);
    }

    /**
     * @covers CouchbaseQueue::pop
     * @return void
     */
    public function testPopReturnsNullForEmptyQueue()
    {
        $collection = $this->defaultCollection();
        $id = $this->uniqueId('set_remove');
        $set = new CouchbaseQueue($id, $collection);

        $this->assertNull($set->pop());

        $collection->upsert($id, ["foo"]);
        $this->assertEquals("foo", $set->pop());
        $this->assertNull($set->pop());
    }

    /**
     * @covers CouchbaseQueue::push
     * @return void
     */
    public function testPushCreatesQueue()
    {
        $collection = $this->defaultCollection();
        $id = $this->uniqueId('queue_push_create');
        $set = new CouchbaseQueue($id, $collection);
        $set->push("foo");
        $set->push("bar");
        $set->push("baz");

        $res = $collection->get($id);
        $this->assertEquals(["baz", "bar", "foo"], $res->content());
    }

    /**
     * @covers CouchbaseQueue::clear
     * @return void
     */
    public function testClearRemovesDocument()
    {
        $collection = $this->defaultCollection();
        $id = $this->uniqueId('clear');
        $set = new CouchbaseQueue($id, $collection);
        $set->push("foo");
        $set->clear();

        $this->assertFalse($collection->exists($id)->exists());

        $this->expectException(DocumentNotFoundException::class);
        $collection->get($id);
    }
}
