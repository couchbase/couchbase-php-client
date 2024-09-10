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

class S3ExternalAnalyticsLink extends AnalyticsLink
{
    private string $dataverseName;
    private string $name;
    private string $accessKeyID;
    private string $secretAccessKey;
    private string $region;

    private ?string $sessionToken = null;

    private ?string $serviceEndpoint = null;

    public function __construct(
        string $name,
        string $dataverseName,
        string $accessKeyID,
        string $region,
        string $secretAccessKey,
    )
    {
        $this->name = $name;
        $this->dataverseName = $dataverseName;
        $this->accessKeyID = $accessKeyID;
        $this->region = $region;
        $this->secretAccessKey = $secretAccessKey;
    }

    /**
     * Static helper to keep code more readable

     * @param string $name
     * @param string $dataverseName
     * @param string $accessKeyID
     * @param string $region
     * @param string $secretAccessKey
     *
     * @return S3ExternalAnalyticsLink
     * @since 4.2.4
     */
    public static function build(
        string $name,
        string $dataverseName,
        string $accessKeyID,
        string $region,
        string $secretAccessKey,
    ): S3ExternalAnalyticsLink
    {
        return new S3ExternalAnalyticsLink(
            $name,
            $dataverseName,
            $accessKeyID,
            $region,
            $secretAccessKey,
        );
    }

    /**
     * Sets AWS S3 service endpoint
     *
     * @param string $serviceEndpoint
     *
     * @return S3ExternalAnalyticsLink
     * @since 4.2.4
     */
    public function setServiceEndpoint(string $serviceEndpoint): S3ExternalAnalyticsLink
    {
        $this->serviceEndpoint = $serviceEndpoint;
        return $this;
    }

    /**
     * Sets the session token
     *
     * @param string $sessionToken
     *
     * @return S3ExternalAnalyticsLink
     * @since 4.2.4
     */
    public function setSessionToken(string $sessionToken): S3ExternalAnalyticsLink
    {
        $this->sessionToken = $sessionToken;
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
        return AnalyticsLinkType::S3;
    }

    /**
     * @internal
     */
    public function export(): array
    {
        $json = [
            "type" => "s3",
            "linkName" => $this->name,
            "dataverse" => $this->dataverseName,
            "accessKeyId" => $this->accessKeyID,
            "secretAccessKey" => $this->secretAccessKey,
            "region" => $this->region
        ];

        if ($this->sessionToken != null) {
            $json["sessionToken"] = $this->sessionToken;
        }
        if ($this->serviceEndpoint != null) {
            $json["serviceEndpoint"] = $this->serviceEndpoint;
        }

        return $json;
    }
}
