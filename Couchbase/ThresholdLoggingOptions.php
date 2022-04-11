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

class ThresholdLoggingOptions
{
    private ?int $orphanedEmitIntervalMilliseconds = null;
    private ?int $orphanedSampleSize = null;

    private ?int $thresholdEmitIntervalMilliseconds = null;
    private ?int $thresholdSampleSize = null;
    private ?int $analyticsThresholdMilliseconds = null;
    private ?int $eventingThresholdMilliseconds = null;
    private ?int $keyValueThresholdMilliseconds = null;
    private ?int $managementThresholdMilliseconds = null;
    private ?int $queryThresholdMilliseconds = null;
    private ?int $searchThresholdMilliseconds = null;
    private ?int $viewThresholdMilliseconds = null;

    /**
     * Specifies how often orphaned spans should be logged.
     *
     * @param int $milliseconds
     *
     * @return ThresholdLoggingOptions
     * @since 4.0.0
     */
    public function orphanedEmitInterval(int $milliseconds): ThresholdLoggingOptions
    {
        $this->orphanedEmitIntervalMilliseconds = $milliseconds;
        return $this;
    }

    /**
     * Specifies the number of orphaned spans which should be kept between each logging interval.
     *
     * @param int $numberOfSamples
     *
     * @return ThresholdLoggingOptions
     * @since 4.0.0
     */
    public function orphanedSampleSize(int $numberOfSamples): ThresholdLoggingOptions
    {
        $this->orphanedSampleSize = $numberOfSamples;
        return $this;
    }


    /**
     * Specifies how often aggregated trace information should be logged.
     *
     * @param int $milliseconds
     *
     * @return ThresholdLoggingOptions
     * @since 4.0.0
     */
    public function thresholdEmitInterval(int $milliseconds): ThresholdLoggingOptions
    {
        $this->thresholdEmitIntervalMilliseconds = $milliseconds;
        return $this;
    }

    /**
     * Specifies the number of entries which should be kept between each logging interval.
     *
     * @param int $numberOfSamples
     *
     * @return ThresholdLoggingOptions
     * @since 4.0.0
     */
    public function thresholdSampleSize(int $numberOfSamples): ThresholdLoggingOptions
    {
        $this->thresholdSampleSize = $numberOfSamples;
        return $this;
    }

    /**
     * Specifies the threshold for when a kv request should be included in the aggregated metrics.
     *
     * @param int $milliseconds
     *
     * @return ThresholdLoggingOptions
     * @since 4.0.0
     */
    public function keyValueThreshold(int $milliseconds): ThresholdLoggingOptions
    {
        $this->keyValueThresholdMilliseconds = $milliseconds;
        return $this;
    }

    /**
     * Specifies the threshold for when a query request should be included in the aggregated metrics.
     *
     * @param int $milliseconds
     *
     * @return ThresholdLoggingOptions
     * @since 4.0.0
     */
    public function queryThreshold(int $milliseconds): ThresholdLoggingOptions
    {
        $this->queryThresholdMilliseconds = $milliseconds;
        return $this;
    }

    /**
     * Specifies the threshold for when a views request should be included in the aggregated metrics.
     *
     * @param int $milliseconds
     *
     * @return ThresholdLoggingOptions
     * @since 4.0.0
     */
    public function viewThreshold(int $milliseconds): ThresholdLoggingOptions
    {
        $this->viewThresholdMilliseconds = $milliseconds;
        return $this;
    }

    /**
     * Specifies the threshold for when a search request should be included in the aggregated metrics.
     *
     * @param int $milliseconds
     *
     * @return ThresholdLoggingOptions
     * @since 4.0.0
     */
    public function searchThreshold(int $milliseconds): ThresholdLoggingOptions
    {
        $this->searchThresholdMilliseconds = $milliseconds;
        return $this;
    }

    /**
     * Specifies the threshold for when an analytics request should be included in the aggregated metrics.
     *
     * @param int $milliseconds
     *
     * @return ThresholdLoggingOptions
     * @since 4.0.0
     */
    public function analyticsThreshold(int $milliseconds): ThresholdLoggingOptions
    {
        $this->analyticsThresholdMilliseconds = $milliseconds;
        return $this;
    }

    /**
     * @internal
     * @return array
     */
    public function export(): array
    {
        return [
            'orphanedEmitInterval' => $this->orphanedEmitIntervalMilliseconds,
            'orphanedSampleSize' => $this->orphanedSampleSize,

            'thresholdEmitInterval' => $this->thresholdEmitIntervalMilliseconds,
            'thresholdSampleSize' => $this->thresholdSampleSize,
            'analyticsThreshold' => $this->analyticsThresholdMilliseconds,
            'eventingThreshold' => $this->eventingThresholdMilliseconds,
            'keyValueThreshold' => $this->keyValueThresholdMilliseconds,
            'managementThreshold' => $this->managementThresholdMilliseconds,
            'queryThreshold' => $this->queryThresholdMilliseconds,
            'searchThreshold' => $this->searchThresholdMilliseconds,
            'viewThreshold' => $this->viewThresholdMilliseconds,
        ];
    }
}
