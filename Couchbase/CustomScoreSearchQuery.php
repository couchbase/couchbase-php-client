<?php

/**
 * Copyright 2026-Present Couchbase, Inc.
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
 * A FTS query that wraps another query and passes its results through a user-defined JavaScript
 * scoring function, which assigns each result its score in place of the score given by the wrapped
 * query.
 *
 * The function is invoked as `function(doc, params)`, where `doc.score` is the score assigned by
 * the wrapped query, `doc.fields` holds the document fields requested with
 * {@link CustomScoreSearchQuery::fields()}, and `params` holds the values given with
 * {@link CustomScoreSearchQuery::parameters()}. It must return a number.
 *
 * This feature is disabled on the server by default. If the cluster does not support it, the search
 * fails with {@link \Couchbase\Exception\FeatureNotAvailableException}.
 *
 * @since 4.7.0
 */
class CustomScoreSearchQuery implements JsonSerializable, SearchQuery
{
    private SearchQuery $query;
    private string $source;
    private array $fields = [];
    private array $parameters = [];

    /**
     * @param SearchQuery $query the query whose results are passed through the scoring function
     * @param string $source the JavaScript source of the scoring function
     *
     * @since 4.7.0
     */
    public function __construct(SearchQuery $query, string $source)
    {
        $this->query = $query;
        $this->source = $source;
    }

    /**
     * Static helper to keep code more readable
     *
     * @param SearchQuery $query the query whose results are passed through the scoring function
     * @param string $source the JavaScript source of the scoring function
     *
     * @return CustomScoreSearchQuery
     * @since 4.7.0
     */
    public static function build(SearchQuery $query, string $source): CustomScoreSearchQuery
    {
        return new CustomScoreSearchQuery($query, $source);
    }

    /**
     * Sets the document fields made available to the scoring function through `doc.fields`. The
     * function cannot see any field that is not listed here. Use `"*"` to make all fields of the
     * document available. Any fields set previously are replaced.
     *
     * @param string ...$fields the names of the fields
     *
     * @return CustomScoreSearchQuery
     * @since 4.7.0
     */
    public function fields(string ...$fields): CustomScoreSearchQuery
    {
        $this->fields = $fields;
        return $this;
    }

    /**
     * Sets the parameters made available to the scoring function through `params`. Any parameters
     * set previously are replaced.
     *
     * @param array $parameters associative array of parameter names to values. Each value may be
     *   any JSON-encodable value, including nested arrays and objects.
     *
     * @return CustomScoreSearchQuery
     * @since 4.7.0
     */
    public function parameters(array $parameters): CustomScoreSearchQuery
    {
        $this->parameters = $parameters;
        return $this;
    }

    /**
     * @internal
     * @return mixed
     */
    public function jsonSerialize(): mixed
    {
        return $this->export();
    }

    /**
     * @internal
     */
    public function export(): array
    {
        $json = [
            'query' => $this->query->export(),
            'source' => $this->source,
        ];
        if (count($this->fields) > 0) {
            $json['fields'] = $this->fields;
        }
        if (count($this->parameters) > 0) {
            $json['params'] = $this->parameters;
        }

        return ['custom_score' => $json];
    }
}
