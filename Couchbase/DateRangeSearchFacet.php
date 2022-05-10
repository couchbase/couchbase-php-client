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
 * A facet that categorizes hits inside date ranges (or buckets) provided by the user.
 */
class DateRangeSearchFacet implements JsonSerializable, SearchFacet
{
    private string $field;
    private int $size;
    private array $ranges = [];

    public function __construct(string $field, int $limit)
    {
        $this->field = $field;
        $this->size = $limit;
    }

    /**
     * @param string $name
     * @param int|string $start
     * @param int|string $end
     *
     * @return DateRangeSearchFacet
     * @throws InvalidArgumentException
     * @since 4.0.0
     */
    public function addRange(string $name, $start = null, $end = null): DateRangeSearchFacet
    {
        $range = [
            'name' => $name,
        ];

        if ($start != null) {
            switch (gettype($start)) {
                case "integer":
                    $range['start'] = date(DATE_RFC3339, $start);
                    break;
                case "string":
                    $range['start'] = $start;
                    break;
                default:
                    throw new InvalidArgumentException();
            }
        }

        if ($end != null) {
            switch (gettype($end)) {
                case "integer":
                    $range['end'] = date(DATE_RFC3339, $end);
                    break;
                case "string":
                    $range['end'] = $end;
                    break;
                default:
                    throw new InvalidArgumentException();
            }
        }

        $this->ranges[] = $range;

        return $this;
    }

    /**
     * @internal
     * @return mixed
     */
    public function jsonSerialize()
    {
        return DateRangeSearchFacet::export($this);
    }

    /**
     * @internal
     *
     * @param DateRangeSearchFacet $facet
     *
     * @return array
     */
    public static function export(DateRangeSearchFacet $facet): array
    {
        return [
            'field' => $facet->field,
            'size' => $facet->size,
            'date_ranges' => $facet->ranges,
        ];
    }
}
