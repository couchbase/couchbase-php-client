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
 * A range (or bucket) for a term search facet result.
 * Counts the number of occurrences of a given term.
 */
class TermFacetResult
{
    private string $term;
    private int $count;

    /**
     * @internal
     *
     * @param array $term
     */
    public function __construct(array $term)
    {
        $this->term = $term['term'];
        $this->count = $term['count'];
    }

    /**
     * @return string
     * @since 4.0.0
     */
    public function term(): string
    {
        return $this->term;
    }

    /**
     * @return int
     * @since 4.0.0
     */
    public function count(): int
    {
        return $this->count;
    }
}
