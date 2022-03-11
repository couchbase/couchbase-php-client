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
 * Interface for retrieving metadata such as error counts and metrics generated during search queries.
 */
interface SearchMetaData
{
    /**
     * Returns the number of pindexes successfully queried
     *
     * @return int|null
     */
    public function successCount(): ?int;

    /**
     * Returns the number of errors messages reported by individual pindexes
     *
     * @return int|null
     */
    public function errorCount(): ?int;

    /**
     * Returns the time taken to complete the query
     *
     * @return int|null
     */
    public function took(): ?int;

    /**
     * Returns the total number of matches for this result
     *
     * @return int|null
     */
    public function totalHits(): ?int;

    /**
     * Returns the highest score of all documents for this search query.
     *
     * @return float|null
     */
    public function maxScore(): ?float;

    /**
     * Returns the metrics generated during execution of this search query.
     *
     * @return array|null
     */
    public function metrics(): ?array;
}
