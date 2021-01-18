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
use Couchbase\Datastructures\CouchbaseQueue;

class CouchbaseQueueTest extends CouchbaseTestCase
{
    /**
     * @covers CouchbaseQueue::count
     * @covers CouchbaseQueue::empty
     * @return void
     */
    public function testNewQueueEmpty()
    {
        $set = new CouchbaseQueue($this->uniqueId('new_set'), $this->collection);
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
        $set = new CouchbaseQueue($this->uniqueId('empty_set'), $this->collection);
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
        $id = $this->uniqueId('set_remove');
        $this->collection->upsert($id, ["foo", 42, "bar"]);

        $set = new CouchbaseQueue($id, $this->collection);
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
        $id = $this->uniqueId('set_remove');
        $set = new CouchbaseQueue($id, $this->collection);

        $this->assertNull($set->pop());

        $this->collection->upsert($id, ["foo"]);
        $this->assertEquals("foo", $set->pop());
        $this->assertNull($set->pop());
    }

    /**
     * @covers CouchbaseQueue::push
     * @return void
     */
    public function testPushCreatesQueue()
    {
        $id = $this->uniqueId('queue_push_create');
        $set = new CouchbaseQueue($id, $this->collection);
        $set->push("foo");
        $set->push("bar");
        $set->push("baz");

        $res = $this->collection->get($id);
        $this->assertEquals(["baz", "bar", "foo"], $res->content());
    }

    /**
     * @covers CouchbaseQueue::clear
     * @return void
     */
    public function testClearRemovesDocument()
    {
        $id = $this->uniqueId('clear');
        $set = new CouchbaseQueue($id, $this->collection);
        $set->push("foo");
        $set->clear();

        $this->assertFalse($this->collection->exists($id)->exists());

        $this->expectException(DocumentNotFoundException::class);
        $this->collection->get($id);
    }
}
