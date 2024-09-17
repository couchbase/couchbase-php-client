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

class AnalyticsDataset
{
    private string $name;
    private string $dataverseName;
    private string $linkName;
    private string $bucketName;

    /**
     * @internal
     * @since 4.2.4
     */
    private function __construct(
        string $name,
        string $dataverseName,
        string $linkName,
        string $bucketName
    )
    {
        $this->name = $name;
        $this->dataverseName = $dataverseName;
        $this->linkName = $linkName;
        $this->bucketName = $bucketName;
    }

    /**
     * Static helper to keep code more readable.
     *
     * @param string $name
     * @param string $dataverseName
     * @param string $linkName
     * @param string $bucketName
     *
     * @return AnalyticsDataset
     * @since 4.2.4
     */
    public static function build(
        string $name,
        string $dataverseName,
        string $linkName,
        string $bucketName
    )
    {
        return new AnalyticsDataset($name, $dataverseName, $linkName, $bucketName);
    }

    /**
     * Gets the name of the analytics dataset (or collection)
     *
     * @return string
     * @since 4.2.4
     */
    public function name(): string
    {
        return $this->name;
    }

    /**
     * Gets the name of the dataverse in which this dataset is stored.
     *
     * @return string
     * @since 4.2.4
     */
    public function dataverseName(): string
    {
        return $this->dataverseName;
    }

    /**
     * Gets the name of the link that is associated with this dataset.
     *
     * @return string
     * @since 4.2.4
     */
    public function linkName(): string
    {
        return $this->linkName;
    }

    /**
     * Gets the name of the bucket that this dataset includes.
     *
     * @return string
     * @since 4.2.4
     */
    public function bucketName(): string
    {
        return $this->bucketName;
    }

    /**
     * @internal
     */
    public static function import(array $data): AnalyticsDataset
    {
        return AnalyticsDataset::build(
            $data["name"] ?? "",
            $data["dataverseName"] ?? "",
            $data["linkName"] ?? "",
            $data["bucketName"] ?? ""
        );
    }
}
