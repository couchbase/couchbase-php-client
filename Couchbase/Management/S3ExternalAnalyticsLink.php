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

class S3ExternalAnalyticsLink implements AnalyticsLink
{
    /**
     * Sets name of the link
     *
     * @param string $name
     *
     * @return S3ExternalAnalyticsLink
     */
    public function name(string $name): S3ExternalAnalyticsLink
    {
    }

    /**
     * Sets dataverse this link belongs to
     *
     * @param string $dataverse
     *
     * @return S3ExternalAnalyticsLink
     */
    public function dataverse(string $dataverse): S3ExternalAnalyticsLink
    {
    }

    /**
     * Sets AWS S3 access key ID
     *
     * @param string $accessKeyId
     *
     * @return S3ExternalAnalyticsLink
     */
    public function accessKeyId(string $accessKeyId): S3ExternalAnalyticsLink
    {
    }

    /**
     * Sets AWS S3 secret key
     *
     * @param string $secretAccessKey
     *
     * @return S3ExternalAnalyticsLink
     */
    public function secretAccessKey(string $secretAccessKey): S3ExternalAnalyticsLink
    {
    }

    /**
     * Sets AWS S3 region
     *
     * @param string $region
     *
     * @return S3ExternalAnalyticsLink
     */
    public function region(string $region): S3ExternalAnalyticsLink
    {
    }

    /**
     * Sets AWS S3 token if temporary credentials are provided. Only available in 7.0+
     *
     * @param string $sessionToken
     *
     * @return S3ExternalAnalyticsLink
     */
    public function sessionToken(string $sessionToken): S3ExternalAnalyticsLink
    {
    }

    /**
     * Sets AWS S3 service endpoint
     *
     * @param string $serviceEndpoint
     *
     * @return S3ExternalAnalyticsLink
     */
    public function serviceEndpoint(string $serviceEndpoint): S3ExternalAnalyticsLink
    {
    }
}
