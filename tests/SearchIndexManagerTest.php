<?php

use Couchbase\Exception\IndexExistsException;
use Couchbase\Exception\IndexNotFoundException;
use Couchbase\Management\SearchIndex;
use Couchbase\Management\SearchIndexManager;

include_once __DIR__ . "/Helpers/CouchbaseTestCase.php";


class SearchIndexManagerTest extends Helpers\CouchbaseTestCase
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
        $this->skipIfCaves();

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
        $this->skipIfCaves();

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
        $this->skipIfCaves();

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
        $this->skipIfCaves();

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
        $this->skipIfCaves();

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
        $this->skipIfCaves();

        $randomIndex = $this->uniqueId("idx");

        $this->expectException(IndexNotFoundException::class);
        $this->manager->pauseIngest($randomIndex);
        $this->expectException(IndexNotFoundException::class);
        $this->manager->resumeIngest($randomIndex);
        $this->expectException(IndexNotFoundException::class);
        $this->manager->freezePlan($randomIndex);
        $this->expectException(IndexNotFoundException::class);
        $this->manager->unfreezePlan($randomIndex);
        $this->expectException(IndexNotFoundException::class);
        $this->manager->allowQuerying($randomIndex);
        $this->expectException(IndexNotFoundException::class);
        $this->manager->disallowQuerying($randomIndex);
    }
}
