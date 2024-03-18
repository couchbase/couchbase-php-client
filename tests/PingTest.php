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

use Couchbase\ServiceType;

include_once __DIR__ . '/Helpers/CouchbaseTestCase.php';

class PingTest extends Helpers\CouchbaseTestCase
{
    public function setUp(): void
    {
        parent::setUp();
        $this->skipIfProtostellar();
    }

    public function testClusterPingNoParams()
    {
        $cluster = $this->connectClusterUnique();
        $result = $cluster->ping();

        $this->assertNotEmpty($result['id']);
        $this->assertNotEmpty($result['sdk']);
        $this->assertEquals(2, $result['version']);
        $this->assertNotEmpty($result['services']);
        if (array_key_exists(ServiceType::ANALYTICS, $result['services'])) {
            $this->verifyService(ServiceType::ANALYTICS, $result['services']);
        }
        if (array_key_exists(ServiceType::SEARCH, $result['services'])) {
            $this->verifyService(ServiceType::SEARCH, $result['services']);
        }
        if (array_key_exists(ServiceType::QUERY, $result['services'])) {
            $this->verifyService(ServiceType::QUERY, $result['services']);
        }
        if (array_key_exists(ServiceType::VIEWS, $result['services'])) {
            $this->verifyService(ServiceType::VIEWS, $result['services']);
        }
        if (array_key_exists(ServiceType::EVENTING, $result['services'])) {
            $this->verifyService(ServiceType::EVENTING, $result['services']);
        }
        if (array_key_exists(ServiceType::MANAGEMENT, $result['services'])) {
            $this->verifyService(ServiceType::MANAGEMENT, $result['services']);
        }
        $this->verifyService(ServiceType::KEY_VALUE, $result['services']);
    }

    public function testBucketPingNoParams()
    {
        $cluster = $this->connectClusterUnique();
        $bucketName = $this->env()->bucketName();
        $bucket = $cluster->bucket($bucketName);
        $bucket->defaultCollection()->upsert($this->uniqueId("ping"), ['foo' => 'bar']);
        $result = $bucket->ping();

        $this->assertNotEmpty($result['id']);
        $this->assertNotEmpty($result['sdk']);
        $this->assertEquals(2, $result['version']);
        $this->assertNotEmpty($result['services']);
        $this->verifyService(ServiceType::KEY_VALUE, $result['services'], $bucketName);
    }

    public function testClusterPingReportId()
    {
        $cluster = $this->connectCluster();
        $result = $cluster->ping(null, 'myreport');

        $this->assertEquals('myreport', $result['id']);
    }

    public function testBucketPingReportId()
    {
        $cluster = $this->connectCluster();
        $bucketName = $this->env()->bucketName();
        $result = $cluster->bucket($bucketName)->ping(null, 'myreport');

        $this->assertEquals('myreport', $result['id']);
    }

    public function verifyService(string $service, $services, $bucketName = null)
    {
        $this->assertArrayHasKey($service, $services);
        $this->assertNotEmpty($services[$service]);
        $endpoint = $services[$service][0];
        $this->assertNotEmpty($endpoint['id']);
        $this->assertNotEmpty($endpoint['remote']);
        $this->assertNotEmpty($endpoint['local']);
        $this->assertIsInt($endpoint['latencyUs']);
        $this->assertContains($endpoint['state'], ['ok', 'error']);

        if ($bucketName == null) {
            $this->assertArrayNotHasKey('bucket', $endpoint);
        } else {
            $this->assertEquals($bucketName, $endpoint['bucket']);
        }
    }
}
