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

class SearchOptions implements JsonSerializable
{
    private ?int $timeoutMilliseconds = null;
    private ?int $limit = null;
    private ?int $skip = null;
    private ?bool $explain = null;
    private ?bool $disableScoring = null;
    private ?MutationState $consistentWith = null;
    private ?array $fields = null;
    private ?array $facets = null;
    private ?array $sort = null;
    private ?array $highlight = null;
    private ?array $collectionNames = null;
    private ?array $raw;
    private ?bool $includeLocations = null;

    /**
     * Static helper to keep code more readable
     *
     * @return SearchOptions
     * @since 4.0.0
     */
    public static function build(): SearchOptions
    {
        return new SearchOptions();
    }

    /**
     * Sets the server side timeout in milliseconds
     *
     * @param int $ms the server side timeout to apply
     *
     * @return SearchOptions
     * @since 4.0.0
     */
    public function timeout(int $ms): SearchOptions
    {
        $this->timeoutMilliseconds = $ms;
        return $this;
    }

    /**
     * Add a limit to the query on the number of hits it can return
     *
     * @param int $limit the maximum number of hits to return
     *
     * @return SearchOptions
     * @since 4.0.0
     */
    public function limit(int $limit): SearchOptions
    {
        $this->limit = $limit;
        return $this;
    }

    /**
     * Set the number of hits to skip (eg. for pagination).
     *
     * @param int $skip the number of results to skip
     *
     * @return SearchOptions
     * @since 4.0.0
     */
    public function skip(int $skip): SearchOptions
    {
        $this->skip = $skip;
        return $this;
    }

    /**
     * Activates the explanation of each result hit in the response
     *
     * @param bool $explain
     *
     * @return SearchOptions
     * @since 4.0.0
     */
    public function explain(bool $explain): SearchOptions
    {
        $this->explain = $explain;
        return $this;
    }

    /**
     * If set to true, the server will not perform any scoring on the hits
     *
     * @param bool $disabled
     *
     * @return SearchOptions
     * @since 4.0.0
     */
    public function disableScoring(bool $disabled): SearchOptions
    {
        $this->disableScoring = $disabled;
        return $this;
    }

    /**
     * Sets the consistency to consider for this FTS query to AT_PLUS and
     * uses the MutationState to parameterize the consistency.
     *
     * This replaces any consistency tuning previously set.
     *
     * @param MutationState $state the mutation state information to work with
     *
     * @return SearchOptions
     * @since 4.0.0
     */
    public function consistentWith(string $index, MutationState $state): SearchOptions
    {
        $this->consistentWith = $state;
        return $this;
    }

    /**
     * Configures the list of fields for which the whole value should be included in the response.
     *
     * If empty, no field values are included. This drives the inclusion of the fields in each hit.
     * Note that to be highlighted, the fields must be stored in the FTS index.
     *
     * @param string[] $fields
     *
     * @return SearchOptions
     * @since 4.0.0
     */
    public function fields(array $fields): SearchOptions
    {
        $this->fields = $fields;
        return $this;
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
     *
     * @return SearchOptions
     * @since 4.0.0
     *
     * @see \SearchFacet
     * @see \TermSearchFacet
     * @see \NumericRangeSearchFacet
     * @see \DateRangeSearchFacet
     */
    public function facets(array $facets): SearchOptions
    {
        if ($this->facets == null) {
            $this->facets = [];
        }
        foreach ($facets as $name => $facet) {
            $this->facets[$name] = json_encode($facet);
        }
        return $this;
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
     *
     * @return SearchOptions
     * @since 4.0.0
     */
    public function sort(array $specs): SearchOptions
    {
        $this->sort = $specs;
        return $this;
    }

    /**
     * Configures the highlighting of matches in the response
     *
     * @param string|null $style highlight style to apply.
     * @param string ...$fields the optional fields on which to highlight.
     *   If none, all fields where there is a match are highlighted.
     *
     * @return SearchOptions
     * @since 4.0.0
     *
     * @see \SearchHighlightMode::HTML
     * @see \SearchHighlightMode::ANSI
     * @see \SearchHighlightMode::SIMPLE
     */
    public function highlight(string $style = null, array $fields = null): SearchOptions
    {
        $this->highlight['style'] = $style;
        $this->highlight['fields'] = $fields;
        return $this;
    }


    /**
     * Configures the list of collections to use for restricting results.
     *
     * @param string[] $collectionNames
     *
     * @return SearchOptions
     * @since 4.0.0
     */
    public function collections(array $collectionNames): SearchOptions
    {
        $this->collectionNames = $collectionNames;
        return $this;
    }

    /**
     * Sets any extra query parameters that the SDK does not provide an option for.
     *
     * @param string $key the name of the parameter
     * @param mixed $value the value of the parameter
     *
     * @return SearchOptions
     */
    public function raw(string $key, $value): SearchOptions
    {
        if ($this->raw == null) {
            $this->raw = [];
        }

        $this->raw[$key] = $value;
        return $this;
    }

    /**
     * If set to true, the result will include search row locations.
     *
     * @param bool $enabled
     *
     * @return SearchOptions
     * @since 4.0.0
     */
    public function includeLocations(bool $enabled): SearchOptions
    {
        $this->includeLocations = $enabled;
        return $this;
    }

    /**
     * @return mixed
     * @internal
     */
    public function jsonSerialize()
    {
        return SearchOptions::export($this);
    }

    /**
     * @internal
     */
    public static function export(?SearchOptions $options): array
    {
        if ($options == null) {
            return [];
        }

        $highlightStyle = null;
        $highlightFields = null;
        if ($options->highlight != null) {
            $highlightFields = $options->highlight['fields'];
            $highlightStyle = $options->highlight['style'];
        }
        $sort = null;
        if ($options->sort != null) {
            $sort = [];
            foreach ($options->sort as $s) {
                $sort[] = json_encode($s);
            }
        }
        return [
            'timeoutMilliseconds' => $options->timeoutMilliseconds,
            'limit' => $options->limit,
            'skip' => $options->skip,
            'explain' => $options->explain,
            'disableScoring' => $options->disableScoring,
            'fields' => $options->fields,
            'sortSpecs' => $sort,
            'consistentWith' => $options->consistentWith == null ? null : $options->consistentWith->export(),
            'facets' => $options->facets,
            'highlightStyle' => $highlightStyle,
            'highlightFields' => $highlightFields,
            'collections' => $options->collectionNames,
            'includeLocations' => $options->includeLocations,
            'showRequest' => null,
        ];
    }
}
