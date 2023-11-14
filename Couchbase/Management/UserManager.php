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

use Couchbase\Extension;

class UserManager implements UserManagerInterface
{
    /**
     * @var resource
     */
    private $core;

    /**
     * @internal
     * @param $core
     * @since 4.0.0
     */
    public function __construct($core)
    {
        $this->core = $core;
    }

    /**
     * Fetch a user.
     *
     * @param string $name the name of the user.
     * @param GetUserOptions|null $options the options to use when fetching the user.
     * @return UserAndMetadata
     * @since 4.0.0
     */
    public function getUser(string $name, GetUserOptions $options = null): UserAndMetadata
    {
        $result = Extension\userGet($this->core, $name, GetUserOptions::export($options));
        return UserAndMetadata::import($result);
    }

    /**
     * Fetch all users.
     *
     * @param GetAllUsersOptions|null $options the options to use when fetching the users.
     * @return array
     * @since 4.0.0
     */
    public function getAllUsers(GetAllUsersOptions $options = null): array
    {
        $result = Extension\userGetAll($this->core, GetAllUsersOptions::export($options));
        $users = [];
        foreach ($result as $user) {
            $users[] = UserAndMetadata::import($user);
        }

        return $users;
    }

    /**
     * Create or replace a user.
     *
     * @param User $user the user.
     * @param UpsertUserOptions|null $options the options to use when upserting the user.
     * @since 4.0.0
     */
    public function upsertUser(User $user, UpsertUserOptions $options = null)
    {
        Extension\userUpsert($this->core, User::export($user), UpsertUserOptions::export($options));
    }

    /**
     * Remove a user.
     *
     * @param string $name the name of the user.
     * @param DropUserOptions|null $options the options to use when dropping the user.
     * @since 4.0.0
     */
    public function dropUser(string $name, DropUserOptions $options = null)
    {
        Extension\userDrop($this->core, $name, DropUserOptions::export($options));
    }

    /**
     * Get all roles and descriptions.
     *
     * @param GetRolesOptions|null $options the options to use when fetching the roles.
     * @return array
     * @see \Couchbase\Management\RoleAndDescription
     * @since 4.0.0
     */
    public function getRoles(GetRolesOptions $options = null): array
    {
        $result = Extension\roleGetAll($this->core, GetRolesOptions::export($options));
        foreach ($result as $role) {
            $roles[] = RoleAndDescription::import($role);
        }

        return $roles;
    }

    /**
     * Fetch a group.
     *
     * @param string $name the name of the user.
     * @param GetGroupOptions|null $options the options to use when fetching the group.
     * @return Group
     * @since 4.0.0
     */
    public function getGroup(string $name, GetGroupOptions $options = null): Group
    {
        $result = Extension\groupGet($this->core, $name, GetGroupOptions::export($options));
        return Group::import($result);
    }

    /**
     * Get all groups.
     *
     * @param GetAllGroupsOptions|null $options the options to use when fetching the groups.
     * @return array
     * @see \Couchbase\Management\Group
     * @since 4.0.0
     */
    public function getAllGroups(GetAllGroupsOptions $options = null): array
    {
        $result = Extension\groupGetAll($this->core, GetAllGroupsOptions::export($options));
        $groups = [];
        foreach ($result as $group) {
            $groups[] = Group::import($group);
        }

        return $groups;
    }

    /**
     * Create or replace a group.
     *
     * @param Group $group the group.
     * @param UpsertGroupOptions|null $options the options to use when upserting the group.
     * @since 4.0.0
     */
    public function upsertGroup(Group $group, UpsertGroupOptions $options = null)
    {
        Extension\groupUpsert($this->core, Group::export($group), UpsertGroupOptions::export($options));
    }

    /**
     * Remove a group.
     *
     * @param string $name the name of the group.
     * @param DropGroupOptions|null $options the options to use when dropping the group.
     * @since 4.0.0
     */
    public function dropGroup(string $name, DropGroupOptions $options = null)
    {
        Extension\groupDrop($this->core, $name, DropGroupOptions::export($options));
    }

    /**
     * Changes password of the currently authenticated user,
     * @param string $newPassword new password
     * @param ChangePasswordOptions|null $options the options to use when changing the password of the user
     * @since 4.1.1
     */
    public function changePassword(string $newPassword, ChangePasswordOptions $options = null)
    {
        Extension\passwordChange($this->core, $newPassword, ChangePasswordOptions::export($options));
    }
}
