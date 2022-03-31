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

use JsonSerializable;

/**
 * A facet that categorizes hits inside date ranges (or buckets) provided by the user.
 */
class DateRangeSearchFacet implements JsonSerializable, SearchFacet
{
    private string $field;
    private int $limit;
    private array $ranges;

    public function jsonSerialize(): mixed
    {
        return DateRangeSearchFacet::export($this);
    }

    public function __construct(string $field, int $limit)
    {
        $this->field = $field;
        $this->limit = $limit;
    }

    /**
     * @param string $name
     * @param int|string $start
     * @param int|string $end
     * @return DateRangeSearchFacet
     * @since 4.0.0
     */
    public function addRange(string $name, $start = null, $end = null): DateRangeSearchFacet
    {
        if ($this->ranges == null) {
            $this->ranges = [];
        }

        $range = [
            'name' => $name
        ];

        if ($start != null) {
            $range['start'] = $start;
        }

        if ($end != null) {
            $range['end'] = $end;
        }

        $this->ranges[] = $range;

        return $this;
    }

    public static function export(DateRangeSearchFacet $facet): array
    {
        return [
            'field' => $facet->field,
            'limit' => $facet->limit,
            'date_ranges' => $facet->ranges,
        ];
    }
}
