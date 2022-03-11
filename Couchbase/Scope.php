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

namespace Couchbase;

/**
 * Scope is an object for providing access to collections.
 */
class Scope
{
    public function __construct(Bucket $bucket, string $name)
    {
    }

    /**
     * Returns the name of the scope.
     *
     * @return string
     */
    public function name(): string
    {
    }

    /**
     * Returns a new Collection object representing the collection specified.
     *
     * @param string $name the name of the collection
     * @return Collection
     */
    public function collection(string $name): Collection
    {
    }

    /**
     * Executes a N1QL query against the cluster with scopeName set implicitly.
     *
     * @param string $statement the N1QL query statement to execute
     * @param QueryOptions $options the options to use when executing the query
     * @return QueryResult
     */
    public function query(string $statement, QueryOptions $options = null): QueryResult
    {
    }

    /**
     * Executes an analytics query against the cluster with scopeName set implicitly.
     *
     * @param string $statement the analytics query statement to execute
     * @param AnalyticsOptions $options the options to use when executing the query
     * @return AnalyticsResult
     */
    public function analyticsQuery(string $statement, AnalyticsOptions $options = null): AnalyticsResult
    {
    }
}
