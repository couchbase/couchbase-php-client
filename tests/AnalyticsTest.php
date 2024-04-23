<?php

use Couchbase\AnalyticsOptions;
use Couchbase\AnalyticsScanConsistency;
use Couchbase\ClusterInterface;
use Couchbase\Exception\DatasetExistsException;
use Couchbase\JsonTranscoder;

include_once __DIR__ . "/Helpers/CouchbaseTestCase.php";

class AnalyticsTest extends Helpers\CouchbaseTestCase
{
    private ClusterInterface $cluster;

    public function setUp(): void
    {
        parent::setUp();
        $this->skipIfProtostellar();

        $this->cluster = $this->connectCluster();
    }

    public function maybeCreateAnalyticsIndex(string $bucketName, string $scopeName = "_default", string $collectionName = "_default")
    {
        try {
            $this->cluster->analyticsQuery("ALTER COLLECTION `$bucketName`.`$scopeName`.`$collectionName` ENABLE ANALYTICS");
        } catch (DatasetExistsException $e) {
        } catch (Exception $e) {
            printf("Dataset setup failed %s", $e);
        }
    }

    public function testScopeAnalyticsQuery()
    {
        $this->skipIfCaves();
        $this->skipIfUnsupported($this->version()->supportsCollections());

        $bucketName = self::env()->bucketName();

        $this->maybeCreateAnalyticsIndex($bucketName);

        $id = $this->uniqueId();
        $bucket = $this->cluster->bucket($bucketName);
        $collection = $bucket->defaultCollection();
        $scope = $bucket->scope("_default");
        $collection->upsert($id, ["bar" => 42]);

        $options = AnalyticsOptions::build()
            ->scanConsistency(AnalyticsScanConsistency::REQUEST_PLUS)
            ->positionalParameters([$id]);

        $res = $scope->analyticsQuery("SELECT * FROM `_default` where meta().id = \$1", $options);
        $this->assertNotEmpty($res->rows());
        $this->assertEquals(42, $res->rows()[0]["_default"]['bar']);
    }

    public function testClusterAnalyticsQuery()
    {
        $this->skipIfCaves();
        $this->skipIfUnsupported($this->version()->supportsCollections());

        $bucketName = self::env()->bucketName();

        $this->maybeCreateAnalyticsIndex($bucketName);

        $id = $this->uniqueId();
        $bucket = $this->cluster->bucket($bucketName);
        $collection = $bucket->defaultCollection();
        $collection->upsert($id, ["bar" => 42]);

        $options = AnalyticsOptions::build()
            ->scanConsistency(AnalyticsScanConsistency::REQUEST_PLUS)
            ->positionalParameters([$id]);
        $res = $this->cluster->analyticsQuery("SELECT * FROM `$bucketName`.`_default`.`_default` where meta().id = \$1", $options);
        $this->assertNotEmpty($res->rows());
        $this->assertEquals(42, $res->rows()[0]["_default"]['bar']);
    }

    public function testRowsShapeAssociative()
    {
        $this->skipIfCaves();

        $opts = AnalyticsOptions::build()->transcoder(new JsonTranscoder(true));
        $result = $this->cluster->analyticsQuery("SELECT 'Hello, PHP!' AS message", $opts);
        $this->assertNotEmpty($result->rows());
        $row = $result->rows()[0];
        $this->assertIsArray($row);
        $this->assertEquals("Hello, PHP!", $row["message"]);
    }

    public function testRowsShapeNonAssociative()
    {
        $this->skipIfCaves();

        $opts = AnalyticsOptions::build()->transcoder(new JsonTranscoder(false));
        $result = $this->cluster->analyticsQuery("SELECT 'Hello, PHP!' AS message", $opts);
        $this->assertNotEmpty($result->rows());
        $row = $result->rows()[0];
        $this->assertIsNotArray($row);
        $this->assertEquals("Hello, PHP!", $row->message);
    }

    public function testRowsShapeDefault()
    {
        $this->skipIfCaves();

        $result = $this->cluster->analyticsQuery("SELECT 'Hello, PHP!' AS message");
        $this->assertNotEmpty($result->rows());
        $row = $result->rows()[0];
        $this->assertIsArray($row);
        $this->assertEquals("Hello, PHP!", $row["message"]);
    }
}
