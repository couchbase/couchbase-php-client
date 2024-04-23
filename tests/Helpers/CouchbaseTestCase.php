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

$defaultPath = __DIR__ . "/../../Couchbase/autoload.php";
if (file_exists($defaultPath)) {
    include_once $defaultPath;
} else {
    $possibleDirs = array_merge(
        glob(__DIR__ . '/../../couchbase-*/'),
        glob(__DIR__ . '/../../couchbase-*/couchbase-*/')
    );
    foreach ($possibleDirs as $dir) {
        $autoloadPath = $dir . 'Couchbase/autoload.php';
        if (file_exists($autoloadPath)) {
            include_once $autoloadPath;
            break;
        }
    }
}

use Couchbase\BucketInterface;
use Couchbase\Cluster;
use Couchbase\ClusterInterface;
use Couchbase\ClusterOptions;

use Couchbase\CollectionInterface;
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

    public function connectCluster(?ClusterOptions $options = null): ClusterInterface
    {
        if ($options == null) {
            $options = new ClusterOptions();
        }
        $options->authenticator(self::env()->buildPasswordAuthenticator());
        if (getenv("TEST_USE_WAN_DEVELOPMENT_PROFILE") == "true") {
            $options->applyProfile("wan_development");
        }
        return Cluster::connect(self::env()->connectionString(), $options);
    }

    public function connectClusterUnique(?ClusterOptions $options = null): ClusterInterface
    {
        if ($options == null) {
            $options = new ClusterOptions();
        }
        $options->authenticator(self::env()->buildPasswordAuthenticator());
        $connstr = self::env()->connectionString();
        if (strpos($connstr, "?") !== false) {
            $connstr .= "&";
        } else {
            $connstr .= "?";
        }
        $connstr .= $this->uniqueId() . "=" . $this->uniqueId();
        return Cluster::connect($connstr, $options);
    }

    public function openBucket(string $name = null): BucketInterface
    {
        if ($name == null) {
            $name = self::env()->bucketName();
        }
        return $this->connectCluster()->bucket($name);
    }

    public function defaultCollection(string $bucketName = null): CollectionInterface
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

    public function skipIfProtostellar(): void
    {
        if ($this->isProtostellar()) {
            $this->markTestSkipped(sprintf("Test is not supported on Protostellar"));
        }
    }

    public function isProtostellar(): bool
    {
        if (
            preg_match("/^protostellar:\/\//", self::env()->connectionString()) ||
            preg_match("/^couchbase2:\/\//", self::env()->connectionString())
        ) {
            return true;
        }
        return false;
    }

    public function skipIfCouchbase(): void
    {
        if (self::env()->useCaves()) {
            $caller = debug_backtrace()[1];
            $this->markTestSkipped(
                sprintf(
                    "%s::%s is not supported on Couchbase server (only for CAVES)",
                    $caller["class"],
                    $caller["function"]
                )
            );
        }
    }

    public function skipIfUnsupported(bool $supported): void
    {
        if (!$supported) {
            $caller = debug_backtrace()[1];
            $this->markTestSkipped(
                sprintf(
                    "%s::%s is not supported on Couchbase server %s",
                    $caller["class"],
                    $caller["function"],
                    $this->env()->version()
                )
            );
        }
    }

    public function skipIfReplicasAreNotConfigured(): void
    {
        if ($this->isProtostellar()) {
            return;
        }
        if (!$this->connectCluster()->replicasConfiguredFor($this->env()->bucketName())) {
            $caller = debug_backtrace()[1];
            $this->markTestSkipped(
                sprintf(
                    "%s::%s is not supported on %s, because replicas are not properly configured",
                    $caller["class"],
                    $caller["function"],
                    $this->env()->useCouchbase() ? "Couchbase Server " . $this->version() : "CAVES"
                )
            );
        }
    }

    /**
     * @throws Exception
     */
    public function retryFor(int $failAfterSecs, int $sleepMillis, callable $fn, $message = null)
    {
        $caller = debug_backtrace()[1]['function'];
        $deadline = time() + $failAfterSecs;
        $sleepMicros = $sleepMillis * 1000;

        $endException = null;
        while (time() <= $deadline) {
            try {
                return $fn();
            } catch (Exception $ex) {
                fprintf(STDERR, "%s(%s) returned exception, will retry: %s\n", $caller, $message, $ex->getMessage());
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
                        "lon" => -122.393,
                    ],
                ]
            );
        }
    }

    protected function version(): ServerVersion
    {
        if ($this->env()->version() != null) {
            return $this->env()->version();
        }
        $versionString = null;
        if ($this->env()->useCouchbase() && !$this->env()->useProtostellar()) {
            $versionString = $this->connectCluster()->version($this->env()->bucketName());
        }
        if ($versionString == null) {
            $versionString = getenv("TEST_SERVER_VERSION") ?: "7.0";
        }

        return $this->env()->setVersion($versionString);
    }

    protected function wrapException($cb, $type = null, $code = null, $message = null): ?Exception
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

    protected function assertError($type, $code, $ex)
    {
        $this->assertErrorType($type, $ex);
        $this->assertErrorCode($code, $ex);
    }

    protected function assertErrorType($type, $ex)
    {
        $this->assertInstanceOf($type, $ex);
    }

    protected function assertErrorMessage($msg, $ex)
    {
        $this->assertMatchesRegularExpression($msg, $ex->getMessage());
    }

    protected function assertErrorCode($code, $ex)
    {
        $this->assertEquals(
            $code,
            $ex->getCode(),
            sprintf(
                "Exception code does not match: %d != %d, exception message: ''",
                $ex->getCode(),
                $code,
                $ex->getMessage()
            )
        );
    }

    protected function fixCavesTimeResolutionOnWindows() {
        if (PHP_OS_FAMILY === 'Windows' && self::env()->useCaves()) {
            usleep(1);
        }
    }
}
