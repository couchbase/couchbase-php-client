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

use Couchbase\Exception\UnsupportedOperationException;

/**
 * Scope is an object for providing access to collections.
 */
class Scope
{
    private string $bucketName;
    private string $name;
    /**
     * @var resource
     */
    private $core;

    /**
     * @private
     *
     * @param string $name
     * @param string $bucketName
     * @param        $core
     *
     * @since 4.0.0
     */
    public function __construct(string $name, string $bucketName, $core)
    {
        $this->name = $name;
        $this->bucketName = $bucketName;
        $this->core = $core;
    }

    /**
     * Returns the name of the scope.
     *
     * @return string
     * @since 4.0.0
     */
    public function name(): string
    {
        return $this->name;
    }

    /**
     * Returns a new Collection object representing the collection specified.
     *
     * @param string $name the name of the collection
     *
     * @return Collection
     * @since 4.0.0
     */
    public function collection(string $name): Collection
    {
        return new Collection($name, $this->name, $this->bucketName, $this->core);
    }

    /**
     * Executes a N1QL query against the cluster with scopeName set implicitly.
     *
     * @param string $statement the N1QL query statement to execute
     * @param QueryOptions|null $options the options to use when executing the query
     *
     * @return QueryResult
     * @since 4.0.0
     */
    public function query(string $statement, QueryOptions $options = null): QueryResult
    {
        $result = Extension\query($this->core, $statement, QueryOptions::export($options, $this->name, $this->bucketName));

        return new QueryResult($result, QueryOptions::getTranscoder($options));
    }

    /**
     * Executes an analytics query against the cluster with scopeName set implicitly.
     *
     * @param string $statement the analytics query statement to execute
     * @param AnalyticsOptions|null $options the options to use when executing the query
     *
     * @return AnalyticsResult
     * @since 4.0.0
     */
    public function analyticsQuery(string $statement, AnalyticsOptions $options = null): AnalyticsResult
    {
        $result = Extension\analyticsQuery($this->core, $statement, AnalyticsOptions::export($options, $this->name, $this->bucketName));

        return new AnalyticsResult($result, AnalyticsOptions::getTranscoder($options));
    }
}
