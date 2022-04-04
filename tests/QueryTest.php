<?php

use Couchbase\Cluster;
use Couchbase\JsonTranscoder;
use Couchbase\MutationState;
use Couchbase\QueryOptions;
use Couchbase\QueryScanConsistency;

include_once __DIR__ . "/Helpers/CouchbaseTestCase.php";

class QueryTest extends Helpers\CouchbaseTestCase
{
    private Cluster $cluster;

    public function setUp(): void
    {
        parent::setUp();

        $this->cluster = $this->connectCluster();
    }

    function maybeCreateIndex(string $bucketName, string $scopeName = "_default", string $collectionName = "_default")
    {
        try {
            $this->cluster->query("CREATE PRIMARY INDEX ON `$bucketName`.`$scopeName`.`$collectionName`;");
        } catch (Exception $e) {
        }
    }

    function testResponseProperties()
    {
        $this->skipIfCaves();

        $bucketName = $this->env()->bucketName();
        $this->maybeCreateIndex($bucketName);

        $key = $this->uniqueId();
        $bucketName = $this->env()->bucketName();
        $collection = $this->cluster->bucket($bucketName)->defaultCollection();
        $collection->upsert($key, ["bar" => 42]);

        $options = (new QueryOptions())->scanConsistency(QueryScanConsistency::REQUEST_PLUS);
        $res = $this->cluster->query("SELECT * FROM `{$bucketName}` USE KEYS \"$key\"", $options);

        $meta = $res->metaData();
        $this->assertNotEmpty($meta);
        $this->assertEquals("success", $meta->status());
        $this->assertNotNull($meta->requestId());
        $this->assertNotNull($meta->metrics());
        $this->assertNotNull($meta->signature());
        $rows = $res->rows();
        $this->assertNotEmpty($rows);
        $this->assertEquals(42, $res->rows()[0][$bucketName]['bar']);
    }

    function testParameters()
    {
        $this->skipIfCaves();

        $bucketName = $this->env()->bucketName();
        $this->maybeCreateIndex($bucketName);

        $key = $this->uniqueId();
        $collection = $this->cluster->bucket($bucketName)->defaultCollection();
        $collection->upsert($key, ["bar" => 42]);

        $options = (new QueryOptions())
            ->scanConsistency(QueryScanConsistency::REQUEST_PLUS)
            ->positionalParameters([$key]);
        $res = $this->cluster->query("SELECT * FROM `$bucketName` USE KEYS \$1", $options);
        $this->assertNotEmpty($res->rows());
        $this->assertEquals(42, $res->rows()[0][$bucketName]['bar']);

        $options = (new QueryOptions())
            ->scanConsistency(QueryScanConsistency::REQUEST_PLUS)
            ->namedParameters(["key" => $key]);
        $res = $this->cluster->query("SELECT * FROM `$bucketName` USE KEYS \$key", $options);
        $this->assertNotEmpty($res->rows());
        $this->assertEquals(42, $res->rows()[0][$bucketName]['bar']);

        // it will use PHP interpolation, and actually breaks query
        $this->wrapException(
            function () use ($bucketName, $key) {
                $options = (new QueryOptions())
                ->scanConsistency(QueryScanConsistency::REQUEST_PLUS)
                ->namedParameters(["key" => $key]);
                $this->cluster->query("SELECT * FROM `$bucketName` USE KEYS $key", $options);
            },
            '\Couchbase\Exception\ParsingFailureException',
            8,
            '/Ambiguous reference to field/'
        );
    }

    function testAtPlus()
    {
        $this->skipIfCaves();

        $bucketName = $this->env()->bucketName();
        $this->maybeCreateIndex($bucketName);

        $collection = $this->cluster->bucket($bucketName)->defaultCollection();
        $key = $this->uniqueId();
        $random = rand(0, 1000000);
        $result = $collection->upsert(
            $key,
            [
            "name" => ["Brass", "Doorknob"],
            "email" => "brass.doorknob@example.com",
            "random" => $random
            ]
        );
        // construct mutation state from the list of mutation results
        $mutationState = new MutationState();
        $mutationState->add($result);

        $options = (new QueryOptions())
            ->consistentWith($mutationState)
            ->positionalParameters(['Brass']);
        $result = $this->cluster->query("SELECT name, random, META($bucketName).id FROM `$bucketName` WHERE \$1 IN name", $options);
        $found = false;
        foreach ($result->rows() as $row) {
            if ($row['random'] == $random) {
                $found = true;
            }
        }
        $this->assertTrue($found, "The record \"$key\" is missing in the result set");
    }

    function testRowsShapeAssociative()
    {
        $this->skipIfCaves();

        $bucketName = $this->env()->bucketName();
        $this->maybeCreateIndex($bucketName);

        $opts = new QueryOptions();
        $opts = $opts->transcoder(new JsonTranscoder(true));
        $result = $this->cluster->query("SELECT 'Hello, PHP!' AS message", $opts);
        $this->assertNotEmpty($result->rows());
        $row = $result->rows()[0];
        $this->assertIsArray($row);
        $this->assertEquals("Hello, PHP!", $row["message"]);
    }

    function testRowsShapeNonAssociative()
    {
        $this->skipIfCaves();

        $bucketName = $this->env()->bucketName();
        $this->maybeCreateIndex($bucketName);

        $opts = new QueryOptions();
        $opts = $opts->transcoder(new JsonTranscoder(false));
        $result = $this->cluster->query("SELECT 'Hello, PHP!' AS message", $opts);
        $this->assertNotEmpty($result->rows());
        $row = $result->rows()[0];
        $this->assertIsNotArray($row);
        $this->assertEquals("Hello, PHP!", $row->message);
    }

    function testRowsShapeDefault()
    {
        $this->skipIfCaves();

        $bucketName = $this->env()->bucketName();
        $this->maybeCreateIndex($bucketName);

        $result = $this->cluster->query("SELECT 'Hello, PHP!' AS message");
        $this->assertNotEmpty($result->rows());
        $row = $result->rows()[0];
        $this->assertIsArray($row);
        $this->assertEquals("Hello, PHP!", $row["message"]);
    }
}
