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

use Couchbase\Exception\FeatureNotAvailableException;

class ClusterRegistry
{
    private static ?array $registry = null;

    public static function registerConnectionHandler(string $pattern, callable $handler): void
    {
        if (self::$registry == null) {
            self::$registry = [];
        }
        self::$registry[$pattern] = $handler;
    }

    public static function unregisterConnectionHandler(string $pattern): void
    {
        if (self::$registry == null) {
            self::$registry = [];
        }
        unset(self::$registry[$pattern]);
    }

    /**
     * @throws FeatureNotAvailableException
     */
    public static function connect(string $connectionString, ClusterOptions $options): ClusterInterface
    {
        if (self::$registry != null) {
            foreach (self::$registry as $pattern => $handler) {
                if (preg_match($pattern, $connectionString)) {
                    return call_user_func($handler, $connectionString, $options);
                }
            }
        }
        throw new FeatureNotAvailableException("unable to connect to \"$connectionString\"");
    }
}
