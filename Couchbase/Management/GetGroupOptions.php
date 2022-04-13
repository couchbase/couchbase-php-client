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

class GetGroupOptions
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
     * @return GetGroupOptions
     * @since 4.0.0
     */
    public static function build(): GetGroupOptions
    {
        return new GetGroupOptions();
    }

    /**
     * Sets the operation timeout in milliseconds.
     *
     * @param int $milliseconds the operation timeout to apply
     * @return GetGroupOptions
     * @since 4.0.0
     */
    public function timeout(int $milliseconds): GetGroupOptions
    {
        $this->timeoutMilliseconds = $milliseconds;
        return $this;
    }

    /**
     * Sets the auth domain.
     *
     * @param string $domain the auth domain
     * @return GetGroupOptions
     * @see \Couchbase\Management\AuthDomain::LOCAL
     * @see \Couchbase\Management\AuthDomain::EXTERNAL
     * @since 4.0.0
     */
    public function domainName(string $domain): GetGroupOptions
    {
        $this->domainName = $domain;
        return $this;
    }

    /**
     * @internal
     * @param GetGroupOptions|null $options
     * @return array
     * @since 4.0.0
     */
    public static function export(?GetGroupOptions $options): array
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
