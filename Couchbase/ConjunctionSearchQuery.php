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
 * A compound FTS query that performs a logical AND between all its sub-queries (conjunction).
 */
class ConjunctionSearchQuery implements JsonSerializable, SearchQuery
{
    private ?float $boost = null;
    private array $queries;

    public function __construct(array $queries)
    {
        $this->queries = $queries;
    }

    /**
     * Static helper to keep code more readable
     *
     * @param array $queries
     *
     * @return ConjunctionSearchQuery
     * @since 4.1.7
     */
    public static function build(array $queries): ConjunctionSearchQuery
    {
        return new ConjunctionSearchQuery($queries);
    }

    /**
     * Sets the boost for this query.
     *
     * @param float $boost the boost value to use.
     *
     * @return ConjunctionSearchQuery
     * @since 4.0.0
     */
    public function boost(float $boost): ConjunctionSearchQuery
    {
        $this->boost = $boost;
        return $this;
    }

    /**
     * Adds new predicate queries to this conjunction query.
     *
     * @param SearchQuery ...$queries the queries to add.
     *
     * @return ConjunctionSearchQuery
     * @since 4.0.0
     */
    public function and(SearchQuery ...$queries): ConjunctionSearchQuery
    {
        $this->queries = array_merge($this->queries, $queries);
        return $this;
    }

    /**
     * @param SearchQuery ...$queries
     *
     * @return ConjunctionSearchQuery
     * @deprecated
     *
     */
    public function every(SearchQuery ...$queries): ConjunctionSearchQuery
    {
        trigger_error(
            'Method ' . __METHOD__ . ' is deprecated, use and()',
            E_USER_DEPRECATED
        );

        $this->queries = array_merge($this->queries, $queries);
        return $this;
    }

    public function childQueries(): array
    {
        return $this->queries;
    }

    /**
     * @internal
     * @return mixed
     * @throws InvalidArgumentException
     */
    public function jsonSerialize()
    {
        return ConjunctionSearchQuery::export($this);
    }

    /**
     * @internal
     * @throws InvalidArgumentException
     */
    public static function export(ConjunctionSearchQuery $query): array
    {
        if (count($query->queries) == 0) {
            throw new InvalidArgumentException();
        }

        $json = [
            'conjuncts' => $query->queries,
        ];
        if ($query->boost != null) {
            $json['boost'] = $query->boost;
        }

        return $json;
    }
}
