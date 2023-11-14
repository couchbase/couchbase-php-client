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

use Helpers\ServerVersion;

include_once __DIR__ . "/Helpers/CouchbaseTestCase.php";

class ServerVersionTest extends Helpers\CouchbaseTestCase
{
    public function testSimpleServerVersion()
    {
        $version = ServerVersion::parse("7.0");
        $this->assertEquals(7, $version->major());
        $this->assertEquals(0, $version->micro());
        $this->assertTrue($version->isCheshireCat());
        $this->assertEquals("7.0.0-0-enterprise", "" . $version);
    }

    public function testVersionWithBuildNumber()
    {
        $version = ServerVersion::parse("7.1.2-9999");
        $this->assertTrue($version->isNeo());
        $this->assertEquals("7.1.2-9999-enterprise", "" . $version);
    }

    public function testFullVersion()
    {
        $version = ServerVersion::parse("6.6.2-8888-community");
        $this->assertTrue($version->isMadHatter());
        $this->assertEquals("6.6.2-8888-community", "" . $version);
    }

    public function testRuntimeVersion()
    {
        $this->skipIfProtostellar();
        $this->skipIfCaves();

        $cluster = $this->connectCluster();
        $versionString = $cluster->version($this->env()->bucketName());
        $this->assertNotNull($versionString);
        $version = ServerVersion::parse($versionString);
        $this->assertNotNull($version->major());
        $this->assertNotNull($version->minor());
        $this->assertNotNull($version->micro());
    }
}
