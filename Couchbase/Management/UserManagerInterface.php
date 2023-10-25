<?php

namespace Couchbase\Management;

interface UserManagerInterface
{
    public function getUser(string $name, GetUserOptions $options = null): UserAndMetadata;

    public function getAllUsers(GetAllUsersOptions $options = null): array;

    public function upsertUser(User $user, UpsertUserOptions $options = null);

    public function dropUser(string $name, DropUserOptions $options = null);

    public function getRoles(GetRolesOptions $options = null): array;

    public function getGroup(string $name, GetGroupOptions $options = null): Group;

    public function getAllGroups(GetAllGroupsOptions $options = null): array;

    public function upsertGroup(Group $group, UpsertGroupOptions $options = null);

    public function dropGroup(string $name, DropGroupOptions $options = null);

    public function changePassword(string $newPassword, ChangePasswordOptions $options = null);
}
