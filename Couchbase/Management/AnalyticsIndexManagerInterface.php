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

namespace Couchbase\Management;

interface AnalyticsIndexManagerInterface
{
    public function createDataverse(string $dataverseName, ?CreateAnalyticsDataverseOptions $options = null): void;

    public function dropDataverse(string $dataverseName, ?DropAnalyticsDataverseOptions $options = null): void;

    public function createDataset(string $datasetName, string $bucketName, ?CreateAnalyticsDatasetOptions $options = null): void;

    public function dropDataset(string $datasetName, ?DropAnalyticsDatasetOptions $options = null): void;

    public function getAllDatasets(?GetAllAnalyticsDatasetsOptions $options = null): array;

    public function createIndex(string $datasetName, string $indexName, array $fields, ?CreateAnalyticsIndexOptions $options = null): void;

    public function dropIndex(string $datasetName, string $indexName, ?DropAnalyticsIndexOptions $options = null): void;

    public function getAllIndexes(?GetAllAnalyticsIndexesOptions $options = null): array;

    public function connectLink(?ConnectAnalyticsLinkOptions $options = null): void;

    public function disconnectLink(?DisconnectAnalyticsLinkOptions $options = null): void;

    public function getPendingMutations(?GetAnalyticsPendingMutationsOptions $options = null): array;

    public function createLink(AnalyticsLink $link, ?CreateAnalyticsLinkOptions $options = null): void;

    public function replaceLink(AnalyticsLink $link, ?ReplaceAnalyticsLinkOptions $options = null): void;

    public function dropLink(string $linkName, string $dataverseName, ?DropAnalyticsLinkOptions $options = null): void;

    public function getLinks(?GetAnalyticsLinksOptions $options = null): array;
}
