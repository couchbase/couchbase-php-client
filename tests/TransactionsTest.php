<?php

/**
 * Copyright 2014-Present Couchbase, Inc.
 *
 * Licensed under the Apache License, Version 2.0 (the 'License');
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *     http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an 'AS IS' BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */

declare(strict_types=1);

use Couchbase\TransactionAttemptContext;

include_once __DIR__ . '/Helpers/CouchbaseTestCase.php';

class TransactionsTest extends Helpers\CouchbaseTestCase
{
    public function testSimpleTransaction()
    {
        $idToInsert = $this->uniqueId();
        $idToReplace = $this->uniqueId();
        $idToRemove = $this->uniqueId();

        $cluster = $this->connectCluster();

        $collection = $cluster->bucket(self::env()->bucketName())->defaultCollection();
        $collection->insert($idToReplace, ["foo" => "bar"]);
        $collection->insert($idToRemove, ["foo" => "bar"]);

        $cluster->transactions()->run(
            function (TransactionAttemptContext $attempt) use ($idToRemove, $idToReplace, $idToInsert, $collection) {
                $attempt->insert($collection, $idToInsert, ["foo" => "baz"]);

                $doc = $attempt->get($collection, $idToReplace);
                $attempt->replace($doc, ["foo" => "baz"]);

                $doc = $attempt->get($collection, $idToRemove);
                $attempt->remove($doc);

                // check Read-Your-Own-Write
                $res = $attempt->get($collection, $idToInsert);
                $this->assertEquals(["foo" => "baz"], $res->content());

                $res = $attempt->get($collection, $idToReplace);
                $this->assertEquals(["foo" => "baz"], $res->content());

                $this->wrapException(function () use ($idToRemove, $collection, $attempt) {
                    $attempt->get($collection, $idToRemove);
                }, Couchbase\Exception\DocumentNotFoundException::class);
            }
        );

        $res = $collection->get($idToInsert);
        $this->assertEquals(["foo" => "baz"], $res->content());

        $res = $collection->get($idToReplace);
        $this->assertEquals(["foo" => "baz"], $res->content());

        $this->wrapException(function () use ($idToRemove, $collection) {
            $collection->get($idToRemove);
        }, Couchbase\Exception\DocumentNotFoundException::class);
    }
}
