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

use Couchbase\Exception\CouchbaseException;
use Couchbase\Exception\InvalidArgumentException;
use Couchbase\Extension;

class AnalyticsIndexManager
{
    /**
     * @var resource
     */
    private $core;

    /**
     * @internal
     */
    public function __construct($core)
    {
        $this->core = $core;
    }

    /**
     * Creates a new analytics dataverse.
     *
     * @param string $dataverseName the name of the dataverse to create
     * @param CreateAnalyticsDataverseOptions|null $options The options to use when creating the dataverse
     *
     * @since 4.2.4
     */
    public function createDataverse(string $dataverseName, ?CreateAnalyticsDataverseOptions $options = null): void
    {
        $function = COUCHBASE_EXTENSION_NAMESPACE . '\\analyticsDataverseCreate';
        $function($this->core, $dataverseName, CreateAnalyticsDataverseOptions::export($options));
    }

    /**
     * Deletes an analytics dataverse.
     *
     * @param string $dataverseName the name of the dataverse to drop
     * @param DropAnalyticsDataverseOptions|null $options The options to use when dropping the dataverse
     *
     * @since 4.2.4
     */
    public function dropDataverse(string $dataverseName, ?DropAnalyticsDataverseOptions $options = null): void
    {
        $function = COUCHBASE_EXTENSION_NAMESPACE . '\\analyticsDataverseDrop';
        $function($this->core, $dataverseName, DropAnalyticsDataverseOptions::export($options));
    }

    /**
     * Creates a new analytics dataset
     *
     * @param string $datasetName The name of the dataset to create
     * @param string $bucketName The name of the bucket where the dataset is stored inside
     * @param CreateAnalyticsDatasetOptions|null $options The options to use when creating the dataset
     *
     * @since 4.2.4
     */
    public function createDataset(string $datasetName, string $bucketName, ?CreateAnalyticsDatasetOptions $options = null): void
    {
        $function = COUCHBASE_EXTENSION_NAMESPACE . '\\analyticsDatasetCreate';
        $function($this->core, $datasetName, $bucketName, CreateAnalyticsDatasetOptions::export($options));
    }

    /**
     * Deletes an analytics dataset
     *
     * @param string $datasetName The name of the dataset to drop
     * @param DropAnalyticsDatasetOptions|null $options The options to use when dropping the dataset
     *
     * @since 4.2.4
     */
    public function dropDataset(string $datasetName, ?DropAnalyticsDatasetOptions $options = null): void
    {
        $function = COUCHBASE_EXTENSION_NAMESPACE . '\\analyticsDatasetDrop';
        $function($this->core, $datasetName, DropAnalyticsDatasetOptions::export($options));
    }

    /**
     * Fetches all datasets from the analytics service
     *
     * @param GetAllAnalyticsDatasetsOptions|null $options The options to use when fetching the datasets
     *
     * @return array an array of {@link AnalyticsDataset}
     * @since 4.2.4
     */
    public function getAllDatasets(?GetAllAnalyticsDatasetsOptions $options = null): array
    {
        $function = COUCHBASE_EXTENSION_NAMESPACE . '\\analyticsDatasetGetAll';
        $result = $function($this->core, GetAllAnalyticsDatasetsOptions::export($options));
        $datasets = [];
        foreach ($result as $dataset) {
            $datasets[] = AnalyticsDataset::import($dataset);
        }
        return $datasets;
    }

    /**
     * Creates a new analytics index.
     *
     * @param string $datasetName The name of the dataset in which the index should be created
     * @param string $indexName The name of the index to create
     * @param array $fields The fields that should be indexed.
     * @param CreateAnalyticsIndexOptions|null $options The options to use when creating the index.
     *
     * @since 4.2.4
     */
    public function createIndex(string $datasetName, string $indexName, array $fields, ?CreateAnalyticsIndexOptions $options = null): void
    {
        $function = COUCHBASE_EXTENSION_NAMESPACE . '\\analyticsIndexCreate';
        $function($this->core, $datasetName, $indexName, $fields, CreateAnalyticsIndexOptions::export($options));
    }

    /**
     * Deletes an analytics index
     *
     * @param string $datasetName The name of the dataset in which the index exists
     * @param string $indexName The name of the index to drop
     * @param DropAnalyticsIndexOptions|null $options The options to use when dropping the index
     *
     * @since 4.2.4
     */
    public function dropIndex(string $datasetName, string $indexName, ?DropAnalyticsIndexOptions $options = null): void
    {
        $function = COUCHBASE_EXTENSION_NAMESPACE . '\\analyticsIndexDrop';
        $function($this->core, $datasetName, $indexName, DropAnalyticsIndexOptions::export($options));
    }

    /**
     * Fetches all indexes from the analytics service.
     *
     * @param GetAllAnalyticsIndexesOptions|null $options The options to use when fetching the indexes.
     *
     * @return array array of {@link AnalyticsIndex}
     * @since 4.2.4
     */
    public function getAllIndexes(?GetAllAnalyticsIndexesOptions $options = null): array
    {
        $function = COUCHBASE_EXTENSION_NAMESPACE . '\\analyticsIndexGetAll';
        $result = $function($this->core, GetAllAnalyticsIndexesOptions::export($options));
        $indexes = [];
        foreach ($result as $index) {
            $indexes[] = AnalyticsIndex::import($index);
        }
        return $indexes;
    }

    /**
     * Connects the analytics link (by default, `Default.Local`)
     *
     * @param ConnectAnalyticsLinkOptions|null $options The options to use when connecting the link
     *
     * @since 4.2.4
     */
    public function connectLink(?ConnectAnalyticsLinkOptions $options = null): void
    {
        $function = COUCHBASE_EXTENSION_NAMESPACE . '\\analyticsLinkConnect';
        $function($this->core, ConnectAnalyticsLinkOptions::export($options));
    }

    /**
     * Disconnects the analytics link (by default, `Default.local`)
     *
     * @param DisconnectAnalyticsLinkOptions|null $options The options to use when disconnecting the link
     *
     * @since 4.2.4
     */
    public function disconnectLink(?DisconnectAnalyticsLinkOptions $options = null): void
    {
        $function = COUCHBASE_EXTENSION_NAMESPACE . '\\analyticsLinkDisconnect';
        $function($this->core, DisconnectAnalyticsLinkOptions::export($options));
    }

    /**
     * Fetches the pending mutations for different dataverses.
     *
     * @param GetAnalyticsPendingMutationsOptions|null $options The options to use when fetching the pending mutations
     *
     * @return array Associative array where top level keys are dataverse names,
     * and values are an associative array of datasets to number of pending mutations.
     * @since 4.2.4
     */
    public function getPendingMutations(?GetAnalyticsPendingMutationsOptions $options = null): array
    {
        $function = COUCHBASE_EXTENSION_NAMESPACE . '\\analyticsPendingMutationsGet';
        $results = $function($this->core, GetAnalyticsPendingMutationsOptions::export($options));

        return GetAnalyticsPendingMutationsOptions::import($results);
    }

    /**
     * Creates a new analytics link.
     *
     * @param AnalyticsLink $link The link that should be created. Either a Couchbase, s3, or azureblob link.
     * @param CreateAnalyticsLinkOptions|null $options The options to use when creating the link
     *
     * @see CouchbaseRemoteAnalyticsLink
     * @see S3ExternalAnalyticsLink
     * @see AzureBlobExternalAnalyticsLink
     *
     * @since 4.2.4
     */
    public function createLink(AnalyticsLink $link, ?CreateAnalyticsLinkOptions $options = null): void
    {
        $function = COUCHBASE_EXTENSION_NAMESPACE . '\\analyticsLinkCreate';
        $function($this->core, $link->export(), CreateAnalyticsLinkOptions::export($options));
    }

    /**
     * Replaces an existing analytics link.
     *
     * @param AnalyticsLink $link The link that should be replaced, Either a Couchbase, s3, or azureblob link.
     * @param ReplaceAnalyticsLinkOptions|null $options The options to use when replacing the link
     *
     * @see CouchbaseRemoteAnalyticsLink
     * @see S3ExternalAnalyticsLink
     * @see AzureBlobExternalAnalyticsLink
     *
     * @since 4.2.4
     */
    public function replaceLink(AnalyticsLink $link, ?ReplaceAnalyticsLinkOptions $options = null): void
    {
        $function = COUCHBASE_EXTENSION_NAMESPACE . '\\analyticsLinkReplace';
        $function($this->core, $link->export(), ReplaceAnalyticsLinkOptions::export($options));
    }

    /**
     * Deletes an analytics link.
     *
     * @param string $linkName The name of the link
     * @param string $dataverseName The dataverse in which the link exists
     * @param DropAnalyticsLinkOptions|null $options The options to use when replacing hte link.
     *
     * @since 4.2.4
     */
    public function dropLink(string $linkName, string $dataverseName, ?DropAnalyticsLinkOptions $options = null): void
    {
        $function = COUCHBASE_EXTENSION_NAMESPACE . '\\analyticsLinkDrop';
        $function($this->core, $linkName, $dataverseName, DropAnalyticsLinkOptions::export($options));
    }

    /**
     * Returns a list of current analytics links.
     *
     * @param GetAnalyticsLinksOptions|null $options The options to use when fetching the links
     *
     * @throws InvalidArgumentException If the linkName is set, the dataverseName must also be set.
     * @throws CouchbaseException
     *
     * @return array array of {@link CouchbaseRemoteAnalyticsLink}, {@link S3ExternalAnalyticsLink}, or {@link AzureBlobExternalAnalyticsLink}
     * @since 4.2.4
     */
    public function getLinks(?GetAnalyticsLinksOptions $options = null): array
    {
        $function = COUCHBASE_EXTENSION_NAMESPACE . '\\analyticsLinkGetAll';
        $result = $function($this->core, GetAnalyticsLinksOptions::export($options));
        $links = [];
        foreach ($result as $link) {
            $links[] = AnalyticsLink::import($link);
        }
        return $links;
    }
}
