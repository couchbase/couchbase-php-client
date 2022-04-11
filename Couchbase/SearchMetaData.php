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
 * Class for retrieving metadata such as error counts and metrics generated during search queries.
 */
class SearchMetaData
{
    private ?int $successCount = null;
    private ?int $errorCount = null;
    private ?int $took = null;
    private ?int $totalHits = null;
    private ?float $maxScore = null;
    private ?array $metrics = null;
    private string $clientContextId;

    /**
     * @internal
     *
     * @param array $metadata
     */
    public function __construct(array $metadata)
    {
        $this->clientContextId = $metadata['clientContextId'];

        // Bit weird that we lift these properties out of metrics AND also expose metrics, but
        // needed for backwards compatibility.
        $metrics = $metadata['metrics'];
        $this->successCount = $metrics['successPartitionCount'];
        $this->errorCount = $metrics['errorPartitionCount'];
        $took = $metrics['tookNanoseconds'];
        if ($took > 0) {
            $this->took = intval($took / 1000);
        }
        $this->totalHits = $metrics['totalRows'];
        $this->maxScore = $metrics['maxScore'];
        $this->metrics = $metrics;
    }

    /**
     * Returns the id used to send this query..
     *
     * @return string
     */
    public function clientContextId(): string
    {
        return $this->clientContextId;
    }

    /**
     * Returns the number of pindexes successfully queried
     *
     * @return int|null
     */
    public function successCount(): ?int
    {
        return $this->successCount;
    }

    /**
     * Returns the number of errors messages reported by individual pindexes
     *
     * @return int|null
     */
    public function errorCount(): ?int
    {
        return $this->errorCount;
    }

    /**
     * Returns the time taken to complete the query
     *
     * @return int|null
     */
    public function took(): ?int
    {
        return $this->took;
    }

    /**
     * Returns the total number of matches for this result
     *
     * @return int|null
     */
    public function totalHits(): ?int
    {
        return $this->totalHits;
    }

    /**
     * Returns the highest score of all documents for this search query.
     *
     * @return float|null
     */
    public function maxScore(): ?float
    {
        return $this->maxScore;
    }

    /**
     * Returns the metrics generated during execution of this search query.
     *
     * @return array|null
     */
    public function metrics(): ?array
    {
        return $this->metrics;
    }
}
