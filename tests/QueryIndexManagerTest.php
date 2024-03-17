<?php

use Couchbase\ClusterInterface;
use Couchbase\Exception\BucketNotFoundException;
use Couchbase\Exception\CouchbaseException;
use Couchbase\Exception\IndexExistsException;
use Couchbase\Exception\IndexNotFoundException;
use Couchbase\Management\BucketManagerInterface;
use Couchbase\Management\BucketSettings;
use Couchbase\Management\BucketType;
use Couchbase\Management\CreateQueryIndexOptions;
use Couchbase\Management\CreateQueryPrimaryIndexOptions;
use Couchbase\Management\QueryIndex;
use Couchbase\Management\QueryIndexType;

include_once __DIR__ . "/Helpers/CouchbaseTestCase.php";

class QueryIndexManagerTest extends Helpers\CouchbaseTestCase
{
    private ClusterInterface $cluster;
    private BucketManagerInterface $bucketManager;
    private ?string $bucketName;

    public function setUp(): void
    {
        parent::setUp();

        $this->cluster = $this->connectCluster();
        if (self::env()->useCouchbase()) {
            $this->bucketManager = $this->connectCluster()->buckets();
            $this->bucketName = $this->uniqueId('bucket');
        }
    }

    public function tearDown(): void
    {
        parent::tearDown();
        if (self::env()->useCouchbase()) {
            try {
                $this->bucketManager->dropBucket($this->bucketName);
            } catch (BucketNotFoundException $ex) {
                /* do nothing */
            }
        }
    }

    public function testQueryIndexesCrud()
    {
        $this->skipIfCaves();

        $manager = $this->cluster->queryIndexes();

        $bucketName = $this->bucketName;
        $settings = new BucketSettings($bucketName);
        $settings->setBucketType(BucketType::COUCHBASE);
        $this->bucketManager->createBucket($settings);

        $deadline = time() + 5; /* 5 seconds from now */

        while (true) {
            try {
                $manager->createPrimaryIndex($bucketName, CreateQueryPrimaryIndexOptions::build()->ignoreIfExists(true));
                break;
            } catch (CouchbaseException $ex) {
                printf("Error during primary index creation for '%s': %s, %s", $bucketName, $ex->getMessage(), var_export($ex->getContext(), true));
                if (time() > $deadline) {
                    $this->fail("timed out waiting for create index to succeed");
                }
                sleep(1);
            }
        }

        $this->wrapException(
            function () use ($manager, $bucketName) {
                $manager->createPrimaryIndex($bucketName, CreateQueryPrimaryIndexOptions::build()->ignoreIfExists(false));
            },
            IndexExistsException::class
        );

        $manager->createIndex(
            $bucketName,
            "testIndex",
            ["field"],
            CreateQueryIndexOptions::build()->ignoreIfExists(true)
        );

        $this->wrapException(
            function () use ($manager, $bucketName) {
                $manager->createIndex(
                    $bucketName,
                    "testIndex",
                    ["field"],
                    CreateQueryIndexOptions::build()->ignoreIfExists(false)
                );
            },
            IndexExistsException::class
        );

        // We create this first to give it a chance to be created by the time we need it.
        $manager->createIndex(
            $bucketName,
            "testIndexDeferred",
            ["field"],
            CreateQueryIndexOptions::build()
                ->ignoreIfExists(true)
                ->deferred(true)
        );

        $manager->buildDeferredIndexes($bucketName);

        $manager->watchIndexes($bucketName, ["testIndexDeferred"], 60_000);

        $indexes = $manager->getAllIndexes($bucketName);
        $this->assertGreaterThanOrEqual(3, count($indexes));

        $index = null;
        /**
         * @var QueryIndex $entry
         */
        foreach ($indexes as $entry) {
            if ($entry->name() == "testIndex") {
                $index = $entry;
            }
        }
        $this->assertNotNull($index);

        $this->assertEquals("testIndex", $index->name());
        $this->assertFalse($index->isPrimary());
        $this->assertEquals(QueryIndexType::GSI, $index->type());
        $this->assertEquals("online", $index->state());
        $this->assertEquals($bucketName, $index->bucketName());
        $this->assertNull($index->scopeName());
        $this->assertNull($index->collectionName());
        $this->assertEquals(["`field`"], $index->indexKey());
        $this->assertNull($index->condition());
        $this->assertNull($index->partition());

        $manager->dropIndex($bucketName, "testIndex");

        $this->wrapException(
            function () use ($manager, $bucketName) {
                $manager->dropIndex($bucketName, "testIndex");
            },
            IndexNotFoundException::class
        );

        $manager->dropPrimaryIndex($bucketName);

        $this->wrapException(
            function () use ($manager, $bucketName) {
                $manager->dropPrimaryIndex($bucketName);
            },
            IndexNotFoundException::class
        );
    }

    public function testCollectionQueryIndexesCrud()
    {
        $this->skipIfCaves();

        $manager = $this->defaultCollection()->queryIndexes();

        $deadline = time() + 5; /* 5 seconds from now */

        while (true) {
            try {
                $manager->createPrimaryIndex(CreateQueryPrimaryIndexOptions::build()->ignoreIfExists(true));
                break;
            } catch (CouchbaseException $ex) {
                printf("Error during primary index creation: %s, %s", $ex->getMessage(), var_export($ex->getContext(), true));
                if (time() > $deadline) {
                    $this->assertFalse("timed out waiting for create index to succeed");
                }
                sleep(1);
            }
        }

        $this->wrapException(
            function () use ($manager) {
                $manager->createPrimaryIndex(CreateQueryPrimaryIndexOptions::build()->ignoreIfExists(false));
            },
            IndexExistsException::class
        );

        $manager->createIndex(
            "testIndex",
            ["field"],
            CreateQueryIndexOptions::build()->ignoreIfExists(true)
        );

        $this->wrapException(
            function () use ($manager) {
                $manager->createIndex(
                    "testIndex",
                    ["field"],
                    CreateQueryIndexOptions::build()->ignoreIfExists(false)
                );
            },
            IndexExistsException::class
        );

        // We create this first to give it a chance to be created by the time we need it.
        $manager->createIndex(
            "testIndexDeferred",
            ["field"],
            CreateQueryIndexOptions::build()
                ->ignoreIfExists(true)
                ->deferred(true)
        );

        $manager->buildDeferredIndexes();

        $manager->watchIndexes(["testIndexDeferred"], 60_000);

        $indexes = $manager->getAllIndexes();
        $this->assertGreaterThanOrEqual(3, count($indexes));

        $index = null;
        /**
         * @var QueryIndex $entry
         */
        foreach ($indexes as $entry) {
            if ($entry->name() == "testIndex") {
                $index = $entry;
            }
        }
        $this->assertNotNull($index);

        $this->assertEquals("testIndex", $index->name());
        $this->assertFalse($index->isPrimary());
        $this->assertEquals(QueryIndexType::GSI, $index->type());
        $this->assertEquals("online", $index->state());
        $this->assertNull($index->scopeName());
        $this->assertNull($index->collectionName());
        $this->assertEquals(["`field`"], $index->indexKey());
        $this->assertNull($index->condition());
        $this->assertNull($index->partition());

        $manager->dropIndex("testIndex");

        $this->wrapException(
            function () use ($manager) {
                $manager->dropIndex("testIndex");
            },
            IndexNotFoundException::class
        );

        $manager->dropPrimaryIndex();

        $this->wrapException(
            function () use ($manager) {
                $manager->dropPrimaryIndex();
            },
            IndexNotFoundException::class
        );
    }
}
