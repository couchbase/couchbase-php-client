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
class TermRangeSearchQuery implements JsonSerializable, SearchQuery
{
    public function jsonSerialize()
    {
    }

    public function __construct()
    {
    }

    /**
     * @param float $boost
     * @return TermRangeSearchQuery
     */
    public function boost(float $boost): TermRangeSearchQuery
    {
    }

    /**
     * @param string $field
     * @return TermRangeSearchQuery
     */
    public function field(string $field): TermRangeSearchQuery
    {
    }

    /**
     * @param string $min
     * @param bool $inclusive
     * @return TermRangeSearchQuery
     */
    public function min(string $min, bool $inclusive = true): TermRangeSearchQuery
    {
    }

    /**
     * @param string $max
     * @param bool $inclusive
     * @return TermRangeSearchQuery
     */
    public function max(string $max, bool $inclusive = false): TermRangeSearchQuery
    {
    }
}
