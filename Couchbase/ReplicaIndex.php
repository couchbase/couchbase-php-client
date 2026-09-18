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
 * Replica to read from with @see GetReplicaStrategy::fromIndex(), where ReplicaIndex::FIRST is the
 * first replica of the vbucket. The active copy cannot be selected this way.
 *
 * @see Collection::getReplica()
 */
interface ReplicaIndex
{
    public const FIRST = 0;
    public const SECOND = 1;
    public const THIRD = 2;
}
