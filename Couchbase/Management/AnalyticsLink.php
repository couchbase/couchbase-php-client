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

abstract class AnalyticsLink
{
    /**
     * Gets the name of the link
     *
     * @return string
     * @since 4.2.4
     */
    abstract protected function name(): string;

    /**
     * Gets the name of the dataverse to which this link belongs to.
     *
     * @return string
     * @since 4.2.4
     */
    abstract protected function dataverseName(): string;

    /**
     * Gets the link type. One of "couchbase", "s3" or "azureblob"
     *
     * @see AnalyticsLinkType::COUCHBASE
     * @see AnalyticsLinkType::S3
     * @see AnalyticsLinkType::AZURE_BLOB
     *
     * @return string
     * @since 4.2.4
     */
    abstract protected function linkType(): string;

    /**
     * @internal
     */
    abstract protected function export(): array;

    /**
     * @throws CouchbaseException
     * @internal
     */
    public static function import(array $data): AnalyticsLink
    {
        if ($data["type"] == "couchbase") {
            $link = CouchbaseRemoteAnalyticsLink::build($data["name"] ?? "", $data["dataverseName"] ?? "", $data["hostname"] ?? "");
            if (array_key_exists("username", $data)) {
                $link->setUsername($data["username"]);
            }
            if (array_key_exists("encryptionLevel", $data)) {
                if ($data["encryptionLevel"] == "full") {
                    $encryption = CouchbaseAnalyticsEncryptionSettings::build(AnalyticsEncryptionLevel::FULL);
                } elseif ($data["encryptionLevel"] == "half") {
                    $encryption = CouchbaseAnalyticsEncryptionSettings::build(AnalyticsEncryptionLevel::HALF);
                } else {
                    $encryption = CouchbaseAnalyticsEncryptionSettings::build(AnalyticsEncryptionLevel::NONE);
                }
                if (array_key_exists("certificate", $data)) {
                    $encryption->setCertificate($data["certificate"]);
                }
                if (array_key_exists("clientCertificate", $data)) {
                    $encryption->setClientCertificate($data["clientCertificate"]);
                }

                $link->setEncryption($encryption);
            }
            return $link;
        } elseif ($data["type"] == "s3") {
            $link = S3ExternalAnalyticsLink::build($data["name"] ?? "", $data["dataverseName"] ?? "", $data["accessKeyId"] ?? "", $data["region"] ?? "", "");
            if (array_key_exists("serviceEndpoint", $data)) {
                $link->setServiceEndpoint($data["serviceEndpoint"]);
            }
            return $link;
        } elseif ($data["type"] == "azureblob") {
            $link = AzureBlobExternalAnalyticsLink::build($data["name"] ?? "", $data["dataverseName"] ?? "");
            if (array_key_exists("accountName", $data)) {
                $link->setAccountName($data["accountName"]);
            }
            if (array_key_exists("blobEndpoint", $data)) {
                $link->setBlobEndpoint($data["blobEndpoint"]);
            }
            if (array_key_exists("endpointSuffix", $data)) {
                $link->setEndpointSuffix($data["endpointSuffix"]);
            }
            return $link;
        } else {
            throw new CouchbaseException("Unexpected analytics link type received importing");
        }
    }
}
