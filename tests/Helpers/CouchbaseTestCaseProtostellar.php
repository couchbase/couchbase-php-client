<?php

namespace Helpers;

use Couchbase\BucketInterface;
use Couchbase\Cluster;
use Couchbase\ClusterInterface;
use Couchbase\ClusterOptions;
use Couchbase\CollectionInterface;
use Couchbase\Integration;
use Exception;
use PHPUnit\Framework\TestCase;

include_once __DIR__ . "/../../vendor/couchbase/couchbase/tests/Helpers/TestEnvironment.php";

class CouchbaseTestCaseProtostellar extends TestCase
{
    private static ?TestEnvironment $env = null;

    public static function env(): TestEnvironment
    {
        if (self::$env == null) {
            self::$env = new TestEnvironment();
        }
        return self::$env;
    }

    public function connectCluster(?ClusterOptions $options = null): ClusterInterface
    {
        Integration::enableProtostellar();
        if ($options == null) {
            $options = new ClusterOptions();
        }
        $options->authenticator(self::env()->buildPasswordAuthenticator());
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
                printf("%s(%s) returned exception, will retry: %s\n", $caller, $message, $ex->getMessage());
                $endException = $ex;
            }

            usleep($sleepMicros);
        }
        throw $endException;
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

    protected function version(): ServerVersion
    {
        if ($this->env()->version() != null) {
            return $this->env()->version();
        }
        $versionString = null;
        if ($this->env()->useCouchbase()) {
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
}
