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

/**
 * A PrefixScan selects every document whose ID starts with a certain prefix
 */
class PrefixScan implements ScanType
{
    private string $prefix;

    /**
     * @param string $prefix
     *
     * @since 4.1.6
     */
    public function __construct(string $prefix)
    {
        $this->prefix = $prefix;
    }

    /**
     * Static helper to keep code more readable
     *
     * @param string $prefix
     *
     * @return PrefixScan
     * @since 4.1.6
     */
    public static function build(string $prefix): PrefixScan
    {
        return new PrefixScan($prefix);
    }

    /**
     * @param string $prefix
     *
     * @return PrefixScan
     * @since 4.1.6
     */
    public function prefix(string $prefix): PrefixScan
    {
        $this->prefix = $prefix;
        return $this;
    }

    /**
     * @internal
     *
     * @param PrefixScan $prefixScan
     *
     * @return array
     * @since 4.1.6
     */
    public static function export(PrefixScan $prefixScan): array
    {
        return [
            'type' => 'prefix_scan',
            'prefix' => $prefixScan->prefix,
        ];
    }
}
