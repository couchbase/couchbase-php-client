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

class SearchOptions implements JsonSerializable
{
    public function jsonSerialize()
    {
    }

    /**
     * Sets the server side timeout in milliseconds
     *
     * @param int $ms the server side timeout to apply
     * @return SearchOptions
     */
    public function timeout(int $ms): SearchOptions
    {
    }

    /**
     * Add a limit to the query on the number of hits it can return
     *
     * @param int $limit the maximum number of hits to return
     * @return SearchOptions
     */
    public function limit(int $limit): SearchOptions
    {
    }

    /**
     * Set the number of hits to skip (eg. for pagination).
     *
     * @param int $skip the number of results to skip
     * @return SearchOptions
     */
    public function skip(int $skip): SearchOptions
    {
    }

    /**
     * Activates the explanation of each result hit in the response
     *
     * @param bool $explain
     * @return SearchOptions
     */
    public function explain(bool $explain): SearchOptions
    {
    }

    /**
     * If set to true, the server will not perform any scoring on the hits
     *
     * @param bool $disabled
     * @return SearchOptions
     */
    public function disableScoring(bool $disabled): SearchOptions
    {
    }

    /**
     * Sets the consistency to consider for this FTS query to AT_PLUS and
     * uses the MutationState to parameterize the consistency.
     *
     * This replaces any consistency tuning previously set.
     *
     * @param MutationState $state the mutation state information to work with
     * @return SearchOptions
     */
    public function consistentWith(string $index, MutationState $state): SearchOptions
    {
    }

    /**
     * Configures the list of fields for which the whole value should be included in the response.
     *
     * If empty, no field values are included. This drives the inclusion of the fields in each hit.
     * Note that to be highlighted, the fields must be stored in the FTS index.
     *
     * @param string[] $fields
     * @return SearchOptions
     */
    public function fields(array $fields): SearchOptions
    {
    }

    /**
     * Adds one SearchFacet-s to the query
     *
     * This is an additive operation (the given facets are added to any facet previously requested),
     * but if an existing facet has the same name it will be replaced.
     *
     * Note that to be faceted, a field's value must be stored in the FTS index.
     *
     * @param SearchFacet[] $facets
     * @return SearchOptions
     *
     * @see \SearchFacet
     * @see \TermSearchFacet
     * @see \NumericRangeSearchFacet
     * @see \DateRangeSearchFacet
     */
    public function facets(array $facets): SearchOptions
    {
    }

    /**
     * Configures the list of fields (including special fields) which are used for sorting purposes.
     * If empty, the default sorting (descending by score) is used by the server.
     *
     * The list of sort fields can include actual fields (like "firstname" but then they must be stored in the
     * index, configured in the server side mapping). Fields provided first are considered first and in a "tie" case
     * the next sort field is considered. So sorting by "firstname" and then "lastname" will first sort ascending by
     * the firstname and if the names are equal then sort ascending by lastname. Special fields like "_id" and
     * "_score" can also be used. If prefixed with "-" the sort order is set to descending.
     *
     * If no sort is provided, it is equal to sort("-_score"), since the server will sort it by score in descending
     * order.
     *
     * @param array $specs sort the fields that should take part in the sorting.
     * @return SearchOptions
     */
    public function sort(array $specs): SearchOptions
    {
    }

    /**
     * Configures the highlighting of matches in the response
     *
     * @param string $style highlight style to apply. Use constants HIGHLIGHT_HTML,
     *   HIGHLIGHT_ANSI, HIGHLIGHT_SIMPLE.
     * @param string ...$fields the optional fields on which to highlight.
     *   If none, all fields where there is a match are highlighted.
     * @return SearchOptions
     *
     * @see \SearchHighlightMode::HTML
     * @see \SearchHighlightMode::ANSI
     * @see \SearchHighlightMode::SIMPLE
     */
    public function highlight(string $style = null, array $fields = null): SearchOptions
    {
    }


    /**
     * Configures the list of collections to use for restricting results.
     *
     * @param string[] $collectionNames
     * @return SearchOptions
     */
    public function collections(array $collectionNames): SearchOptions
    {
    }
}
