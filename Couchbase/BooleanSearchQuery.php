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
 * A compound FTS query that allows various combinations of sub-queries.
 */
class BooleanSearchQuery implements JsonSerializable, SearchQuery
{
    private ?float $boost = null;
    private ConjunctionSearchQuery $must;
    private DisjunctionSearchQuery $mustNot;
    private DisjunctionSearchQuery $should;

    public function __construct()
    {
        $this->must = new ConjunctionSearchQuery([]);
        $this->mustNot = new DisjunctionSearchQuery([]);
        $this->should = new DisjunctionSearchQuery([]);
    }

    /**
     * Static helper to keep code more readable
     *
     * @return BooleanSearchQuery
     * @since 4.1.7
     */
    public static function build(): BooleanSearchQuery
    {
        return new BooleanSearchQuery();
    }

    /**
     * Sets the boost for this query.
     *
     * @param float $boost the boost value to use.
     *
     * @return BooleanSearchQuery
     * @since 4.0.0
     */
    public function boost(float $boost): BooleanSearchQuery
    {
        $this->boost = $boost;
        return $this;
    }

    /**
     * Sets a query which must match.
     *
     * @param ConjunctionSearchQuery $query query which must match.
     *
     * @return BooleanSearchQuery
     * @since 4.0.0
     */
    public function must(ConjunctionSearchQuery $query): BooleanSearchQuery
    {
        $this->must->and(...$query->childQueries());
        return $this;
    }

    /**
     * Sets a query which must not match.
     *
     * @param DisjunctionSearchQuery $query query which must not match.
     *
     * @return BooleanSearchQuery
     * @since 4.0.0
     */
    public function mustNot(DisjunctionSearchQuery $query): BooleanSearchQuery
    {
        $this->mustNot->or(...$query->childQueries());
        return $this;
    }

    /**
     * Sets a query which must should match.
     *
     * @param DisjunctionSearchQuery $query query which should match.
     *
     * @return BooleanSearchQuery
     * @since 4.0.0
     */
    public function should(DisjunctionSearchQuery $query): BooleanSearchQuery
    {
        $this->should->or(...$query->childQueries());
        return $this;
    }

    /**
     * Sets the minimum value before that should query will boost.
     *
     * @param int $minForShould the minimum value before that should query will boost
     *
     * @return BooleanSearchQuery
     * @since 4.0.0
     */
    public function min(int $minForShould): BooleanSearchQuery
    {
        $this->should->min($minForShould);
        return $this;
    }

    /**
     * @internal
     * @return mixed
     */
    public function jsonSerialize()
    {
        return BooleanSearchQuery::export($this);
    }

    /**
     * @internal
     */
    public static function export(BooleanSearchQuery $query): array
    {
        $json = [];

        if (count($query->must->childQueries()) > 0) {
            $json['must'] = $query->must;
        }
        if (count($query->mustNot->childQueries()) > 0) {
            $json['must_not'] = $query->mustNot;
        }
        if (count($query->should->childQueries()) > 0) {
            $json['should'] = $query->should;
        }
        if ($query->boost != null) {
            $json['boost'] = $query->boost;
        }

        return $json;
    }
}
