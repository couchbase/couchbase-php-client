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
 * A facet that categorizes hits into numerical ranges (or buckets) provided by the user.
 */
class NumericRangeSearchFacet implements JsonSerializable, SearchFacet
{
    private string $field;
    private int $limit;
    private array $ranges;

    /**
     * @throws InvalidArgumentException
     */
    public function jsonSerialize(): mixed
    {
        return NumericRangeSearchFacet::export($this);
    }

    public function __construct(string $field, int $limit)
    {
        $this->field = $field;
        $this->limit = $limit;
    }

    /**
     * @param string $name
     * @param float $min
     * @param float $max
     * @return NumericRangeSearchFacet
     * @since 4.0.0
     */
    public function addRange(string $name, float $min = null, float $max = null): NumericRangeSearchFacet
    {
        if ($this->ranges == null) {
            $this->ranges = [];
        }

        $range = [
            'name' => $name
        ];

        if ($min != null) {
            $range['min'] = $min;
        }

        if ($max != null) {
            $range['max'] = $max;
        }

        $this->ranges[] = $range;

        return $this;
    }

    /**
     * @private
     * @throws InvalidArgumentException
     */
    public static function export(NumericRangeSearchFacet $facet): array
    {
        if ($facet->ranges == null) {
            throw new InvalidArgumentException();
        }

        return [
            'field' => $facet->field,
            'limit' => $facet->limit,
            'numeric_ranges' => $facet->ranges,
        ];
    }
}
