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
 * Class representing facet results.
 *
 * Only one method might return non-null value among terms(), numericRanges() and dateRanges().
 */
class SearchFacetResult
{
    private string $field;
    private int $total;
    private int $missing;
    private int $other;
    private ?array $terms = null;
    private ?array $numericRanges = null;
    private ?array $dateRanges = null;

    /**
     * @internal
     *
     * @param array $facet
     */
    public function __construct(array $facet)
    {
        $this->field = $facet['field'];
        $this->total = $facet['total'];
        $this->missing = $facet['missing'];
        $this->other = $facet['other'];
        if (array_key_exists('terms', $facet)) {
            $this->terms = [];
            foreach ($facet['terms'] as $term) {
                $this->terms[] = new TermFacetResult($term);
            }
        }
        if (array_key_exists('dateRanges', $facet)) {
            $this->dateRanges = [];
            foreach ($facet['dateRanges'] as $range) {
                $this->dateRanges[] = new DateRangeFacetResult($range);
            }
        }
        if (array_key_exists('numericRanges', $facet)) {
            $this->numericRanges = [];
            foreach ($facet['numericRanges'] as $range) {
                $this->numericRanges[] = new NumericRangeFacetResult($range);
            }
        }
    }

    /**
     * The field the SearchFacet was targeting.
     *
     * @return string
     * @since 4.0.0
     */
    public function field(): string
    {
        return $this->field;
    }

    /**
     * The total number of *valued* facet results. Total = other() + terms (but doesn't include * missing()).
     *
     * @return int
     * @since 4.0.0
     */
    public function total(): int
    {
        return $this->total;
    }

    /**
     * The number of results that couldn't be faceted, missing the adequate value. Not matter how many more
     * buckets are added to the original facet, these result won't ever be included in one.
     *
     * @return int
     * @since 4.0.0
     */
    public function missing(): int
    {
        return $this->missing;
    }

    /**
     * The number of results that could have been faceted (because they have a value for the facet's field) but
     * weren't, due to not having a bucket in which they belong. Adding a bucket can result in these results being
     * faceted.
     *
     * @return int
     * @since 4.0.0
     */
    public function other(): int
    {
        return $this->other;
    }

    /**
     * @return array of pairs string name to TermFacetResult
     * @since 4.0.0
     */
    public function terms(): ?array
    {
        return $this->terms;
    }

    /**
     * @return array of pairs string name to NumericRangeFacetResult
     * @since 4.0.0
     */
    public function numericRanges(): ?array
    {
        return $this->numericRanges;
    }

    /**
     * @return array of pairs string name to DateRangeFacetResult
     * @since 4.0.0
     */
    public function dateRanges(): ?array
    {
        return $this->dateRanges;
    }
}
