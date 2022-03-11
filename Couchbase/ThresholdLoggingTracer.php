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
 * This implements a basic default tracer which keeps track of operations
 * which falls outside a specified threshold.  Note that to reduce the
 * performance impact of using this tracer, this class is not actually
 * used by the SDK, and simply acts as a placeholder which triggers a
 * native implementation to be used instead.
 */
class ThresholdLoggingTracer implements RequestTracer
{
    public function requestSpan(string $name, RequestSpan $parent = null)
    {
    }

    /**
     * Specifies how often aggregated trace information should be logged,
     * specified in microseconds.
     */
    public function emitInterval(int $duration)
    {
    }

    /**
     * Specifies the threshold for when a kv request should be included
     * in the aggregated metrics, specified in microseconds.
     */
    public function kvThreshold(int $duration)
    {
    }

    /**
     * Specifies the threshold for when a query request should be included
     * in the aggregated metrics, specified in microseconds.
     */
    public function queryThreshold(int $duration)
    {
    }

    /**
     * Specifies the threshold for when a views request should be included
     * in the aggregated metrics, specified in microseconds.
     */
    public function viewsThreshold(int $duration)
    {
    }

    /**
     * Specifies the threshold for when a search request should be included
     * in the aggregated metrics, specified in microseconds.
     */
    public function searchThreshold(int $duration)
    {
    }

    /**
     * Specifies the threshold for when an analytics request should be included
     * in the aggregated metrics, specified in microseconds.
     */
    public function analyticsThreshold(int $duration)
    {
    }

    /**
     * Specifies the number of entries which should be kept between each
     * logging interval.
     */
    public function sampleSize(int $size)
    {
    }
}
