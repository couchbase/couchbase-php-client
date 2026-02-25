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

use Couchbase\Exception\InvalidArgumentException;
use Couchbase\RequestSpan;

class GetAnalyticsLinksOptions
{
    private ?int $timeoutMilliseconds = null;
    private ?string $dataverseName = null;
    private ?string $name = null;
    private ?string $linkType = null;
    private ?RequestSpan $parentSpan = null;

    /**
     * Static helper to keep code more readable
     *
     * @return GetAnalyticsLinksOptions
     * @since 4.2.4
     */
    public static function build(): GetAnalyticsLinksOptions
    {
        return new GetAnalyticsLinksOptions();
    }

    /**
     * Sets the operation timeout in milliseconds.
     *
     * @param int $milliseconds the operation timeout to apply
     *
     * @return GetAnalyticsLinksOptions
     * @since 4.2.4
     */
    public function timeout(int $milliseconds): GetAnalyticsLinksOptions
    {
        $this->timeoutMilliseconds = $milliseconds;
        return $this;
    }

    /**
     * Customizes the dataverse to restrict links to
     *
     * @param string $dataverseName The name of the dataverse
     *
     * @return GetAnalyticsLinksOptions
     * @since 4.2.4
     */
    public function dataverseName(string $dataverseName): GetAnalyticsLinksOptions
    {
        $this->dataverseName = $dataverseName;
        return $this;
    }

    /**
     * The type of links to restrict returned links to.
     *
     * @param string $type the link type, must be value one of 'couchbase', 's3', or 'azureblob'
     *
     * @see AnalyticsLinkType::AZURE_BLOB
     * @see AnalyticsLinkType::S3
     * @see AnalyticsLinkType::COUCHBASE
     *
     * @throws InvalidArgumentException
     *
     * @return GetAnalyticsLinksOptions
     * @since 4.2.4
     */
    public function linkType(string $type): GetAnalyticsLinksOptions
    {
        if (
            $type != AnalyticsLinkType::COUCHBASE &&
            $type != AnalyticsLinkType::AZURE_BLOB &&
            $type != AnalyticsLinkType::S3
        ) {
            throw new InvalidArgumentException("linkType value must be one of 'couchbase', 's3', or 'azureblob'");
        }
        $this->linkType = $type;
        return $this;
    }

    /**
     * Sets the name of the link to fetch. If set, then dataverse must also be set.
     *
     * @param string $name The name of the link
     *
     * @return GetAnalyticsLinksOptions
     * @since 4.2.4
     */
    public function name(string $name): GetAnalyticsLinksOptions
    {
        $this->name = $name;
        return $this;
    }

    /**
     * Sets the parent span.
     *
     * @param RequestSpan $parentSpan the parent span
     *
     * @return GetAnalyticsLinksOptions
     * @since 4.5.0
     */
    public function parentSpan(RequestSpan $parentSpan): GetAnalyticsLinksOptions
    {
        $this->parentSpan = $parentSpan;
        return $this;
    }

    /**
     * @internal
     */
    public static function getParentSpan(?GetAnalyticsLinksOptions $options): ?RequestSpan
    {
        return $options?->parentSpan;
    }

    /**
     * @internal
     */
    public static function export(?GetAnalyticsLinksOptions $options): array
    {
        if ($options == null) {
            return [];
        }

        if (isset($options->name) && !isset($options->dataverseName)) {
            throw new InvalidArgumentException("If the link name is set, the dataverseName must also be set.");
        }

        return [
            'timeoutMilliseconds' => $options->timeoutMilliseconds,
            'dataverseName' => $options->dataverseName,
            'name' => $options->name,
            'linkType' => $options->linkType
        ];
    }
}
