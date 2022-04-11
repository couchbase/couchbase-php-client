<?php

/**
 * Copyright 2014-Present Couchbase, Inc.
 *
 * Licensed under the Apache License, Version 2.0 (the 'License');
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *     http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an 'AS IS' BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */

declare(strict_types=1);

namespace Couchbase;

/**
 * Class for retrieving results from search queries.
 */
class SearchResult
{
    private ?SearchMetaData $metadata = null;
    private ?array $facets = null;
    private ?array $rows = null;

    /**
     * @internal
     *
     * @param array $result
     */
    public function __construct(array $result)
    {
        $this->metadata = new SearchMetaData($result['meta']);
        $this->rows = [];
        foreach ($result['rows'] as $row) {
            $this->rows[] = [
                'id' => $row['id'],
                'index' => $row['index'],
                'score' => $row['score'],
                'explanation' => (array)json_decode($row['explanation']),
                'locations' => $row['locations'],
                'fragments' => $row['fragments'],
                'fields' => (array)json_decode($row['fields']),
            ];
        }
        $this->facets = [];
        foreach ($result['facets'] as $facet) {
            $this->facets[$facet['name']] = new SearchFacetResult($facet);
        }
    }

    /**
     * Returns metadata generated during query execution
     *
     * @return SearchMetaData|null
     */
    public function metaData(): ?SearchMetaData
    {
        return $this->metadata;
    }

    /**
     * Returns any facets returned by the query
     *
     * Array contains instances of SearchFacetResult
     * @return array|null
     */
    public function facets(): ?array
    {
        return $this->facets;
    }

    /**
     * Returns any rows returned by the query
     *
     * @return array|null
     */
    public function rows(): ?array
    {
        return $this->rows;
    }
}
