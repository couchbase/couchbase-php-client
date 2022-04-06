<?php

use Couchbase\Cluster;
use Couchbase\GetOptions;
use Couchbase\JsonTranscoder;
use Couchbase\MutationState;
use Couchbase\QueryOptions;
use Couchbase\QueryScanConsistency;
use Couchbase\UpsertOptions;

include_once __DIR__ . "/Helpers/CouchbaseTestCase.php";

class QueryTest extends Helpers\CouchbaseTestCase
{
    private Cluster $cluster;

    public function setUp(): void
    {
        parent::setUp();

        $this->cluster = $this->connectCluster();
    }

    function maybeCreateIndex(string $nameSpace)
    {
        try {
            $this->cluster->query("CREATE PRIMARY INDEX ON $nameSpace;");
        } catch (Exception $e) {
        }
    }

    function nameSpace(string $bucketName, string $scopeName = "_default", string $collectionName = "_default"): string
    {
        return "`$bucketName`.`$scopeName`.`$collectionName`";
    }

    function testResponseProperties()
    {
        $this->skipIfCaves();

        $bucketName = $this->env()->bucketName();
        $nameSpace = $this->nameSpace($bucketName);
        $this->maybeCreateIndex($nameSpace);

        $key = $this->uniqueId('a');
        $collection = $this->cluster->bucket($bucketName)->defaultCollection();
        $collection->upsert($key, ["bar" => 42]);

        $options = (new QueryOptions())->scanConsistency(QueryScanConsistency::REQUEST_PLUS);
        $res = $this->cluster->query("SELECT * FROM $nameSpace USE KEYS \"$key\"", $options);

        $meta = $res->metaData();
        $this->assertNotEmpty($meta);
        $this->assertEquals("success", $meta->status());
        $this->assertNotNull($meta->requestId());
        $this->assertNotNull($meta->metrics());
        $this->assertNotNull($meta->signature());
        $rows = $res->rows();
        $this->assertNotEmpty($rows);
        $this->assertEquals(42, $res->rows()[0][$collection->name()]['bar']);
    }

    function testParameters()
    {
        $this->skipIfCaves();

        $bucketName = $this->env()->bucketName();
        $nameSpace = $this->nameSpace($bucketName);
        $this->maybeCreateIndex($nameSpace);

        // USE KEYS doesn't like a number as first character.
        $key = $this->uniqueId('a');
        $collection = $this->cluster->bucket($bucketName)->defaultCollection();
        $collection->upsert($key, ["bar" => 42]);

        $options = (new QueryOptions())
            ->scanConsistency(QueryScanConsistency::REQUEST_PLUS)
            ->positionalParameters([$key]);
        $res = $this->cluster->query("SELECT * FROM $nameSpace USE KEYS \$1", $options);
        $this->assertNotEmpty($res->rows());
        $this->assertEquals(42, $res->rows()[0][$collection->name()]['bar']);

        $options = (new QueryOptions())
            ->scanConsistency(QueryScanConsistency::REQUEST_PLUS)
            ->namedParameters(["key" => $key]);
        $res = $this->cluster->query("SELECT * FROM $nameSpace USE KEYS \$key", $options);
        $this->assertNotEmpty($res->rows());
        $this->assertEquals(42, $res->rows()[0][$collection->name()]['bar']);

        // it will use PHP interpolation, and actually breaks query
        $this->wrapException(
            function () use ($bucketName, $key, $nameSpace) {
                $options = (new QueryOptions())
                    ->scanConsistency(QueryScanConsistency::REQUEST_PLUS)
                    ->namedParameters(["key" => $key]);
                $this->cluster->query("SELECT * FROM $nameSpace USE KEYS $key", $options);
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
        $nameSpace = $this->nameSpace($bucketName);
        $this->maybeCreateIndex($nameSpace);

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

        $collectionName = $collection->name();
        $options = (new QueryOptions())
            ->consistentWith($mutationState)
            ->positionalParameters(['Brass']);
        $result = $this->cluster->query("SELECT name, random, META($collectionName).id FROM $nameSpace WHERE \$1 IN name", $options);
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

        $result = $this->cluster->query("SELECT 'Hello, PHP!' AS message");
        $this->assertNotEmpty($result->rows());
        $row = $result->rows()[0];
        $this->assertIsArray($row);
        $this->assertEquals("Hello, PHP!", $row["message"]);
    }

    function testPreserveExpiry()
    {
        $this->skipIfCaves();

        $bucketName = $this->env()->bucketName();
        $nameSpace = $this->nameSpace($bucketName);
        $this->maybeCreateIndex($nameSpace);

        $key = $this->uniqueId('a');
        $collection = $this->cluster->bucket($bucketName)->defaultCollection();
        $collection->upsert($key, ["bar" => 42], (new UpsertOptions())->expiry(15));

        $this->cluster->query("UPDATE $nameSpace AS d USE KEYS \"$key\" SET d.bar = 45", (new QueryOptions())->preserveExpiry(true));

        $result = $collection->get($key, (new GetOptions())->withExpiry(true));
        $val = $result->content();
        $this->assertEquals(["bar" => 45], $val);
        $expiry = $result->expiryTime();
        $this->assertNotNull($expiry);
    }

    function testPrepared()
    {
        $this->skipIfCaves();

        $bucketName = $this->env()->bucketName();
        $nameSpace = $this->nameSpace($bucketName);
        $this->maybeCreateIndex($nameSpace);

        $key = $this->uniqueId('a');
        $collection = $this->cluster->bucket($bucketName)->defaultCollection();
        $collection->upsert($key, ["bar" => 42]);

        $options = (new QueryOptions())->scanConsistency(QueryScanConsistency::REQUEST_PLUS)->adhoc(false);
        $res = $this->cluster->query("SELECT * FROM $nameSpace USE KEYS \"$key\"", $options);

        $rows = $res->rows();
        $this->assertNotEmpty($rows);
        $this->assertEquals(42, $res->rows()[0][$collection->name()]['bar']);
    }

    function testScope()
    {
        $this->skipIfCaves();

        $bucketName = $this->env()->bucketName();
        $nameSpace = $this->nameSpace($bucketName);
        $this->maybeCreateIndex($nameSpace);

        $key = $this->uniqueId('a');
        $bucket = $this->cluster->bucket($bucketName);
        $collection = $bucket->defaultCollection();
        $collection->upsert($key, ["bar" => 42]);

        $options = (new QueryOptions())->scanConsistency(QueryScanConsistency::REQUEST_PLUS);
        $res = $bucket->scope("_default")->query("SELECT * FROM `_default` USE KEYS \"$key\"", $options);

        $meta = $res->metaData();
        $this->assertNotEmpty($meta);
        $this->assertEquals("success", $meta->status());
        $this->assertNotNull($meta->requestId());
        $this->assertNotNull($meta->metrics());
        $this->assertNotNull($meta->signature());
        $rows = $res->rows();
        $this->assertNotEmpty($rows);
        $this->assertEquals(42, $res->rows()[0][$collection->name()]['bar']);
    }
}
