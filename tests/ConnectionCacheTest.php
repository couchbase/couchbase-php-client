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
use Couchbase\Exception\CouchbaseException;
use Couchbase\ForkEvent;
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
     * Runs $body as $requests requests served by one php-cgi worker, with the ini settings of this
     * process plus $ini. The settings are the JSON array of PHP arguments in TEST_PHP_ARGS, which
     * bin/test.rb exports, or else the -d arguments of this process. $body sees $options and
     * connections(), the count of open persistent connections. Every request must complete and the
     * worker must exit with status 0. Returns every line of output that is a number.
     *
     * @param array<string, string> $ini
     * @param array<string, string> $env
     * @return string[]
     */
    private function runWorkerRequests(string $body, array $ini = [], int $requests = 3, array $env = []): array
    {
        /* Alpine names the binaries php85 and php-cgi85 */
        $cgi = dirname(PHP_BINARY) . DIRECTORY_SEPARATOR . (PHP_OS_FAMILY === "Windows" ? "php-cgi.exe" : "php-cgi");
        $versioned = dirname(PHP_BINARY) . DIRECTORY_SEPARATOR . "php-cgi" . substr(basename(PHP_BINARY), 3);
        if (!is_executable($cgi) && is_executable($versioned)) {
            $cgi = $versioned;
        }
        $args = json_decode((string)getenv("TEST_PHP_ARGS"), true);
        if (!is_array($args)) {
            $cmdline = @file_get_contents("/proc/self/cmdline");
            $args = $cmdline === false ? null : array_slice(explode("\0", rtrim($cmdline, "\0")), 1);
        }
        if (!is_executable($cgi) || $args === null) {
            $this->markTestSkipped("Requires php-cgi next to " . PHP_BINARY . " and TEST_PHP_ARGS or /proc/self/cmdline");
        }
        $defines = php_ini_loaded_file() === false ? [] : ["-c", php_ini_loaded_file()];
        for ($i = 0; $i < count($args); $i++) {
            if ($args[$i] === "-d" && $i + 1 < count($args)) {
                $defines[] = "-d";
                $defines[] = $args[++$i];
            } elseif (strncmp($args[$i], "-d", 2) === 0 && strlen($args[$i]) > 2) {
                $defines[] = "-d";
                $defines[] = substr($args[$i], 2);
            }
        }
        foreach ($ini as $name => $value) {
            $defines[] = "-d";
            $defines[] = "$name=$value";
        }

        $script = tempnam(sys_get_temp_dir(), "couchbase-cgi");
        $log = tempnam(sys_get_temp_dir(), "couchbase-cgi-output");
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
                function connections(): int
                {
                    ob_start();
                    phpinfo(INFO_MODULES);
                    preg_match('/couchbase_persistent_connections\D*(\d+)/', ob_get_clean(), $matches);
                    return (int)$matches[1];
                }
                $options = (new Couchbase\ClusterOptions())->authenticator(new Couchbase\PasswordAuthenticator(%s, %s));

                PHP,
                var_export(dirname((new ReflectionClass(Cluster::class))->getFileName()) . "/autoload.php", true),
                var_export(self::env()->username(), true),
                var_export(self::env()->password(), true)
            ) . $body . "\necho \"request-done\\n\";\n"
        );
        try {
            $command = array_merge([$cgi, "-q", "-T", (string)$requests], $defines, [$script]);
            /* output goes to a file, as proc_open() pipes cannot be polled on Windows */
            $worker = proc_open($command, [1 => ["file", $log, "w"], 2 => ["redirect", 1]], $pipes, null, array_merge(getenv(), $env));
            /* a worker hung in request shutdown or fork preparation fails the test instead of stalling it */
            $deadline = microtime(true) + 120;
            while (($info = proc_get_status($worker))["running"] && microtime(true) < $deadline) {
                usleep(100_000);
            }
            if ($info["running"]) {
                proc_terminate($worker, 9);
            }
            proc_close($worker);
            $output = (string)file_get_contents($log);
            $this->assertFalse($info["running"], "php-cgi worker did not exit within 120 seconds:\n" . $output);
            /* only the first proc_get_status() call after the exit reports the exit code */
            $status = $info["exitcode"];
        } finally {
            unlink($script);
            unlink($log);
        }

        if (strpos($output, "no-couchbase") !== false) {
            $this->markTestSkipped("php-cgi does not load the couchbase extension with this configuration");
        }
        /* a request that fails after printing its count, or a crash at shutdown, must not pass */
        $this->assertSame(0, $status, $output);
        $this->assertSame($requests, substr_count($output, "request-done"), $output);
        preg_match_all('/^\d+$/m', $output, $counts);
        return $counts[0];
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

        $body = sprintf(
            <<<'PHP'
            echo connections(), "\n";
            $kept = new Couchbase\Cluster(%s, $options);
            $transactions = $kept->transactions();
            $kept->setAuthenticator(new Couchbase\PasswordAuthenticator(%s, %s));
            unset($transactions);
            PHP,
            var_export($this->uniqueConnectionString(), true),
            var_export(self::env()->username(), true),
            var_export(self::env()->password(), true)
        );

        $this->assertSame(["0", "0", "0"], $this->runWorkerRequests($body));
    }

    /**
     * Connections created over couchbase.max_persistent while every other one is in use must be
     * reclaimed once those are released, at the next miss rather than at the end of the request.
     */
    public function testMaxPersistentReclaimsConnectionsReleasedOverLimit()
    {
        $this->skipIfProtostellar();

        $body = sprintf(
            <<<'PHP'
            $first = new Couchbase\Cluster(%s, $options);
            $second = new Couchbase\Cluster(%s, $options);
            $third = new Couchbase\Cluster(%s, $options);
            echo connections(), "\n";
            unset($first, $second);
            $fourth = new Couchbase\Cluster(%s, $options);
            echo connections(), "\n";
            PHP,
            var_export($this->uniqueConnectionString(), true),
            var_export($this->uniqueConnectionString(), true),
            var_export($this->uniqueConnectionString(), true),
            var_export($this->uniqueConnectionString(), true)
        );

        $this->assertSame(
            ["3", "2"],
            $this->runWorkerRequests($body, ["couchbase.max_persistent" => "2", "couchbase.persistent_timeout" => "-1"], 1)
        );
    }

    /**
     * An idle connection is kept across requests while it has been unused for less than
     * couchbase.persistent_timeout.
     */
    public function testPersistentTimeoutKeepsRecentlyUsedConnection()
    {
        $this->skipIfProtostellar();

        $body = sprintf(
            <<<'PHP'
            echo connections(), "\n";
            $cluster = new Couchbase\Cluster(%s, $options);
            PHP,
            var_export($this->uniqueConnectionString(), true)
        );

        $this->assertSame(
            ["0", "1", "1"],
            $this->runWorkerRequests($body, ["couchbase.max_persistent" => "-1", "couchbase.persistent_timeout" => "600000"])
        );
    }

    /**
     * With both settings at 0, as MIGRATION_FROM_3_x.md documents, every request starts without
     * cached connections and bootstraps a new one that works.
     */
    public function testZeroLimitAndTimeoutBootstrapEveryRequest()
    {
        $this->skipIfProtostellar();

        $body = sprintf(
            <<<'PHP'
            echo connections(), "\n";
            $cluster = new Couchbase\Cluster(%s, $options);
            $cluster->bucket(%s)->defaultCollection()->upsert(%s, ["answer" => 42]);
            echo connections(), "\n";
            PHP,
            var_export($this->uniqueConnectionString(), true),
            var_export(self::env()->bucketName(), true),
            var_export($this->uniqueId(), true)
        );

        $this->assertSame(
            ["0", "1", "0", "1", "0", "1"],
            $this->runWorkerRequests($body, ["couchbase.max_persistent" => "0", "couchbase.persistent_timeout" => "0"])
        );
    }

    /**
     * A detached connection whose transactions are still being torn down on a background thread
     * must survive a sweep during the request, although no PHP value references it any more.
     */
    public function testSweepKeepsDetachedConnectionDuringNativeTeardown()
    {
        $this->skipIfProtostellar();
        $this->skipIfCaves();
        $this->skipIfUnsupported($this->version()->supportsTransactions());

        $body = sprintf(
            <<<'PHP'
            $detached = new Couchbase\Cluster(%s, $options);
            $transactions = $detached->transactions();
            $detached->setAuthenticator(new Couchbase\PasswordAuthenticator(%s, %s));
            unset($detached, $transactions);
            $probe = new Couchbase\Cluster(%s, $options);
            echo connections(), "\n";
            PHP,
            var_export($this->uniqueConnectionString(), true),
            var_export(self::env()->username(), true),
            var_export(self::env()->password(), true),
            var_export($this->uniqueConnectionString(), true)
        );

        $this->assertSame(
            ["2"],
            $this->runWorkerRequests($body, [], 1, ["COUCHBASE_TEST_TEARDOWN_DELAY_MS" => "2000"])
        );
    }

    /**
     * An attached connection past couchbase.persistent_timeout whose transactions are still being
     * torn down at request end must be freed by the pass after the request, not left to the next.
     */
    public function testRequestEndFreesExpiredConnectionAfterTeardown()
    {
        $this->skipIfProtostellar();
        $this->skipIfCaves();
        $this->skipIfUnsupported($this->version()->supportsTransactions());

        $body = sprintf(
            <<<'PHP'
            echo connections(), "\n";
            $cluster = new Couchbase\Cluster(%s, $options);
            $transactions = $cluster->transactions();
            unset($transactions);
            PHP,
            var_export($this->uniqueConnectionString(), true)
        );

        $this->assertSame(
            ["0", "0"],
            $this->runWorkerRequests(
                $body,
                ["couchbase.max_persistent" => "-1", "couchbase.persistent_timeout" => "0"],
                2,
                ["COUCHBASE_TEST_TEARDOWN_DELAY_MS" => "2000"]
            )
        );
    }

    /**
     * A child inherits the dependency tokens of teardown threads, but not the threads, so fork
     * preparation must not return while a teardown is in progress.
     */
    public function testForkPreparationWaitsForTeardown()
    {
        $this->skipIfProtostellar();
        $this->skipIfCaves();
        $this->skipIfUnsupported($this->version()->supportsTransactions());

        $body = sprintf(
            <<<'PHP'
            $cluster = new Couchbase\Cluster(%s, $options);
            $transactions = $cluster->transactions();
            unset($transactions);
            $start = microtime(true);
            Couchbase\Cluster::notifyFork(Couchbase\ForkEvent::PREPARE);
            echo (int)((microtime(true) - $start) * 1000), "\n";
            Couchbase\Cluster::notifyFork(Couchbase\ForkEvent::PARENT);
            PHP,
            var_export($this->uniqueConnectionString(), true)
        );

        [$elapsed] = $this->runWorkerRequests($body, [], 1, ["COUCHBASE_TEST_TEARDOWN_DELAY_MS" => "2000"]);
        $this->assertGreaterThanOrEqual(1500, (int)$elapsed);
    }

    /**
     * Of two idle connections at couchbase.max_persistent, the one used least recently is freed,
     * not the one created first or used last.
     */
    public function testMaxPersistentEvictsTheConnectionUsedLongestAgo()
    {
        $this->skipIfProtostellar();
        $this->skipIfCaves();

        $body = sprintf(
            <<<'PHP'
            /* the KV session a connection bootstraps with identifies it for as long as it lives */
            $id = fn($cluster) => $cluster->diagnostics()["services"]["kv"][0]["id"] ?? "";
            $first = new Couchbase\Cluster(%1$s, $options);
            $firstId = $id($first);
            unset($first);
            $second = new Couchbase\Cluster(%2$s, $options);
            unset($second);
            $first = new Couchbase\Cluster(%1$s, $options);
            unset($first);
            $third = new Couchbase\Cluster(%3$s, $options);
            echo connections(), "\n";
            echo $firstId !== "" && $id(new Couchbase\Cluster(%1$s, $options)) === $firstId ? 1 : 0, "\n";
            PHP,
            var_export($this->uniqueConnectionString(), true),
            var_export($this->uniqueConnectionString(), true),
            var_export($this->uniqueConnectionString(), true)
        );

        $this->assertSame(
            ["2", "1"],
            $this->runWorkerRequests($body, ["couchbase.max_persistent" => "2", "couchbase.persistent_timeout" => "-1"], 1)
        );
    }

    /**
     * The pass after a request frees neither an attached connection with the default settings nor
     * one used less than couchbase.persistent_timeout ago, so a teardown still in progress on it
     * must not keep the worker from serving the next request.
     */
    public function testRequestEndDoesNotWaitForTeardownWithNothingToFree()
    {
        $this->skipIfProtostellar();
        $this->skipIfCaves();
        $this->skipIfUnsupported($this->version()->supportsTransactions());

        $body = sprintf(
            <<<'PHP'
            echo (int)(microtime(true) * 1000), "\n";
            $cluster = new Couchbase\Cluster(%s, $options);
            $transactions = $cluster->transactions();
            unset($transactions);
            echo connections(), "\n";
            echo (int)(microtime(true) * 1000), "\n";
            PHP,
            var_export($this->uniqueConnectionString(), true)
        );

        foreach (["-1", "600000"] as $timeout) {
            [, $firstCount, $firstEnd, $secondStart, $secondCount] = $this->runWorkerRequests(
                $body,
                ["couchbase.max_persistent" => "-1", "couchbase.persistent_timeout" => $timeout],
                2,
                ["COUCHBASE_TEST_TEARDOWN_DELAY_MS" => "5000"]
            );
            $this->assertSame(["1", "1"], [$firstCount, $secondCount]);
            /* only the gap between the requests includes the pass after the first one */
            $this->assertLessThan(2500, (int)$secondStart - (int)$firstEnd, "persistent_timeout=$timeout");
        }
    }

    /**
     * With couchbase.max_persistent=1 and no timeout, the connection used last stays cached
     * across requests, and connecting elsewhere frees the idle one to make room.
     */
    public function testMaxPersistentOfOneKeepsOneConnectionAcrossRequests()
    {
        $this->skipIfProtostellar();

        $body = sprintf(
            <<<'PHP'
            echo connections(), "\n";
            $first = new Couchbase\Cluster(%s, $options);
            unset($first);
            $second = new Couchbase\Cluster(%s, $options);
            echo connections(), "\n";
            PHP,
            var_export($this->uniqueConnectionString(), true),
            var_export($this->uniqueConnectionString(), true)
        );

        $this->assertSame(
            ["0", "1", "1", "1", "1", "1"],
            $this->runWorkerRequests($body, ["couchbase.max_persistent" => "1", "couchbase.persistent_timeout" => "-1"])
        );
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

    public function testDisconnectClosesConnectionForEveryHolder()
    {
        $this->skipIfProtostellar();

        $connstr = $this->uniqueConnectionString();
        $cluster = new Cluster($connstr, $this->options());
        $collection = $cluster->bucket(self::env()->bucketName())->defaultCollection();
        $sharing = new Cluster($connstr, $this->options());
        $id = $this->uniqueId();
        $collection->upsert($id, ["answer" => 42]);

        $cluster->disconnect();

        foreach ([fn() => $collection->get($id), fn() => $sharing->ping(), fn() => $cluster->ping()] as $operation) {
            try {
                $operation();
                $this->fail("operation on a disconnected cluster must throw");
            } catch (CouchbaseException $e) {
                $this->assertStringContainsString("Cluster::disconnect()", $e->getMessage());
            }
        }

        $cluster->disconnect();
        Cluster::notifyFork(ForkEvent::PREPARE);
        Cluster::notifyFork(ForkEvent::PARENT);

        $reconnected = new Cluster($connstr, $this->options());
        $this->assertNotSame(self::core($cluster), self::core($reconnected));
        $this->assertEquals(
            ["answer" => 42],
            $reconnected->bucket(self::env()->bucketName())->defaultCollection()->get($id)->content()
        );
    }

    public function testDisconnectAfterSetAuthenticatorKeepsCachedConnection()
    {
        $this->skipIfProtostellar();

        $connstr = $this->uniqueConnectionString();
        $detached = new Cluster($connstr, $this->options());
        $collection = $detached->bucket(self::env()->bucketName())->defaultCollection();
        $detached->setAuthenticator(self::env()->buildPasswordAuthenticator());
        $cached = new Cluster($connstr, $this->options());
        $this->assertNotSame(self::core($detached), self::core($cached));

        $detached->disconnect();

        try {
            $collection->upsert($this->uniqueId(), ["answer" => 42]);
            $this->fail("operation on a disconnected cluster must throw");
        } catch (CouchbaseException $e) {
            $this->assertStringContainsString("Cluster::disconnect()", $e->getMessage());
        }
        $id = $this->uniqueId();
        $cached->bucket(self::env()->bucketName())->defaultCollection()->upsert($id, ["answer" => 42]);
        $this->assertSame(self::core($cached), self::core(new Cluster($connstr, $this->options())));
    }

    public function testDisconnectFailsScanStartedBefore()
    {
        $this->skipIfProtostellar();
        $this->skipIfCaves();
        $this->skipIfUnsupported($this->version()->supportsRangeScan());

        $cluster = new Cluster($this->uniqueConnectionString(), $this->options());
        $collection = $cluster->bucket(self::env()->bucketName())->defaultCollection();
        $prefix = $this->uniqueId();
        $collection->upsert($prefix . "-0", ["answer" => 42]);
        $scan = $collection->scan(new PrefixScan($prefix));

        $cluster->disconnect();

        $this->expectException(CouchbaseException::class);
        $this->expectExceptionMessage("cluster_closed");
        iterator_to_array($scan);
    }

    public function testDisconnectFailsTransactionsCreatedBefore()
    {
        $this->skipIfProtostellar();
        $this->skipIfCaves();
        $this->skipIfUnsupported($this->version()->supportsTransactions());

        $cluster = new Cluster($this->uniqueConnectionString(), $this->options());
        $collection = $cluster->bucket(self::env()->bucketName())->defaultCollection();
        $id = $this->uniqueId();
        $collection->upsert($id, ["answer" => 42]);
        $transactions = $cluster->transactions();

        $cluster->disconnect();
        /* lets the io_context stop, so a call that posted work there would hang */
        usleep(500_000);

        $this->expectException(CouchbaseException::class);
        $this->expectExceptionMessage("cluster_closed");
        $transactions->run(fn($context) => $context->get($collection, $id));
    }

    public function testDisconnectInsideTransactionFailsRun()
    {
        $this->skipIfProtostellar();
        $this->skipIfCaves();
        $this->skipIfUnsupported($this->version()->supportsTransactions());

        $cluster = new Cluster($this->uniqueConnectionString(), $this->options());
        $collection = $cluster->bucket(self::env()->bucketName())->defaultCollection();
        $id = $this->uniqueId();
        $collection->upsert($id, ["answer" => 42]);

        $this->expectException(CouchbaseException::class);
        $this->expectExceptionMessage("cluster_closed");
        $cluster->transactions()->run(
            function ($context) use ($cluster, $collection, $id) {
                $context->get($collection, $id);
                $cluster->disconnect();
            }
        );
    }

    public function testForkAfterDisconnect()
    {
        $this->skipIfProtostellar();
        if (!extension_loaded("pcntl")) {
            $this->markTestSkipped("The 'pcntl' extension is required to fork");
        }

        $cluster = new Cluster($this->uniqueConnectionString(), $this->options());
        $cluster->disconnect();
        $connstr = $this->uniqueConnectionString();
        $id = $this->uniqueId();

        Cluster::notifyFork(ForkEvent::PREPARE);
        $pid = pcntl_fork();
        if ($pid == 0) {
            Cluster::notifyFork(ForkEvent::CHILD);
            /* the disconnected cluster stays closed in the child, and a new one works there */
            try {
                $cluster->ping();
                exit(2);
            } catch (CouchbaseException $e) {
                if (strpos($e->getMessage(), "Cluster::disconnect()") === false) {
                    exit(3);
                }
            }
            try {
                (new Cluster($connstr, $this->options()))
                    ->bucket(self::env()->bucketName())
                    ->defaultCollection()
                    ->upsert($id, ["answer" => 42]);
            } catch (Throwable $e) {
                exit(4);
            }
            exit(0);
        }
        Cluster::notifyFork(ForkEvent::PARENT);
        $this->assertGreaterThan(0, $pid);

        $deadline = time() + 30;
        while (($exited = pcntl_waitpid($pid, $status, WNOHANG)) == 0 && time() < $deadline) {
            usleep(100_000);
        }
        if ($exited == 0) {
            exec("kill -9 " . $pid);
            pcntl_waitpid($pid, $status);
        }
        $this->assertSame($pid, $exited, "the child did not exit within 30 seconds");
        $this->assertTrue(pcntl_wifexited($status));
        $this->assertSame(0, pcntl_wexitstatus($status));
    }
}
