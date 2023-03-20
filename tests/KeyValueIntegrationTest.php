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
use Couchbase\DecrementOptions;
use Couchbase\Exception\DocumentNotFoundException;
use Couchbase\GetOptions;
use Couchbase\IncrementOptions;
use Couchbase\Integration;
use Couchbase\RawBinaryTranscoder;
use Couchbase\UpsertOptions;
use PHPUnit\Framework\TestCase;

final class KeyValueIntegrationTest extends TestCase
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
        $username = getenv("TEST_USERNAME") ?: "Administrator";
        $password = getenv("TEST_PASSWORD") ?: "password";
        $options->credentials($username, $password);
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

    public function testInsertAndGet(): void
    {
        $id = uniqid();

        $insertResult = $this->defaultCollection->insert($id, ["stringValue" => "Hello, World"]);

        $this->assertIsNumeric($insertResult->cas());
        $this->assertGreaterThan(0, $insertResult->cas());


        $this->assertGreaterThan(0, $insertResult->mutationToken()->partitionId());

        $this->assertIsNumeric($insertResult->mutationToken()->partitionId());
        $this->assertGreaterThan(0, $insertResult->mutationToken()->partitionUuid());

        $this->assertIsNumeric($insertResult->mutationToken()->partitionUuid());
        $this->assertGreaterThan(0, $insertResult->mutationToken()->sequenceNumber());

        $getResult = $this->defaultCollection->get($id);

        $this->assertEquals($getResult->cas(), $insertResult->cas());

        $getContent = $getResult->content();
        $this->assertIsArray($getContent);
        $this->assertEquals(["stringValue" => "Hello, World"], $getContent);
    }

    public function testUpsert(): void
    {
        $id = uniqid();


        $this->expectException(DocumentNotFoundException::class);
        $this->defaultCollection->get($id);

        $this->defaultCollection->upsert($id, ["hello" => "earth"]);
        $getRes = $this->defaultCollection->get($id);

        $this->assertEquals(["hello" => "earth"], $getRes->content());

        $this->defaultCollection->upsert($id, ["hello" => "mars"]);
        $getRes = $this->defaultCollection->get($id);

        $this->assertEquals(["hello" => "mars"], $getRes->content());
    }

    public function testReplace(): void
    {
        $id = uniqid();

        $res = $this->defaultCollection->insert($id, ["time" => "day"]);
        $originalCas = $res->cas();

        $res = $this->defaultCollection->replace($id, ["time" => "night"]);
        $replacedCas = $res->cas();
        $this->assertNotEquals($originalCas, $replacedCas);

        $res = $this->defaultCollection->get($id);
        $this->assertEquals($replacedCas, $res->cas());
        $this->assertEquals(["time" => "night"], $res->content());
    }

    public function testRemove(): void
    {
        $id = uniqid();

        $this->defaultCollection->insert($id, ["hello" => "world"]);

        $originalRes = $this->defaultCollection->get($id);

        $this->assertNotNull($originalRes);

        $this->defaultCollection->remove($id);

        $this->expectException(DocumentNotFoundException::class);
        $this->defaultCollection->get($id);
    }

    public function testAppend(): void
    {
        $id = uniqid();

        $res = $this->defaultCollection->upsert($id, "foo", UpsertOptions::build()->transcoder(RawBinaryTranscoder::getInstance()));
        $originalCas = $res->cas();

        $res = $this->defaultCollection->binary()->append($id, "bar");
        $appendedCas = $res->cas();
        $this->assertNotEquals($appendedCas, $originalCas);

        $res = $this->defaultCollection->get($id, GetOptions::build()->transcoder(RawBinaryTranscoder::getInstance()));
        $this->assertEquals($appendedCas, $res->cas());
        $this->assertEquals("foobar", $res->content());
    }

    public function testPrepend(): void
    {
        $id = uniqid();

        $res = $this->defaultCollection->upsert($id, "foo", UpsertOptions::build()->transcoder(RawBinaryTranscoder::getInstance()));
        $originalCas = $res->cas();

        $res = $this->defaultCollection->binary()->prepend($id, "bar");
        $prependedCas = $res->cas();
        $this->assertNotEquals($prependedCas, $originalCas);

        $res = $this->defaultCollection->get($id, GetOptions::build()->transcoder(RawBinaryTranscoder::getInstance()));
        $this->assertEquals($prependedCas, $res->cas());
        $this->assertEquals("barfoo", $res->content());
    }

    public function testIncrement(): void
    {
        $id = uniqid();

        $res = $this->defaultCollection->binary()->increment($id, IncrementOptions::build()->initial(42));
        $initialCas = $res->cas();
        $this->assertEquals(42, $res->content());

        $res = $this->defaultCollection->get($id);
        $this->assertEquals($initialCas, $res->cas());
        $this->assertEquals(42, $res->content());

        $res = $this->defaultCollection->binary()->increment($id);
        $incrementedCas = $res->cas();
        $this->assertEquals(43, $res->content());

        $res = $this->defaultCollection->get($id);
        $this->assertEquals($incrementedCas, $res->cas());
        $this->assertEquals(43, $res->content());
    }

    public function testDecrement(): void
    {
        $id = uniqid();

        $res = $this->defaultCollection->binary()->decrement($id, DecrementOptions::build()->initial(42));
        $initialCas = $res->cas();
        $this->assertEquals(42, $res->content());

        $res = $this->defaultCollection->get($id);
        $this->assertEquals($initialCas, $res->cas());
        $this->assertEquals(42, $res->content());

        $res = $this->defaultCollection->binary()->decrement($id);
        $decrementedCas = $res->cas();
        $this->assertEquals(41, $res->content());

        $res = $this->defaultCollection->get($id);
        $this->assertEquals($decrementedCas, $res->cas());
        $this->assertEquals(41, $res->content());
    }
}
