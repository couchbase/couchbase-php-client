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
 * A FTS query that queries fields explicitly indexed as boolean.
 */
class BooleanFieldSearchQuery implements JsonSerializable, SearchQuery
{
    private ?float $boost = null;
    private ?string $field = null;
    private bool $value;

    public function __construct(bool $value)
    {
        $this->value = $value;
    }

    /**
     * Static helper to keep code more readable
     *
     * @param bool $value
     *
     * @return BooleanFieldSearchQuery
     * @since 4.1.7
     */
    public static function build(bool $value): BooleanFieldSearchQuery
    {
        return new BooleanFieldSearchQuery($value);
    }

    /**
     * Sets the boost for this query.
     *
     * @param float $boost the boost value to use.
     *
     * @return BooleanFieldSearchQuery
     * @since 4.0.0
     */
    public function boost(float $boost): BooleanFieldSearchQuery
    {
        $this->boost = $boost;
        return $this;
    }

    /**
     * Sets the field for this query.
     *
     * @param string $field the field to use.
     *
     * @return BooleanFieldSearchQuery
     * @since 4.0.0
     */
    public function field(string $field): BooleanFieldSearchQuery
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
        return BooleanFieldSearchQuery::export($this);
    }

    /**
     * @internal
     */
    public static function export(BooleanFieldSearchQuery $facet): array
    {
        $json = [
            'bool' => $facet->value,
        ];
        if ($facet->boost != null) {
            $json['boost'] = $facet->boost;
        }
        if ($facet->field != null) {
            $json['field'] = $facet->field;
        }

        return $json;
    }
}
