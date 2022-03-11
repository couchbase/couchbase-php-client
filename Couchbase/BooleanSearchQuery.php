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
 * A compound FTS query that allows various combinations of sub-queries.
 */
class BooleanSearchQuery implements JsonSerializable, SearchQuery
{
    public function jsonSerialize()
    {
    }

    public function __construct()
    {
    }

    /**
     * @param float $boost
     * @return BooleanSearchQuery
     */
    public function boost($boost): BooleanSearchQuery
    {
    }

    /**
     * @param ConjunctionSearchQuery $query
     * @return BooleanSearchQuery
     */
    public function must(ConjunctionSearchQuery $query): BooleanSearchQuery
    {
    }

    /**
     * @param DisjunctionSearchQuery $query
     * @return BooleanSearchQuery
     */
    public function mustNot(DisjunctionSearchQuery $query): BooleanSearchQuery
    {
    }

    /**
     * @param DisjunctionSearchQuery $query
     * @return BooleanSearchQuery
     */
    public function should(DisjunctionSearchQuery $query): BooleanSearchQuery
    {
    }
}
