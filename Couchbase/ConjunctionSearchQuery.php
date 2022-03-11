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
 * A compound FTS query that performs a logical AND between all its sub-queries (conjunction).
 */
class ConjunctionSearchQuery implements JsonSerializable, SearchQuery
{
    public function jsonSerialize()
    {
    }

    public function __construct(array $queries)
    {
    }

    /**
     * @param float $boost
     * @return ConjunctionSearchQuery
     */
    public function boost($boost): ConjunctionSearchQuery
    {
    }

    /**
     * @param SearchQuery ...$queries
     * @return ConjunctionSearchQuery
     */
    public function every(SearchQuery ...$queries): ConjunctionSearchQuery
    {
    }
}
