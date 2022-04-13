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

class User
{
    private string $username = "";
    private ?string $displayName = null;
    private ?array $groups = null;
    private ?array $roles = null;
    private ?string $password = null;

    /**
     * @since 4.0.0
     */
    public function __construct()
    {
    }

    /**
     * Static helper to keep code more readable
     *
     * @return User
     * @since 4.0.0
     */
    public static function build(): User
    {
        return new User();
    }

    /**
     * Gets the username for the user.
     *
     * @return string
     * @since 4.0.0
     */
    public function username(): string
    {
        return $this->username;
    }

    /**
     * Gets the display name for the user.
     *
     * @return string
     * @since 4.0.0
     */
    public function displayName(): string
    {
        return $this->displayName;
    }

    /**
     * Gets the group names for the user.
     *
     * @return array
     * @since 4.0.0
     */
    public function groups(): array
    {
        return $this->groups;
    }

    /**
     * Gets the roles for the user.
     *
     * @return array
     * @see \Couchbase\Management\Role
     * @since 4.0.0
     */
    public function roles(): array
    {
        return $this->roles;
    }

    /**
     * Sets the username for the user.
     *
     * @param string $username the username
     * @return User
     * @since 4.0.0
     */
    public function setUsername(string $username): User
    {
        $this->username = $username;
        return $this;
    }

    /**
     * Sets the password for the user.
     *
     * @param string $password the password
     * @return User
     * @since 4.0.0
     */
    public function setPassword(string $password): User
    {
        $this->password = $password;
        return $this;
    }

    /**
     * Sets the display name for the user.
     *
     * @param string $name the display name
     * @return User
     * @since 4.0.0
     */
    public function setDisplayName(string $name): User
    {
        $this->displayName = $name;
        return $this;
    }

    /**
     * Sets the group names for the user.
     *
     * @param array $groups the group names
     * @return User
     * @since 4.0.0
     */
    public function setGroups(array $groups): User
    {
        $this->groups = $groups;
        return $this;
    }

    /**
     * Sets the roles for the user.
     *
     * @param array $roles the roles
     * @return User
     * @see \Couchbase\Management\Role
     * @since 4.0.0
     */
    public function setRoles(array $roles): User
    {
        $this->roles = $roles;
        return $this;
    }

    /**
     * @internal
     * @since 4.0.0
     */
    public static function import(array $user): User
    {
        $settings = User::build()->setUsername($user['username']);
        if (array_key_exists('displayName', $user)) {
            $settings->setDisplayName($user['displayName']);
        }
        if (array_key_exists('groups', $user)) {
            $settings->setGroups($user['groups']);
        }
        if (array_key_exists('roles', $user)) {
            $roles = [];
            foreach ($user['roles'] as $role) {
                $roles[] = Role::import($role);
            }
            $settings->setRoles($roles);
        }

        return $settings;
    }

    /**
     * @param User $user
     *
     * @return array
     * @throws InvalidArgumentException
     * @since 4.0.0
     * @internal
     *
     */
    public static function export(User $user): array
    {
        if ($user->username == "") {
            throw new InvalidArgumentException();
        }

        $roles = [];
        if ($user->roles != null) {
            foreach ($user->roles as $role) {
                $roles[] = Role::export($role);
            }
        }

        return [
            'username' => $user->username,
            'displayName' => $user->displayName,
            'groups' => $user->groups,
            'roles' => $roles,
            'password' => $user->password
        ];
    }
}
