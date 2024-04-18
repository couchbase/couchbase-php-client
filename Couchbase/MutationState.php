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
 * MutationState is an object which holds and aggregates mutation tokens across operations.
 */
class MutationState
{
    private array $tokens;

    public function __construct()
    {
    }

    /**
     * Adds the result of a mutation operation to this mutation state.
     *
     * @param MutationResult $source the result object to add to this state
     *
     * @return MutationState
     */
    public function add(MutationResult $source): MutationState
    {
        $token = $source->mutationToken();
        if ($token != null) {
            $this->tokens[] = $token;
        }

        return $this;
    }

    public function tokens(): array
    {
        return $this->tokens;
    }

    public function export(): array
    {
        $state = [];
        /** @var MutationToken $token */
        foreach ($this->tokens as $token) {
            $state[] = [
                "partitionId" => $token->partitionId(),
                "partitionUuid" => $token->partitionUuid(),
                "sequenceNumber" => $token->sequenceNumber(),
                "bucketName" => $token->bucketName(),
            ];
        }

        return $state;
    }
}
