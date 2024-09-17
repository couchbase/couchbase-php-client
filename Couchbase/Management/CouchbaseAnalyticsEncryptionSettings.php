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

use Couchbase\Exception\InvalidArgumentException;

class CouchbaseAnalyticsEncryptionSettings
{
    private string $encryptionLevel;

    private ?string $certificate = null;
    private ?string $clientCertificate = null;
    private ?string $clientKey = null;

    /**
     * @throws InvalidArgumentException
     */
    public function __construct(string $level)
    {
        if (
            $level != AnalyticsEncryptionLevel::FULL &&
            $level != AnalyticsEncryptionLevel::HALF &&
            $level != AnalyticsEncryptionLevel::NONE
        ) {
            throw new InvalidArgumentException("Encryption level must be a value 'none', 'half', or 'full'");
        }

        $this->encryptionLevel = $level;
    }

    /**
     * Static helper to keep code more readable
     *
     * @param string $level Accepted values are 'none', 'half', 'full'.
     *
     * @throws InvalidArgumentException if $level is not an accepted value
     *
     * @see AnalyticsEncryptionLevel::FULL
     * @see AnalyticsEncryptionLevel::NONE
     * @see AnalyticsEncryptionLevel::HALF
     *
     * @since 4.2.4
     */
    public static function build(string $level): CouchbaseAnalyticsEncryptionSettings
    {
        return new CouchbaseAnalyticsEncryptionSettings($level);
    }

    /**
     * Provides a certificate to use for connecting when encryption level is set
     * to full. Required when encryption level is set to @see AnalyticsEncryptionLevel::FULL
     *
     * @param string $certificate
     *
     * @return CouchbaseAnalyticsEncryptionSettings
     * @since 4.2.4
     */
    public function setCertificate(string $certificate): CouchbaseAnalyticsEncryptionSettings
    {
        $this->certificate = $certificate;
        return $this;
    }

    /**
     * Provides a client certificate to use for connecting when encryption level
     * is set to full.  Cannot be set if a username/password are used.
     *
     * @param string $clientCertificate
     *
     * @return CouchbaseAnalyticsEncryptionSettings
     * @since 4.2.4
     */
    public function setClientCertificate(string $clientCertificate): CouchbaseAnalyticsEncryptionSettings
    {
        $this->clientCertificate = $clientCertificate;
        return $this;
    }

    /**
     * Provides a client key to use for connecting when encryption level is set
     * to full.  Cannot be set if a username/password are used.
     *
     * @param string $key
     *
     * @return CouchbaseAnalyticsEncryptionSettings
     * @since 4.2.4
     */
    public function setClientKey(string $key): CouchbaseAnalyticsEncryptionSettings
    {
        $this->clientKey = $key;
        return $this;
    }

    /**
     * Gets the certificate to use for connecting when encryption level is set
     * to full.
     *
     * @return string|null
     * @since 4.2.4
     */
    public function certificate(): ?string
    {
        return $this->certificate;
    }

    /**
     * Gets the client certificate to use for connecting when encryption level is set
     * to full.
     *
     * @return string|null
     * @since 4.2.4
     */
    public function clientCertificate(): ?string
    {
        return $this->clientCertificate;
    }

    /**
     * Gets the client key to use for connecting when encryption level is set
     * to full
     *
     * @return string|null
     * @since 4.2.4
     */
    public function clientKey(): ?string
    {
        return $this->clientKey;
    }

    /**
     * Gets the encryption level
     *
     * @return string
     * @since 4.2.4
     */
    public function encryptionLevel(): string
    {
        return $this->encryptionLevel;
    }
}
