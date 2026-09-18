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
 * Selects the replica that Collection::getReplica() reads from.
 *
 * @see Collection::getReplica()
 */
class GetReplicaStrategy
{
    private int $replicaIndex;
    private bool $wrap = false;

    private function __construct(int $replicaIndex)
    {
        $this->replicaIndex = $replicaIndex;
    }

    /**
     * Read from the replica at the given index.
     *
     * @param int $replicaIndex the replica to read from
     *
     * @see ReplicaIndex
     *
     * @return GetReplicaStrategy
     */
    public static function fromIndex(int $replicaIndex): GetReplicaStrategy
    {
        return new GetReplicaStrategy($replicaIndex);
    }

    /**
     * Resolve an index that cannot be read as requested to the next one that can, instead of
     * failing with ReplicaIndexOutOfBoundsException or ReplicaIndexCurrentlyUnavailableException.
     *
     * The search walks the bucket's configured replica count, starting at the requested index
     * modulo that count. Two failures remain: a bucket configured for no replicas still throws
     * ReplicaIndexOutOfBoundsException, and a full lap that finds nothing readable throws
     * ReplicaIndexCurrentlyUnavailableException.
     *
     * @param bool $wrap whether to wrap around the available replicas
     *
     * @return GetReplicaStrategy
     */
    public function wrap(bool $wrap): GetReplicaStrategy
    {
        $this->wrap = $wrap;
        return $this;
    }

    /**
     * @param GetReplicaStrategy $strategy
     *
     * @return array
     * @internal
     */
    public static function export(GetReplicaStrategy $strategy): array
    {
        return [
            'replicaIndex' => $strategy->replicaIndex,
            'wrap' => $strategy->wrap,
        ];
    }
}
