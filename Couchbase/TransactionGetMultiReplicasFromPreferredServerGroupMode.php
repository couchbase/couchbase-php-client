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
 * Strategy to deal with potential read skews while reading multiple documents.
 *
 * Essentially when a transaction reads document X and then Y, and another
 * transaction commits a change to Y inbetween those reads - read skew has
 * occurred.
 *
 * @since 4.3.0
 */
interface TransactionGetMultiReplicasFromPreferredServerGroupMode
{
    /**
     * Some time-bounded effort will be made to detect and avoid read skew.
     *
     * @since 4.3.0
     */
    public const PRIORITISE_LATENCY = "prioritiseLatency";

    /**
     * No read skew detection should be attempted. Once the documents are fetched, they will be
     * returned immediately.
     *
     * @since 4.3.0
     */
    public const DISABLE_READ_SKEW_DETECTION = "disableReadSkewDetection";

    /**
     * Great effort will be made to detect and avoid read skew.
     *
     * @since 4.3.0
     */
    public const PRIORITISE_READ_SKEW_DETECTION = "prioritiseReadSkewDetection";
}
