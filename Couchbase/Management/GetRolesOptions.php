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

namespace Couchbase\Management;

class GetRolesOptions
{
    private ?string $domainName = null;
    private ?int $timeoutMilliseconds = null;

    /**
     * @since 4.0.0
     */
    public function __construct()
    {
    }

    /**
     * Static helper to keep code more readable
     *
     * @return GetRolesOptions
     * @since 4.0.0
     */
    public static function build(): GetRolesOptions
    {
        return new GetRolesOptions();
    }

    /**
     * Sets the operation timeout in milliseconds.
     *
     * @param int $milliseconds the operation timeout to apply
     * @return GetRolesOptions
     * @since 4.0.0
     */
    public function timeout(int $milliseconds): GetRolesOptions
    {
        $this->timeoutMilliseconds = $milliseconds;
        return $this;
    }

    /**
     * Sets the auth domain.
     *
     * @param string $domain the auth domain
     * @return GetRolesOptions
     * @see \Couchbase\Management\AuthDomain::LOCAL
     * @see \Couchbase\Management\AuthDomain::EXTERNAL
     * @since 4.0.0
     */
    public function domainName(string $domain): GetRolesOptions
    {
        $this->domainName = $domain;
        return $this;
    }

    /**
     * @internal
     * @param GetRolesOptions|null $options
     * @return array
     * @since 4.0.0
     */
    public static function export(?GetRolesOptions $options): array
    {
        if ($options == null) {
            return [];
        }
        return [
            'timeoutMilliseconds' => $options->timeoutMilliseconds,
            'domain' => $options->domainName
        ];
    }
}
