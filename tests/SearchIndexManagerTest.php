<?php

use Couchbase\Exception\FeatureNotAvailableException;
use Couchbase\Exception\IndexExistsException;
use Couchbase\Exception\IndexNotFoundException;
use Couchbase\Management\ScopeSearchIndexManagerInterface;
use Couchbase\Management\SearchIndex;
use Couchbase\Management\SearchIndexManagerInterface;

include_once __DIR__ . "/Helpers/CouchbaseTestCase.php";


class SearchIndexManagerTest extends Helpers\CouchbaseTestCase
{
    private SearchIndexManagerInterface $manager;
    private ScopeSearchIndexManagerInterface $scopeManager;
    private string $indexName;

    public function setUp(): void
    {
        parent::setUp();

        $this->manager = $this->connectCluster()->searchIndexes();
        $this->scopeManager = $this->openBucket()->defaultScope()->searchIndexes();
        $this->indexName = $this->uniqueId("idx");
    }

    public function tearDown(): void
    {
        parent::tearDown();

        try {
            $this->manager->dropIndex($this->indexName);
            $this->scopeManager->dropIndex($this->indexName);
        } catch (IndexNotFoundException | FeatureNotAvailableException $exception) {
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

    public function testScopeUpsertAndGetIndex()
    {
        $this->skipIfCaves();
        $this->skipIfUnsupported($this->version()->supportsScopeSearchIndexes());

        $this->indexName = $this->uniqueId("idx");
        $bucketName = $this->openBucket()->name();
        $index = SearchIndex::build($this->indexName, $bucketName);
        $this->scopeManager->upsertIndex($index);

        $foundIndex = $this->scopeManager->getIndex($this->indexName);
        $this->assertEquals($this->indexName, $foundIndex->name());
        $this->assertEquals($bucketName, $foundIndex->sourceName());
        $this->scopeManager->dropIndex($this->indexName);
    }

    public function testScopeUpsertTwiceGivesIndexExists()
    {
        $this->skipIfCaves();
        $this->skipIfUnsupported($this->version()->supportsScopeSearchIndexes());

        $this->indexName = $this->uniqueId("idx");
        $bucketName = $this->openBucket()->name();
        $index = SearchIndex::build($this->indexName, $bucketName);
        $this->scopeManager->upsertIndex($index);
        $this->expectException(IndexExistsException::class);
        $this->scopeManager->upsertIndex($index);

        $this->scopeManager->dropIndex($this->indexName);
    }

    public function testScopeGetAllIndexes()
    {
        $this->skipIfCaves();
        $this->skipIfUnsupported($this->version()->supportsScopeSearchIndexes());

        $this->indexName = $this->uniqueId("idx");
        $bucketName = $this->openBucket()->name();
        $index = SearchIndex::build($this->indexName, $bucketName);
        $this->scopeManager->upsertIndex($index);

        $indexes = $this->scopeManager->getAllIndexes();
        $this->assertNotEmpty($indexes);

        $found = false;
        foreach ($indexes as $foundIndex) {
            if ($foundIndex->name() == $this->indexName) {
                $found = true;
            }
        }
        $this->assertTrue($found);
    }

    public function testScopeDropIndex()
    {
        $this->skipIfCaves();
        $this->skipIfUnsupported($this->version()->supportsScopeSearchIndexes());

        $this->indexName = $this->uniqueId("idx");
        $bucketName = $this->openBucket()->name();
        $index = SearchIndex::build($this->indexName, $bucketName);
        $this->scopeManager->upsertIndex($index);

        $upsertedIndex = $this->scopeManager->getIndex($this->indexName);
        $this->assertEquals($this->indexName, $upsertedIndex->name());

        $this->scopeManager->dropIndex($this->indexName);

        $this->expectException(IndexNotFoundException::class);
        $this->scopeManager->getIndex($this->indexName);
    }
    public function testScopeVariousIndexTasks()
    {
        $this->skipIfCaves();
        $this->skipIfUnsupported($this->version()->supportsScopeSearchIndexes());

        $randomIndex = $this->uniqueId("idx");
        $functions = [
            [$this->scopeManager, 'pauseIngest'],
            [$this->scopeManager, 'resumeIngest'],
            [$this->scopeManager, 'freezePlan'],
            [$this->scopeManager, 'unfreezePlan'],
            [$this->scopeManager, 'allowQuerying'],
            [$this->scopeManager, 'disallowQuerying']
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

    public function testScopeThrowsFeatureNotAvailable()
    {
        $this->skipIfCaves();
        $this->skipIfUnsupported(!$this->version()->supportsScopeSearchIndexes());

        $randomIndex = $this->uniqueId("idx");
        $functions = [
            [$this->scopeManager, 'pauseIngest'],
            [$this->scopeManager, 'resumeIngest'],
            [$this->scopeManager, 'freezePlan'],
            [$this->scopeManager, 'unfreezePlan'],
            [$this->scopeManager, 'allowQuerying'],
            [$this->scopeManager, 'disallowQuerying']
        ];
        foreach ($functions as $func) {
            $this->wrapException(
                function () use ($randomIndex, $func) {
                    $func($randomIndex);
                },
                FeatureNotAvailableException::class
            );
        }
    }
}
