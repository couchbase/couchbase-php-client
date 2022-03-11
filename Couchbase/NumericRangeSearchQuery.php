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
 * A FTS query that matches documents on a range of values. At least one bound is required, and the
 * inclusiveness of each bound can be configured.
 */
class NumericRangeSearchQuery implements JsonSerializable, SearchQuery
{
    public function jsonSerialize()
    {
    }

    public function __construct()
    {
    }

    /**
     * @param float $boost
     * @return NumericRangeSearchQuery
     */
    public function boost(float $boost): NumericRangeSearchQuery
    {
    }

    /**
     * @param string $field
     * @return NumericRangeSearchQuery
     */
    public function field($field): NumericRangeSearchQuery
    {
    }

    /**
     * @param float $min
     * @param bool $inclusive
     * @return NumericRangeSearchQuery
     */
    public function min(float $min, bool $inclusive = false): NumericRangeSearchQuery
    {
    }

    /**
     * @param float $max
     * @param bool $inclusive
     * @return NumericRangeSearchQuery
     */
    public function max(float $max, bool $inclusive = false): NumericRangeSearchQuery
    {
    }
}
