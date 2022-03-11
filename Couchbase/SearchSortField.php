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
 * Sort by a field in the hits.
 */
class SearchSortField implements JsonSerializable, SearchSort
{
    public function jsonSerialize()
    {
    }

    public function __construct(string $field)
    {
    }

    /**
     * Direction of the sort
     *
     * @param bool $descending
     *
     * @return SearchSortField
     */
    public function descending(bool $descending): SearchSortField
    {
    }

    /**
     * Set type of the field
     *
     * @param string type the type
     *
     * @see SearchSortType::AUTO
     * @see SearchSortType::STRING
     * @see SearchSortType::NUMBER
     * @see SearchSortType::DATE
     */
    public function type(string $type): SearchSortField
    {
    }

    /**
     * Set mode of the sort
     *
     * @param string mode the mode
     *
     * @see SearchSortMode::MIN
     * @see SearchSortMode::MAX
     */
    public function mode(string $mode): SearchSortField
    {
    }

    /**
     * Set where the hits with missing field will be inserted
     *
     * @param string missing strategy for hits with missing fields
     *
     * @see SearchSortMissing::FIRST
     * @see SearchSortMissing::LAST
     */
    public function missing(string $missing): SearchSortField
    {
    }
}
