<?php

use Couchbase\ClusterInterface;
use Couchbase\DesignDocumentNamespace;
use Couchbase\Extension;
use Couchbase\ViewConsistency;
use Couchbase\ViewOptions;
use Couchbase\UpsertOptions;
use Couchbase\DurabilityLevel;
use Couchbase\Management\BucketManager;
use Couchbase\Management\BucketType;
use Couchbase\Management\BucketSettings;

include_once __DIR__ . "/Helpers/CouchbaseTestCase.php";

class ViewTest extends Helpers\CouchbaseTestCase
{
    private ClusterInterface $cluster;
    private BucketManager $bucketManager;
    private string $bucketName;

    public function setUp(): void
    {
        parent::setUp();
        $this->skipIfProtostellar();
        $this->skipIfCaves();

        $this->cluster = $this->connectCluster();

        $this->bucketName = $this->uniqueId("viewtest");

        $this->bucketManager = $this->cluster->buckets();
        $settings = new BucketSettings($this->bucketName);
        $settings->setBucketType(BucketType::COUCHBASE);
        $this->bucketManager->createBucket($settings);
        $this->consistencyUtil()->waitUntilBucketPresent($this->bucketName);

        while (true) {
            sleep(1);
            try {
                $this->collection = $this->openBucket($this->bucketName)->defaultCollection();
                break;
            } catch (BucketNotFoundException $ex) {
                // do nothing
            }
        }
    }

    public function tearDown(): void
    {
        try {
            $this->bucketManager->dropBucket($this->bucketName);
            $this->consistencyUtil()->waitUntilBucketDropped($this->bucketName);
        } catch (BucketNotFoundException $ex) {
            /* do nothing */
        }
    }

    public function testConsistency()
    {
        $this->skipIfCaves();


        $ddocName = $this->uniqueId();
        $view = [
            'name' => 'test',
            'map' => "function(doc, meta) { if (meta.id.startsWith(\"{$ddocName}\")) emit(meta.id); }",
            'reduce' => '_count',
        ];

        $ddoc = [
            'name' => $ddocName,
            'views' => [
                'test' => $view,
            ],
        ];

        Extension\viewIndexUpsert($this->cluster->core(), $this->bucketName, $ddoc, DesignDocumentNamespace::PRODUCTION, []);
        $this->consistencyUtil()->waitUntilViewPresent($this->bucketName, $ddocName, 'test');
        sleep(1); // give design document a second to settle

        $key = $this->uniqueId($ddocName);
        $bucket = $this->cluster->bucket($this->bucketName);

        $options = UpsertOptions::build()
            ->durabilityLevel(DurabilityLevel::MAJORITY_AND_PERSIST_TO_ACTIVE);
        $bucket->defaultCollection()->upsert($key, ['foo' => 42], $options);

        $res = $bucket->viewQuery($ddocName, 'test');
        $this->assertEmpty($res->rows());

        $options = ViewOptions::build()
            ->scanConsistency(ViewConsistency::REQUEST_PLUS)
            ->reduce(false)
            ->timeout(200_000);
        $res = $bucket->viewQuery($ddocName, 'test', $options);
        $this->assertCount(1, $res->rows());
        $this->assertEquals($key, $res->rows()[0]->id());

        // TODO: drop design doc.
    }

    public function testGrouping()
    {
        $this->skipIfCaves();

        $ddocName = $this->uniqueId();
        $view = [
            'name' => 'test',
            'map' => "function(doc, meta) { if (doc && doc.ddoc == \"$ddocName\") emit([doc.country, doc.city]); }",
            'reduce' => '_count',
        ];

        $ddoc = [
            'name' => $ddocName,
            'views' => [
                'test' => $view,
            ],
        ];

        Extension\viewIndexUpsert($this->cluster->core(), $this->bucketName, $ddoc, DesignDocumentNamespace::PRODUCTION, []);
        $this->consistencyUtil()->waitUntilViewPresent($this->bucketName, $ddocName, 'test');
        sleep(1); // give design document a second to settle

        $bucket = $this->cluster->bucket($this->bucketName);
        $collection = $bucket->defaultCollection();

        $options = UpsertOptions::build()
            ->durabilityLevel(DurabilityLevel::MAJORITY_AND_PERSIST_TO_ACTIVE);

        $collection->upsert(
            $this->uniqueId($ddocName),
            ['ddoc' => $ddocName, 'country' => 'USA', 'city' => 'New York', 'name' => 'John Doe'],
            $options
        );
        $collection->upsert(
            $this->uniqueId($ddocName),
            ['ddoc' => $ddocName, 'country' => 'USA', 'city' => 'New York', 'name' => 'Jane Doe'],
            $options
        );
        $collection->upsert(
            $this->uniqueId($ddocName),
            ['ddoc' => $ddocName, 'country' => 'USA', 'city' => 'Miami', 'name' => 'Bill Brown'],
            $options
        );
        $collection->upsert(
            $this->uniqueId($ddocName),
            ['ddoc' => $ddocName, 'country' => 'France', 'city' => 'Paris', 'name' => 'Jean Bon'],
            $options
        );
        sleep(1); // give docs time to propagate

        $options = ViewOptions::build()->scanConsistency(ViewConsistency::REQUEST_PLUS)->timeout(200_000);
        $res = $bucket->viewQuery($ddocName, 'test', $options);
        $this->assertCount(1, $res->rows());
        $this->assertEquals(4, $res->rows()[0]->value());

        $options = ViewOptions::build()
            ->scanConsistency(ViewConsistency::REQUEST_PLUS)
            ->groupLevel(1)
            ->timeout(120_000);
        $res = $bucket->viewQuery($ddocName, 'test', $options);
        $this->assertCount(2, $res->rows());
        $this->assertEquals(["France"], $res->rows()[0]->key());
        $this->assertEquals(1, $res->rows()[0]->value());
        $this->assertEquals(["USA"], $res->rows()[1]->key());
        $this->assertEquals(3, $res->rows()[1]->value());

        $options = ViewOptions::build()
            ->scanConsistency(ViewConsistency::REQUEST_PLUS)
            ->group(true)
            ->timeout(120_000);
        $res = $bucket->viewQuery($ddocName, 'test', $options);
        $this->assertCount(3, $res->rows());
        $this->assertEquals(["France", "Paris"], $res->rows()[0]->key());
        $this->assertEquals(1, $res->rows()[0]->value());
        $this->assertEquals(["USA", "Miami"], $res->rows()[1]->key());
        $this->assertEquals(1, $res->rows()[1]->value());
        $this->assertEquals(["USA", "New York"], $res->rows()[2]->key());
        $this->assertEquals(2, $res->rows()[2]->value());

        $options = ViewOptions::build()
            ->scanConsistency(ViewConsistency::REQUEST_PLUS)
            ->group(true)
            ->reduce(true)
            ->keys(array_values([['USA', 'New York']]))
            ->timeout(120_000);
        $res = $bucket->viewQuery($ddocName, 'test', $options);
        $this->assertCount(1, $res->rows());
        $this->assertEquals(["USA", "New York"], $res->rows()[0]->key());
        $this->assertEquals(2, $res->rows()[0]->value());

        // TODO: drop design doc.
    }
}
