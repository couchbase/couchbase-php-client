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
 * A range (or bucket) for a numeric range facet result. Counts the number of matches
 * that fall into the named range (which can overlap with other user-defined ranges).
 */
interface NumericRangeFacetResult
{
    /**
     * @return string
     */
    public function name(): string;

    /**
     * @return int|float|null
     */
    public function min();

    /**
     * @return int|float|null
     */
    public function max();

    /**
     * @return int
     */
    public function count(): int;
}
