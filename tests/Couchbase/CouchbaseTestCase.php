<?php

declare(strict_types=1);

/*
 *   Copyright 2020-2021 Couchbase, Inc.
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

namespace Couchbase\Tests;

use Couchbase\ClusterOptions;
use Couchbase\Cluster;
use Couchbase\Collection;
use PHPUnit\Framework\TestCase;

class CouchbaseTestCase extends TestCase
{
    protected Collection $collection;

    public function setUp(): void
    {
        $connectionString = getenv("TEST_CONNECTION_STRING") ?: "couchbase://127.0.0.1";
        $username = getenv("TEST_USERNAME") ?: "Administrator";
        $password = getenv("TEST_PASSWORD") ?: "password";
        $bucket = getenv("TEST_BUCKET") ?: "default";

        $options = new ClusterOptions();
        $options->credentials($username, $password);
        $cluster = new Cluster($connectionString, $options);
        $bucket = $cluster->bucket($bucket);
        $this->collection = $bucket->defaultCollection();
    }

    /**
     * Generates unique identifier.
     * @param string $prefix
     * @return string unique identifier
     */
    public function uniqueId(string $prefix): string
    {
        return sprintf("%s_%d", $prefix, rand());
    }
}
