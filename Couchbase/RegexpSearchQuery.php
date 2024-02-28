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
 * A FTS query that allows for simple matching of regular expressions.
 */
class RegexpSearchQuery implements SearchQuery
{
    private string $regexp;
    private ?float $boost = null;
    private ?string $field = null;

    public function __construct(string $regexp)
    {
        $this->regexp = $regexp;
    }

    /**
     * Static helper to keep code more readable
     *
     * @param string $regexp
     *
     * @return RegexpSearchQuery
     * @since 4.1.7
     */
    public static function build(string $regexp): RegexpSearchQuery
    {
        return new RegexpSearchQuery($regexp);
    }

    /**
     * Sets the boost for this query.
     *
     * @param float $boost the boost value to use.
     *
     * @return RegexpSearchQuery
     * @since 4.0.0
     */
    public function boost(float $boost): RegexpSearchQuery
    {
        $this->boost = $boost;
        return $this;
    }

    /**
     * Sets the field for this query.
     *
     * @param string $field the field to use.
     *
     * @return RegexpSearchQuery
     * @since 4.0.0
     */
    public function field(string $field): RegexpSearchQuery
    {
        $this->field = $field;
        return $this;
    }

    /**
     * @internal
     */
    public static function export(RegexpSearchQuery $query): array
    {
        $json = [
            'regexp' => $query->regexp,
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
