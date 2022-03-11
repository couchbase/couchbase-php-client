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
 * Interface representing facet results.
 *
 * Only one method might return non-null value among terms(), numericRanges() and dateRanges().
 */
interface SearchFacetResult
{
    /**
     * The field the SearchFacet was targeting.
     *
     * @return string
     */
    public function field(): string;

    /**
     * The total number of *valued* facet results. Total = other() + terms (but doesn't include * missing()).
     *
     * @return int
     */
    public function total(): int;

    /**
     * The number of results that couldn't be faceted, missing the adequate value. Not matter how many more
     * buckets are added to the original facet, these result won't ever be included in one.
     *
     * @return int
     */
    public function missing(): int;

    /**
     * The number of results that could have been faceted (because they have a value for the facet's field) but
     * weren't, due to not having a bucket in which they belong. Adding a bucket can result in these results being
     * faceted.
     *
     * @return int
     */
    public function other(): int;

    /**
     * @return array of pairs string name to TermFacetResult
     */
    public function terms(): ?array;

    /**
     * @return array of pairs string name to NumericRangeFacetResult
     */
    public function numericRanges(): ?array;

    /**
     * @return array of pairs string name to DateRangeFacetResult
     */
    public function dateRanges(): ?array;
}
