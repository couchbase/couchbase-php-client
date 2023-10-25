<?php

use Couchbase\ClusterInterface;
use Couchbase\DesignDocumentNamespace;
use Couchbase\Extension;
use Couchbase\ViewConsistency;
use Couchbase\ViewOptions;

include_once __DIR__ . "/Helpers/CouchbaseTestCase.php";

class ViewTest extends Helpers\CouchbaseTestCase
{
    private ClusterInterface $cluster;

    public function setUp(): void
    {
        parent::setUp();
        $this->skipIfProtostellar();

        $this->cluster = $this->connectCluster();
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

        $bucketName = $this->env()->bucketName();
        Extension\viewIndexUpsert($this->cluster->core(), $bucketName, $ddoc, DesignDocumentNamespace::PRODUCTION, []);
        sleep(1); // give design document a second to settle

        $key = $this->uniqueId($ddocName);
        $bucket = $this->cluster->bucket($bucketName);
        $bucket->defaultCollection()->upsert($key, ['foo' => 42]);

        $res = $bucket->viewQuery($ddocName, 'test');
        $this->assertEmpty($res->rows());

        $options = ViewOptions::build()
            ->scanConsistency(ViewConsistency::REQUEST_PLUS)
            ->reduce(false);
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

        $bucketName = $this->env()->bucketName();
        Extension\viewIndexUpsert($this->cluster->core(), $bucketName, $ddoc, DesignDocumentNamespace::PRODUCTION, []);
        sleep(1); // give design document a second to settle

        $bucket = $this->cluster->bucket($bucketName);
        $collection = $bucket->defaultCollection();

        $collection->upsert(
            $this->uniqueId($ddocName),
            ['ddoc' => $ddocName, 'country' => 'USA', 'city' => 'New York', 'name' => 'John Doe']
        );
        $collection->upsert(
            $this->uniqueId($ddocName),
            ['ddoc' => $ddocName, 'country' => 'USA', 'city' => 'New York', 'name' => 'Jane Doe']
        );
        $collection->upsert(
            $this->uniqueId($ddocName),
            ['ddoc' => $ddocName, 'country' => 'USA', 'city' => 'Miami', 'name' => 'Bill Brown']
        );
        $collection->upsert(
            $this->uniqueId($ddocName),
            ['ddoc' => $ddocName, 'country' => 'France', 'city' => 'Paris', 'name' => 'Jean Bon']
        );
        sleep(1); // give docs time to propagate

        $options = ViewOptions::build()->scanConsistency(ViewConsistency::REQUEST_PLUS);
        $res = $bucket->viewQuery($ddocName, 'test', $options);
        $this->assertCount(1, $res->rows());
        $this->assertEquals(4, $res->rows()[0]->value());

        $options = ViewOptions::build()
            ->scanConsistency(ViewConsistency::REQUEST_PLUS)
            ->groupLevel(1);
        $res = $bucket->viewQuery($ddocName, 'test', $options);
        $this->assertCount(2, $res->rows());
        $this->assertEquals(["France"], $res->rows()[0]->key());
        $this->assertEquals(1, $res->rows()[0]->value());
        $this->assertEquals(["USA"], $res->rows()[1]->key());
        $this->assertEquals(3, $res->rows()[1]->value());

        $options = ViewOptions::build()
            ->scanConsistency(ViewConsistency::REQUEST_PLUS)
            ->group(true);
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
            ->keys(array_values([['USA', 'New York']]));
        $res = $bucket->viewQuery($ddocName, 'test', $options);
        $this->assertCount(1, $res->rows());
        $this->assertEquals(["USA", "New York"], $res->rows()[0]->key());
        $this->assertEquals(2, $res->rows()[0]->value());

        // TODO: drop design doc.
    }
}
