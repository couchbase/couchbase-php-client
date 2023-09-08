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

class CollectionSpec
{
    private string $name;
    private string $scopeName;
    private ?int $maxExpiry;
    private ?bool $history;

    /**
     * @param string $name
     * @param string $scopeName
     * @param int|null $maxExpiry
     * @since 4.1.3
     */
    public function __construct(string $name, string $scopeName, int $maxExpiry = null, bool $history = null)
    {
        $this->name = $name;
        $this->scopeName = $scopeName;
        $this->maxExpiry = $maxExpiry;
        $this->history = $history;
    }

    /**
     * Static helper to keep code more readable
     *
     * @param string $name
     * @param string $scopeName
     * @param int|null $maxExpiry
     * @return CollectionSpec
     * @since 4.1.3
     */
    public static function build(string $name, string $scopeName, int $maxExpiry = null, bool $history = null): CollectionSpec
    {
        return new CollectionSpec($name, $scopeName, $maxExpiry, $history);
    }

    /**
     * Get the name of the collection
     *
     * @return string collection name
     * @since 4.1.3
     */
    public function name(): string
    {
        return $this->name;
    }

    /**
     * Get the name of the scope which the collection belongs to
     *
     * @return string scope name
     * @since 4.1.3
     */
    public function scopeName(): string
    {
        return $this->scopeName;
    }

    /**
     * Get the max expiry of the collection
     *
     * @return int|null
     * @since 4.1.3
     */
    public function maxExpiry(): ?int
    {
        return $this->maxExpiry;
    }

    /**
     * Gets the history retention override setting on this collection.
     * Only supported for Magma buckets
     *
     * @return bool|null
     * @since 4.1.6
     */
    public function history(): ?bool
    {
        return $this->history;
    }

    /**
     * Set the name of the collection
     *
     * @param string $name collection name
     * @return CollectionSpec
     * @since 4.1.3
     */
    public function setName(string $name): CollectionSpec
    {
        $this->name = $name;
        return $this;
    }

    /**
     * Sets the name of the scope which the collection belongs to
     * @param string $scopeName scope name
     * @return CollectionSpec
     * @since 4.1.3
     */
    public function setScopeName(string $scopeName): CollectionSpec
    {
        $this->scopeName = $scopeName;
        return $this;
    }

    /**
     * Sets the max expiry of the collection
     *
     * @param int $seconds max expiry in seconds
     * @return CollectionSpec
     * @since 4.1.3
     */
    public function setMaxExpiry(int $seconds): CollectionSpec
    {
        $this->maxExpiry = $seconds;
        return $this;
    }

    /**
     * Sets the history retention override setting for this collection.
     * Only supported for Magma buckets.
     *
     * @param bool $history
     * @return CollectionSpec
     * @since 4.1.6
     */
    public function setHistory(bool $history): CollectionSpec
    {
        $this->history = $history;
        return $this;
    }

    /**
     * @param CollectionSpec $spec
     * @return array
     * @since 4.1.3
     */
    public static function export(CollectionSpec $spec): array
    {
        return [
            'name' => $spec->name,
            'scopeName' => $spec->scopeName,
            'maxExpiry' => $spec->maxExpiry,
            'history' => $spec->history,
        ];
    }

    /**
     * @param array $collection
     * @return CollectionSpec
     * @since 4.1.3
     */
    public static function import(array $collection): CollectionSpec
    {
        $collectionSpec = new CollectionSpec($collection['name'], $collection['scopeName']);
        if (array_key_exists('maxExpiry', $collection)) {
            $collectionSpec->setMaxExpiry($collection['maxExpiry']);
        }
        if (array_key_exists('history', $collection)) {
            $collectionSpec->setHistory($collection['history']);
        }
        return $collectionSpec;
    }
}
