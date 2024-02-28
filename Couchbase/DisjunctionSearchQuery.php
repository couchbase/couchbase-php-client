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

use Couchbase\Exception\InvalidArgumentException;
use JsonSerializable;

/**
 * A compound FTS query that performs a logical OR between all its sub-queries (disjunction). It requires that a
 * minimum of the queries match. The minimum is configurable (default 1).
 */
class DisjunctionSearchQuery implements JsonSerializable, SearchQuery
{
    private ?float $boost = null;
    private array $queries;
    private ?int $min = null;

    /**
     * @param array $queries
     */
    public function __construct(array $queries)
    {
        $this->queries = $queries;
    }

    /**
     * Static helper to keep code more readable
     *
     * @param array $queries
     *
     * @return DisjunctionSearchQuery
     * @since 4.1.7
     */
    public static function build(array $queries): DisjunctionSearchQuery
    {
        return new DisjunctionSearchQuery($queries);
    }

    /**
     * Sets the boost for this query.
     *
     * @param float $boost the boost value to use.
     *
     * @return DisjunctionSearchQuery
     * @since 4.0.0
     */
    public function boost(float $boost): DisjunctionSearchQuery
    {
        $this->boost = $boost;
        return $this;
    }

    /**
     * Adds new predicate queries to this disjunction query.
     *
     * @param SearchQuery ...$queries the queries to add.
     *
     * @return DisjunctionSearchQuery
     * @since 4.0.0
     */
    public function or(SearchQuery ...$queries): DisjunctionSearchQuery
    {
        $this->queries = array_merge($this->queries, $queries);
        return $this;
    }

    /**
     * @param SearchQuery ...$queries
     *
     * @return DisjunctionSearchQuery
     * @deprecated
     *
     */
    public function either(SearchQuery ...$queries): DisjunctionSearchQuery
    {
        trigger_error(
            'Method ' . __METHOD__ . ' is deprecated, use or()',
            E_USER_DEPRECATED
        );

        $this->queries = array_merge($this->queries, $queries);
        return $this;
    }

    /**
     * @param int $min
     *
     * @return DisjunctionSearchQuery
     * @since 4.0.0
     */
    public function min(int $min): DisjunctionSearchQuery
    {
        $this->min = $min;
        return $this;
    }

    public function childQueries(): array
    {
        return $this->queries;
    }

    /**
     * @internal
     * @return mixed
     */
    public function jsonSerialize()
    {
        return DisjunctionSearchQuery::export($this);
    }

    /**
     * @internal
     * @throws InvalidArgumentException
     */
    public static function export(DisjunctionSearchQuery $query): array
    {
        if (count($query->queries) == 0) {
            throw new InvalidArgumentException();
        }
        if (count($query->queries) < $query->min) {
            throw new InvalidArgumentException();
        }

        $json = [
            'disjuncts' => $query->queries,
        ];
        if ($query->boost != null) {
            $json['boost'] = $query->boost;
        }
        if ($query->min != null) {
            $json['min'] = $query->min;
        }

        return $json;
    }
}
