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
 * A FTS query that allows for simple matching on a given prefix.
 */
class PrefixSearchQuery implements JsonSerializable, SearchQuery
{
    public function jsonSerialize()
    {
    }

    public function __construct(string $prefix)
    {
    }

    /**
     * @param float $boost
     * @return PrefixSearchQuery
     */
    public function boost(float $boost): PrefixSearchQuery
    {
    }

    /**
     * @param string $field
     * @return PrefixSearchQuery
     */
    public function field(string $field): PrefixSearchQuery
    {
    }
}
