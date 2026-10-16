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

use Couchbase\Exception\InvalidArgumentException;

/**
 * Selects the replica that Collection::getReplica() reads from.
 *
 * Strategies are created through the static factories on this class.
 *
 * @see Collection::getReplica()
 *
 * @since 4.6.0
 */
abstract class GetReplicaStrategy
{
    /**
     * Read from the replica at the given index.
     *
     * @param int $replicaIndex the replica to read from, one of the ReplicaIndex constants
     * @param GetReplicaStrategyFromIndexOptions|null $options the options for the strategy
     *
     * @see ReplicaIndex
     *
     * @return GetReplicaStrategy
     * @throws InvalidArgumentException if the index is not one of the ReplicaIndex constants
     * @since 4.6.0
     */
    public static function fromIndex(
        int $replicaIndex,
        ?GetReplicaStrategyFromIndexOptions $options = null
    ): GetReplicaStrategy {
        if (
            $replicaIndex !== ReplicaIndex::FIRST &&
            $replicaIndex !== ReplicaIndex::SECOND &&
            $replicaIndex !== ReplicaIndex::THIRD
        ) {
            throw new InvalidArgumentException(
                sprintf("replica index must be ReplicaIndex::FIRST, SECOND or THIRD, got %d", $replicaIndex)
            );
        }
        return new FromIndexGetReplicaStrategy(
            $replicaIndex,
            GetReplicaStrategyFromIndexOptions::getWrap($options)
        );
    }

    /**
     * @return array
     * @internal
     */
    abstract protected function exportStrategy(): array;

    /**
     * @param GetReplicaStrategy $strategy
     *
     * @return array
     * @internal
     */
    public static function export(GetReplicaStrategy $strategy): array
    {
        return $strategy->exportStrategy();
    }
}
