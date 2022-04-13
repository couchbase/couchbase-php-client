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

class UserAndMetadata
{
    private string $domain;
    private User $user;
    private ?array $effectiveRoles;
    private ?string $passwordChanged;
    private ?array $externalGroups;

    /**
     * @internal
     * @param string $domain auth domain for the user
     * @param User $user the user
     * @since 4.0.0
     */
    public function __construct(string $domain, User $user)
    {
        $this->domain = $domain;
        $this->user = $user;
    }

    /**
     * Gets the auth domain.
     *
     * @return string
     * @since 4.0.0
     */
    public function domain(): string
    {
        return $this->domain;
    }

    /**
     * Gets the user.
     *
     * @return User
     * @since 4.0.0
     */
    public function user(): User
    {
        return $this->user;
    }

    /**
     * Gets the effective roles - the roles the user has from groups and directly.
     *
     * @return array
     * @see \Couchbase\Management\RoleAndOrigin
     * @since 4.0.0
     */
    public function effectiveRoles(): ?array
    {
        return $this->effectiveRoles;
    }

    /**
     * Gets the date the password last changed.
     *
     * @return string
     * @since 4.0.0
     */
    public function passwordChanged(): ?string
    {
        return $this->passwordChanged;
    }

    /**
     * Gets any external group names that the user is assigned to.
     *
     * @return string
     * @since 4.0.0
     */
    public function externalGroups(): ?array
    {
        return $this->externalGroups;
    }

    /**
     * @internal
     * @since 4.0.0
     */
    public static function import(array $user): UserAndMetadata
    {
        $settings = new UserAndMetadata($user['domain'], User::import($user));
        if (array_key_exists('effectiveRoles', $user)) {
            $roles = [];
            foreach ($user['effectiveRoles'] as $role) {
                $roles[] = RoleAndOrigin::import($role);
            }
            $settings->effectiveRoles = $roles;
        }
        if (array_key_exists('passwordChanged', $user)) {
            $settings->passwordChanged = $user['passwordChanged'];
        }
        if (array_key_exists('externalGroups', $user)) {
            $settings->externalGroups = $user['externalGroups'];
        }


        return $settings;
    }
}
