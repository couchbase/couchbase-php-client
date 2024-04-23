<?php

use Couchbase\DurabilityLevel;
use Couchbase\Exception\BucketNotFlushableException;
use Couchbase\Exception\BucketNotFoundException;
use Couchbase\Management\BucketManagerInterface;
use Couchbase\Management\BucketSettings;
use Couchbase\Management\BucketType;
use Couchbase\Management\CompressionMode;
use Couchbase\Management\ConflictResolutionType;
use Couchbase\Management\EvictionPolicy;
use Couchbase\Management\StorageBackend;

include_once __DIR__ . "/Helpers/CouchbaseTestCase.php";

class BucketManagerTest extends Helpers\CouchbaseTestCase
{
    private BucketManagerInterface $manager;
    private string $bucketName;

    public function setUp(): void
    {
        parent::setUp();

        $this->manager = $this->connectCluster()->buckets();
        $this->bucketName = $this->uniqueId('bucket');
    }

    public function tearDown(): void
    {
        parent::tearDown();
        try {
            $this->manager->dropBucket($this->bucketName);
        } catch (BucketNotFoundException $ex) {
        }
    }

    public function testCreateCouchbaseBucket()
    {
        $settings = new BucketSettings($this->bucketName);
        $settings->setBucketType(BucketType::COUCHBASE);
        $this->manager->createBucket($settings);

        $result = $this->manager->getBucket($this->bucketName);
        $this->assertEquals($this->bucketName, $result->name());
        $this->assertEquals(BucketType::COUCHBASE, $result->bucketType());
    }

    public function testCreateMemcachedBucket()
    {
        $this->skipIfProtostellar();
        $settings = new BucketSettings($this->bucketName);
        $settings->setBucketType(BucketType::MEMCACHED);
        $this->manager->createBucket($settings);

        $result = $this->manager->getBucket($this->bucketName);
        $this->assertEquals($this->bucketName, $result->name());
        $this->assertEquals(BucketType::MEMCACHED, $result->bucketType());
    }

    public function testCreateEphemeralBucket()
    {
        $settings = new BucketSettings($this->bucketName);
        $settings->setBucketType(BucketType::EPHEMERAL);
        $this->manager->createBucket($settings);

        $result = $this->manager->getBucket($this->bucketName);
        $this->assertEquals($this->bucketName, $result->name());
        $this->assertEquals(BucketType::EPHEMERAL, $result->bucketType());
    }

    public function testDropBucket()
    {
        $bucketName = $this->uniqueId('bucket');
        $settings = new BucketSettings($bucketName);
        $settings->setBucketType(BucketType::COUCHBASE);
        $this->manager->createBucket($settings);

        $this->manager->dropBucket($bucketName);

        $this->expectException(BucketNotFoundException::class);
        $this->manager->getBucket($bucketName);
    }

    public function testGetAllBuckets()
    {
        $names = [];
        for ($i = 0; $i < 2; $i++) {
            $names[] = $this->uniqueId(sprintf('bucket_%d', $i));
        }

        foreach ($names as $name) {
            $settings = new BucketSettings($name);
            $settings->setBucketType(BucketType::COUCHBASE);
            $this->manager->createBucket($settings);
        }

        $result = $this->manager->getAllBuckets();
        $this->assertGreaterThanOrEqual(2, count($result));

        foreach ($names as $name) {
            $this->manager->dropBucket($name);
        }
    }

    public function testCreateBucketRamQuota()
    {
        $settings = new BucketSettings($this->bucketName);
        $settings->setBucketType(BucketType::COUCHBASE)->setRamQuotaMb(200);
        $this->manager->createBucket($settings);

        $result = $this->manager->getBucket($this->bucketName);
        $this->assertEquals($this->bucketName, $result->name());
        $this->assertEquals(200, $result->ramQuotaMb());
    }

    public function testCreateBucketFlushEnable()
    {
        $this->skipIfProtostellar();

        $settings = new BucketSettings($this->bucketName);
        $settings->setBucketType(BucketType::COUCHBASE)->enableFlush(true);
        $this->manager->createBucket($settings);

        $result = $this->manager->getBucket($this->bucketName);
        $this->assertTrue($result->flushEnabled());

        $this->manager->flush($this->bucketName);
    }

    public function testCreateBucketFlushNotEnabled()
    {
        $this->skipIfProtostellar();

        $settings = new BucketSettings($this->bucketName);
        $settings->setBucketType(BucketType::COUCHBASE);
        $this->manager->createBucket($settings);

        $result = $this->manager->getBucket($this->bucketName);
        $this->assertFalse($result->flushEnabled());

        $this->expectException(BucketNotFlushableException::class);
        $this->manager->flush($this->bucketName);
    }

    public function testCreateBucketNumReplicas()
    {
        $settings = new BucketSettings($this->bucketName);
        $settings->setBucketType(BucketType::COUCHBASE)->setNumReplicas(2);
        $this->manager->createBucket($settings);

        $result = $this->manager->getBucket($this->bucketName);
        $this->assertEquals(2, $result->numReplicas());
    }

    public function testCreateBucketReplicaIndexes()
    {
        $this->skipIfReplicasAreNotConfigured();

        $settings = new BucketSettings($this->bucketName);
        $settings->setBucketType(BucketType::COUCHBASE)->enableReplicaIndexes(true);
        $this->manager->createBucket($settings);

        $result = $this->manager->getBucket($this->bucketName);
        $this->assertTrue($result->replicaIndexes());
    }

    public function testCouchbaseBucketEvictionPolicy()
    {
        $this->skipIfCaves();

        $settings = new BucketSettings($this->bucketName);
        $settings->setBucketType(BucketType::COUCHBASE)->setEvictionPolicy(EvictionPolicy::FULL);
        $this->manager->createBucket($settings);

        $result = $this->manager->getBucket($this->bucketName);
        $this->assertEquals(EvictionPolicy::FULL, $result->evictionPolicy());

        $settings->setEvictionPolicy(EvictionPolicy::VALUE_ONLY);
        $this->manager->updateBucket($settings);
        $result = $this->manager->getBucket($this->bucketName);
        $this->assertEquals(EvictionPolicy::VALUE_ONLY, $result->evictionPolicy());
    }

    public function testEphemeralBucketEvictionPolicyNoEviction()
    {
        $this->skipIfCaves();

        $settings = new BucketSettings($this->bucketName);
        $settings->setBucketType(BucketType::EPHEMERAL)->setEvictionPolicy(EvictionPolicy::NO_EVICTION);
        $this->manager->createBucket($settings);

        $result = $this->manager->getBucket($this->bucketName);
        $this->assertEquals(EvictionPolicy::NO_EVICTION, $result->evictionPolicy());
    }

    public function testEphemeralBucketEvictionPolicyNRUEviction()
    {
        $this->skipIfCaves();

        $settings = new BucketSettings($this->bucketName);
        $settings->setBucketType(BucketType::EPHEMERAL)->setEvictionPolicy(EvictionPolicy::NOT_RECENTLY_USED);
        $this->manager->createBucket($settings);

        $result = $this->manager->getBucket($this->bucketName);
        $this->assertEquals(EvictionPolicy::NOT_RECENTLY_USED, $result->evictionPolicy());
    }

    public function testCreateBucketStorageBackendCouchstore()
    {
        $this->skipIfCaves();
        $this->skipIfUnsupported($this->version()->supportsMagmaStorageBackend());

        $settings = new BucketSettings($this->bucketName);
        $settings->setBucketType(BucketType::COUCHBASE)->setStorageBackend(StorageBackend::COUCHSTORE);
        $this->manager->createBucket($settings);

        $result = $this->manager->getBucket($this->bucketName);
        $this->assertEquals(StorageBackend::COUCHSTORE, $result->storageBackend());
    }

    public function testCreateBucketStorageBackendMagma()
    {
        $this->skipIfCaves();
        $this->skipIfUnsupported($this->version()->supportsMagmaStorageBackend());

        $settings = new BucketSettings($this->bucketName);
        $settings->setBucketType(BucketType::COUCHBASE)->setStorageBackend(StorageBackend::MAGMA)->setRamQuotaMb(1024);
        $this->manager->createBucket($settings);

        $result = $this->manager->getBucket($this->bucketName);
        $this->assertEquals(StorageBackend::MAGMA, $result->storageBackend());
    }

    public function testBucketMaxExpiry()
    {
        $this->skipIfCaves();

        $settings = new BucketSettings($this->bucketName);
        $settings->setBucketType(BucketType::COUCHBASE)->setMaxExpiry(5);
        $this->manager->createBucket($settings);

        $result = $this->manager->getBucket($this->bucketName);
        $this->assertEquals(5, $result->maxExpiry());

        $settings->setMaxExpiry(10);
        $this->manager->updateBucket($settings);

        $manager = $this->manager;
        $bucketName = $this->bucketName;
        $result = $this->retryFor(
            10,
            1000,
            function () use ($manager, $bucketName) {
                $result = $manager->getBucket($bucketName);
                if ($result->maxExpiry() == 5) {
                    throw new RuntimeException("the bucket still has old maxExpiry, retrying");
                }
            }
        );

        $result = $this->manager->getBucket($this->bucketName);
        $this->assertEquals(10, $result->maxExpiry());
    }

    public function testBucketCompressionMode()
    {
        $this->skipIfCaves();

        $settings = new BucketSettings($this->bucketName);
        $settings->setBucketType(BucketType::COUCHBASE)->setCompressionMode(CompressionMode::OFF);
        $this->manager->createBucket($settings);

        $result = $this->manager->getBucket($this->bucketName);
        $this->assertEquals(CompressionMode::OFF, $result->compressionMode());

        $settings->setCompressionMode(CompressionMode::PASSIVE);
        $this->manager->updateBucket($settings);

        $result = $this->manager->getBucket($this->bucketName);
        $this->assertEquals(CompressionMode::PASSIVE, $result->compressionMode());

        $settings->setCompressionMode(CompressionMode::ACTIVE);
        $this->manager->updateBucket($settings);

        $result = $this->manager->getBucket($this->bucketName);
        $this->assertEquals(CompressionMode::ACTIVE, $result->compressionMode());
    }

    public function testBucketDurabilityLevel()
    {
        $this->skipIfCaves();
        $this->skipIfUnsupported($this->version()->supportsMinimumDurabilityLevel());

        $settings = new BucketSettings($this->bucketName);
        $settings->setBucketType(BucketType::COUCHBASE)->setMinimumDurabilityLevel(DurabilityLevel::NONE);
        $this->manager->createBucket($settings);

        $result = $this->manager->getBucket($this->bucketName);
        $this->assertEquals(DurabilityLevel::NONE, $result->minimumDurabilityLevel());

        $settings->setMinimumDurabilityLevel(DurabilityLevel::MAJORITY);
        $this->manager->updateBucket($settings);

        $result = $this->manager->getBucket($this->bucketName);
        $this->assertEquals(DurabilityLevel::MAJORITY, $result->minimumDurabilityLevel());

        $settings->setMinimumDurabilityLevel(DurabilityLevel::MAJORITY_AND_PERSIST_TO_ACTIVE);
        $this->manager->updateBucket($settings);

        $result = $this->manager->getBucket($this->bucketName);
        $this->assertEquals(DurabilityLevel::MAJORITY_AND_PERSIST_TO_ACTIVE, $result->minimumDurabilityLevel());

        $settings->setMinimumDurabilityLevel(DurabilityLevel::PERSIST_TO_MAJORITY);
        $this->manager->updateBucket($settings);

        $result = $this->manager->getBucket($this->bucketName);
        $this->assertEquals(DurabilityLevel::PERSIST_TO_MAJORITY, $result->minimumDurabilityLevel());
    }

    public function testCreateBucketConflictResolutionSeqNo()
    {
        $this->skipIfCaves();

        $settings = new BucketSettings($this->bucketName);
        $settings->setBucketType(BucketType::COUCHBASE)->setConflictResolutionType(ConflictResolutionType::SEQUENCE_NUMBER);
        $this->manager->createBucket($settings);

        $result = $this->manager->getBucket($this->bucketName);
        $this->assertEquals(ConflictResolutionType::SEQUENCE_NUMBER, $result->conflictResolutionType());
    }

    public function testCreateBucketConflictResolutionTimestamp()
    {
        $this->skipIfCaves();

        $settings = new BucketSettings($this->bucketName);
        $settings->setBucketType(BucketType::COUCHBASE)->setConflictResolutionType(ConflictResolutionType::TIMESTAMP);
        $this->manager->createBucket($settings);

        $result = $this->manager->getBucket($this->bucketName);
        $this->assertEquals(ConflictResolutionType::TIMESTAMP, $result->conflictResolutionType());
    }

    public function testCreateBucketConflictResolutionCustom()
    {
        $this->skipIfCaves();
        $this->skipIfUnsupported($this->version()->supportsCustomConflictResolutionType());

        $settings = new BucketSettings($this->bucketName);
        $settings->setBucketType(BucketType::COUCHBASE)->setConflictResolutionType(ConflictResolutionType::CUSTOM);
        $this->manager->createBucket($settings);

        $result = $this->manager->getBucket($this->bucketName);
        $this->assertEquals(ConflictResolutionType::CUSTOM, $result->conflictResolutionType());
    }

    public function testCreateHistory()
    {
        $this->skipIfCaves();
        $this->skipIfProtostellar();
        $this->skipIfUnsupported($this->version()->supportsBucketDedup());

        $settings = new BucketSettings($this->bucketName);
        $settings->setBucketType(BucketType::COUCHBASE)->setStorageBackend(StorageBackend::MAGMA)->setRamQuotaMb(1024)
            ->enableHistoryRetentionCollectionDefault(true)->setHistoryRetentionBytes(2147483648)
            ->setHistoryRetentionDuration(13000);
        $this->manager->createBucket($settings);

        $result = $this->manager->getBucket($this->bucketName);
        $this->assertTrue($result->historyRetentionCollectionDefault());
        $this->assertEquals(2147483648, $result->historyRetentionBytes());
        $this->assertEquals(13000, $result->historyRetentionDuration());
    }

    public function testUpdateHistory()
    {
        $this->skipIfCaves();
        $this->skipIfProtostellar();
        $this->skipIfUnsupported($this->version()->supportsBucketDedup());

        $settings = new BucketSettings($this->bucketName);
        $settings->setBucketType(BucketType::COUCHBASE)->setStorageBackend(StorageBackend::MAGMA)->setRamQuotaMb(1024)
            ->enableHistoryRetentionCollectionDefault(false);
        $this->manager->createBucket($settings);

        $result = $this->manager->getBucket($this->bucketName);

        $this->assertFalse($result->historyRetentionCollectionDefault());
        $this->assertEquals(0, $result->historyRetentionDuration());
        $this->assertEquals(0, $result->historyRetentionBytes());

        $settings->enableHistoryRetentionCollectionDefault(true)->setHistoryRetentionDuration(100)
            ->setHistoryRetentionBytes(2147483648);

        $this->manager->updateBucket($settings);

        $result = $this->manager->getBucket($this->bucketName);
        $this->assertTrue($result->historyRetentionCollectionDefault());
        $this->assertEquals(2147483648, $result->historyRetentionBytes());
        $this->assertEquals(100, $result->historyRetentionDuration());
    }
}
