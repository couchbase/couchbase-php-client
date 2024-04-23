<?php

use Couchbase\Exception\CollectionExistsException;
use Couchbase\Exception\CollectionNotFoundException;
use Couchbase\Exception\CouchbaseException;
use Couchbase\Exception\FeatureNotAvailableException;
use Couchbase\Exception\InvalidArgumentException;
use Couchbase\Exception\ScopeExistsException;
use Couchbase\Exception\ScopeNotFoundException;
use Couchbase\Management\BucketSettings;
use Couchbase\Management\CollectionManager;
use Couchbase\Management\CollectionManagerInterface;
use Couchbase\Management\CollectionSpec;
use Couchbase\Management\CreateCollectionSettings;
use Couchbase\Management\ScopeSpec;
use Couchbase\Management\StorageBackend;
use Couchbase\Management\UpdateCollectionSettings;

include_once __DIR__ . "/Helpers/CouchbaseTestCase.php";

class CollectionManagerTest extends Helpers\CouchbaseTestCase
{
    private CollectionManagerInterface $manager;
    private string $bucketName;

    public function setUp(): void
    {
        parent::setUp();

        $this->manager = $this->openBucket()->collections();
        $this->bucketName = $this->uniqueId('bucket');
    }

    public function testCreateScopeThrowsScopesExistsException(): void
    {
        $scopeName = uniqid("scope");
        $this->manager->createScope($scopeName);
        $this->expectException(ScopeExistsException::class);
        $this->manager->createScope($scopeName);
    }

    public function testCreateScopeAndGetAllScopes(): void
    {
        $scopeName = $this->uniqueId("scope");
        $this->manager->createScope($scopeName);
        $scopes = $this->manager->getAllScopes();
        $this->assertNotNull($scopes);
        $found = false;
        foreach ($scopes as $scope) {
            if ($scope->name() == $scopeName) {
                $found = true;
            }
        }
        $this->assertTrue($found);
    }

    public function testDropScopes(): void
    {
        $scopeName = $this->uniqueId("scope");
        $this->manager->createScope($scopeName);

        $scopes = $this->manager->getAllScopes();
        $this->assertNotNull($scopes);

        $found = false;
        foreach ($scopes as $scope) {
            if ($scope->name() == $scopeName) {
                $found = true;
            }
        }
        $this->assertTrue($found);

        $this->manager->dropScope($scopeName);

        $scopes = $this->manager->getAllScopes();
        $this->assertNotNull($scopes);

        $found = false;
        foreach ($scopes as $scope) {
            if ($scope->name() == $scopeName) {
                $found = true;
            }
        }
        $this->assertFalse($found);
    }

    public function testDropScopeDoesNotExist(): void
    {
        $this->skipIfCaves();

        $scopeName = $this->uniqueId("scope");
        $this->expectException(ScopeNotFoundException::class);
        $this->manager->dropScope($scopeName);
    }

    public function testCreateCollectionDeprecatedApi(): void
    {
        $collectionName = $this->uniqueId("collection");
        $scopeName = $this->uniqueId("scope");
        $this->manager->createScope($scopeName);
        $collectionSpec = new CollectionSpec($collectionName, $scopeName);
        $this->manager->createCollection($collectionSpec);

        $selectedScope = $this->getScope($scopeName);

        $found = false;
        foreach ($selectedScope->collections() as $collection) {
            if ($collection->name() == $collectionName) {
                $found = true;
            }
        }
        $this->assertTrue($found);
    }

    public function testCreateCollectionExistsDeprecatedApi(): void
    {
        $collectionName = $this->uniqueId("collection");
        $scopeName = $this->uniqueId("scope");
        $this->manager->createScope($scopeName);
        $collectionSpec = new CollectionSpec($collectionName, $scopeName);
        $this->manager->createCollection($collectionSpec);
        $this->expectException(CollectionExistsException::class);
        $this->manager->createCollection($collectionSpec);
    }

    public function testDropCollectionNotExistsDeprecatedApi(): void
    {
        $this->skipIfCaves();

        $collectionName = $this->uniqueId("collection");
        $scopeName = $this->uniqueId("scope");
        $this->manager->createScope($scopeName);
        $collectionSpec = new CollectionSpec($collectionName, $scopeName);
        $this->expectException(CollectionNotFoundException::class);
        $this->manager->dropCollection($collectionSpec);
    }

    public function testDropCollectionDeprecatedApi(): void
    {
        $collectionName = $this->uniqueId("collection");
        $scopeName = $this->uniqueId("scope");
        $this->manager->createScope($scopeName);
        $collectionSpec = new CollectionSpec($collectionName, $scopeName);
        $this->manager->createCollection($collectionSpec);

        $selectedScope = $this->getScope($scopeName);

        $found = false;
        foreach ($selectedScope->collections() as $collection) {
            if ($collection->name() == $collectionName) {
                $found = true;
            }
        }
        $this->assertTrue($found);

        $this->manager->dropCollection($collectionSpec);
        $scopes = $this->manager->getAllScopes();

        $selectedScope = null;
        foreach ($scopes as $scope) {
            if ($scope->name() == $scopeName) {
            }
            $selectedScope = $scope;
        }
        $this->assertNotNull($selectedScope);

        $found = false;
        foreach ($selectedScope->collections() as $collection) {
            if ($collection->name() == $collectionName) {
                $found = true;
            }
        }
        $this->assertFalse($found);
    }

    public function testUpdateCollection(): void
    {
        $this->skipIfCaves();
        $this->skipIfUnsupported($this->version()->supportsUpdateCollectionMaxExpiry());
        $collectionName = $this->uniqueId("collection");
        $scopeName = $this->uniqueId("scope");
        $this->manager->createScope($scopeName);
        $this->manager->createCollection($scopeName, $collectionName);

        $selectedScope = $this->getScope($scopeName);

        $found = false;
        foreach ($selectedScope->collections() as $collection) {
            if ($collection->name() == $collectionName) {
                $found = true;
                $foundCollection = $collection;
            }
        }
        $this->assertTrue($found);

        $this->assertEquals(0, $foundCollection->maxExpiry());

        $this->manager->updateCollection($scopeName, $collectionName, UpdateCollectionSettings::build(3));

        $updatedScope = $this->getScope($scopeName);

        foreach ($updatedScope->collections() as $collection) {
            if ($collection->name() == $collectionName) {
                $updatedCollection = $collection;
            }
        }

        $this->assertEquals(3, $updatedCollection->maxExpiry());
    }

    public function testDropCollection(): void
    {
        $collectionName = $this->uniqueId("collection");
        $scopeName = $this->uniqueId("scope");
        $this->manager->createScope($scopeName);
        $this->manager->createCollection($scopeName, $collectionName);

        $selectedScope = $this->getScope($scopeName);

        $found = false;
        foreach ($selectedScope->collections() as $collection) {
            if ($collection->name() == $collectionName) {
                $found = true;
            }
        }
        $this->assertTrue($found);

        $this->manager->dropCollection($scopeName, $collectionName);

        $selectedScope = $this->getScope($scopeName);

        $found = false;
        foreach ($selectedScope->collections() as $collection) {
            if ($collection->name() == $collectionName) {
                $found = true;
            }
        }
        $this->assertFalse($found);
    }
    public function testCollectionHistory(): void
    {
        $this->skipIfCaves();
        $this->skipIfProtostellar();
        $this->skipIfUnsupported($this->version()->supportsMagmaStorageBackend());
        $this->skipIfUnsupported($this->version()->supportsBucketDedup());

        // Create magma bucket
        $collectionName = $this->uniqueId("collection");
        $scopeName = $this->uniqueId("scope");
        $bucketManager = $this->connectCluster()->buckets();
        $bucketName = $this->uniqueId("magma");
        $bucketSettings = BucketSettings::build($bucketName)->setStorageBackend(StorageBackend::MAGMA)->setRamQuotaMb(1024);
        $bucketManager->createBucket($bucketSettings);

        $deadline = time() + 5; /* 5 seconds from now */
        while (true) {
            try {
                $bucketManager->getBucket($bucketName);
                $collectionManager = $this->openBucket($bucketName)->collections();
                break;
            } catch (CouchbaseException $ex) {
                printf("Error getting bucket: %s, %s", $ex->getMessage(), var_export($ex->getContext(), true));
                if (time() > $deadline) {
                    $this->assertFalse("timed out waiting to get bucket");
                }
                sleep(1);
            }
        }

        $collectionManager->createScope($scopeName);
        $collectionManager->createCollection($scopeName, $collectionName, CreateCollectionSettings::build(null, false));

        $selectedScope = $this->getScope($scopeName, $collectionManager);
        $found = false;
        foreach ($selectedScope->collections() as $collection) {
            if ($collection->name() == $collectionName) {
                $found = true;
                $foundCollection = $collection;
            }
        }
        $this->assertTrue($found);
        $this->assertFalse($foundCollection->history());

        $collectionManager->updateCollection($scopeName, $collectionName, UpdateCollectionSettings::build(null, true));

        $selectedScope = $this->getScope($scopeName, $collectionManager);

        foreach ($selectedScope->collections() as $collection) {
            if ($collection->name() == $collectionName) {
                $foundCollection = $collection;
            }
        }
        $this->assertTrue($foundCollection->history());

        $bucketManager->dropBucket($bucketName);
    }

    public function testCreateCollectionNoExpiry()
    {
        $this->skipIfCaves();
        $this->skipIfProtostellar();
        $this->skipIfUnsupported($this->version()->supportsCollectionMaxTTLNoExpiry());

        $collectionName = $this->uniqueId("collection");
        $scopeName = $this->uniqueId("scope");
        $this->manager->createScope($scopeName);
        $this->manager->createCollection($scopeName, $collectionName, CreateCollectionSettings::build(-1));

        $selectedScope = $this->getScope($scopeName);

        $found = false;
        foreach ($selectedScope->collections() as $collection) {
            if ($collection->name() == $collectionName) {
                $found = true;
                $foundCollection = $collection;
            }
        }
        $this->assertTrue($found);

        $this->assertEquals(-1, $foundCollection->maxExpiry());
    }

    public function testCreateCollectionNoExpiryNotSupported()
    {
        $this->skipIfCaves();
        $this->skipIfProtostellar();
        $this->skipIfUnsupported(!$this->version()->supportsCollectionMaxTTLNoExpiry());

        $collectionName = $this->uniqueId("collection");
        $scopeName = $this->uniqueId("scope");
        $this->manager->createScope($scopeName);

        $this->expectException(FeatureNotAvailableException::class);
        $this->manager->createCollection($scopeName, $collectionName, CreateCollectionSettings::build(-1, false));
    }

    public function testCreateCollectionInvalidExpiry()
    {
        $this->expectException(InvalidArgumentException::class);
        CreateCollectionSettings::build(-5, false);
    }

    public function testCreateCollectionZeroExpiry(): void
    {
        $settings = CreateCollectionSettings::build(0);
        $this->assertNotNull($settings);
    }

    /**
     * @throws ScopeNotFoundException
     */
    private function getScope(string $scopeName, CollectionManager $manager = null): ScopeSpec
    {
        if (is_null($manager)) {
            $manager = $this->manager;
        }
        $scopes = $manager->getAllScopes();
        foreach ($scopes as $scope) {
            if ($scope->name() == $scopeName) {
                return $scope;
            }
        }
        throw new ScopeNotFoundException();
    }
}
