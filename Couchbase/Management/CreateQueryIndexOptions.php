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

class CreateQueryIndexOptions
{
    private ?int $timeoutMilliseconds = null;
    private ?string $scopeName = null;
    private ?string $collectionName = null;
    private ?string $condition = null;
    private ?bool $ignoreIfExists = null;
    private ?int $numberOfReplicas = null;
    private ?bool $deferred = null;

    /**
     * Static helper to keep code more readable
     *
     * @return CreateQueryIndexOptions
     * @since 4.0.0
     */
    public static function build(): CreateQueryIndexOptions
    {
        return new CreateQueryIndexOptions();
    }

    /**
     * @param string $scopeName
     *
     * @deprecated 'Collection.queryIndexes()' should now be used for collection-related query index operations
     * @return CreateQueryIndexOptions
     * @since 4.0.0
     */
    public function scopeName(string $scopeName): CreateQueryIndexOptions
    {
        $this->scopeName = $scopeName;
        return $this;
    }

    /**
     * @param string $collectionName
     *
     * @deprecated 'Collection.queryIndexes()' should now be used for collection-related query index operations
     * @return CreateQueryIndexOptions
     * @since 4.0.0
     */
    public function collectionName(string $collectionName): CreateQueryIndexOptions
    {
        $this->collectionName = $collectionName;
        return $this;
    }

    /**
     * @param string $condition
     *
     * @return CreateQueryIndexOptions
     * @since 4.0.0
     */
    public function condition(string $condition): CreateQueryIndexOptions
    {
        $this->condition = $condition;
        return $this;
    }

    /**
     * @param bool $shouldIgnore
     *
     * @return CreateQueryIndexOptions
     * @since 4.0.0
     */
    public function ignoreIfExists(bool $shouldIgnore): CreateQueryIndexOptions
    {
        $this->ignoreIfExists = $shouldIgnore;
        return $this;
    }

    /**
     * @param int $numberOfReplicas
     *
     * @return CreateQueryIndexOptions
     * @since 4.0.0
     */
    public function numReplicas(int $numberOfReplicas): CreateQueryIndexOptions
    {
        $this->numberOfReplicas = $numberOfReplicas;
        return $this;
    }

    /**
     * @param bool $isDeferred
     *
     * @return CreateQueryIndexOptions
     * @since 4.0.0
     */
    public function deferred(bool $isDeferred): CreateQueryIndexOptions
    {
        $this->deferred = $isDeferred;
        return $this;
    }

    /**
     * Sets the operation timeout in milliseconds.
     *
     * @param int $milliseconds the operation timeout to apply
     *
     * @return CreateQueryIndexOptions
     * @since 4.0.0
     */
    public function timeout(int $milliseconds): CreateQueryIndexOptions
    {
        $this->timeoutMilliseconds = $milliseconds;
        return $this;
    }

    /**
     * @param CreateQueryIndexOptions|null $options
     *
     * @return array
     * @internal
     * @since 4.0.0
     */
    public static function export(?CreateQueryIndexOptions $options): array
    {
        if ($options == null) {
            return [];
        }
        return [
            'timeoutMilliseconds' => $options->timeoutMilliseconds,
            'scopeName' => $options->scopeName,
            'collectionName' => $options->collectionName,
            'condition' => $options->condition,
            'ignoreIfExists' => $options->ignoreIfExists,
            'numberOfReplicas' => $options->numberOfReplicas,
            'deferred' => $options->deferred,
        ];
    }
}
