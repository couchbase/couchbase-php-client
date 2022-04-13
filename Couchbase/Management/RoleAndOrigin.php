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

class RoleAndOrigin
{
    private Role $role;
    private array $origins;

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
     * Gets the role.
     *
     * @return array
     * @see \Couchbase\Management\Origin
     * @since 4.0.0
     */
    public function origins(): array
    {
        return $this->origins;
    }

    /**
     * @internal
     * @since 4.0.0
     */
    public static function import(array $role): RoleAndOrigin
    {
        $settings = new RoleAndOrigin();
        $settings->role = Role::import($role);
        $settings->origins = [];
        foreach ($role['origins'] as $origin) {
            $settings->origins[] = Origin::import($origin);
        }

        return $settings;
    }
}
