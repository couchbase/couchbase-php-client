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
use Couchbase\ClusterOptions;
use Couchbase\Collection;
use PHPUnit\Framework\TestCase;
use Couchbase\Cluster;

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
}