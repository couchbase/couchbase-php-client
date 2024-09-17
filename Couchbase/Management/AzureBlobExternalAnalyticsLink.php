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

class AzureBlobExternalAnalyticsLink extends AnalyticsLink
{
    private string $dataverseName;
    private string $name;

    private ?string $connectionString = null;
    private ?string $accountName = null;
    private ?string $accountKey = null;
    private ?string $sharedAccessSignature = null;
    private ?string $blobEndpoint = null;
    private ?string $endpointSuffix = null;

    public function __construct(string $name, string $dataverseName)
    {
        $this->name = $name;
        $this->dataverseName = $dataverseName;
    }

    /**
     * Static helper to keep code more readable
     *
     * @param string $name
     * @param string $dataverseName
     *
     * @return AzureBlobExternalAnalyticsLink
     * @since 4.2.4
     */
    public static function build(string $name, string $dataverseName): AzureBlobExternalAnalyticsLink
    {
        return new AzureBlobExternalAnalyticsLink($name, $dataverseName);
    }

    /**
     * Sets the connection string can be used as an authentication method, '$connectionString' contains other
     * authentication methods embedded inside the string. Only a single authentication method can be used.
     * (e.g. "AccountName=myAccountName;AccountKey=myAccountKey").
     *
     * @param string $connectionString
     *
     * @return AzureBlobExternalAnalyticsLink
     */
    public function setConnectionString(string $connectionString): AzureBlobExternalAnalyticsLink
    {
        $this->connectionString = $connectionString;
        return $this;
    }

    /**
     * Sets Azure blob storage account name
     *
     * @param string $accountName
     *
     * @return AzureBlobExternalAnalyticsLink
     */
    public function setAccountName(string $accountName): AzureBlobExternalAnalyticsLink
    {
        $this->accountName = $accountName;
        return $this;
    }

    /**
     * Sets Azure blob storage account key
     *
     * @param string $accountKey
     *
     * @return AzureBlobExternalAnalyticsLink
     */
    public function setAccountKey(string $accountKey): AzureBlobExternalAnalyticsLink
    {
        $this->accountKey = $accountKey;
        return $this;
    }

    /**
     * Sets token that can be used for authentication
     *
     * @param string $signature
     *
     * @return AzureBlobExternalAnalyticsLink
     */
    public function setSharedAccessSignature(string $signature): AzureBlobExternalAnalyticsLink
    {
        $this->sharedAccessSignature = $signature;
        return $this;
    }

    /**
     * Sets Azure blob storage endpoint
     *
     * @param string $blobEndpoint
     *
     * @return AzureBlobExternalAnalyticsLink
     */
    public function setBlobEndpoint(string $blobEndpoint): AzureBlobExternalAnalyticsLink
    {
        $this->blobEndpoint = $blobEndpoint;
        return $this;
    }

    /**
     * Sets Azure blob endpoint suffix
     *
     * @param string $suffix
     *
     * @return AzureBlobExternalAnalyticsLink
     */
    public function setEndpointSuffix(string $suffix): AzureBlobExternalAnalyticsLink
    {
        $this->endpointSuffix = $suffix;
        return $this;
    }

    /**
     * @inheritdoc
     */
    public function name(): string
    {
        return $this->name;
    }

    /**
     * @inheritdoc
     */
    public function dataverseName(): string
    {
        return $this->dataverseName;
    }

    /**
     * @inheritdoc
     */
    public function linkType(): string
    {
        return AnalyticsLinkType::AZURE_BLOB;
    }

    /**
     * @internal
     */
    public function export(): array
    {
        $json = [
            "type" => "azureblob",
            "linkName" => $this->name,
            "dataverse" => $this->dataverseName,
        ];

        if ($this->connectionString != null) {
            $json["connectionString"] = $this->connectionString;
        }

        if ($this->accountName != null) {
            $json["accountName"] = $this->accountName;
        }

        if ($this->accountKey != null) {
            $json["accountKey"] = $this->accountKey;
        }

        if ($this->sharedAccessSignature != null) {
            $json["sharedAccessSignature"] = $this->sharedAccessSignature;
        }

        if ($this->blobEndpoint != null) {
            $json["blobEndpoint"] = $this->blobEndpoint;
        }

        if ($this->endpointSuffix != null) {
            $json["endpointSuffix"] = $this->endpointSuffix;
        }

        return $json;
    }
}
