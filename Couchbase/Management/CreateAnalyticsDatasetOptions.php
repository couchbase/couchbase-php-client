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

class CreateAnalyticsDatasetOptions
{
    private ?int $timeoutMilliseconds = null;
    private ?bool $ignoreIfExists = null;
    private ?string $condition = null;
    private ?string $dataverseName = null;

    /**
     * Static helper to keep code more readable
     *
     * @return CreateAnalyticsDatasetOptions
     * @since 4.2.4
     */
    public static function build(): CreateAnalyticsDatasetOptions
    {
        return new CreateAnalyticsDatasetOptions();
    }

    /**
     * Sets the where clause to use for creating the dataset
     *
     * @param string $condition
     *
     * @return CreateAnalyticsDatasetOptions
     * @since 4.2.4
     */
    public function condition(string $condition): CreateAnalyticsDatasetOptions
    {
        $this->condition = $condition;
        return $this;
    }

    /**
     * Customizes the dataverse from which this dataset should be created.
     *
     * @param string $dataverseName The name of the dataverse
     *
     * @return CreateAnalyticsDatasetOptions
     * @since 4.2.4
     */
    public function dataverseName(string $dataverseName): CreateAnalyticsDatasetOptions
    {
        $this->dataverseName = $dataverseName;
        return $this;
    }

    /**
     * @param bool $shouldIgnore
     *
     * @return CreateAnalyticsDatasetOptions
     * @since 4.2.4
     */
    public function ignoreIfExists(bool $shouldIgnore): CreateAnalyticsDatasetOptions
    {
        $this->ignoreIfExists = $shouldIgnore;
        return $this;
    }

    /**
     * Sets the operation timeout in milliseconds.
     *
     * @param int $milliseconds the operation timeout to apply
     *
     * @return CreateAnalyticsDatasetOptions
     * @since 4.2.4
     */
    public function timeout(int $milliseconds): CreateAnalyticsDatasetOptions
    {
        $this->timeoutMilliseconds = $milliseconds;
        return $this;
    }

    /**
     * @internal
     */
    public static function export(?CreateAnalyticsDatasetOptions $options): array
    {
        if ($options == null) {
            return [];
        }

        return [
            'timeoutMilliseconds' => $options->timeoutMilliseconds,
            'ignoreIfExists' => $options->ignoreIfExists,
            'condition' => $options->condition,
            'dataverseName' => $options->dataverseName,
        ];
    }
}
