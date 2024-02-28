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
 * A FTS query that matches several given terms (a "phrase"), applying further processing
 * like analyzers to them.
 */
class MatchPhraseSearchQuery implements JsonSerializable, SearchQuery
{
    private string $matchPhrase;
    private ?float $boost = null;
    private ?string $field = null;
    private ?string $analyzer = null;

    public function __construct(string $phrase)
    {
        $this->matchPhrase = $phrase;
    }

    /**
     * Static helper to keep code more readable
     *
     * @param string $phrase
     *
     * @return MatchPhraseSearchQuery
     * @since 4.1.7
     */
    public static function build(string $phrase): MatchPhraseSearchQuery
    {
        return new MatchPhraseSearchQuery($phrase);
    }

    /**
     * Sets the boost for this query.
     *
     * @param float $boost the boost value to use.
     *
     * @return MatchPhraseSearchQuery
     * @since 4.0.0
     */
    public function boost(float $boost): MatchPhraseSearchQuery
    {
        $this->boost = $boost;
        return $this;
    }

    /**
     * Sets the field for this query.
     *
     * @param string $field the field to use.
     *
     * @return MatchPhraseSearchQuery
     * @since 4.0.0
     */
    public function field(string $field): MatchPhraseSearchQuery
    {
        $this->field = $field;
        return $this;
    }

    /**
     * Sets the analytics for this query.
     *
     * @param string $analyzer the analyzer to use for this query.
     *
     * @return MatchPhraseSearchQuery
     * @since 4.0.0
     */
    public function analyzer(string $analyzer): MatchPhraseSearchQuery
    {
        $this->analyzer = $analyzer;
        return $this;
    }

    /**
     * @internal
     * @return mixed
     */
    public function jsonSerialize()
    {
        return MatchPhraseSearchQuery::export($this);
    }

    /**
     * @internal
     */
    public static function export(MatchPhraseSearchQuery $query): array
    {
        $json = [
            'match_phrase' => $query->matchPhrase,
        ];
        if ($query->boost != null) {
            $json['boost'] = $query->boost;
        }
        if ($query->field != null) {
            $json['field'] = $query->field;
        }
        if ($query->analyzer != null) {
            $json['analyzer'] = $query->analyzer;
        }

        return $json;
    }
}
