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

class Role
{
    private string $name = "";
    private ?string $bucket = null;
    private ?string $scope = null;
    private ?string $collection = null;

    /**
     * @param string $name name of the role
     * @since 4.0.0
     */
    public function __construct()
    {
    }

    /**
     * Static helper to keep code more readable
     *
     * @return Role
     * @since 4.0.0
     */
    public static function build(): Role
    {
        return new Role();
    }

    /**
     * Gets the name of the role.
     *
     * @return string
     * @since 4.0.0
     */
    public function name(): string
    {
        return $this->name;
    }

    /**
     * Gets the bucket name of the role.
     *
     * @return string|null
     * @since 4.0.0
     */
    public function bucket(): ?string
    {
        return $this->bucket;
    }

    /**
     * Gets the scope name of the role.
     *
     * @return string|null
     * @since 4.0.0
     */
    public function scope(): ?string
    {
        return $this->scope;
    }

    /**
     * Gets the collection name of the role.
     *
     * @return string|null
     * @since 4.0.0
     */
    public function collection(): ?string
    {
        return $this->collection;
    }

    /**
     * Sets the name of the role.
     *
     * @param string $name the name of the role
     * @return Role
     * @since 4.0.0
     */
    public function setName(string $name): Role
    {
        $this->name = $name;
        return $this;
    }

    /**
     * Sets the bucket name of the role.
     *
     * @param string $bucket the name of the bucket
     * @return Role
     * @since 4.0.0
     */
    public function setBucket(string $bucket): Role
    {
        $this->bucket = $bucket;
        return $this;
    }

    /**
     * Sets the scope name of the role.
     *
     * @param string $scope the name of the scope
     * @return Role
     * @since 4.0.0
     */
    public function setScope(string $scope): Role
    {
        $this->scope = $scope;
        return $this;
    }

    /**
     * Sets the collection name of the role.
     *
     * @param string $collection the name of the collection
     * @return Role
     * @since 4.0.0
     */
    public function setCollection(string $collection): Role
    {
        $this->collection = $collection;
        return $this;
    }

    /**
     * @internal
     * @since 4.0.0
     */
    public static function import(array $role): Role
    {
        $settings = Role::build()->setName($role['name']);
        if (array_key_exists('bucket', $role)) {
            $settings->setBucket($role['bucket']);
        }
        if (array_key_exists('scope', $role)) {
            $settings->setScope($role['scope']);
        }
        if (array_key_exists('collection', $role)) {
            $settings->setCollection($role['collection']);
        }

        return $settings;
    }

    /**
     * @param Role $role
     *
     * @return array
     * @throws InvalidArgumentException
     * @since 4.0.0
     * @internal
     *
     */
    public static function export(Role $role): array
    {
        if ($role->name == "") {
            throw new InvalidArgumentException();
        }

        return [
            'name' => $role->name,
            'bucket' => $role->bucket,
            'scope' => $role->scope,
            'collection' => $role->collection,
        ];
    }
}
