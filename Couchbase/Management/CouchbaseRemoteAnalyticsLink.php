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

class CouchbaseRemoteAnalyticsLink extends AnalyticsLink
{
    private string $dataverseName;
    private string $name;
    private string $hostname;

    private ?CouchbaseAnalyticsEncryptionSettings $encryption = null;
    private ?string $username = null;
    private ?string $password = null;

    public function __construct(string $name, string $dataverseName, string $hostname)
    {
        $this->name = $name;
        $this->dataverseName = $dataverseName;
        $this->hostname = $hostname;
    }

    /**
     * Static helper to keep code more readable
     * @param string $name
     * @param string $dataverseName
     * @param string $hostname
     *
     * @return CouchbaseRemoteAnalyticsLink
     * @since 4.2.4
     */
    public static function build(string $name, string $dataverseName, string $hostname): CouchbaseRemoteAnalyticsLink
    {
        return new CouchbaseRemoteAnalyticsLink($name, $dataverseName, $hostname);
    }

    /**
     * Sets the username to use for authentication with the remote cluster.
     *
     * Optional if client-certificate authentication is being used.
     *
     * @param string $username
     *
     * @return CouchbaseRemoteAnalyticsLink
     * @since 4.2.4
     */
    public function setUsername(string $username): CouchbaseRemoteAnalyticsLink
    {
        $this->username = $username;
        return $this;
    }

    /**
     * Sets the password to use for authentication with the remote cluster.
     *
     * Optional if client-certificate authentication is being used.
     *
     * @param string $password
     *
     * @return CouchbaseRemoteAnalyticsLink
     * @since 4.2.4
     */
    public function setPassword(string $password): CouchbaseRemoteAnalyticsLink
    {
        $this->password = $password;
        return $this;
    }

    /**
     * Sets settings for connection encryption
     *
     * @param CouchbaseAnalyticsEncryptionSettings $settings
     *
     * @return CouchbaseRemoteAnalyticsLink
     * @since 4.2.4
     */
    public function setEncryption(CouchbaseAnalyticsEncryptionSettings $settings): CouchbaseRemoteAnalyticsLink
    {
        $this->encryption = $settings;
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
        return AnalyticsLinkType::COUCHBASE;
    }

    /**
     * @internal
     */
    public function export(): array
    {
        $json = [
            "type" => "couchbase",
            "linkName" => $this->name,
            "dataverse" => $this->dataverseName,
            "hostname" => $this->hostname,
        ];

        if ($this->username != null) {
            $json["username"] = $this->username;
        }
        if ($this->password != null) {
            $json["password"] = $this->password;
        }
        if ($this->encryption != null) {
            $json["encryptionLevel"] = $this->encryption->encryptionLevel();

            if ($this->encryption->certificate() != null) {
                $json["certificate"] = $this->encryption->certificate();
            }

            if ($this->encryption->clientCertificate() != null) {
                $json["clientCertificate"] = $this->encryption->clientCertificate();
            }

            if ($this->encryption->clientKey() != null) {
                $json["clientKey"] = $this->encryption->clientKey();
            }
        } else {
            $json["encryptionLevel"] = AnalyticsEncryptionLevel::NONE;
        }

        return $json;
    }
}
