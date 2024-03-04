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
 * A FTS query that finds all matches from a given location (point) within the given distance.
 *
 * Both the point and the distance are required.
 */
class GeoDistanceSearchQuery implements JsonSerializable, SearchQuery
{
    private float $longitude;
    private float $latitude;
    private string $distance;
    private ?float $boost = null;
    private ?string $field = null;

    /**
     * @param float $longitude
     * @param float $latitude
     * @param string|null $distance
     *
     * @since 4.0.0
     */
    public function __construct(float $longitude, float $latitude, string $distance = null)
    {
        $this->longitude = $longitude;
        $this->latitude = $latitude;
        $this->distance = $distance;
    }

    /**
     * Static helper to keep code more readable
     *
     * @param float $longitude
     * @param float $latitude
     * @param string|null $distance
     *
     * @return GeoDistanceSearchQuery
     * @since 4.1.7
     */
    public static function build(float $longitude, float $latitude, string $distance = null): GeoDistanceSearchQuery
    {
        return new GeoDistanceSearchQuery($longitude, $latitude, $distance);
    }

    /**
     * Sets the boost for this query.
     *
     * @param float $boost the boost value to use.
     *
     * @return GeoDistanceSearchQuery
     * @since 4.0.0
     */
    public function boost(float $boost): GeoDistanceSearchQuery
    {
        $this->boost = $boost;
        return $this;
    }

    /**
     * Sets the field for this query.
     *
     * @param string $field the field to use.
     *
     * @return GeoDistanceSearchQuery
     * @since 4.0.0
     */
    public function field(string $field): GeoDistanceSearchQuery
    {
        $this->field = $field;
        return $this;
    }

    /**
     * @internal
     * @return mixed
     */
    public function jsonSerialize()
    {
        return GeoDistanceSearchQuery::export($this);
    }

    /**
     * @internal
     */
    public static function export(GeoDistanceSearchQuery $query): array
    {
        $json = [
            'location' => [$query->longitude, $query->latitude],
            'distance' => $query->distance,
        ];
        if ($query->boost != null) {
            $json['boost'] = $query->boost;
        }
        if ($query->field != null) {
            $json['field'] = $query->field;
        }

        return $json;
    }
}
