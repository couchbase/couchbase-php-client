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
 * Authenticator type which uses a JWT token to authenticate with the cluster.
 *
 * @since 4.5.0
 *
 * @UNCOMMITTED: This API may change in the future.
 */
Class JwtAuthenticator implements Authenticator
{
    private string $token;

    /**
     * Creates a new JwtAuthenticator instance.
     *
     * @param string $token JWT to use for authentication.
     *
     * @since 4.5.0
     * @UNCOMMITTED: This API may change in the future.
     */
    public function __construct(string $token)
    {
        $this->token = $token;
    }

    /**
     * @internal
     */
    public function export(): array
    {
        return [
            'type' => 'jwt',
            'token' => $this->token,
        ];
    }
}
