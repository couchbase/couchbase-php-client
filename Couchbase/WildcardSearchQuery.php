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
 * A FTS query that allows for simple matching using wildcard characters (* and ?).
 */
class WildcardSearchQuery implements JsonSerializable, SearchQuery
{
    private string $wildcard;
    private ?float $boost = null;
    private ?string $field = null;

    /**
     * @param string $wildcard
     */
    public function __construct(string $wildcard)
    {
        $this->wildcard = $wildcard;
    }

    /**
     * Static helper to keep code more readable
     *
     * @param string $wildcard
     *
     * @return WildcardSearchQuery
     * @since 4.1.7
     */
    public static function build(string $wildcard): WildcardSearchQuery
    {
        return new WildcardSearchQuery($wildcard);
    }

    /**
     * Sets the boost for this query.
     *
     * @param float $boost the boost value to use.
     *
     * @return WildcardSearchQuery
     * @since 4.0.0
     */
    public function boost(float $boost): WildcardSearchQuery
    {
        $this->boost = $boost;
        return $this;
    }

    /**
     * Sets the field for this query.
     *
     * @param string $field the field to use.
     *
     * @return WildcardSearchQuery
     * @since 4.0.0
     */
    public function field(string $field): WildcardSearchQuery
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
        return WildcardSearchQuery::export($this);
    }

    /**
     * @internal
     */
    public static function export(WildcardSearchQuery $query): array
    {
        $json = [
            'wildcard' => $query->wildcard,
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
