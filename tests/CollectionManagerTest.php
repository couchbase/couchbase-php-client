<?php

use Couchbase\Exception\CollectionExistsException;
use Couchbase\Exception\CollectionNotFoundException;
use Couchbase\Exception\ScopeExistsException;
use Couchbase\Exception\ScopeNotFoundException;
use Couchbase\Management\CollectionManager;
use Couchbase\Management\CollectionSpec;
use Couchbase\Management\ScopeSpec;

include_once __DIR__ . "/Helpers/CouchbaseTestCase.php";

class CollectionManagerTest extends Helpers\CouchbaseTestCase
{
    private CollectionManager $manager;
    private string $bucketName;

    public function setUp(): void
    {
        parent::setUp();

        $this->manager = $this->openBucket()->collections();
        $this->bucketName = $this->uniqueId('bucket');
    }

    public function testCreateScopeThrowsScopesExistsException(): void
    {
        $scopeName= uniqid("scope");
        $this->manager->createScope($scopeName);
        $this->expectException(ScopeExistsException::class);
        $this->manager->createScope($scopeName);
    }

    public function testCreateScopeAndGetAllScopes(): void
    {
        $scopeName= $this->uniqueId("scope");
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

    public function testCreateCollection(): void
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

    public function testCreateCollectionExists(): void
    {
        $collectionName = $this->uniqueId("collection");
        $scopeName = $this->uniqueId("scope");
        $this->manager->createScope($scopeName);
        $collectionSpec = new CollectionSpec($collectionName, $scopeName);
        $this->manager->createCollection($collectionSpec);
        $this->expectException(CollectionExistsException::class);
        $this->manager->createCollection($collectionSpec);
    }

    public function testDropCollectionNotExists(): void
    {
        $this->skipIfCaves();

        $collectionName = $this->uniqueId("collection");
        $scopeName= $this->uniqueId("scope");
        $this->manager->createScope($scopeName);
        $collectionSpec = new CollectionSpec($collectionName, $scopeName);
        $this->expectException(CollectionNotFoundException::class);
        $this->manager->dropCollection($collectionSpec);
    }

    public function testDropCollection(): void
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
            if ($scope->name() == $scopeName);
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

    /**
     * @throws ScopeNotFoundException
     */
    private function getScope(string $scopeName): ScopeSpec
    {
        $scopes = $this->manager->getAllScopes();
        foreach ($scopes as $scope) {
            if ($scope->name() == $scopeName) {
                return $scope;
            }
        }
        throw new ScopeNotFoundException();
    }
}