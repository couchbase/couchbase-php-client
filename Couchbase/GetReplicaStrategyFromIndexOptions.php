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
 * Options for @see GetReplicaStrategy::fromIndex().
 *
 * @since 4.6.0
 */
class GetReplicaStrategyFromIndexOptions
{
    private bool $wrap = false;

    /**
     * Static helper to keep code more readable
     *
     * @return GetReplicaStrategyFromIndexOptions
     * @since 4.6.0
     */
    public static function build(): GetReplicaStrategyFromIndexOptions
    {
        return new GetReplicaStrategyFromIndexOptions();
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
     * @return GetReplicaStrategyFromIndexOptions
     * @since 4.6.0
     */
    public function wrap(bool $wrap): GetReplicaStrategyFromIndexOptions
    {
        $this->wrap = $wrap;
        return $this;
    }

    /**
     * @param GetReplicaStrategyFromIndexOptions|null $options
     *
     * @return bool
     * @internal
     */
    public static function getWrap(?GetReplicaStrategyFromIndexOptions $options): bool
    {
        return $options?->wrap ?? false;
    }
}
