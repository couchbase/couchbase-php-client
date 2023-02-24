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

class GetAllQueryIndexesOptions
{
    private ?int $timeoutMilliseconds = null;
    private ?string $scopeName = null;
    private ?string $collectionName = null;

    /**
     * Static helper to keep code more readable
     *
     * @return GetAllQueryIndexesOptions
     * @since 4.0.0
     */
    public static function build(): GetAllQueryIndexesOptions
    {
        return new GetAllQueryIndexesOptions();
    }

    /**
     * @param string $scopeName
     *
     * @deprecated 'Collection.queryIndexes()' should now be used for collection-related query index operations
     * @return GetAllQueryIndexesOptions
     */
    public function scopeName(string $scopeName): GetAllQueryIndexesOptions
    {
        $this->scopeName = $scopeName;
        return $this;
    }

    /**
     * @param string $collectionName
     *
     * @deprecated 'Collection.queryIndexes()' should now be used for collection-related query index operations
     * @return GetAllQueryIndexesOptions
     */
    public function collectionName(string $collectionName): GetAllQueryIndexesOptions
    {
        $this->collectionName = $collectionName;
        return $this;
    }

    /**
     * Sets the operation timeout in milliseconds.
     *
     * @param int $milliseconds the operation timeout to apply
     *
     * @return GetAllQueryIndexesOptions
     * @since 4.0.0
     */
    public function timeout(int $milliseconds): GetAllQueryIndexesOptions
    {
        $this->timeoutMilliseconds = $milliseconds;
        return $this;
    }

    /**
     * @param GetAllQueryIndexesOptions|null $options
     *
     * @return array
     * @internal
     * @since 4.0.0
     */
    public static function export(?GetAllQueryIndexesOptions $options): array
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
