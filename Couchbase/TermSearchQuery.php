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
 * A facet that gives the number of occurrences of the most recurring terms in all hits.
 */
class TermSearchQuery implements JsonSerializable, SearchQuery
{
    private string $term;
    private ?float $boost = null;
    private ?string $field = null;
    private ?int $prefixLength = null;
    private ?int $fuzziness = null;

    /**
     * @param string $term
     */
    public function __construct(string $term)
    {
        $this->term = $term;
    }

    /**
     * Static helper to keep code more readable
     *
     * @param string $term
     *
     * @return TermSearchQuery
     * @since 4.1.7
     */
    public static function build(string $term): TermSearchQuery
    {
        return new TermSearchQuery($term);
    }

    /**
     * Sets the boost for this query.
     *
     * @param float $boost the boost value to use.
     *
     * @return TermSearchQuery
     * @since 4.0.0
     */
    public function boost(float $boost): TermSearchQuery
    {
        $this->boost = $boost;
        return $this;
    }

    /**
     * Sets the field for this query.
     *
     * @param string $field the field to use.
     *
     * @return TermSearchQuery
     * @since 4.0.0
     */
    public function field(string $field): TermSearchQuery
    {
        $this->field = $field;
        return $this;
    }

    /**
     * Sets the prefix length for this query.
     *
     * @param int $prefixLength the prefix length to use.
     *
     * @return TermSearchQuery
     * @since 4.0.0
     */
    public function prefixLength(int $prefixLength): TermSearchQuery
    {
        $this->prefixLength = $prefixLength;
        return $this;
    }

    /**
     *
     * Set the fuzziness for this query.
     *
     * @param int $fuzziness the fuzziness to use.
     *
     * @return TermSearchQuery
     * @since 4.0.0
     */
    public function fuzziness(int $fuzziness): TermSearchQuery
    {
        $this->fuzziness = $fuzziness;
        return $this;
    }

    /**
     * @internal
     * @return mixed
     */
    public function jsonSerialize()
    {
        return TermSearchQuery::export($this);
    }

    /**
     * @internal
     */
    public static function export(TermSearchQuery $query): array
    {
        $json = [
            'term' => $query->term,
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

        return $json;
    }
}
