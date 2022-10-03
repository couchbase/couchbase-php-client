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
        $expectedOptions = new ClusterOptions();
        $expectedOptions->connectTimeout(20_000);
        $expectedOptions->keyValueTimeout(20_000);
        $expectedOptions->keyValueDurableTimeout(20_000);
        $expectedOptions->viewTimeout(120_000);
        $expectedOptions->queryTimeout(120_000);
        $expectedOptions->analyticsTimeout(120_000);
        $expectedOptions->searchTimeout(120_000);
        $expectedOptions->managementTimeout(120_000);
        $options = new ClusterOptions();
        $options->applyProfile("wan_development");
        $this->assertEquals($expectedOptions, $options);
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
        $expectedOptions = new ClusterOptions();
        $expectedOptions->connectTimeout(40_000);
        $expectedOptions->analyticsTimeout(80_000);
        ConfigProfiles::getInstance()->registerProfile("test_profile", $testProfile);
        $options = new ClusterOptions();
        $options->applyProfile("test_profile");
        $this->assertEquals($expectedOptions, $options);
    }
}
