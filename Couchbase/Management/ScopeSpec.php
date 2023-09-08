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

class ScopeSpec
{
    private string $name;
    private array $collections;

    /**
     * @param string $name
     * @param array $collections
     * @since 4.1.3
     */
    public function __construct(string $name, array $collections)
    {
        $this->name = $name;
        $this->collections = $collections;
    }

    /**
     * Static helper to keep code more readable
     *
     * @param string $name
     * @param array $collections
     * @return ScopeSpec
     */
    public static function build(string $name, array $collections): ScopeSpec
    {
        return new ScopeSpec($name, $collections);
    }

    /**
     * Gets the name of the scope
     * @return string scope name
     * @since 4.1.3
     */
    public function name(): string
    {
        return $this->name;
    }

    /**
     * Gets the array of collections
     *
     * @see CollectionSpec
     * @return array
     * @since 4.1.3
     */
    public function collections(): array
    {
        return $this->collections;
    }

    /**
     * Sets the name of the scope
     *
     * @param string $name scope name
     * @return ScopeSpec
     * @since 4.1.3
     */
    public function setName(string $name): ScopeSpec
    {
        $this->name = $name;
        return $this;
    }

    /**
     * Sets the collections included in the scope
     *
     * @see CollectionSpec
     * @param array $collections
     * @return $this
     */
    public function setCollections(array $collections): ScopeSpec
    {
        $this->collections = $collections;
        return $this;
    }

    /**
     * @param ScopeSpec $spec
     * @return array
     */
    public static function export(ScopeSpec $spec): array
    {
        return [
            "name" => $spec->name,
            "collections" => $spec->collections
        ];
    }

    /**
     * @param array $scope
     * @return ScopeSpec
     */
    public static function import(array $scope): ScopeSpec
    {
        $collections = [];
        foreach ($scope['collections'] as $collection) {
            $newColl = new CollectionSpec($collection['name'], $scope['name']);
            $newColl->setMaxExpiry($collection['max_expiry']);
            if (array_key_exists("history", $collection)) {
                $newColl->setHistory($collection['history']);
            }
            $collections[] = $newColl;
        }
        return new ScopeSpec($scope['name'], $collections);
    }
}
