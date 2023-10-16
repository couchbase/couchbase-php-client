<?php

use Couchbase\Exception\IndexExistsException;
use Couchbase\Exception\IndexNotFoundException;
use Couchbase\Management\SearchIndex;
use Couchbase\Protostellar\Management\SearchIndexManager;

include_once __DIR__ . "/Helpers/CouchbaseTestCaseProtostellar.php";


class SearchIndexManagerTest extends Helpers\CouchbaseTestCaseProtostellar
{
    private SearchIndexManager $manager;
    private string $indexName;

    public function setUp(): void
    {
        parent::setUp();

        $this->manager = $this->connectCluster()->searchIndexes();
        $this->indexName = $this->uniqueId("idx");
    }

    public function tearDown(): void
    {
        parent::tearDown();

        try {
            $this->manager->dropIndex($this->indexName);
        } catch (IndexNotFoundException $exception) {
        }
    }


    public function testUpsertAndGetIndex()
    {

        $this->indexName = $this->uniqueId("idx");
        $bucketName = $this->openBucket()->name();
        $index = SearchIndex::build($this->indexName, $bucketName);
        $this->manager->upsertIndex($index);

        $foundIndex = $this->manager->getIndex($this->indexName);
        $this->assertEquals($this->indexName, $foundIndex->name());
        $this->assertEquals($bucketName, $foundIndex->sourceName());
        $this->manager->dropIndex($this->indexName);
    }

    public function testUpsertTwiceGivesIndexExists()
    {

        $this->indexName = $this->uniqueId("idx");
        $bucketName = $this->openBucket()->name();
        $index = SearchIndex::build($this->indexName, $bucketName);
        $this->manager->upsertIndex($index);
        $this->expectException(IndexExistsException::class);
        $this->manager->upsertIndex($index);

        $this->manager->dropIndex($this->indexName);
    }

    public function testGetAllIndexes()
    {

        $this->indexName = $this->uniqueId("idx");
        $bucketName = $this->openBucket()->name();
        $index = SearchIndex::build($this->indexName, $bucketName);
        $this->manager->upsertIndex($index);

        $indexes = $this->manager->getAllIndexes();
        $this->assertNotEmpty($indexes);

        $found = false;
        foreach ($indexes as $foundIndex) {
            if ($foundIndex->name() == $this->indexName) {
                $found = true;
            }
        }
        $this->assertTrue($found);
    }

    public function testDropIndex()
    {

        $this->indexName = $this->uniqueId("idx");
        $bucketName = $this->openBucket()->name();
        $index = SearchIndex::build($this->indexName, $bucketName);
        $this->manager->upsertIndex($index);

        $upsertedIndex = $this->manager->getIndex($this->indexName);
        $this->assertEquals($this->indexName, $upsertedIndex->name());

        $this->manager->dropIndex($this->indexName);

        $this->expectException(IndexNotFoundException::class);
        $this->manager->getIndex($this->indexName);
    }

    public function testAnalyzeDocument()
    {

        $this->indexName = $this->uniqueId("idx");
        $bucketName = $this->openBucket()->name();
        $index = SearchIndex::build($this->indexName, $bucketName);
        $this->manager->upsertIndex($index);

        $tokens = $this->retryFor(
            5,
            1000,
            function () {
                return $this->manager->analyzeDocument($this->indexName, ["name" => "hello world"]);
            }
        );
        $this->assertNotEmpty($tokens);
    }
    public function testVariousIndexTasks()
    {

        $randomIndex = $this->uniqueId("idx");
        $functions = [
            [$this->manager, 'pauseIngest'],
            [$this->manager, 'resumeIngest'],
            [$this->manager, 'freezePlan'],
            [$this->manager, 'unfreezePlan'],
            [$this->manager, 'allowQuerying'],
            [$this->manager, 'disallowQuerying']
        ];
        foreach ($functions as $func) {
            $this->wrapException(
                function () use ($randomIndex, $func) {
                    $func($randomIndex);
                },
                IndexNotFoundException::class
            );
        }
    }
}
