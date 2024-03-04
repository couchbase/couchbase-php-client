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
 * A FTS query which allows to match geo bounding boxes.
 */
class GeoBoundingBoxSearchQuery implements JsonSerializable, SearchQuery
{
    private float $topLeftLongitude;
    private float $topLeftLatitude;
    private float $bottomRightLongitude;
    private float $bottomRightLatitude;
    private ?float $boost = null;
    private ?string $field = null;

    /**
     * @param float $topLeftLongitude
     * @param float $topLeftLatitude
     * @param float $bottomRightLongitude
     * @param float $bottomRightLatitude
     *
     * @since 4.0.0
     */
    public function __construct(float $topLeftLongitude, float $topLeftLatitude, float $bottomRightLongitude, float $bottomRightLatitude)
    {
        $this->topLeftLongitude = $topLeftLongitude;
        $this->topLeftLatitude = $topLeftLatitude;
        $this->bottomRightLongitude = $bottomRightLongitude;
        $this->bottomRightLatitude = $bottomRightLatitude;
    }

    /**
     * Static helper to keep code more readable
     *
     * @param float $topLeftLongitude
     * @param float $topLeftLatitude
     * @param float $bottomRightLongitude
     * @param float $bottomRightLatitude
     *
     * @return GeoBoundingBoxSearchQuery
     * @since 4.1.7
     */
    public static function build(float $topLeftLongitude, float $topLeftLatitude, float $bottomRightLongitude, float $bottomRightLatitude): GeoBoundingBoxSearchQuery
    {
        return new GeoBoundingBoxSearchQuery($topLeftLongitude, $topLeftLatitude, $bottomRightLongitude, $bottomRightLatitude);
    }

    /**
     * Sets the boost for this query.
     *
     * @param float $boost the boost value to use.
     *
     * @return GeoBoundingBoxSearchQuery
     * @since 4.0.0
     */
    public function boost(float $boost): GeoBoundingBoxSearchQuery
    {
        $this->boost = $boost;
        return $this;
    }

    /**
     * Sets the field for this query.
     *
     * @param string $field the field to use.
     *
     * @return GeoBoundingBoxSearchQuery
     * @since 4.0.0
     */
    public function field(string $field): GeoBoundingBoxSearchQuery
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
        return GeoBoundingBoxSearchQuery::export($this);
    }

    /**
     * @internal
     */
    public static function export(GeoBoundingBoxSearchQuery $query): array
    {
        $json = [
            'top_left' => [$query->topLeftLongitude, $query->topLeftLatitude],
            'bottom_right' => [$query->bottomRightLongitude, $query->bottomRightLatitude],
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
