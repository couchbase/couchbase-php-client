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
 * A FTS query that matches several given terms (a "phrase"), applying further processing
 * like analyzers to them.
 */
class MatchPhraseSearchQuery implements JsonSerializable, SearchQuery
{
    public function jsonSerialize()
    {
    }

    public function __construct(string $value)
    {
    }

    /**
     * @param float $boost
     * @return MatchPhraseSearchQuery
     */
    public function boost(float $boost): MatchPhraseSearchQuery
    {
    }

    /**
     * @param string $field
     * @return MatchPhraseSearchQuery
     */
    public function field(string $field): MatchPhraseSearchQuery
    {
    }

    /**
     * @param string $analyzer
     * @return MatchPhraseSearchQuery
     */
    public function analyzer(string $analyzer): MatchPhraseSearchQuery
    {
    }
}
