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
 * A FTS query that finds all matches within the given polygon area.
 */
class GeoPolygonQuery implements JsonSerializable, SearchQuery
{
    private array $coordinates;
    private ?float $boost = null;
    private ?string $field = null;

    /**
     * @param array $coordinates list of objects of type Coordinate
     *
     * @see Coordinate
     * @since 4.0.0
     */
    public function __construct(array $coordinates)
    {
        $this->coordinates = $coordinates;
    }

    /**
     * Sets the boost for this query.
     *
     * @param float $boost the boost value to use.
     *
     * @return GeoPolygonQuery
     * @since 4.0.0
     */
    public function boost(float $boost): GeoPolygonQuery
    {
        $this->boost = $boost;
        return $this;
    }

    /**
     * Sets the field for this query.
     *
     * @param string $field the field to use.
     *
     * @return GeoPolygonQuery
     * @since 4.0.0
     */
    public function field(string $field): GeoPolygonQuery
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
        return GeoPolygonQuery::export($this);
    }

    /**
     * @internal
     */
    public static function export(GeoPolygonQuery $query): array
    {
        $coordinates = [];
        foreach ($query->coordinates as $coordinate) {
            $coordinates[] = $coordinate;
        }

        $json = [
            'polygon_points' => $coordinates,
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
