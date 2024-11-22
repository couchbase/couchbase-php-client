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

namespace Couchbase;

use Couchbase\Exception\CouchbaseException;

class ExtensionNamespaceResolver
{
    const COUCHBASE_EXTENSION_VERSION = "4_2_4";

    public static function defineExtensionNamespace()
    {
        if (defined('COUCHBASE_EXTENSION_NAMESPACE')) {
            return;
        }
        $namespace = self::aliasExtensionNamespace();
        define('COUCHBASE_EXTENSION_NAMESPACE', $namespace);
    }

    private static function aliasExtensionNamespace()
    {
        if (extension_loaded('couchbase')) {
            return "Couchbase\\Extension";
        } elseif (extension_loaded('couchbase_' . self::COUCHBASE_EXTENSION_VERSION)) {
            $func = "Couchbase\\Extension_" . self::COUCHBASE_EXTENSION_VERSION . "\\loadExceptionAliases";
            $func();
            return "Couchbase\\Extension_" . self::COUCHBASE_EXTENSION_VERSION;
        } else {
            throw new CouchbaseException(
                sprintf("Could not load Couchbase extension (tried \"couchbase\" and \"couchbase_%d\")", self::COUCHBASE_EXTENSION_VERSION)
            );
        }
    }
}
