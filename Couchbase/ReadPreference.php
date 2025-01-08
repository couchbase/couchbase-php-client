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
 * Select read preference (or affinity) for the replica APIs such as:
 *
 * @see Collection::getAllReplicas()
 * @see Collection::getAnyReplica()
 * @see Collection::lookupInAllReplicas()
 * @see Collection::lookupInAnyReplica()
 * @see TransactionAttemptContext::getReplicaFromPreferredServerGroup()
 *
 * @see https://docs.couchbase.com/server/current/manage/manage-groups/manage-groups.html
 */
interface ReadPreference
{
    /**
     * Do not enforce any filtering for replica set.
     */
    public const NO_PREFERENCE = "noPreference";

    /**
     * Exclude any nodes that do not belong to local group selected during
     * cluster instantiation with @see ClusterOptions::preferredServerGroup()
     */
    public const SELECTED_SERVER_GROUP = "selectedServerGroup";
}
