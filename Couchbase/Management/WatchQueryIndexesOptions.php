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

class WatchQueryIndexesOptions
{
    private ?string $scopeName = null;
    private ?string $collectionName = null;
    private ?bool $watchPrimary = null;

    /**
     * Static helper to keep code more readable
     *
     * @return WatchQueryIndexesOptions
     * @since 4.0.0
     */
    public static function build(): WatchQueryIndexesOptions
    {
        return new WatchQueryIndexesOptions();
    }

    /**
     * @param string $scopeName
     *
     * @deprecated 'Collection.queryIndexes()' should now be used for collection-related query index operations
     * @return WatchQueryIndexesOptions
     * @since 4.0.0
     */
    public function scopeName(string $scopeName): WatchQueryIndexesOptions
    {
        $this->scopeName = $scopeName;
        return $this;
    }

    /**
     * @param string $collectionName
     *
     * @deprecated 'Collection.queryIndexes()' should now be used for collection-related query index operations
     * @return WatchQueryIndexesOptions
     * @since 4.0.0
     */
    public function collectionName(string $collectionName): WatchQueryIndexesOptions
    {
        $this->collectionName = $collectionName;
        return $this;
    }

    /**
     * Whether to watch the primary index
     *
     * @param bool $shouldWatch
     *
     * @return WatchQueryIndexesOptions
     * @since 4.0.0
     */
    public function watchPrimary(bool $shouldWatch): WatchQueryIndexesOptions
    {
        $this->watchPrimary = $shouldWatch;
        return $this;
    }

    /**
     * @param WatchQueryIndexesOptions|null $options
     *
     * @return array
     * @internal
     * @since 4.0.0
     */
    public static function export(?WatchQueryIndexesOptions $options): array
    {
        if ($options == null) {
            return [];
        }
        return [
            'scopeName' => $options->scopeName,
            'collectionName' => $options->collectionName,
            'watchPrimary' => $options->watchPrimary,
        ];
    }
}
