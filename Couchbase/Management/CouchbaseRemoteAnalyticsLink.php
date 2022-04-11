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

class CouchbaseRemoteAnalyticsLink implements AnalyticsLink
{
    /**
     * Sets name of the link
     *
     * @param string $name
     *
     * @return CouchbaseRemoteAnalyticsLink
     */
    public function name(string $name): CouchbaseRemoteAnalyticsLink
    {
    }

    /**
     * Sets dataverse this link belongs to
     *
     * @param string $dataverse
     *
     * @return CouchbaseRemoteAnalyticsLink
     */
    public function dataverse(string $dataverse): CouchbaseRemoteAnalyticsLink
    {
    }

    /**
     * Sets the hostname of the target Couchbase cluster
     *
     * @param string $hostname
     *
     * @return CouchbaseRemoteAnalyticsLink
     */
    public function hostname(string $hostname): CouchbaseRemoteAnalyticsLink
    {
    }

    /**
     * Sets the username to use for authentication with the remote cluster.
     *
     * Optional if client-certificate authentication is being used.
     *
     * @param string $username
     *
     * @return CouchbaseRemoteAnalyticsLink
     */
    public function username(string $username): CouchbaseRemoteAnalyticsLink
    {
    }

    /**
     * Sets the password to use for authentication with the remote cluster.
     *
     * Optional if client-certificate authentication is being used.
     *
     * @param string $password
     *
     * @return CouchbaseRemoteAnalyticsLink
     */
    public function password(string $password): CouchbaseRemoteAnalyticsLink
    {
    }

    /**
     * Sets settings for connection encryption
     *
     * @param EncryptionSettings $settings
     *
     * @return CouchbaseRemoteAnalyticsLink
     */
    public function encryption(EncryptionSettings $settings): CouchbaseRemoteAnalyticsLink
    {
    }
}
