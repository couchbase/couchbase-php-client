<?php

use Couchbase\AnalyticsOptions;
use Couchbase\AnalyticsScanConsistency;
use Couchbase\Cluster;
use Couchbase\JsonTranscoder;

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

        $options = (new AnalyticsOptions())
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

        $options = (new AnalyticsOptions())
            ->scanConsistency(AnalyticsScanConsistency::REQUEST_PLUS)
            ->positionalParameters([$id]);
        $res = $this->cluster->analyticsQuery("SELECT * FROM `beer-sample`.`_default`.`_default` where meta().id = \$1", $options);
        $this->assertNotEmpty($res->rows());
        $this->assertEquals(42, $res->rows()[0]["_default"]['bar']);
    }

    function testRowsShapeAssociative()
    {
        $this->skipIfCaves();

        $this->maybeCreateAnalyticsIndex("beer-sample");

        $opts = new AnalyticsOptions();
        $opts = $opts->transcoder(new JsonTranscoder(true));
        $result = $this->cluster->analyticsQuery("SELECT 'Hello, PHP!' AS message", $opts);
        $this->assertNotEmpty($result->rows());
        $row = $result->rows()[0];
        $this->assertIsArray($row);
        $this->assertEquals("Hello, PHP!", $row["message"]);
    }

    function testRowsShapeNonAssociative()
    {
        $this->skipIfCaves();

        $this->maybeCreateAnalyticsIndex("beer-sample");

        $opts = new AnalyticsOptions();
        $opts = $opts->transcoder(new JsonTranscoder(false));
        $result = $this->cluster->analyticsQuery("SELECT 'Hello, PHP!' AS message", $opts);
        $this->assertNotEmpty($result->rows());
        $row = $result->rows()[0];
        $this->assertIsNotArray($row);
        $this->assertEquals("Hello, PHP!", $row->message);
    }

    function testRowsShapeDefault()
    {
        $this->skipIfCaves();

        $this->maybeCreateAnalyticsIndex("beer-sample");

        $result = $this->cluster->analyticsQuery("SELECT 'Hello, PHP!' AS message");
        $this->assertNotEmpty($result->rows());
        $row = $result->rows()[0];
        $this->assertIsArray($row);
        $this->assertEquals("Hello, PHP!", $row["message"]);
    }
}
