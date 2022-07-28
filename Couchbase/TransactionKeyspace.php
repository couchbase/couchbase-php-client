<?php

/**
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

namespace Couchbase;

class TransactionKeyspace
{
    public const DEFAULT_SCOPE = "_default";
    public const DEFAULT_COLLECTION = "_default";

    private string $bucket;
    private string $scope;
    private string $collection;

    /**
     * @param string $bucket
     * @param string $scope
     * @param string $collection
     * @since 4.0.1
     */
    public function __construct(string $bucket, string $scope = self::DEFAULT_SCOPE, string $collection = self::DEFAULT_COLLECTION)
    {
        $this->bucket = $bucket;
        $this->scope = $scope;
        $this->collection = $collection;
    }

    /**
     * @param TransactionKeyspace|null $keyspace
     *
     * @return array|null
     * @internal
     * @since 4.0.1
     */
    public static function export(?TransactionKeyspace $keyspace): ?array
    {
        if ($keyspace == null) {
            return null;
        }
        return [
            'bucket' => $keyspace->bucket,
            'scope' => $keyspace->scope,
            'collection' => $keyspace->collection,
        ];
    }
}
