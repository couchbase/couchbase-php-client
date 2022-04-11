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
 * An object which contains levels of durability that can be enforced when
 * using mutation operations.
 */
interface DurabilityLevel
{
    /**
     * Apply no durability level.
     */
    public const NONE = "none";

    /**
     * Apply a durability level where the document must be written to memory
     * on a majority of nodes in the cluster.
     */
    public const MAJORITY = "majority";

    /**
     * Apply a durability level where the document must be written to memory
     * on a majority of nodes in the cluster and written to disk on the
     * active node.
     */
    public const MAJORITY_AND_PERSIST_TO_ACTIVE = "majorityAndPersistToActive";

    /**
     * Apply a durability level where the document must be written to disk
     * on a majority of nodes in the cluster.
     */
    public const PERSIST_TO_MAJORITY = "persistToMajority";
}
