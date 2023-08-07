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

use Couchbase\Exception\InvalidArgumentException;

/**
 * A SamplingScan performs a scan on a random sampling of keys with the sampling bounded by a limit.
 */
class SamplingScan implements ScanType
{
    private int $limit;
    private ?int $seed;

    /**
     * @param int $limit
     * @param int|null $seed
     *
     * @throws InvalidArgumentException
     * @since 4.1.6
     */
    public function __construct(int $limit, int $seed = null)
    {
        if ($limit < 1) {
            throw new InvalidArgumentException("The limit must be positive");
        }
        $this->limit = $limit;
        $this->seed = $seed;
    }

    /**
     * Static helper to keep the code more readable.
     *
     * @param int $limit
     * @param int|null $seed
     *
     * @return SamplingScan
     * @throws InvalidArgumentException
     * @since 4.1.6
     */
    public static function build(int $limit, int $seed = null): SamplingScan
    {
        return new SamplingScan($limit, $seed);
    }

    /**
     * @param int $limit
     *
     * @return SamplingScan
     * @since 4.1.6
     */
    public function limit(int $limit): SamplingScan
    {
        $this->limit = $limit;
        return $this;
    }

    /**
     * @param int $seed
     *
     * @return SamplingScan
     * @since 4.1.6
     */
    public function seed(int $seed): SamplingScan
    {
        $this->seed = $seed;
        return $this;
    }

    /**
     * @internal
     *
     * @param SamplingScan $samplingScan
     *
     * @return array
     * @since 4.1.6
     */
    public static function export(SamplingScan $samplingScan): array
    {
        return [
            'type' => 'sampling_scan',
            'limit' => $samplingScan->limit,
            'seed' => $samplingScan->seed,
        ];
    }
}
