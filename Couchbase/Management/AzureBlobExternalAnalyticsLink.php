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

class AzureBlobExternalAnalyticsLink implements AnalyticsLink
{
    /**
     * Sets name of the link
     *
     * @param string $name
     *
     * @return AzureBlobExternalAnalyticsLink
     */
    public function name(string $name): AzureBlobExternalAnalyticsLink
    {
    }

    /**
     * Sets dataverse this link belongs to
     *
     * @param string $dataverse
     *
     * @return AzureBlobExternalAnalyticsLink
     */
    public function dataverse(string $dataverse): AzureBlobExternalAnalyticsLink
    {
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
    public function connectionString(string $connectionString): AzureBlobExternalAnalyticsLink
    {
    }

    /**
     * Sets Azure blob storage account name
     *
     * @param string $accountName
     *
     * @return AzureBlobExternalAnalyticsLink
     */
    public function accountName(string $accountName): AzureBlobExternalAnalyticsLink
    {
    }

    /**
     * Sets Azure blob storage account key
     *
     * @param string $accountKey
     *
     * @return AzureBlobExternalAnalyticsLink
     */
    public function accountKey(string $accountKey): AzureBlobExternalAnalyticsLink
    {
    }

    /**
     * Sets token that can be used for authentication
     *
     * @param string $signature
     *
     * @return AzureBlobExternalAnalyticsLink
     */
    public function sharedAccessSignature(string $signature): AzureBlobExternalAnalyticsLink
    {
    }

    /**
     * Sets Azure blob storage endpoint
     *
     * @param string $blobEndpoint
     *
     * @return AzureBlobExternalAnalyticsLink
     */
    public function blobEndpoint(string $blobEndpoint): AzureBlobExternalAnalyticsLink
    {
    }

    /**
     * Sets Azure blob endpoint suffix
     *
     * @param string $suffix
     *
     * @return AzureBlobExternalAnalyticsLink
     */
    public function endpointSuffix(string $suffix): AzureBlobExternalAnalyticsLink
    {
    }
}
