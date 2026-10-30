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
        for ($i = 0; $i < 3; $i++) {
            $cluster = new Cluster($connstr, $this->options());
            $counts[] = self::persistentConnections();
            $cluster->setAuthenticator(self::env()->buildPasswordAuthenticator());
            unset($cluster);
        }

        $this->assertSame(array_fill(0, 3, $counts[0]), $counts);
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
