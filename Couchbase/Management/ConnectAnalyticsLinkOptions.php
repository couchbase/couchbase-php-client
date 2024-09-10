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

class ConnectAnalyticsLinkOptions
{
    private ?int $timeoutMilliseconds = null;
    private ?string $dataverseName = null;
    private ?string $linkName = null;
    private ?bool $force = null;

    /**
     * Static helper to keep code more readable
     *
     * @return ConnectAnalyticsLinkOptions
     * @since 4.2.4
     */
    public static function build(): ConnectAnalyticsLinkOptions
    {
        return new ConnectAnalyticsLinkOptions();
    }

    /**
     * Customizes the dataverse to connect to
     *
     * @param string $dataverseName The name of the dataverse
     *
     * @return ConnectAnalyticsLinkOptions
     * @since 4.2.4
     */
    public function dataverseName(string $dataverseName): ConnectAnalyticsLinkOptions
    {
        $this->dataverseName = $dataverseName;
        return $this;
    }

    /**
     * Sets whether to force link creation even if the bucket UUID changed
     *
     * @param bool $force whether to force link creation
     *
     * @return ConnectAnalyticsLinkOptions
     * @since 4.2.4
     */
    public function force(bool $force): ConnectAnalyticsLinkOptions
    {
        $this->force = $force;
        return $this;
    }

    /**
     * Sets the name of the link
     *
     * @param string $linkName the link name
     *
     * @return ConnectAnalyticsLinkOptions
     * @since 4.2.4
     */
    public function linkName(string $linkName): ConnectAnalyticsLinkOptions
    {
        $this->linkName = $linkName;
        return $this;
    }

    /**
     * Sets the operation timeout in milliseconds.
     *
     * @param int $milliseconds the operation timeout to apply
     *
     * @return ConnectAnalyticsLinkOptions
     * @since 4.2.4
     */
    public function timeout(int $milliseconds): ConnectAnalyticsLinkOptions
    {
        $this->timeoutMilliseconds = $milliseconds;
        return $this;
    }

    /**
     * @param ConnectAnalyticsLinkOptions|null $options
     *
     * @return array
     * @internal
     * @since 4.2.4
     */
    public static function export(?ConnectAnalyticsLinkOptions $options): array
    {
        if ($options == null) {
            return [];
        }

        return [
            'timeoutMilliseconds' => $options->timeoutMilliseconds,
            'dataverseName' => $options->dataverseName,
            'linkName' => $options->linkName,
            'force' => $options->force,
        ];
    }
}
