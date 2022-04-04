<?php

use Couchbase\AnalyticsScanConsistency;
use Couchbase\Cluster;

include_once __DIR__ . "/Helpers/CouchbaseTestCase.php";

class AnalyticsTest extends Helpers\CouchbaseTestCase
{
    private Cluster $cluster;

    public function setUp(): void
    {
        parent::setUp();

        $this->cluster = $this->connectCluster();
    }

    function maybeCreateAnalyticsIndex(string $bucketName, string $scopeName = "_default", string $collectionName = "_default")
    {
        try {
            $this->cluster->analyticsQuery("ALTER COLLECTION `$bucketName`.`$scopeName`.`$collectionName` ENABLE ANALYTICS");
        } catch (Exception $e) {}
    }

    function testScopeAnalyticsQuery()
    {
        $this->skipIfCaves();

        $this->maybeCreateAnalyticsIndex("beer-sample");

        $id = $this->uniqueId();
        $bucket = $this->cluster->bucket('beer-sample');
        $collection = $bucket->defaultCollection();
        $scope = $bucket->scope("_default");
        $collection->upsert($id, ["bar" => 42]);

        $options = (new \Couchbase\AnalyticsOptions())
            ->scanConsistency(AnalyticsScanConsistency::REQUEST_PLUS)
            ->positionalParameters([$id]);
        $res = $scope->analyticsQuery("SELECT * FROM `_default` where meta().id = \$1", $options);
        $this->assertNotEmpty($res->rows());
        $this->assertEquals(42, $res->rows()[0]["_default"]['bar']);
    }

    function testClusterAnalyticsQuery()
    {
        $this->skipIfCaves();

        $this->maybeCreateAnalyticsIndex("beer-sample");

        $id = $this->uniqueId();
        $bucket = $this->cluster->bucket('beer-sample');
        $collection = $bucket->defaultCollection();
        $collection->upsert($id, ["bar" => 42]);

        $options = (new \Couchbase\AnalyticsOptions())
            ->scanConsistency(AnalyticsScanConsistency::REQUEST_PLUS)
            ->positionalParameters([$id]);
        $res = $this->cluster->analyticsQuery("SELECT * FROM `beer-sample`.`_default`.`_default` where meta().id = \$1", $options);
        $this->assertNotEmpty($res->rows());
        $this->assertEquals(42, $res->rows()[0]["_default"]['bar']);
    }
}
