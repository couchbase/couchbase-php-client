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

use Couchbase\ExtensionNamespaceResolver;

include_once __DIR__ . "/Helpers/CouchbaseTestCase.php";

class ABITest extends Helpers\CouchbaseTestCase
{
    public function setUp(): void
    {
        $this->skipIfNotABI();
        parent::setUp();
    }

    public function testExtensionsLoadedPermutation() {
        $abi = getenv('TEST_ABI');
        $unversionedFunc = 'Couchbase\\Extension\\version';
        $versionedFunc = 'Couchbase\\Extension_' . ExtensionNamespaceResolver::COUCHBASE_EXTENSION_VERSION . '\\version';
        if ($abi == "both") {
            print_r($unversionedFunc());
            print_r($versionedFunc());
            $this->assertTrue(true);
        } else if ($abi == "versioned") {
            print_r($versionedFunc());
            $this->assertTrue(true);
        } else if ($abi == "unversioned") {
            print_r($unversionedFunc());
            $this->assertTrue(true);
        }
    }

    public function testNamespaceResolver()
    {
        ExtensionNamespaceResolver::defineExtensionNamespace();
        $abi = getenv('TEST_ABI');

        if ($abi == "both" || $abi == "unversioned") {
            $this->assertEquals("Couchbase\\Extension", COUCHBASE_EXTENSION_NAMESPACE);
        } else if ($abi == "versioned") {
            $this->assertEquals("Couchbase\\Extension_" . ExtensionNamespaceResolver::COUCHBASE_EXTENSION_VERSION, COUCHBASE_EXTENSION_NAMESPACE);
        }
    }
}
