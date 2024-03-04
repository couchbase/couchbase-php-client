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
class TermRangeSearchQuery implements JsonSerializable, SearchQuery
{
    private ?float $boost = null;
    private ?string $field = null;
    private ?string $min = null;
    private ?string $max = null;
    private ?bool $inclusiveMin = null;
    private ?bool $inclusiveMax = null;

    /**
     * Static helper to keep code more readable
     *
     * @return TermRangeSearchQuery
     * @since 4.1.7
     */
    public static function build(): TermRangeSearchQuery
    {
        return new TermRangeSearchQuery();
    }

    /**
     * Sets the boost for this query.
     *
     * @param float $boost the boost value to use.
     *
     * @return TermRangeSearchQuery
     * @since 4.0.0
     */
    public function boost(float $boost): TermRangeSearchQuery
    {
        $this->boost = $boost;
        return $this;
    }

    /**
     * Sets the field for this query.
     *
     * @param string $field the field to use.
     *
     * @return TermRangeSearchQuery
     * @since 4.0.0
     */
    public function field(string $field): TermRangeSearchQuery
    {
        $this->field = $field;
        return $this;
    }

    /**
     * Sets the lower boundary of the range, inclusive or not depending on the second parameter.
     *
     * @param string $min the lower boundary of the range.
     * @param bool $inclusive whether the lower boundary should be inclusive.
     *
     * @return TermRangeSearchQuery
     * @since 4.0.0
     */
    public function min(string $min, bool $inclusive = true): TermRangeSearchQuery
    {
        $this->min = $min;
        $this->inclusiveMin = $inclusive;
        return $this;
    }

    /**
     * Sets the upper boundary of the range, inclusive or not depending on the second parameter.
     *
     * @param string $max the upper boundary of the range.
     * @param bool $inclusive whether the upper boundary should be inclusive.
     *
     * @return TermRangeSearchQuery
     * @since 4.0.0
     */
    public function max(string $max, bool $inclusive = false): TermRangeSearchQuery
    {
        $this->max = $max;
        $this->inclusiveMax = $inclusive;
        return $this;
    }

    /**
     * @internal
     * @return mixed
     */
    public function jsonSerialize()
    {
        return TermRangeSearchQuery::export($this);
    }

    /**
     * @internal
     * @throws InvalidArgumentException
     */
    public static function export(TermRangeSearchQuery $query): array
    {
        if ($query->min == null && $query->max == null) {
            throw new InvalidArgumentException("Either max or min must be set for term range query");
        }

        $json = [
        ];
        if ($query->boost != null) {
            $json['boost'] = $query->boost;
        }
        if ($query->field != null) {
            $json['field'] = $query->field;
        }
        if ($query->min != null) {
            $json['min'] = $query->min;
        }
        if ($query->max != null) {
            $json['max'] = $query->max;
        }
        if ($query->inclusiveMin != null) {
            $json['inclusive_min'] = $query->inclusiveMin;
        }
        if ($query->inclusiveMax != null) {
            $json['inclusive_max'] = $query->inclusiveMax;
        }

        return $json;
    }
}
