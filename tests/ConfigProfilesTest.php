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

use Couchbase\ClusterOptions;
use Couchbase\ConfigProfile;
use Couchbase\ConfigProfiles;
use Couchbase\Exception\InvalidArgumentException;


include_once __DIR__ . "/Helpers/CouchbaseTestCase.php";

class ConfigProfilesTest extends Helpers\CouchbaseTestCase
{
    public function testDevelopmentProfile()
    {
        $options = new ClusterOptions();
        $options->applyProfile("wan_development");
        $this->assertEquals(20000, $options->getConnectTimeoutMilliseconds());
        $this->assertEquals(20000, $options->getKeyValueTimeoutMilliseconds());
        $this->assertEquals(20000, $options->getKeyValueDurableTimeoutMilliseconds());
        $this->assertEquals(120000, $options->getViewTimeoutMilliseconds());
        $this->assertEquals(120000, $options->getQueryTimeoutMilliseconds());
        $this->assertEquals(120000, $options->getAnalyticsTimeoutMilliseconds());
        $this->assertEquals(120000, $options->getSearchTimeoutMilliseconds());
        $this->assertEquals(120000, $options->getManagementTimeoutMilliseconds());
    }

    public function testUnregisteredProfile()
    {
        $options = new ClusterOptions();
        $this->expectException(InvalidArgumentException::class);
        $options->applyProfile("unregistered_profile");
    }

    public function testCustomProfile()
    {
        $testProfile = new class implements ConfigProfile
        {
            public function apply(ClusterOptions $options): void
            {
                $options->connectTimeout(40000);
                $options->analyticsTimeout(80000);
            }
        };
        $options = new ClusterOptions();
        ConfigProfiles::getInstance()->registerProfile("test_profile", $testProfile);
        $options->applyProfile("test_profile");
        $this->assertEquals(40000, $options->getConnectTimeoutMilliseconds());
        $this->assertEquals(80000, $options->getAnalyticsTimeoutMilliseconds());
    }
}
