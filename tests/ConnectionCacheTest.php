<?php

/**
 * Copyright 2014-Present Couchbase, Inc.
 *
 * Licensed under the Apache License, Version 2.0 (the 'License');
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *     http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an 'AS IS' BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */

declare(strict_types=1);

use Couchbase\Cluster;
use Couchbase\ClusterOptions;
use Couchbase\DurabilityLevel;
use Couchbase\Exception\AuthenticationFailureException;
use Couchbase\Management\Role;
use Couchbase\Management\User;
use Couchbase\PasswordAuthenticator;
use Couchbase\PrefixScan;
use Couchbase\UpsertOptions;

include_once __DIR__ . "/Helpers/CouchbaseTestCase.php";

class ConnectionCacheTest extends Helpers\CouchbaseTestCase
{
    private function uniqueConnectionString(): string
    {
        $connstr = self::env()->connectionString();
        return $connstr . (strpos($connstr, "?") === false ? "?" : "&") . $this->uniqueId() . "=" . $this->uniqueId();
    }

    private function options(): ClusterOptions
    {
        $options = new ClusterOptions();
        $options->authenticator(self::env()->buildPasswordAuthenticator());
        return $options;
    }

    /**
     * @return resource
     */
    private static function core(Cluster $cluster)
    {
        return (new ReflectionProperty(Cluster::class, "core"))->getValue($cluster);
    }

    public function testClustersWithDifferentOptionsDoNotShareConnection()
    {
        $this->skipIfProtostellar();

        $connstr = $this->uniqueConnectionString();
        $first = new Cluster($connstr, $this->options());
        $second = new Cluster($connstr, $this->options()->queryTimeout(42_000));

        $this->assertNotSame(self::core($first), self::core($second));
    }

    public function testIdenticalOptionsShareConnection()
    {
        $this->skipIfProtostellar();

        $connstr = $this->uniqueConnectionString();
        $first = new Cluster($connstr, $this->options()->queryTimeout(42_000)->keyValueTimeout(3_000));
        $second = new Cluster($connstr, $this->options()->keyValueTimeout(3_000)->queryTimeout(42_000));

        $this->assertSame(self::core($first), self::core($second));
    }

    public function testSetAuthenticatorRemovesConnectionFromCache()
    {
        $this->skipIfProtostellar();

        $connstr = $this->uniqueConnectionString();
        $invalidCredentials = new PasswordAuthenticator("wrong-username", "wrong-password");
        $cluster = new Cluster($connstr, $this->options());
        $cluster->setAuthenticator($invalidCredentials);

        $this->assertNotSame(self::core($cluster), self::core(new Cluster($connstr, $this->options())));

        $this->expectException(AuthenticationFailureException::class);
        new Cluster($connstr, $this->options()->authenticator($invalidCredentials));
    }

    private static function persistentConnections(): int
    {
        ob_start();
        phpinfo(INFO_MODULES);
        preg_match('/couchbase_persistent_connections => (\d+)/', ob_get_clean(), $matches);
        return (int)$matches[1];
    }

    public function testSetAuthenticatorDoesNotAccumulateDetachedConnections()
    {
        $this->skipIfProtostellar();

        $connstr = $this->uniqueConnectionString();
        $counts = [];
        for ($i = 0; $i < 10; $i++) {
            $cluster = new Cluster($connstr, $this->options());
            $counts[] = self::persistentConnections();
            $cluster->setAuthenticator(self::env()->buildPasswordAuthenticator());
            unset($cluster);
        }

        /*
         * The sweep during a request waits for the detached list to double, so one released
         * connection may still be open. Connections detached by earlier tests can be freed
         * meanwhile, so the count may also drop.
         */
        $this->assertLessThanOrEqual($counts[0] + 1, max($counts), implode(",", $counts));
    }

    /**
     * Each request leaves a detached connection referenced by a request global and by a released
     * Transactions object. Only the sweep after request shutdown can free it, so the count at the
     * start of every request served by the same worker must not grow.
     */
    public function testWorkerFreesDetachedConnectionsBetweenRequests()
    {
        $this->skipIfProtostellar();
        $this->skipIfCaves();
        $this->skipIfUnsupported($this->version()->supportsTransactions());

        $cgi = dirname(PHP_BINARY) . DIRECTORY_SEPARATOR . "php-cgi";
        $cmdline = @file_get_contents("/proc/self/cmdline");
        if (!is_executable($cgi) || $cmdline === false) {
            $this->markTestSkipped("Requires php-cgi next to " . PHP_BINARY . " and /proc/self/cmdline");
        }
        $args = explode("\0", rtrim($cmdline, "\0"));
        $defines = php_ini_loaded_file() === false ? [] : ["-c", php_ini_loaded_file()];
        for ($i = 1; $i < count($args) - 1; $i++) {
            if ($args[$i] === "-d") {
                $defines[] = "-d";
                $defines[] = $args[++$i];
            }
        }

        $script = tempnam(sys_get_temp_dir(), "couchbase-cgi");
        file_put_contents(
            $script,
            sprintf(
                <<<'PHP'
                <?php
                if (!extension_loaded("couchbase")) {
                    echo "no-couchbase\n";
                    return;
                }
                require_once %s;
                ob_start();
                phpinfo(INFO_MODULES);
                preg_match('/couchbase_persistent_connections\D*(\d+)/', ob_get_clean(), $matches);
                echo $matches[1], "\n";
                $authenticator = new Couchbase\PasswordAuthenticator(%s, %s);
                $options = (new Couchbase\ClusterOptions())->authenticator($authenticator);
                $kept = new Couchbase\Cluster(%s, $options);
                $transactions = $kept->transactions();
                $kept->setAuthenticator($authenticator);
                unset($transactions);
                echo "request-done\n";
                PHP,
                var_export(dirname((new ReflectionClass(Cluster::class))->getFileName()) . "/autoload.php", true),
                var_export(self::env()->username(), true),
                var_export(self::env()->password(), true),
                var_export($this->uniqueConnectionString(), true)
            )
        );
        try {
            $command = array_merge([$cgi, "-q", "-T", "3"], $defines, [$script]);
            exec(implode(" ", array_map("escapeshellarg", $command)) . " 2>&1", $lines, $status);
        } finally {
            unlink($script);
        }

        $output = implode("\n", $lines);
        if (strpos($output, "no-couchbase") !== false) {
            $this->markTestSkipped("php-cgi does not load the couchbase extension with this configuration");
        }
        /* a request that fails after printing its count, or a crash at shutdown, must not pass */
        $this->assertSame(0, $status, $output);
        $this->assertSame(3, substr_count($output, "request-done"), $output);
        preg_match_all('/^\d+$/m', $output, $counts);
        $this->assertSame(["0", "0", "0"], $counts[0], $output);
    }

    /**
     * Detached connections still in use when the last sweep ran must be freed by later lookups
     * once released, without waiting for the list to double or for the request to end.
     */
    public function testReleasedDetachedConnectionsAreFreedDuringRequest()
    {
        $this->skipIfProtostellar();

        $probe = $this->uniqueConnectionString();
        new Cluster($probe, $this->options());

        $detached = [];
        for ($i = 0; $i < 4; $i++) {
            $cluster = new Cluster($this->uniqueConnectionString(), $this->options());
            $cluster->setAuthenticator(self::env()->buildPasswordAuthenticator());
            $detached[] = $cluster;
        }
        $held = self::persistentConnections();

        unset($cluster);
        $detached = [];
        for ($i = 0; $i < 8; $i++) {
            new Cluster($probe, $this->options());
        }
        /* connections detached by earlier tests may be freed meanwhile too, so this is a bound */
        $this->assertGreaterThanOrEqual(4, $held - self::persistentConnections());
    }

    public function testSetAuthenticatorToCredentialsOfAnotherCachedConnection()
    {
        $this->skipIfProtostellar();
        $this->skipIfCaves();

        $username = $this->uniqueId("test");
        $users = $this->connectCluster()->users();
        $users->upsertUser(
            User::build()->setUsername($username)->setPassword("secret")
                ->setRoles([Role::build()->setName("bucket_full_access")->setBucket("*")])
        );
        try {
            $this->consistencyUtil()->waitUntilUserPresent($username);
            $connstr = $this->uniqueConnectionString();
            $credentials = new PasswordAuthenticator($username, "secret");
            $second = $this->retryFor(
                5,
                100,
                fn() => new Cluster($connstr, $this->options()->authenticator($credentials)),
                "connect using the new user"
            );
            $first = new Cluster($connstr, $this->options());
            $collection = $first->bucket(self::env()->bucketName())->defaultCollection();
            $first->setAuthenticator($credentials);

            $this->assertNotSame(self::core($first), self::core($second));
            $this->assertSame(
                self::core($second),
                self::core(new Cluster($connstr, $this->options()->authenticator($credentials)))
            );

            $id = $this->uniqueId();
            $collection->upsert($id, ["answer" => 42]);
            $this->assertEquals(["answer" => 42], $collection->get($id)->content());
        } finally {
            $users->dropUser($username);
        }
    }

    public function testSweepKeepsDetachedConnectionUsedByTransactions()
    {
        $this->skipIfProtostellar();
        $this->skipIfCaves();
        $this->skipIfUnsupported($this->version()->supportsTransactions());

        $connstr = $this->uniqueConnectionString();
        $cluster = new Cluster($connstr, $this->options());
        $id = $this->uniqueId();
        $cluster->bucket(self::env()->bucketName())->defaultCollection()->upsert($id, ["answer" => 42]);
        $transactions = $cluster->transactions();
        $cluster->setAuthenticator(self::env()->buildPasswordAuthenticator());
        unset($cluster);

        $collection = (new Cluster($connstr, $this->options()))->bucket(self::env()->bucketName())->defaultCollection();
        $transactions->run(fn($context) => $context->get($collection, $id));
        $this->addToAssertionCount(1);
    }

    public function testSweepKeepsDetachedConnectionUsedByScan()
    {
        $this->skipIfProtostellar();
        $this->skipIfCaves();
        $this->skipIfUnsupported($this->version()->supportsRangeScan());

        $id = $this->uniqueId();
        $this->retryFor(
            10,
            500,
            fn() => $this->defaultCollection()->upsert(
                $id,
                ["answer" => 42],
                UpsertOptions::build()->durabilityLevel(DurabilityLevel::MAJORITY_AND_PERSIST_TO_ACTIVE)
            ),
            "persist the document the scan must return"
        );

        $connstr = $this->uniqueConnectionString();
        $cluster = new Cluster($connstr, $this->options());
        $collection = $cluster->bucket(self::env()->bucketName())->defaultCollection();
        $scan = $collection->scan(new PrefixScan($id));
        $cluster->setAuthenticator(self::env()->buildPasswordAuthenticator());
        unset($cluster, $collection);

        new Cluster($connstr, $this->options());

        $this->assertCount(1, iterator_to_array($scan));
    }
}
