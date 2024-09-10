<?php

use Couchbase\Exception\DatasetNotFoundException;
use Couchbase\Exception\DataverseNotFoundException;
use Couchbase\Exception\IndexNotFoundException;
use Couchbase\Exception\LinkNotFoundException;
use Couchbase\Management\AnalyticsIndexManager;
use Couchbase\Management\CreateAnalyticsDatasetOptions;
use Couchbase\Management\CreateAnalyticsDataverseOptions;
use Couchbase\Management\CreateAnalyticsIndexOptions;
use Couchbase\Management\DropAnalyticsDatasetOptions;
use Couchbase\Management\S3ExternalAnalyticsLink;

include_once __DIR__ . "/Helpers/CouchbaseTestCase.php";


class AnalyticsManagerTest extends Helpers\CouchbaseTestCase
{
    private AnalyticsIndexManager $manager;
    private string $indexName;
    private string $datasetName;
    private string $dataverseName;
    private string $linkName;

    public function setUp(): void
    {
        parent::setUp();

        $this->manager = $this->connectCluster()->analyticsIndexes();
        $this->indexName = $this->uniqueId("idx");
        $this->datasetName = $this->uniqueId("ds");
        $this->dataverseName = $this->uniqueId("dv");
        $this->linkName = $this->uniqueId("ln");
    }

    public function testAnalyticsIndexCreationWorkflow()
    {
        $this->skipIfProtostellar();
        $this->skipIfCaves();

        $this->retryFor(
            5,
            1000,
            function () {
                $this->manager->createDataverse($this->dataverseName);
            }
        );

        $this->manager->createDataset($this->datasetName, self::env()->bucketName(), CreateAnalyticsDatasetOptions::build()->dataverseName($this->dataverseName));

        $datasets = $this->manager->getAllDatasets();

        $exists = false;
        foreach ($datasets as $dataset) {
            if ($dataset->name() == $this->datasetName && $dataset->dataverseName() == $this->dataverseName) {
                $exists = true;
            }
        }
        $this->assertTrue($exists);

        $this->manager->createIndex($this->datasetName, $this->indexName, [ "name" => "string" ], CreateAnalyticsIndexOptions::build()->dataverseName($this->dataverseName));

        $indexes = $this->manager->getAllIndexes();

        $exists = false;
        foreach ($indexes as $index) {
            if ($index->name() == $this->indexName && $index->datasetName() == $this->datasetName && $index->dataverseName() == $this->dataverseName) {
                $exists = true;
            }
        }

        $this->assertTrue($exists);

        $this->manager->createIndex($this->datasetName, $this->indexName, [ "name" => "string"], CreateAnalyticsIndexOptions::build()->dataverseName($this->dataverseName)->ignoreIfExists(true));

        $this->manager->dropIndex($this->datasetName, $this->indexName, \Couchbase\Management\DropAnalyticsIndexOptions::build()->dataverseName($this->dataverseName));

        $indexes = $this->manager->getAllIndexes();

        $exists = false;
        foreach ($indexes as $index) {
            if ($index->name() == $this->indexName && $index->datasetName() == $this->datasetName && $index->dataverseName() == $this->dataverseName) {
                $exists = true;
            }
        }

        $this->assertFalse($exists);

        $this->manager->dropDataset($this->datasetName, DropAnalyticsDatasetOptions::build()->dataverseName($this->dataverseName));

        $datasets = $this->manager->getAllDatasets();

        $exists = false;
        foreach ($datasets as $dataset) {
            if ($dataset->name() == $this->datasetName && $dataset->dataverseName() == $this->dataverseName) {
                $exists = true;
            }
        }

        $this->assertFalse($exists);

        $this->manager->dropDataverse($this->dataverseName);
    }

    public function testLinkCreationWorkflow()
    {
        $this->skipIfProtostellar();
        $this->skipIfCaves();

        $this->retryFor(
            5,
            1000,
            function () {
                $this->manager->createDataverse($this->dataverseName, CreateAnalyticsDataverseOptions::build()->ignoreIfExists(true));
            }
        );

        $s3Link = S3ExternalAnalyticsLink::build($this->linkName, $this->dataverseName, "accessKeyId", "us-east-1", "secretAccessKey");

        $this->manager->createLink($s3Link);

        $links = $this->manager->getLinks();

        $found = false;

        foreach ($links as $link) {
            if ($link->dataverseName() == $this->dataverseName && $link->name() == $this->linkName && $link->linkType() == "s3") {
                $found = true;
            }
        }
        $this->assertTrue($found);

        $this->manager->dropLink($this->linkName, $this->dataverseName);

        $links = $this->manager->getLinks();

        $found = false;

        foreach ($links as $link) {
            if ($link->dataverseName() == $this->dataverseName && $link->name() == $this->linkName && $link->linkType() == "s3") {
                $found = true;
            }
        }
        $this->assertFalse($found);

        $this->expectException(LinkNotFoundException::class);
        $this->manager->replaceLink($s3Link);
    }

    public function testGetPendingMutations()
    {
        $this->skipIfProtostellar();
        $this->skipIfCaves();

        $this->retryFor(
            5,
            1000,
            function () {
                $this->manager->createDataverse($this->dataverseName, CreateAnalyticsDataverseOptions::build()->ignoreIfExists(true));
            }
        );

        $this->manager->createDataset("dataset1", self::env()->bucketName(), CreateAnalyticsDatasetOptions::build()->ignoreIfExists(true));
        $this->manager->createDataset("dataset2", self::env()->bucketName(), CreateAnalyticsDatasetOptions::build()->dataverseName($this->dataverseName)->ignoreIfExists(true));
        $this->manager->createDataset("dataset3", self::env()->bucketName(), CreateAnalyticsDatasetOptions::build()->dataverseName($this->dataverseName)->ignoreIfExists(true));

        $this->manager->connectLink();

        $res = $this->manager->getPendingMutations();

        $this->assertArrayHasKey("Default", $res);
        $this->assertArrayHasKey($this->dataverseName, $res);
        $this->assertIsArray($res[$this->dataverseName]);
        $this->assertGreaterThanOrEqual(2, count($res[$this->dataverseName]));
        $this->assertArrayHasKey("dataset2", $res[$this->dataverseName]);

        $this->manager->disconnectLink();
    }


    public function testFailDropMissingIndex()
    {
        $this->skipIfProtostellar();
        $this->skipIfCaves();

        $this->manager->createDataset($this->datasetName, self::env()->bucketName(), CreateAnalyticsDatasetOptions::build()->ignoreIfExists(true));

        $this->expectException(IndexNotFoundException::class);
        $this->manager->dropIndex($this->datasetName, "does-not-exist");
    }

    public function testFailDropMissingDataset()
    {
        $this->skipIfProtostellar();
        $this->skipIfCaves();

        $this->expectException(DatasetNotFoundException::class);
        $this->manager->dropDataset("does-not-exist");
    }

    public function testFailDropMissingDataverse()
    {
        $this->skipIfProtostellar();
        $this->skipIfCaves();

        $this->expectException(DataverseNotFoundException::class);
        $this->manager->dropDataverse("does-not-exist");
    }
}
