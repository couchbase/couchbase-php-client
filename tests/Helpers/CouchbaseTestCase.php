<?php

/**
 * Copyright 2014-Present Couchbase, Inc.
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

namespace Helpers;

include_once __DIR__ . "/TestEnvironment.php";
include_once __DIR__ . "/../../Couchbase/autoload.php";

use Couchbase\Bucket;
use Couchbase\Cluster;
use Couchbase\ClusterOptions;
use Couchbase\Collection;

use Exception;
use PHPUnit\Framework\TestCase;

class CouchbaseTestCase extends TestCase
{
    private static ?TestEnvironment $env = null;

    public static function env(): TestEnvironment
    {
        if (self::$env == null) {
            self::$env = new TestEnvironment();
        }
        return self::$env;
    }

    public static function setUpBeforeClass(): void
    {
        self::env()->start();
    }

    public static function tearDownBeforeClass(): void
    {
        self::env()->stop();
    }

    public function connectCluster(?ClusterOptions $options = null): Cluster
    {
        if ($options == null) {
            $options = new ClusterOptions();
        }
        $options->authenticator(self::env()->buildPasswordAuthenticator());
        return new Cluster(self::env()->connectionString(), $options);
    }

    public function openBucket(string $name = null): Bucket
    {
        if ($name == null) {
            $name = self::env()->bucketName();
        }
        return $this->connectCluster()->bucket($name);
    }

    public function defaultCollection(string $bucketName = null): Collection
    {
        return $this->openBucket($bucketName)->defaultCollection();
    }

    public function uniqueId(string $prefix = null): string
    {
        if ($prefix != null) {
            return sprintf("%s_%s", $prefix, self::env()::randomId());
        }
        return self::env()::randomId();
    }

    public function skipIfCaves(): void
    {
        if (self::env()->useCaves()) {
            $caller = debug_backtrace()[1];
            $this->markTestSkipped(sprintf("%s::%s is not supported on CAVES", $caller["class"], $caller["function"]));
        }
    }

    public function skipIfCouchbase(): void
    {
        if (self::env()->useCaves()) {
            $caller = debug_backtrace()[1];
            $this->markTestSkipped(sprintf("%s::%s is not supported on Couchbase server (only for CAVES)", $caller["class"], $caller["function"]));
        }
    }

    /**
     * @throws Exception
     */
    public function retryFor(int $failAfterSecs, int $sleepMillis, callable $fn)
    {
        $deadline = time() + $failAfterSecs;
        $sleepMicros = $sleepMillis * 1000;

        $endException = null;
        while (time() <= $deadline) {
            try {
                return $fn();
            } catch (Exception $ex) {
                printf("Function returned exception, will retry: %s\n", $ex->getMessage());
                $endException = $ex;
            }

            usleep($sleepMicros);
        }

        throw $endException;
    }

    public function upsertDocs(Cluster $cluster, int $num, string $service)
    {
        $collection = $cluster->bucket($this->env()->bucketName())->defaultCollection();
        $idBase = $this->uniqueId();

        for ($i = 0; $i < $num; $i++) {
            $collection->upsert(
                sprintf("%d_%s_%d", $i, $idBase, $service),
                [
                    "answer" => 42,
                    "service" => $service,
                    "city" => "London",
                    "geo" => [
                        "accuracy" => "ROOFTOP",
                        "lat" => 37.7825,
                        "lon" => -122.393
                    ]
                ]
            );
        }
    }

    function wrapException($cb, $type = null, $code = null, $message = null)
    {
        $exOut = null;
        try {
            $cb();
        } catch (Exception $ex) {
            $exOut = $ex;
        }

        if ($type !== null) {
            $this->assertErrorType($type, $exOut);
        }
        if ($code !== null) {
            $this->assertErrorCode($code, $exOut);
        }
        if ($message !== null) {
            $this->assertErrorMessage($message, $exOut);
        }

        return $exOut;
    }

    function assertError($type, $code, $ex)
    {
        $this->assertErrorType($type, $ex);
        $this->assertErrorCode($code, $ex);
    }

    function assertErrorType($type, $ex)
    {
        $this->assertInstanceOf($type, $ex);
    }

    function assertErrorMessage($msg, $ex)
    {
        $this->assertMatchesRegularExpression($msg, $ex->getMessage());
    }

    function assertErrorCode($code, $ex)
    {
        $this->assertEquals($code, $ex->getCode(), "Exception code does not match: {$ex->getCode()} != {$code}, exception message: '{$ex->getMessage()}'");
    }
}
