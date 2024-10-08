<?php

/*
 * Copyright 2022-Present Couchbase, Inc.
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

interface UserManagerInterface
{
    public function getUser(string $name, ?GetUserOptions $options = null): UserAndMetadata;

    public function getAllUsers(?GetAllUsersOptions $options = null): array;

    public function upsertUser(User $user, ?UpsertUserOptions $options = null);

    public function dropUser(string $name, ?DropUserOptions $options = null);

    public function getRoles(?GetRolesOptions $options = null): array;

    public function getGroup(string $name, ?GetGroupOptions $options = null): Group;

    public function getAllGroups(?GetAllGroupsOptions $options = null): array;

    public function upsertGroup(Group $group, ?UpsertGroupOptions $options = null);

    public function dropGroup(string $name, ?DropGroupOptions $options = null);

    public function changePassword(string $newPassword, ?ChangePasswordOptions $options = null);
}
