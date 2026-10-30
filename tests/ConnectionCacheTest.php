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
}
