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
class DateRangeSearchQuery implements JsonSerializable, SearchQuery
{
    public function jsonSerialize()
    {
    }

    public function __construct()
    {
    }

    /**
     * @param float $boost
     * @return DateRangeSearchQuery
     */
    public function boost(float $boost): DateRangeSearchQuery
    {
    }

    /**
     * @param string $field
     * @return DateRangeSearchQuery
     */
    public function field(string $field): DateRangeSearchQuery
    {
    }

    /**
     * @param int|string $start The strings will be taken verbatim and supposed to be formatted with custom date
     *      time formatter (see dateTimeParser). Integers interpreted as unix timestamps and represented as RFC3339
     *      strings.
     * @param bool $inclusive
     * @return DateRangeSearchQuery
     */
    public function start($start, bool $inclusive = false): DateRangeSearchQuery
    {
    }

    /**
     * @param int|string $end The strings will be taken verbatim and supposed to be formatted with custom date
     *      time formatter (see dateTimeParser). Integers interpreted as unix timestamps and represented as RFC3339
     *      strings.
     * @param bool $inclusive
     * @return DateRangeSearchQuery
     */
    public function end($end, bool $inclusive = false): DateRangeSearchQuery
    {
    }

    /**
     * @param string $dateTimeParser
     * @return DateRangeSearchQuery
     */
    public function dateTimeParser(string $dateTimeParser): DateRangeSearchQuery
    {
    }
}
