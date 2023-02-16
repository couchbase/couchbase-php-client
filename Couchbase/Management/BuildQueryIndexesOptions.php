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

class BuildQueryIndexesOptions
{
    private ?int $timeoutMilliseconds = null;
    private ?string $scopeName = null;
    private ?string $collectionName = null;

    /**
     * Static helper to keep code more readable
     *
     * @return BuildQueryIndexesOptions
     * @since 4.0.0
     */
    public static function build(): BuildQueryIndexesOptions
    {
        return new BuildQueryIndexesOptions();
    }

    /**
     * @param string $scopeName
     *
     * @deprecated 'Collection.queryIndexes()' should now be used for collection-related query index operations
     * @return BuildQueryIndexesOptions
     * @since 4.0.0
     */
    public function scopeName(string $scopeName): BuildQueryIndexesOptions
    {
        $this->scopeName = $scopeName;
        return $this;
    }

    /**
     * @param string $collectionName
     *
     * @deprecated 'Collection.queryIndexes()' should now be used for collection-related query index operations
     * @return BuildQueryIndexesOptions
     * @since 4.0.0
     */
    public function collectionName(string $collectionName): BuildQueryIndexesOptions
    {
        $this->collectionName = $collectionName;
        return $this;
    }

    /**
     * Sets the operation timeout in milliseconds.
     *
     * @param int $milliseconds the operation timeout to apply
     *
     * @return BuildQueryIndexesOptions
     * @since 4.0.0
     */
    public function timeout(int $milliseconds): BuildQueryIndexesOptions
    {
        $this->timeoutMilliseconds = $milliseconds;
        return $this;
    }

    /**
     * @param BuildQueryIndexesOptions|null $options
     *
     * @return array
     * @internal
     * @since 4.0.0
     */
    public static function export(?BuildQueryIndexesOptions $options): array
    {
        if ($options == null) {
            return [];
        }
        return [
            'timeoutMilliseconds' => $options->timeoutMilliseconds,
            'scopeName' => $options->scopeName,
            'collectionName' => $options->collectionName,
        ];
    }
}
