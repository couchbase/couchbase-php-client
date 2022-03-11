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
 * A FTS query that matches a given term, applying further processing to it
 * like analyzers, stemming and even #fuzziness(int).
 */
class MatchSearchQuery implements JsonSerializable, SearchQuery
{
    public function jsonSerialize()
    {
    }

    public function __construct(string $value)
    {
    }

    /**
     * @param float $boost
     * @return MatchSearchQuery
     */
    public function boost(float $boost): MatchSearchQuery
    {
    }

    /**
     * @param string $field
     * @return MatchSearchQuery
     */
    public function field(string $field): MatchSearchQuery
    {
    }

    /**
     * @param string $analyzer
     * @return MatchSearchQuery
     */
    public function analyzer(string $analyzer): MatchSearchQuery
    {
    }

    /**
     * @param int $prefixLength
     * @return MatchSearchQuery
     */
    public function prefixLength(int $prefixLength): MatchSearchQuery
    {
    }

    /**
     * @param int $fuzziness
     * @return MatchSearchQuery
     */
    public function fuzziness(int $fuzziness): MatchSearchQuery
    {
    }
}
