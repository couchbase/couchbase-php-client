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
use JsonSerializable;

/**
 * A FTS query that matches documents on a range of values. At least one bound is required, and the
 * inclusiveness of each bound can be configured.
 */
class DateRangeSearchQuery implements JsonSerializable, SearchQuery
{
    private ?float $boost = null;
    private ?string $field = null;
    private ?string $start = null;
    private ?string $end = null;
    private ?bool $inclusiveStart = null;
    private ?bool $inclusiveEnd = null;
    private ?string $datetimeParser = null;

    /**
     * Static helper to keep code more readable
     *
     * @return DateRangeSearchQuery
     * @since 4.1.7
     */
    public static function build(): DateRangeSearchQuery
    {
        return new DateRangeSearchQuery();
    }

    /**
     * Sets the boost for this query.
     *
     * @param float $boost the boost value to use.
     *
     * @return DateRangeSearchQuery
     * @since 4.0.0
     */
    public function boost(float $boost): DateRangeSearchQuery
    {
        $this->boost = $boost;
        return $this;
    }

    /**
     * Sets the field for this query.
     *
     * @param string $field the field to use.
     *
     * @return DateRangeSearchQuery
     * @since 4.0.0
     */
    public function field(string $field): DateRangeSearchQuery
    {
        $this->field = $field;
        return $this;
    }

    /**
     * Sets the lower boundary of the range, inclusive or not depending on the second parameter.
     *
     * @param int|string $start The strings will be taken verbatim and supposed to be formatted with custom date
     *      time formatter (see dateTimeParser). Integers interpreted as unix timestamps and represented as RFC3339
     *      strings.
     * @param bool $inclusive
     *
     * @return DateRangeSearchQuery
     * @throws InvalidArgumentException
     * @since 4.0.0
     */
    public function start($start, bool $inclusive = false): DateRangeSearchQuery
    {
        switch (gettype($start)) {
            case "integer":
                $this->start = date(DATE_RFC3339, $start);
                break;
            case "string":
                $this->start = $start;
                break;
            default:
                throw new InvalidArgumentException();
        }

        $this->inclusiveStart = $inclusive;
        return $this;
    }

    /**
     * Sets the upper boundary of the range, inclusive or not depending on the second parameter.
     *
     * @param int|string $end The strings will be taken verbatim and supposed to be formatted with custom date
     *      time formatter (see dateTimeParser). Integers interpreted as unix timestamps and represented as RFC3339
     *      strings.
     * @param bool $inclusive
     *
     * @return DateRangeSearchQuery
     * @throws InvalidArgumentException
     * @since 4.0.0
     */
    public function end($end, bool $inclusive = false): DateRangeSearchQuery
    {
        switch (gettype($end)) {
            case "integer":
                $this->end = date(DATE_RFC3339, $end);
                break;
            case "string":
                $this->end = $end;
                break;
            default:
                throw new InvalidArgumentException();
        }

        $this->inclusiveEnd = $inclusive;
        return $this;
    }


    /**
     * Sets the name of the date/time parser to use to interpret start/end.
     *
     * @param string $parser the name of the parser.
     *
     * @return DateRangeSearchQuery
     * @since 4.0.0
     */
    public function datetimeParser(string $parser): DateRangeSearchQuery
    {
        $this->datetimeParser = $parser;
        return $this;
    }

    /**
     * @internal
     * @return mixed
     */
    public function jsonSerialize()
    {
        return DateRangeSearchQuery::export($this);
    }

    /**
     * @internal
     * @throws InvalidArgumentException
     */
    public static function export(DateRangeSearchQuery $query): array
    {
        if ($query->start == null && $query->end == null) {
            throw new InvalidArgumentException();
        }

        $json = [];
        if ($query->boost != null) {
            $json['boost'] = $query->boost;
        }
        if ($query->field != null) {
            $json['field'] = $query->field;
        }
        if ($query->start != null) {
            $json['start'] = $query->start;
        }
        if ($query->end != null) {
            $json['end'] = $query->end;
        }
        if ($query->inclusiveStart != null) {
            $json['inclusive_start'] = $query->inclusiveStart;
        }
        if ($query->inclusiveEnd != null) {
            $json['inclusive_end'] = $query->inclusiveEnd;
        }
        if ($query->datetimeParser != null) {
            $json['datetime_parser'] = $query->datetimeParser;
        }

        return $json;
    }
}
