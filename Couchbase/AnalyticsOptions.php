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

use Couchbase\Exception\UnsupportedOperationException;

/**
 * @since 4.0.0
 */
class AnalyticsOptions
{
    /**
     * @param int $arg
     * @return AnalyticsOptions
     * @throws UnsupportedOperationException
     * @since 4.0.0
     */
    public function timeout(int $arg): AnalyticsOptions
    {
        throw new UnsupportedOperationException();
    }

    /**
     * @param array $pairs
     * @return AnalyticsOptions
     * @throws UnsupportedOperationException
     * @since 4.0.0
     */
    public function namedParameters(array $pairs): AnalyticsOptions
    {
        throw new UnsupportedOperationException();
    }

    /**
     * @param array $args
     * @return AnalyticsOptions
     * @throws UnsupportedOperationException
     * @since 4.0.0
     */
    public function positionalParameters(array $args): AnalyticsOptions
    {
        throw new UnsupportedOperationException();
    }

    /**
     * @param string $key
     * @param $value
     * @return AnalyticsOptions
     * @throws UnsupportedOperationException
     * @since 4.0.0
     */
    public function raw(string $key, $value): AnalyticsOptions
    {
        throw new UnsupportedOperationException();
    }

    /**
     * @param string $value
     * @return AnalyticsOptions
     * @throws UnsupportedOperationException
     * @since 4.0.0
     */
    public function clientContextId(string $value): AnalyticsOptions
    {
        throw new UnsupportedOperationException();
    }

    /**
     * @param bool $urgent
     * @return AnalyticsOptions
     * @throws UnsupportedOperationException
     * @since 4.0.0
     */
    public function priority(bool $urgent): AnalyticsOptions
    {
        throw new UnsupportedOperationException();
    }

    /**
     * @param bool $arg
     * @return AnalyticsOptions
     * @throws UnsupportedOperationException
     * @since 4.0.0
     */
    public function readonly(bool $arg): AnalyticsOptions
    {
        throw new UnsupportedOperationException();
    }

    /**
     * @param string $arg
     * @return AnalyticsOptions
     * @throws UnsupportedOperationException
     * @since 4.0.0
     */
    public function scanConsistency(string $arg): AnalyticsOptions
    {
        throw new UnsupportedOperationException();
    }
}
