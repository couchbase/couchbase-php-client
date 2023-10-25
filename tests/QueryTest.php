<?php

use Couchbase\ClusterInterface;
use Couchbase\GetOptions;
use Couchbase\JsonTranscoder;
use Couchbase\MutationState;
use Couchbase\QueryOptions;
use Couchbase\QueryScanConsistency;
use Couchbase\UpsertOptions;

include_once __DIR__ . "/Helpers/CouchbaseTestCase.php";

class QueryTest extends Helpers\CouchbaseTestCase
{
    private ClusterInterface $cluster;

    public function setUp(): void
    {
        parent::setUp();

        $this->cluster = $this->connectCluster();
    }

    public function maybeCreateIndex(string $nameSpace)
    {
        try {
            $this->cluster->query("CREATE PRIMARY INDEX ON $nameSpace;");
        } catch (Exception $e) {
        }
    }

    public function nameSpace(string $bucketName, string $scopeName = "_default", string $collectionName = "_default"): string
    {
        return "`$bucketName`.`$scopeName`.`$collectionName`";
    }

    public function testResponseProperties()
    {
        $this->skipIfCaves();

        $bucketName = $this->env()->bucketName();
        $nameSpace = $bucketName;
        if ($this->version()->supportsCollections()) {
            $nameSpace = $this->nameSpace($bucketName);
        }
        $this->maybeCreateIndex($nameSpace);

        $key = $this->uniqueId('a');
        $collection = $this->cluster->bucket($bucketName)->defaultCollection();
        $collection->upsert($key, ["bar" => 42]);

        $collectionName = $bucketName;
        if ($this->version()->supportsCollections()) {
            $collectionName = $collection->name();
        }

        $options = QueryOptions::build()->scanConsistency(QueryScanConsistency::REQUEST_PLUS);
        $res = $this->cluster->query("SELECT * FROM $nameSpace USE KEYS \"$key\"", $options);

        $meta = $res->metaData();
        $this->assertNotEmpty($meta);
        $this->assertEquals("success", $meta->status());
        $this->assertNotNull($meta->requestId());
        $this->assertNotNull($meta->metrics());
        $this->assertNotNull($meta->signature());
        $rows = $res->rows();
        $this->assertNotEmpty($rows);
        $this->assertEquals(42, $res->rows()[0][$collectionName]['bar']);
    }

    public function testParameters()
    {
        $this->skipIfCaves();

        $bucketName = $this->env()->bucketName();
        $nameSpace = $bucketName;
        if ($this->version()->supportsCollections()) {
            $nameSpace = $this->nameSpace($bucketName);
        }
        $this->maybeCreateIndex($nameSpace);

        // USE KEYS doesn't like a number as first character.
        $key = $this->uniqueId('a');
        $collection = $this->cluster->bucket($bucketName)->defaultCollection();
        $collection->upsert($key, ["bar" => 42]);

        $collectionName = $bucketName;
        if ($this->version()->supportsCollections()) {
            $collectionName = $collection->name();
        }

        $options = QueryOptions::build()
            ->scanConsistency(QueryScanConsistency::REQUEST_PLUS)
            ->positionalParameters([$key]);
        $res = $this->cluster->query("SELECT * FROM $nameSpace USE KEYS \$1", $options);
        $this->assertNotEmpty($res->rows());
        $this->assertEquals(42, $res->rows()[0][$collectionName]['bar']);

        $options = QueryOptions::build()
            ->scanConsistency(QueryScanConsistency::REQUEST_PLUS)
            ->namedParameters(["key" => $key]);
        $res = $this->cluster->query("SELECT * FROM $nameSpace USE KEYS \$key", $options);
        $this->assertNotEmpty($res->rows());
        $this->assertEquals(42, $res->rows()[0][$collectionName]['bar']);

        // it will use PHP interpolation, and actually breaks query
        $this->wrapException(
            function () use ($bucketName, $key, $nameSpace) {
                $options = QueryOptions::build()
                    ->scanConsistency(QueryScanConsistency::REQUEST_PLUS)
                    ->namedParameters(["key" => $key]);
                $this->cluster->query("SELECT * FROM $nameSpace USE KEYS $key", $options);
            },
            null,
            null,
            '/Ambiguous reference to field/'
        );
    }

    public function testAtPlus()
    {
        $this->skipIfCaves();
        $this->skipIfProtostellar(); // ConsistentWith not supported in CNG

        $bucketName = $this->env()->bucketName();
        $nameSpace = $bucketName;
        if ($this->version()->supportsCollections()) {
            $nameSpace = $this->nameSpace($bucketName);
        }
        $this->maybeCreateIndex($nameSpace);

        $collection = $this->cluster->bucket($bucketName)->defaultCollection();
        $key = $this->uniqueId();
        $random = rand(0, 1000000);
        $result = $collection->upsert(
            $key,
            [
                "name" => ["Brass", "Doorknob"],
                "email" => "brass.doorknob@example.com",
                "random" => $random,
            ]
        );
        // construct mutation state from the list of mutation results
        $mutationState = new MutationState();
        $mutationState->add($result);

        $collectionName = $collection->name();
        $options = QueryOptions::build()
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

    public function testRowsShapeAssociative()
    {
        $this->skipIfCaves();

        $opts = QueryOptions::build()->transcoder(new JsonTranscoder(true));
        $result = $this->cluster->query("SELECT 'Hello, PHP!' AS message", $opts);
        $this->assertNotEmpty($result->rows());
        $row = $result->rows()[0];
        $this->assertIsArray($row);
        $this->assertEquals("Hello, PHP!", $row["message"]);
    }

    public function testRowsShapeNonAssociative()
    {
        $this->skipIfCaves();

        $opts = QueryOptions::build()->transcoder(new JsonTranscoder(false));
        $result = $this->cluster->query("SELECT 'Hello, PHP!' AS message", $opts);
        $this->assertNotEmpty($result->rows());
        $row = $result->rows()[0];
        $this->assertIsNotArray($row);
        $this->assertEquals("Hello, PHP!", $row->message);
    }

    public function testRowsShapeDefault()
    {
        $this->skipIfCaves();

        $result = $this->cluster->query("SELECT 'Hello, PHP!' AS message");
        $this->assertNotEmpty($result->rows());
        $row = $result->rows()[0];
        $this->assertIsArray($row);
        $this->assertEquals("Hello, PHP!", $row["message"]);
    }

    public function testPreserveExpiry()
    {
        $this->skipIfCaves();
        $this->skipIfUnsupported($this->version()->supportsPreserveExpiryForQuery());

        $bucketName = $this->env()->bucketName();
        $nameSpace = $this->nameSpace($bucketName);
        $this->maybeCreateIndex($nameSpace);

        $key = $this->uniqueId('a');
        $collection = $this->cluster->bucket($bucketName)->defaultCollection();
        $collection->upsert($key, ["bar" => 42], (new UpsertOptions())->expiry(15));

        $this->cluster->query(
            "UPDATE $nameSpace AS d USE KEYS \"$key\" SET d.bar = 45",
            QueryOptions::build()->preserveExpiry(true)
        );

        $result = $collection->get($key, (new GetOptions())->withExpiry(true));
        $val = $result->content();
        $this->assertEquals(["bar" => 45], $val);
        $expiry = $result->expiryTime();
        $this->assertNotNull($expiry);
    }

    public function testPrepared()
    {
        $this->skipIfCaves();

        $bucketName = $this->env()->bucketName();
        $nameSpace = $bucketName;
        if ($this->version()->supportsCollections()) {
            $nameSpace = $this->nameSpace($bucketName);
        }
        $collection = $this->cluster->bucket($bucketName)->defaultCollection();
        $this->maybeCreateIndex($nameSpace);

        $key = $this->uniqueId('a');
        $collection->upsert($key, ["bar" => 42]);

        $collectionName = $bucketName;
        if ($this->version()->supportsCollections()) {
            $collectionName = $collection->name();
        }

        $options = QueryOptions::build()
            ->scanConsistency(QueryScanConsistency::REQUEST_PLUS)
            ->adhoc(false);
        $res = $this->cluster->query("SELECT * FROM $nameSpace USE KEYS \"$key\"", $options);

        $rows = $res->rows();
        $this->assertNotEmpty($rows);
        $this->assertEquals(42, $res->rows()[0][$collectionName]['bar']);
    }

    public function testScope()
    {
        $this->skipIfCaves();
        $this->skipIfUnsupported($this->version()->supportsCollections());

        $bucketName = $this->env()->bucketName();
        $nameSpace = $this->nameSpace($bucketName);
        $this->maybeCreateIndex($nameSpace);

        $key = $this->uniqueId('a');
        $bucket = $this->cluster->bucket($bucketName);
        $collection = $bucket->defaultCollection();
        $collection->upsert($key, ["bar" => 42]);

        $options = QueryOptions::build()->scanConsistency(QueryScanConsistency::REQUEST_PLUS);
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
