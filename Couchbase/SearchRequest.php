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

class SearchRequest
{
    private ?SearchQuery $searchQuery = null;
    private ?VectorSearch $vectorSearch = null;

    /**
     * Constructs a SearchRequest.
     *
     * Use {@link SearchRequest::searchQuery()} or {@link SearchRequest::vectorSearch()} to combine a SearchQuery
     * with a VectorQuery.
     *
     * @param SearchQuery|VectorSearch $searchQueryOrVectorSearch
     *
     * @since 4.1.7
     *
     * Note: VectorSearch has stability @UNCOMMITTED
     */
    public function __construct(SearchQuery|VectorSearch $searchQueryOrVectorSearch)
    {
        if ($searchQueryOrVectorSearch instanceof SearchQuery) {
            $this->searchQuery = $searchQueryOrVectorSearch;
        } else {
            $this->vectorSearch = $searchQueryOrVectorSearch;
        }
    }

    /**
     * Static helper to keep code more readable
     *
     * Use {@link SearchRequest::searchQuery()} or {@link SearchRequest::vectorSearch()} to combine a SearchQuery
     * with a VectorQuery.
     *
     * @param SearchQuery|VectorSearch $searchQueryOrVectorSearch
     *
     * @since 4.1.7
     * @return SearchRequest
     *
     * Note: VectorSearch has stability @UNCOMMITTED
     */
    public static function build(SearchQuery|VectorSearch $searchQueryOrVectorSearch): SearchRequest
    {
        return new SearchRequest($searchQueryOrVectorSearch);
    }

    /**
     * Sets a SearchQuery in the SearchRequest, if one is not already set.
     *
     * @param SearchQuery $searchQuery
     *
     * @since 4.1.7
     * @return SearchRequest
     *
     * @throws InvalidArgumentException
     */
    public function searchQuery(SearchQuery $searchQuery): SearchRequest
    {
        if ($this->searchQuery != null) {
            throw new InvalidArgumentException("There can only be one SearchQuery in a SearchRequest");
        }
        $this->searchQuery = $searchQuery;
        return $this;
    }

    /**
     * Sets a VectorSearch in the SearchRequest, if one is not already set.
     *
     * @param VectorSearch $vectorSearch
     *
     * @since 4.1.7
     * @return SearchRequest
     *
     * @throws InvalidArgumentException
     *
     * @UNCOMMITTED: This API may change in the future.
     */
    public function vectorSearch(VectorSearch $vectorSearch): SearchRequest
    {
        if ($this->vectorSearch != null) {
            throw new InvalidArgumentException("There can only be one VectorSearch in a SearchRequest");
        }
        $this->vectorSearch = $vectorSearch;
        return $this;
    }

    /**
     * @internal
     *
     * @param SearchRequest $request
     *
     * @return array
     * @since 4.1.7
     *
     * @throws InvalidArgumentException
     */
    public static function export(SearchRequest $request): array
    {
        if ($request->searchQuery == null) {
            $request->searchQuery(new MatchNoneSearchQuery());
        }

        return [
            'searchQuery' => $request->searchQuery,
            'vectorSearch' => $request->vectorSearch,
        ];
    }
}
