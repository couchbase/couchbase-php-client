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
 * Interface for results created by operations that perform mutations.
 */
class MutationResult extends Result
{
    private ?MutationToken $mutationToken = null;

    /**
     * @internal
     *
     * @param array $response raw response from the extension
     *
     * @since 4.0.0
     */
    public function __construct(array $response)
    {
        parent::__construct($response);
        if (array_key_exists('mutationToken', $response)) {
            $this->mutationToken = new MutationToken($response['mutationToken']);
        }
    }

    /**
     * Returns the mutation token generated during the mutation
     *
     * @return MutationToken|null
     * @since 4.0.0
     */
    public function mutationToken(): ?MutationToken
    {
        return $this->mutationToken;
    }
}
