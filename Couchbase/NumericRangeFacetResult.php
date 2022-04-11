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
 * A range (or bucket) for a numeric range facet result. Counts the number of matches
 * that fall into the named range (which can overlap with other user-defined ranges).
 */
class NumericRangeFacetResult
{
    private string $name;
    private $min;
    private $max;
    private int $count;

    /**
     * @internal
     *
     * @param array $range
     */
    public function __construct(array $range)
    {
        $this->name = $range['name'];
        $this->count = $range['count'];
        if (array_key_exists('min', $range)) {
            $this->min = $range['min'];
        }
        if (array_key_exists('max', $range)) {
            $this->max = $range['max'];
        }
    }

    /**
     * @return string
     * @since 4.0.0
     */
    public function name(): string
    {
        return $this->name;
    }

    /**
     * @return int|float|null
     * @since 4.0.0
     */
    public function min()
    {
        return $this->min;
    }

    /**
     * @return int|float|null
     */
    public function max()
    {
        return $this->max;
    }

    /**
     * @return int
     * @since 4.0.0
     */
    public function count(): int
    {
        return $this->count;
    }
}
