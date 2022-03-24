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
 * Set of metrics returned by an analytics query.
 */
class AnalyticsMetrics
{
    private int $errorCount;
    private int $processedObjects;
    private int $resultCount;
    private int $resultSize;
    private int $warningCount;
    private int $elapsedTimeMilliseconds;
    private int $executionTimeMilliseconds;

    public function __construct(?array $metrics = null) {
        if ($metrics == null) {
            $this->errorCount = 0;
            $this->processedObjects = 0;
            $this->resultCount = 0;
            $this->resultSize = 0;
            $this->warningCount = 0;
            $this->elapsedTimeMilliseconds = 0;
            $this->executionTimeMilliseconds = 0;
        } else {
            $this->errorCount = $metrics["errorCount"];
            $this->processedObjects = $metrics["processedObjects"];
            $this->resultCount = $metrics["resultCount"];
            $this->resultSize = $metrics["resultSize"];
            $this->warningCount = $metrics["warningCount"];
            $this->elapsedTimeMilliseconds = $metrics["elapsedTimeMilliseconds"];
            $this->executionTimeMilliseconds = $metrics["executionTimeMilliseconds"];
        }
    }

    /**
     * The number of errors returned by this query.
     *
     * @return int
     */
    public function errorCount(): int
    {
        return $this->errorCount;
    }

    /**
     * The number of objects processed by this query.
     *
     * @return int
     */
    public function processedObjects(): int
    {
        return $this->processedObjects;
    }

    /**
     * The number of results returned by this query.
     *
     * @return int
     */
    public function resultCount(): int
    {
        return $this->resultCount;
    }

    /**
     * The total number of bytes in the results.
     *
     * @return int
     */
    public function resultSize(): int
    {
        return $this->resultSize;
    }

    /**
     * The number of warnings returned by this query.
     *
     * @return int
     */
    public function warningCount(): int
    {
        return $this->warningCount;
    }

    /**
     * The total time taken for the request, that is the time from when the
     * request was received until the results were returned.
     *
     * @return int
     */
    public function elapsedTimeMilliseconds(): int
    {
        return $this->elapsedTimeMilliseconds;
    }

    /**
     * The time taken for the execution of the request, that is the time from
     * when query execution started until the results were returned.
     *
     * @return int
     */
    public function executionTimeMilliseconds(): int
    {
        return $this->executionTimeMilliseconds;
    }


}
