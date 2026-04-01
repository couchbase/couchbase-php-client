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
use Couchbase\Observability\ObservabilityContext;
use Couchbase\Observability\ObservabilityConstants;
use Couchbase\Observability\ObservabilityHandler;

class UserManager implements UserManagerInterface
{
    /**
     * @var resource
     */
    private $core;

    private ObservabilityContext $observability;

    /**
     * @internal
     * @param $core
     * @param ObservabilityContext $observability
     * @since 4.0.0
     */
    public function __construct($core, ObservabilityContext $observability)
    {
        $this->core = $core;
        $this->observability = ObservabilityContext::from(
            $observability,
            service: ObservabilityConstants::ATTR_VALUE_SERVICE_MANAGEMENT
        );
    }

    /**
     * Fetch a user.
     *
     * @param string $name the name of the user.
     * @param GetUserOptions|null $options the options to use when fetching the user.
     * @return UserAndMetadata
     * @since 4.0.0
     */
    public function getUser(string $name, ?GetUserOptions $options = null): UserAndMetadata
    {
        return $this->observability->recordOperation(
            ObservabilityConstants::OP_UM_GET_USER,
            GetUserOptions::getParentSpan($options),
            function (ObservabilityHandler $obsHandler) use ($name, $options) {
                $function = COUCHBASE_EXTENSION_NAMESPACE . '\\userGet';
                $result = $function($this->core, $name, GetUserOptions::export($options), $obsHandler->getCoreSpansArray());
                return UserAndMetadata::import($result);
            }
        );
    }

    /**
     * Fetch all users.
     *
     * @param GetAllUsersOptions|null $options the options to use when fetching the users.
     * @return array
     * @since 4.0.0
     */
    public function getAllUsers(?GetAllUsersOptions $options = null): array
    {
        return $this->observability->recordOperation(
            ObservabilityConstants::OP_UM_GET_ALL_USERS,
            GetAllUsersOptions::getParentSpan($options),
            function (ObservabilityHandler $obsHandler) use ($options) {
                $function = COUCHBASE_EXTENSION_NAMESPACE . '\\userGetAll';
                $result = $function($this->core, GetAllUsersOptions::export($options), $obsHandler->getCoreSpansArray());
                $users = [];
                foreach ($result as $user) {
                    $users[] = UserAndMetadata::import($user);
                }

                return $users;
            }
        );
    }

    /**
     * Create or replace a user.
     *
     * @param User $user the user.
     * @param UpsertUserOptions|null $options the options to use when upserting the user.
     * @since 4.0.0
     */
    public function upsertUser(User $user, ?UpsertUserOptions $options = null)
    {
        $this->observability->recordOperation(
            ObservabilityConstants::OP_UM_UPSERT_USER,
            UpsertUserOptions::getParentSpan($options),
            function (ObservabilityHandler $obsHandler) use ($user, $options) {
                $function = COUCHBASE_EXTENSION_NAMESPACE . '\\userUpsert';
                $function($this->core, User::export($user), UpsertUserOptions::export($options), $obsHandler->getCoreSpansArray());
            }
        );
    }

    /**
     * Remove a user.
     *
     * @param string $name the name of the user.
     * @param DropUserOptions|null $options the options to use when dropping the user.
     * @since 4.0.0
     */
    public function dropUser(string $name, ?DropUserOptions $options = null)
    {
        $this->observability->recordOperation(
            ObservabilityConstants::OP_UM_DROP_USER,
            DropUserOptions::getParentSpan($options),
            function (ObservabilityHandler $obsHandler) use ($name, $options) {
                $function = COUCHBASE_EXTENSION_NAMESPACE . '\\userDrop';
                $function($this->core, $name, DropUserOptions::export($options), $obsHandler->getCoreSpansArray());
            }
        );
    }

    /**
     * Get all roles and descriptions.
     *
     * @param GetRolesOptions|null $options the options to use when fetching the roles.
     * @return array
     * @see \Couchbase\Management\RoleAndDescription
     * @since 4.0.0
     */
    public function getRoles(?GetRolesOptions $options = null): array
    {
        return $this->observability->recordOperation(
            ObservabilityConstants::OP_UM_GET_ROLES,
            GetRolesOptions::getParentSpan($options),
            function (ObservabilityHandler $obsHandler) use ($options) {
                $function = COUCHBASE_EXTENSION_NAMESPACE . '\\roleGetAll';
                $result = $function($this->core, GetRolesOptions::export($options), $obsHandler->getCoreSpansArray());
                foreach ($result as $role) {
                    $roles[] = RoleAndDescription::import($role);
                }

                return $roles;
            }
        );
    }

    /**
     * Fetch a group.
     *
     * @param string $name the name of the user.
     * @param GetGroupOptions|null $options the options to use when fetching the group.
     * @return Group
     * @since 4.0.0
     */
    public function getGroup(string $name, ?GetGroupOptions $options = null): Group
    {
        return $this->observability->recordOperation(
            ObservabilityConstants::OP_UM_GET_GROUP,
            GetGroupOptions::getParentSpan($options),
            function (ObservabilityHandler $obsHandler) use ($name, $options) {
                $function = COUCHBASE_EXTENSION_NAMESPACE . '\\groupGet';
                $result = $function($this->core, $name, GetGroupOptions::export($options), $obsHandler->getCoreSpansArray());
                return Group::import($result);
            }
        );
    }

    /**
     * Get all groups.
     *
     * @param GetAllGroupsOptions|null $options the options to use when fetching the groups.
     * @return array
     * @see \Couchbase\Management\Group
     * @since 4.0.0
     */
    public function getAllGroups(?GetAllGroupsOptions $options = null): array
    {
        return $this->observability->recordOperation(
            ObservabilityConstants::OP_UM_GET_ALL_GROUPS,
            GetAllGroupsOptions::getParentSpan($options),
            function (ObservabilityHandler $obsHandler) use ($options) {
                $function = COUCHBASE_EXTENSION_NAMESPACE . '\\groupGetAll';
                $result = $function($this->core, GetAllGroupsOptions::export($options), $obsHandler->getCoreSpansArray());
                $groups = [];
                foreach ($result as $group) {
                    $groups[] = Group::import($group);
                }

                return $groups;
            }
        );
    }

    /**
     * Create or replace a group.
     *
     * @param Group $group the group.
     * @param UpsertGroupOptions|null $options the options to use when upserting the group.
     * @since 4.0.0
     */
    public function upsertGroup(Group $group, ?UpsertGroupOptions $options = null)
    {
        $this->observability->recordOperation(
            ObservabilityConstants::OP_UM_UPSERT_GROUP,
            UpsertGroupOptions::getParentSpan($options),
            function (ObservabilityHandler $obsHandler) use ($group, $options) {
                $function = COUCHBASE_EXTENSION_NAMESPACE . '\\groupUpsert';
                $function($this->core, Group::export($group), UpsertGroupOptions::export($options), $obsHandler->getCoreSpansArray());
            }
        );
    }

    /**
     * Remove a group.
     *
     * @param string $name the name of the group.
     * @param DropGroupOptions|null $options the options to use when dropping the group.
     * @since 4.0.0
     */
    public function dropGroup(string $name, ?DropGroupOptions $options = null)
    {
        $this->observability->recordOperation(
            ObservabilityConstants::OP_UM_DROP_GROUP,
            DropGroupOptions::getParentSpan($options),
            function (ObservabilityHandler $obsHandler) use ($name, $options) {
                $function = COUCHBASE_EXTENSION_NAMESPACE . '\\groupDrop';
                $function($this->core, $name, DropGroupOptions::export($options), $obsHandler->getCoreSpansArray());
            }
        );
    }

    /**
     * Changes password of the currently authenticated user,
     * @param string $newPassword new password
     * @param ChangePasswordOptions|null $options the options to use when changing the password of the user
     * @since 4.1.1
     */
    public function changePassword(string $newPassword, ?ChangePasswordOptions $options = null)
    {
        $this->observability->recordOperation(
            ObservabilityConstants::OP_UM_CHANGE_PASSWORD,
            ChangePasswordOptions::getParentSpan($options),
            function (ObservabilityHandler $obsHandler) use ($newPassword, $options) {
                $function = COUCHBASE_EXTENSION_NAMESPACE . '\\passwordChange';
                $function($this->core, $newPassword, ChangePasswordOptions::export($options), $obsHandler->getCoreSpansArray());
            }
        );
    }
}
