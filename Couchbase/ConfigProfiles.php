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
    public static array $knownProfiles = [];

    public function __construct()
    {
        $this->registerProfile('wan_development', new DevelopmentProfile());
    }

    /**
     * @param string $profileName Name of new profile to be registered
     * @param ConfigProfile $profile Instance of new profile
     */
    public function registerProfile(string $profileName, ConfigProfile $profile): void
    {
        ConfigProfiles::$knownProfiles[$profileName] = $profile;
    }

    /**
     * @throws InvalidArgumentException
     */
    public function checkProfiles(string $profileName, ClusterOptions $options): void
    {
        if(isset(ConfigProfiles::$knownProfiles[$profileName]))
        {
            ConfigProfiles::$knownProfiles[$profileName]->apply($options);
            return;
        }
        throw new InvalidArgumentException("unregistered profile: " . $profileName);
    }

    public static function getInstance()
    {
        if(self::$instance == null)
        {
            self::$instance = new ConfigProfiles();
        }
        return self::$instance;
    }

}