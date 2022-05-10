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

class Coordinate implements JsonSerializable
{
    private float $longitude;
    private float $latitude;

    /**
     * @param float $longitude
     * @param float $latitude
     *
     * @see GeoPolygonQuery
     * @since 4.0.0
     */
    public function __construct(float $longitude, float $latitude)
    {
        $this->longitude = $longitude;
        $this->latitude = $latitude;
    }

    /**
     * @internal
     * @return mixed
     */
    public function jsonSerialize()
    {
        return Coordinate::export($this);
    }

    /**
     * @internal
     */
    public static function export(Coordinate $coord): array
    {
        return [$coord->longitude, $coord->latitude];
    }
}
