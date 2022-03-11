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

class AnalyticsIndexManager
{
    public function createDataverse(string $dataverseName, CreateAnalyticsDataverseOptions $options = null)
    {
    }

    public function dropDataverse(string $dataverseName, DropAnalyticsDataverseOptions $options = null)
    {
    }

    public function createDataset(string $datasetName, string $bucketName, CreateAnalyticsDatasetOptions $options = null)
    {
    }

    public function dropDataset(string $datasetName, DropAnalyticsDatasetOptions $options = null)
    {
    }

    public function getAllDatasets()
    {
    }

    public function createIndex(string $datasetName, string $indexName, array $fields, CreateAnalyticsIndexOptions $options = null)
    {
    }

    public function dropIndex(string $datasetName, string $indexName, DropAnalyticsIndexOptions $options = null)
    {
    }

    public function getAllIndexes()
    {
    }

    public function connectLink(ConnectAnalyticsLinkOptions $options = null)
    {
    }

    public function disconnectLink(DisconnectAnalyticsLinkOptions $options = null)
    {
    }

    public function getPendingMutations()
    {
    }

    public function createLink(AnalyticsLink $link, CreateAnalyticsLinkOptions $options = null)
    {
    }

    public function replaceLink(AnalyticsLink $link, ReplaceAnalyticsLinkOptions $options = null)
    {
    }

    public function dropLink(string $linkName, string $dataverseName, DropAnalyticsLinkOptions $options = null)
    {
    }

    public function getLinks(GetAnalyticsLinksOptions $options = null)
    {
    }
}
