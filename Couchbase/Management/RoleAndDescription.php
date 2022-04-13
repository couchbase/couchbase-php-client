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

class RoleAndDescription
{
    private Role $role;
    private string $displayName;
    private string $description;

    /**
     * Gets the role.
     *
     * @return Role
     * @since 4.0.0
     */
    public function role(): Role
    {
        return $this->role;
    }

    /**
     * Gets the display name of the role.
     *
     * @return string
     * @since 4.0.0
     */
    public function displayName(): string
    {
        return $this->displayName;
    }

    /**
     * Gets the description of the role.
     *
     * @return string
     * @since 4.0.0
     */
    public function description(): string
    {
        return $this->description;
    }

    /**
     * @internal
     * @since 4.0.0
     */
    public static function import(array $role): RoleAndDescription
    {
        $settings = new RoleAndDescription();
        $settings->role = Role::import($role);
        $settings->displayName = $role['displayName'];
        $settings->description = $role['description'];

        return $settings;
    }
}
