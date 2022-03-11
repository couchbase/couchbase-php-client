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

namespace Couchbase\Management;

interface EvictionPolicy
{
    /**
     * During ejection, everything (including key, metadata, and value) will be ejected.
     *
     * Full Ejection reduces the memory overhead requirement, at the cost of performance.
     *
     * This value is only valid for buckets of type COUCHBASE.
     */
    public const FULL = "fullEviction";

    /**
     * During ejection, only the value will be ejected (key and metadata will remain in memory).
     *
     * Value Ejection needs more system memory, but provides better performance than Full Ejection.
     *
     * This value is only valid for buckets of type COUCHBASE.
     */
    public const VALUE_ONLY = "valueOnly";

    /**
     * Couchbase Server keeps all data until explicitly deleted, but will reject
     * any new data if you reach the quota (dedicated memory) you set for your bucket.
     *
     * This value is only valid for buckets of type EPHEMERAL.
     */
    public const NO_EVICTION = "noEviction";

    /**
     * When the memory quota is reached, Couchbase Server ejects data that has not been used recently.
     *
     * This value is only valid for buckets of type EPHEMERAL.
     */
    public const NOT_RECENTLY_USED = "nruEviction";
}
