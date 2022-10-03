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

namespace Couchbase;

use Couchbase\Exception\InvalidArgumentException;

/**
 * Singleton class which stores the registered configuration profiles
 */
class ConfigProfiles
{
    private static ?ConfigProfiles $instance = null;
    private array $knownProfiles = [];

    public function __construct()
    {
        $this->registerProfile('wan_development', new WanDevelopmentProfile());
    }

    /**
     * @param string $profileName Name of new profile to be registered
     * @param ConfigProfile $profile Instance of new profile
     * @since 4.0.1
     */
    public function registerProfile(string $profileName, ConfigProfile $profile): void
    {
        $this->knownProfiles[$profileName] = $profile;
    }

    /**
     * Goes through registered profiles and applies it if it exists, else throws an exception
     * @throws InvalidArgumentException
     * @internal
     * @since 4.0.1
     */
    public function checkProfiles(string $profileName, ClusterOptions $options): void
    {
        if (isset($this->knownProfiles[$profileName])) {
            $this->knownProfiles[$profileName]->apply($options);
            return;
        }
        throw new InvalidArgumentException("unregistered profile: " . $profileName);
    }

    /**
     * Returns static instance of ConfigProfiles
     * @return ConfigProfiles
     * @since 4.0.1
     */
    public static function getInstance(): ConfigProfiles
    {
        if (self::$instance == null) {
            self::$instance = new ConfigProfiles();
        }
        return self::$instance;
    }
}
