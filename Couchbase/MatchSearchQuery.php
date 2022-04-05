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
 * A FTS query that matches a given term, applying further processing to it
 * like analyzers, stemming and even #fuzziness(int).
 */
class MatchSearchQuery implements JsonSerializable, SearchQuery
{
    private string $match;
    private ?float $boost = null;
    private ?string $field = null;
    private ?int $prefixLength = null;
    private ?int $fuzziness = null;
    private ?string $analyzer = null;

    public function __construct(string $match)
    {
        $this->match = $match;
    }

    /**
     * Sets the boost for this query.
     *
     * @param float $boost the boost value to use.
     * @return MatchSearchQuery
     * @since 4.0.0
     */
    public function boost(float $boost): MatchSearchQuery
    {
        $this->boost = $boost;
        return $this;
    }

    /**
     * Sets the field for this query.
     *
     * @param string $field the field to use.
     * @return MatchSearchQuery
     * @since 4.0.0
     */
    public function field(string $field): MatchSearchQuery
    {
        $this->field = $field;
        return $this;
    }

    /**
     * Sets the analytics for this query.
     *
     * @param string $analyzer the analyzer to use for this query.
     * @return MatchSearchQuery
     * @since 4.0.0
     */
    public function analyzer(string $analyzer): MatchSearchQuery
    {
        $this->analyzer = $analyzer;
        return $this;
    }

    /**
     * Sets the prefix length for this query.
     *
     * @param int $prefixLength the prefix length to use.
     * @return MatchSearchQuery
     * @since 4.0.0
     */
    public function prefixLength(int $prefixLength): MatchSearchQuery
    {
        $this->prefixLength = $prefixLength;
        return $this;
    }

    /**
     *
     * Set the fuzziness for this query.
     *
     * @param int $fuzziness the fuzziness to use.
     * @return MatchSearchQuery
     * @since 4.0.0
     */
    public function fuzziness(int $fuzziness): MatchSearchQuery
    {
        $this->fuzziness = $fuzziness;
        return $this;
    }

    /**
     * @private
     * @return mixed
     */
    public function jsonSerialize()
    {
        return MatchSearchQuery::export($this);
    }

    /**
     * @private
     */
    public static function export(MatchSearchQuery $query): array
    {
        $json = [
            'match' => $query->match,
        ];
        if ($query->boost != null) {
            $json['boost'] = $query->boost;
        }
        if ($query->field != null) {
            $json['field'] = $query->field;
        }
        if ($query->prefixLength != null) {
            $json['prefix_length'] = $query->prefixLength;
        }
        if ($query->fuzziness != null) {
            $json['fuzziness'] = $query->fuzziness;
        }
        if ($query->analyzer != null) {
            $json['analyzer'] = $query->analyzer;
        }

        return $json;
    }
}
