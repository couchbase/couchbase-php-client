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

class DisconnectAnalyticsLinkOptions
{
    private ?int $timeoutMilliseconds = null;
    private ?string $dataverseName = null;
    private ?string $linkName = null;

    /**
     * Static helper to keep code more readable
     *
     * @return DisconnectAnalyticsLinkOptions
     * @since 4.2.4
     */
    public static function build(): DisconnectAnalyticsLinkOptions
    {
        return new DisconnectAnalyticsLinkOptions();
    }

    /**
     * Customizes the dataverse to disconnect from
     *
     * @param string $dataverseName The name of the dataverse
     *
     * @return DisconnectAnalyticsLinkOptions
     * @since 4.2.4
     */
    public function dataverseName(string $dataverseName): DisconnectAnalyticsLinkOptions
    {
        $this->dataverseName = $dataverseName;
        return $this;
    }

    /**
     * Sets the name of the link
     *
     * @param string $linkName the link name
     *
     * @return DisconnectAnalyticsLinkOptions
     * @since 4.2.4
     */
    public function linkName(string $linkName): DisconnectAnalyticsLinkOptions
    {
        $this->linkName = $linkName;
        return $this;
    }

    /**
     * Sets the operation timeout in milliseconds.
     *
     * @param int $milliseconds the operation timeout to apply
     *
     * @return DisconnectAnalyticsLinkOptions
     * @since 4.2.4
     */
    public function timeout(int $milliseconds): DisconnectAnalyticsLinkOptions
    {
        $this->timeoutMilliseconds = $milliseconds;
        return $this;
    }

    /**
     * @internal
     */
    public static function export(?DisconnectAnalyticsLinkOptions $options): array
    {
        if ($options == null) {
            return [];
        }

        return [
            'timeoutMilliseconds' => $options->timeoutMilliseconds,
            'dataverseName' => $options->dataverseName,
            'linkName' => $options->linkName,
        ];
    }
}
