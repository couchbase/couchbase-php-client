<?php

/*
 * Copyright 2022-Present Couchbase, Inc.
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *     http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */

declare(strict_types=1);

use Couchbase\BucketInterface;
use Couchbase\ClusterInterface;
use Couchbase\ClusterOptions;
use Couchbase\CollectionInterface;
use Couchbase\QueryOptions;
use Couchbase\QueryScanConsistency;
use Couchbase\Integration;
use PHPUnit\Framework\TestCase;

final class QueryIntegrationTest extends TestCase
{
    private const CONNECTION_STRING_ENV = "TEST_CONNECTION_STRING";
    private const BUCKET_NAME_ENV = "TEST_BUCKET";

    private const DEFAULT_CONNECTION_STRING = "localhost";
    private const DEFAULT_BUCKET_NAME = "default";

    private ClusterInterface $cluster;
    private CollectionInterface $defaultCollection;
    private BucketInterface $bucket;

    protected function setUp(): void
    {
        parent::setUp();
        Integration::enableProtostellar();
        $options = new ClusterOptions();
        $this->cluster = Couchbase\Cluster::connect(
            getenv(self::CONNECTION_STRING_ENV)
                ?: self::DEFAULT_CONNECTION_STRING,
            $options
        );
        $this->bucket = $this->cluster->bucket(getenv(self::BUCKET_NAME_ENV) ?: self::DEFAULT_BUCKET_NAME);
        $this->defaultCollection = $this->bucket->defaultCollection();
    }

    protected function tearDown(): void
    {
        $this->cluster->close();
        parent::tearDown();
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

    public function testRowsShapeDefault()
    {
        $result = $this->cluster->query("SELECT 'Hello, PHP!' AS message");
        $this->assertNotEmpty($result->rows());
        $row = $result->rows()[0];
        $this->assertIsArray($row);
        $this->assertEquals("Hello, PHP!", $row["message"]);
    }

    public function testResponseProperties()
    {
        $nameSpace = $this->nameSpace($this->bucket->name());
        $this->maybeCreateIndex($nameSpace);
        $key = uniqid();
        $this->defaultCollection->upsert($key, ["bar" => 42]);

        $options = QueryOptions::build()->scanConsistency(QueryScanConsistency::REQUEST_PLUS);
        $res = $this->cluster->query("SELECT * FROM $nameSpace USE KEYS \"$key\"", $options);

        $meta = $res->metaData();
        $this->assertNotEmpty($meta);
        $this->assertEquals("SUCCESS", $meta->status());
        $this->assertNotNull($meta->requestId());
        $this->assertNotNull($meta->metrics());
        $rows = $res->rows();
        $this->assertNotEmpty($rows);
        $this->assertEquals(42, $res->rows()[0][$this->defaultCollection->name()]['bar']);
    }


}
