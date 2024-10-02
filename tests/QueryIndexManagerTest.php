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
                $this->consistencyUtil()->waitUntilBucketDropped($this->bucketName);
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
        $this->consistencyUtil()->waitUntilBucketPresent($bucketName);

        $deadline = time() + 20; /* 20 seconds from now */

        while (true) {
            try {
                try {
                    $manager->createPrimaryIndex(
                        $bucketName,
                        CreateQueryPrimaryIndexOptions::build()
                            ->timeout(200_000)
                            ->ignoreIfExists(true)
                    );
                } catch (\Couchbase\Exception\InternalServerException $ex) {
                    if (preg_match('/will be retried/', $ex->getMessage())) {
                        fprintf(STDERR, "Ignoring transient error during primary index creation for '%s': %s, %s\n", $bucketName, $ex->getMessage(), var_export($ex->getContext(), true));
                    } else {
                        throw $ex;
                    }
                }
                $this->consistencyUtil()->waitUntilQueryIndexReady($bucketName, "#primary", true);
                break;
            } catch (CouchbaseException $ex) {
                fprintf(STDERR, "Ignoring error during primary index creation for '%s': %s, %s\n", $bucketName, $ex->getMessage(), var_export($ex->getContext(), true));
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

        $indexName = $this->uniqueId('testIndex');
        $manager->createIndex(
            $bucketName,
            $indexName,
            ["field"],
            CreateQueryIndexOptions::build()
                ->timeout(200_000)
                ->ignoreIfExists(true)
        );
        $this->consistencyUtil()->waitUntilQueryIndexReady($bucketName, $indexName, false);

        $this->wrapException(
            function () use ($manager, $bucketName, $indexName) {
                $manager->createIndex(
                    $bucketName,
                    $indexName,
                    ["field"],
                    CreateQueryIndexOptions::build()
                        ->timeout(200_000)
                        ->ignoreIfExists(false)
                );
            },
            IndexExistsException::class
        );

        // We create this first to give it a chance to be created by the time we need it.
        $deferredIndexName = $this->uniqueId('testIndexDeferred');
        $manager->createIndex(
            $bucketName,
            $deferredIndexName,
            ["field"],
            CreateQueryIndexOptions::build()
                ->timeout(200_000)
                ->ignoreIfExists(true)
                ->deferred(true)
        );

        $manager->buildDeferredIndexes($bucketName);

        $manager->watchIndexes($bucketName, [$deferredIndexName], 60_000);

        $indexes = $manager->getAllIndexes($bucketName);
        $this->assertGreaterThanOrEqual(3, count($indexes));

        $index = null;
        /**
         * @var QueryIndex $entry
         */
        foreach ($indexes as $entry) {
            if ($entry->name() == $indexName) {
                $index = $entry;
            }
        }
        $this->assertNotNull($index);

        $this->assertEquals($indexName, $index->name());
        $this->assertFalse($index->isPrimary());
        $this->assertEquals(QueryIndexType::GSI, $index->type());
        $this->assertEquals("online", $index->state());
        $this->assertEquals($bucketName, $index->bucketName());
        $this->assertNull($index->scopeName());
        $this->assertNull($index->collectionName());
        $this->assertEquals(["`field`"], $index->indexKey());
        $this->assertNull($index->condition());
        $this->assertNull($index->partition());

        $manager->dropIndex($bucketName, $indexName);

        $this->wrapException(
            function () use ($manager, $bucketName, $indexName) {
                $manager->dropIndex($bucketName, $indexName);
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
                try {
                    $manager->createPrimaryIndex(
                        CreateQueryPrimaryIndexOptions::build()
                        ->timeout(200_000)
                        ->ignoreIfExists(true)
                    );
                } catch (\Couchbase\Exception\InternalServerException $ex) {
                    if (preg_match('/will be retried/', $ex->getMessage())) {
                        fprintf(STDERR, "Ignoring transient error during primary index creation for '%s': %s, %s\n", $bucketName, $ex->getMessage(), var_export($ex->getContext(), true));
                    } else {
                        throw $ex;
                    }
                }
                $this->consistencyUtil()->waitUntilQueryIndexReady($this->env()->bucketName(), "#primary", true);
                break;
            } catch (CouchbaseException $ex) {
                fprintf(STDERR, "Ignoring error during primary index creation: %s, %s\n", $ex->getMessage(), var_export($ex->getContext(), true));
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

        $indexName = $this->uniqueId('testIndex');
        $manager->createIndex(
            $indexName,
            ["field"],
            CreateQueryIndexOptions::build()
                ->timeout(200_000)
                ->ignoreIfExists(true)
        );
        $this->consistencyUtil()->waitUntilQueryIndexReady($this->env()->bucketName(), $indexName, false);

        $this->wrapException(
            function () use ($manager, $indexName) {
                $manager->createIndex(
                    $indexName,
                    ["field"],
                    CreateQueryIndexOptions::build()
                        ->timeout(200_000)
                        ->ignoreIfExists(false)
                );
            },
            IndexExistsException::class
        );

        // We create this first to give it a chance to be created by the time we need it.
        $deferredIndexName = $this->uniqueId('testIndexDeferred');
        $manager->createIndex(
            $deferredIndexName,
            ["field"],
            CreateQueryIndexOptions::build()
                ->timeout(200_000)
                ->ignoreIfExists(true)
                ->deferred(true)
        );

        $manager->buildDeferredIndexes();

        $manager->watchIndexes([$deferredIndexName], 60_000);

        $indexes = $manager->getAllIndexes();
        $this->assertGreaterThanOrEqual(3, count($indexes));

        $index = null;
        /**
         * @var QueryIndex $entry
         */
        foreach ($indexes as $entry) {
            if ($entry->name() == $indexName) {
                $index = $entry;
            }
        }
        $this->assertNotNull($index);

        $this->assertEquals($indexName, $index->name());
        $this->assertFalse($index->isPrimary());
        $this->assertEquals(QueryIndexType::GSI, $index->type());
        $this->assertEquals("online", $index->state());
        $this->assertNull($index->scopeName());
        $this->assertNull($index->collectionName());
        $this->assertEquals(["`field`"], $index->indexKey());
        $this->assertNull($index->condition());
        $this->assertNull($index->partition());

        $manager->dropIndex($indexName);

        $this->wrapException(
            function () use ($manager, $indexName) {
                $manager->dropIndex($indexName);
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
