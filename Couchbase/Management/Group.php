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

class Group
{
    private string $name = "";
    private ?string $description = null;
    private ?array $roles = null;
    private ?string $ldapGroupReference = null;

    /**
     * @since 4.0.0
     */
    public function __construct()
    {
    }

    /**
     * Static helper to keep code more readable
     *
     * @return Group
     * @since 4.0.0
     */
    public static function build(): Group
    {
        return new Group();
    }

    /**
     * Gets the name of the group.
     *
     * @return string
     * @since 4.0.0
     */
    public function name(): string
    {
        return $this->name;
    }

    /**
     * Gets the description of the group.
     *
     * @return string|null
     * @since 4.0.0
     */
    public function description(): ?string
    {
        return $this->description;
    }

    /**
     * Gets the roles for the group.
     *
     * @return array|null
     * @see \Couchbase\Management\Role
     * @since 4.0.0
     */
    public function roles(): ?array
    {
        return $this->roles;
    }

    /**
     * Gets the ldap reference of the group.
     *
     * @return string|null
     * @since 4.0.0
     */
    public function ldapGroupReference(): ?string
    {
        return $this->ldapGroupReference;
    }

    /**
     * Sets the name of the group.
     *
     * @param string $name the name of the group
     * @return Group
     * @since 4.0.0
     */
    public function setName(string $name): Group
    {
        $this->name = $name;
        return $this;
    }

    /**
     * Sets the description of the group.
     *
     * @param string $description the description of the group
     * @return Group
     * @since 4.0.0
     */
    public function setDescription(string $description): Group
    {
        $this->description = $description;
        return $this;
    }

    /**
     * Sets the ldap reference of the group.
     *
     * @param string $reference the ldap reference of the group
     * @return Group
     * @since 4.0.0
     */
    public function setLdapGroupReference(string $reference): Group
    {
        $this->ldapGroupReference = $reference;
        return $this;
    }

    /**
     * Sets the roles for the group.
     *
     * @param array $roles the roles for the group
     * @return Group
     * @since 4.0.0
     */
    public function setRoles(array $roles): Group
    {
        $this->roles = $roles;
        return $this;
    }

    /**
     * @internal
     * @since 4.0.0
     */
    public static function import(array $group): Group
    {
        $settings = Group::build()->setName($group['name']);
        if (array_key_exists('description', $group)) {
            $settings->setDescription($group['description']);
        }
        if (array_key_exists('ldapGroupReference', $group)) {
            $settings->setLdapGroupReference($group['ldapGroupReference']);
        }
        if (array_key_exists('roles', $group)) {
            $roles = [];
            foreach ($group['roles'] as $role) {
                $roles[] = Role::import($role);
            }
            $settings->setRoles($roles);
        }

        return $settings;
    }

    /**
     * @internal
     *
     * @param Group $group
     *
     * @return array
     * @since 4.0.0
     */
    public static function export(Group $group): array
    {
        if ($group->name() == "") {
            throw new InvalidArgumentException();
        }
        $roles = [];
        if ($group->roles != null) {
            foreach ($group->roles as $role) {
                $roles[] = Role::export($role);
            }
        }

        return [
            'name' => $group->name,
            'description' => $group->description,
            'ldapGroupReference' => $group->ldapGroupReference,
            'roles' => $roles,
        ];
    }
}
