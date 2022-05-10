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
 * Sort by a location and unit in the hits.
 */
class SearchSortGeoDistance implements JsonSerializable, SearchSort
{
    private string $field;
    private float $longitude;
    private float $latitude;
    private ?bool $descending = null;
    private ?string $unit = null;

    /**
     * @param string $field
     * @param float $longitude
     * @param float $latitude
     */
    public function __construct(string $field, float $longitude, float $latitude)
    {
        $this->field = $field;
        $this->longitude = $longitude;
        $this->latitude = $latitude;
    }

    /**
     * Direction of the sort
     *
     * @param bool $descending
     *
     * @return SearchSortGeoDistance
     * @since 4.0.0
     */
    public function descending(bool $descending): SearchSortGeoDistance
    {
        $this->descending = $descending;
        return $this;
    }

    /**
     * Name of the units
     *
     * @param string $unit
     *
     * @return SearchSortGeoDistance
     * @since 4.0.0
     */
    public function unit(string $unit): SearchSortGeoDistance
    {
        $this->unit = $unit;
        return $this;
    }

    /**
     * @internal
     * @return mixed
     */
    public function jsonSerialize()
    {
        return SearchSortGeoDistance::export($this);
    }

    /**
     * @internal
     */
    public static function export(SearchSortGeoDistance $sort): array
    {
        $json = [
            'by' => 'geo_distance',
            'field' => $sort->field,
            'location' => [$sort->longitude, $sort->latitude],
        ];

        if ($sort->descending != null) {
            $json['desc'] = $sort->descending;
        }
        if ($sort->unit != null) {
            $json['unit'] = $sort->unit;
        }

        return $json;
    }
}
