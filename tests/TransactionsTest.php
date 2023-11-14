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

use Couchbase\Exception\TransactionException;
use Couchbase\Exception\TransactionFailedException;
use Couchbase\QueryOptions;
use Couchbase\TransactionAttemptContext;
use Couchbase\TransactionQueryOptions;

include_once __DIR__ . '/Helpers/CouchbaseTestCase.php';

class TransactionsTest extends Helpers\CouchbaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->skipIfProtostellar();
    }

    public function testSimpleTransaction()
    {
        $this->skipIfUnsupported($this->version()->supportsTransactions());

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

                $this->wrapException(
                    function () use ($idToRemove, $collection, $attempt) {
                        $attempt->get($collection, $idToRemove);
                    },
                    Couchbase\Exception\DocumentNotFoundException::class
                );
            }
        );

        $res = $collection->get($idToInsert);
        $this->assertEquals(["foo" => "baz"], $res->content());

        $res = $collection->get($idToReplace);
        $this->assertEquals(["foo" => "baz"], $res->content());

        $this->wrapException(
            function () use ($idToRemove, $collection) {
                $collection->get($idToRemove);
            },
            Couchbase\Exception\DocumentNotFoundException::class
        );
    }

    public function testWorksWithQuery()
    {
        $this->skipIfCaves();
        $this->skipIfUnsupported($this->version()->supportsTransactionsQueries());

        $prefix = $this->uniqueId();
        $idOne = $prefix . "_1";
        $idTwo = $prefix . "_2";

        $cluster = $this->connectCluster();

        $collection = $cluster->bucket(self::env()->bucketName())->defaultCollection();
        $collection->insert($idOne, ["foo" => "bar"]);

        $cluster->transactions()->run(
            function (TransactionAttemptContext $attempt) use ($idTwo, $idOne, $collection) {
                $doc = $attempt->insert($collection, $idTwo, ["foo" => "baz"]);

                $collectionQualifier = "`{$collection->bucketName()}`.`{$collection->scopeName()}`.`{$collection->name()}`";

                $res = $attempt->query(
                    "SELECT foo FROM $collectionQualifier WHERE META().id IN \$ids ORDER BY META().id ASC",
                    TransactionQueryOptions::build()->namedParameters(
                        [
                            'ids' => [$idOne, $idTwo],
                        ]
                    )
                );

                $this->assertCount(2, $res->rows());

                $attempt->replace($doc, ["foo" => "bag"]);

                $doc = $attempt->get($collection, $idOne);
                $attempt->replace($doc, ["foo" => "bad"]);
            }
        );

        $res = $collection->get($idOne);
        $this->assertEquals(["foo" => "bad"], $res->content());

        $res = $collection->get($idTwo);
        $this->assertEquals(["foo" => "bag"], $res->content());
    }

    public function testFailsWithApplicationErrors()
    {
        $this->skipIfUnsupported($this->version()->supportsTransactions());

        $idToInsert = $this->uniqueId();
        $idToReplace = $this->uniqueId();
        $idToRemove = $this->uniqueId();

        $cluster = $this->connectCluster();

        $collection = $cluster->bucket(self::env()->bucketName())->defaultCollection();
        $collection->insert($idToReplace, ["foo" => "bar"]);
        $collection->insert($idToRemove, ["foo" => "bar"]);

        $numberOfAttempts = 0;
        /** @var TransactionException $ex */
        $ex = $this->wrapException(
            function () use ($collection, $idToInsert, $idToReplace, $idToRemove, &$numberOfAttempts, $cluster) {
                $cluster->transactions()->run(
                    function (TransactionAttemptContext $attempt) use (
                        &$numberOfAttempts,
                        $idToRemove,
                        $idToReplace,
                        $idToInsert,
                        $collection
                    ) {
                        $numberOfAttempts++;

                        $attempt->insert($collection, $idToInsert, ["foo" => "baz"]);

                        $doc = $attempt->get($collection, $idToReplace);
                        $attempt->replace($doc, ["foo" => "baz"]);

                        $doc = $attempt->get($collection, $idToRemove);
                        $attempt->remove($doc);

                        throw new Exception("application failure");
                    }
                );
            },
            TransactionFailedException::class,
            null,
            "/Exception caught during execution of transaction/"
        );
        $this->assertErrorType(Exception::class, $ex->getPrevious());
        $this->assertErrorMessage("/application failure/", $ex->getPrevious());

        $this->assertEquals(1, $numberOfAttempts);

        $this->wrapException(
            function () use ($idToInsert, $collection) {
                $collection->get($idToInsert);
            },
            Couchbase\Exception\DocumentNotFoundException::class
        );

        $res = $collection->get($idToReplace);
        $this->assertEquals(["foo" => "bar"], $res->content());

        $res = $collection->get($idToRemove);
        $this->assertEquals(["foo" => "bar"], $res->content());
    }

    public function testCommitWithQuery()
    {
        $this->skipIfCaves();
        $this->skipIfUnsupported($this->version()->supportsTransactionsQueries());

        $idToInsert = $this->uniqueId();
        $idToReplace = $this->uniqueId();
        $idToRemove = $this->uniqueId();

        $cluster = $this->connectCluster();

        $collection = $cluster->bucket(self::env()->bucketName())->defaultCollection();
        $collection->insert($idToReplace, ["foo" => "bar"]);
        $collection->insert($idToRemove, ["foo" => "bar"]);

        $cluster->transactions()->run(
            function (TransactionAttemptContext $attempt) use ($idToRemove, $idToReplace, $idToInsert, $collection) {
                $collectionQualifier = "`{$collection->bucketName()}`.`{$collection->scopeName()}`.`{$collection->name()}`";

                $attempt->query(
                    "INSERT INTO $collectionQualifier VALUES (\$1, \$2)",
                    TransactionQueryOptions::build()->positionalParameters([$idToInsert, ["foo" => "baz"]])
                );

                $attempt->query(
                    "UPDATE $collectionQualifier SET foo=\"baz\" WHERE META().id = \$1",
                    TransactionQueryOptions::build()->positionalParameters([$idToReplace])
                );

                $attempt->query(
                    "DELETE FROM $collectionQualifier WHERE META().id = \$1",
                    TransactionQueryOptions::build()->positionalParameters([$idToRemove])
                );

                // check Read-Your-Own-Write
                $res = $attempt->get($collection, $idToInsert);
                $this->assertEquals(["foo" => "baz"], $res->content());

                $res = $attempt->get($collection, $idToReplace);
                $this->assertEquals(["foo" => "baz"], $res->content());

                $this->wrapException(
                    function () use ($idToRemove, $collection, $attempt) {
                        $attempt->get($collection, $idToRemove);
                    },
                    Couchbase\Exception\DocumentNotFoundException::class
                );
            }
        );

        $res = $collection->get($idToInsert);
        $this->assertEquals(["foo" => "baz"], $res->content());

        $res = $collection->get($idToReplace);
        $this->assertEquals(["foo" => "baz"], $res->content());

        $this->wrapException(
            function () use ($idToRemove, $collection) {
                $collection->get($idToRemove);
            },
            Couchbase\Exception\DocumentNotFoundException::class
        );
    }

    public function testRollbackAfterQuery()
    {
        $this->skipIfCaves();
        $this->skipIfUnsupported($this->version()->supportsTransactionsQueries());

        $idToInsert = $this->uniqueId();
        $idToReplace = $this->uniqueId();
        $idToRemove = $this->uniqueId();

        $cluster = $this->connectCluster();

        $collection = $cluster->bucket(self::env()->bucketName())->defaultCollection();
        $collection->insert($idToReplace, ["foo" => "bar"]);
        $collection->insert($idToRemove, ["foo" => "bar"]);

        $numberOfAttempts = 0;
        /** @var TransactionFailedException $ex */
        $ex = $this->wrapException(
            function () use (&$numberOfAttempts, $collection, $idToInsert, $idToReplace, $idToRemove, $cluster) {
                $cluster->transactions()->run(
                    function (TransactionAttemptContext $attempt) use (
                        &$numberOfAttempts,
                        $idToRemove,
                        $idToReplace,
                        $idToInsert,
                        $collection
                    ) {
                        $numberOfAttempts++;

                        $collectionQualifier = "`{$collection->bucketName()}`.`{$collection->scopeName()}`.`{$collection->name()}`";

                        $attempt->query(
                            "INSERT INTO $collectionQualifier VALUES (\$1, \$2)",
                            TransactionQueryOptions::build()->positionalParameters([$idToInsert, ["foo" => "baz"]])
                        );

                        $attempt->query(
                            "UPDATE $collectionQualifier SET foo=\"baz\" WHERE META().id = \$1",
                            TransactionQueryOptions::build()->positionalParameters([$idToReplace])
                        );

                        $attempt->query(
                            "DELETE FROM $collectionQualifier WHERE META().id = \$1",
                            TransactionQueryOptions::build()->positionalParameters([$idToRemove])
                        );

                        throw new Exception("application failure");
                    }
                );
            },
            TransactionFailedException::class,
            null,
            "/Exception caught during execution of transaction/"
        );
        $this->assertErrorType(Exception::class, $ex->getPrevious());
        $this->assertErrorMessage("/application failure/", $ex->getPrevious());

        $this->assertEquals(1, $numberOfAttempts);

        $this->wrapException(
            function () use ($idToInsert, $collection) {
                $collection->get($idToInsert);
            },
            Couchbase\Exception\DocumentNotFoundException::class
        );

        $res = $collection->get($idToReplace);
        $this->assertEquals(["foo" => "bar"], $res->content());

        $res = $collection->get($idToRemove);
        $this->assertEquals(["foo" => "bar"], $res->content());
    }
}
