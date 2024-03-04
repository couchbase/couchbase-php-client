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
 * A FTS query that matches several terms (a "phrase") as is. The order of the terms mater and no further processing is
 * applied to them, so they must appear in the index exactly as provided.  Usually for debugging purposes, prefer
 * MatchPhraseQuery.
 */
class PhraseSearchQuery implements JsonSerializable, SearchQuery
{
    private array $terms;
    private ?float $boost = null;
    private ?string $field = null;

    /**
     * @param string ...$terms
     */
    public function __construct(string ...$terms)
    {
        $this->terms = $terms;
    }

    /**
     * Static helper to keep code more readable
     *
     * @param string ...$terms
     *
     * @return PhraseSearchQuery
     * @since 4.1.7
     */
    public static function build(string ...$terms): PhraseSearchQuery
    {
        return new PhraseSearchQuery(...$terms);
    }

    /**
     * Sets the boost for this query.
     *
     * @param float $boost the boost value to use.
     *
     * @return PhraseSearchQuery
     * @since 4.0.0
     */
    public function boost(float $boost): PhraseSearchQuery
    {
        $this->boost = $boost;
        return $this;
    }

    /**
     * Sets the field for this query.
     *
     * @param string $field the field to use.
     *
     * @return PhraseSearchQuery
     * @since 4.0.0
     */
    public function field(string $field): PhraseSearchQuery
    {
        $this->field = $field;
        return $this;
    }

    /**
     * @internal
     * @return mixed
     */
    public function jsonSerialize()
    {
        return PhraseSearchQuery::export($this);
    }

    /**
     * @internal
     * @throws InvalidArgumentException
     */
    public static function export(PhraseSearchQuery $query): array
    {
        if (count($query->terms) == 0) {
            throw new InvalidArgumentException();
        }

        $json = [
            'terms' => json_encode($query->terms),
        ];
        if ($query->boost != null) {
            $json['boost'] = $query->boost;
        }
        if ($query->field != null) {
            $json['field'] = $query->field;
        }

        return $json;
    }
}
