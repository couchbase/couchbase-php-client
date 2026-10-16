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
 * Reads from the replica at a given index.
 *
 * @see GetReplicaStrategy::fromIndex()
 *
 * @since 4.6.0
 */
final class FromIndexGetReplicaStrategy extends GetReplicaStrategy
{
    private int $replicaIndex;
    private bool $wrap;

    /**
     * @internal use GetReplicaStrategy::fromIndex()
     */
    public function __construct(int $replicaIndex, bool $wrap)
    {
        $this->replicaIndex = $replicaIndex;
        $this->wrap = $wrap;
    }

    /**
     * @return array
     * @internal
     */
    protected function exportStrategy(): array
    {
        return [
            'replicaIndex' => $this->replicaIndex,
            'wrap' => $this->wrap,
        ];
    }
}
