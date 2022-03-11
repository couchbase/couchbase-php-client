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
 * A FTS query that finds all matches from a given location (point) within the given distance.
 *
 * Both the point and the distance are required.
 */
class GeoDistanceSearchQuery implements JsonSerializable, SearchQuery
{
    public function jsonSerialize()
    {
    }

    public function __construct(float $longitude, float $latitude, string $distance = null)
    {
    }

    /**
     * @param float $boost
     * @return GeoDistanceSearchQuery
     */
    public function boost(float $boost): GeoDistanceSearchQuery
    {
    }

    /**
     * @param string $field
     * @return GeoDistanceSearchQuery
     */
    public function field(string $field): GeoDistanceSearchQuery
    {
    }
}
