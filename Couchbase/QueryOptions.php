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

class QueryOptions
{
    /**
     * Sets the operation timeout in milliseconds.
     *
     * @param int $arg the operation timeout to apply
     * @return QueryOptions
     */
    public function timeout(int $arg): QueryOptions
    {
    }

    /**
     * Sets the mutation state to achieve consistency with for read your own writes (RYOW).
     *
     * @param MutationState $arg the mutation state to achieve consistency with
     * @return QueryOptions
     */
    public function consistentWith(MutationState $arg): QueryOptions
    {
    }

    /**
     * Sets the scan consistency.
     *
     * @param int $arg the scan consistency level
     * @return QueryOptions
     */
    public function scanConsistency(int $arg): QueryOptions
    {
    }

    /**
     * Sets the maximum buffered channel size between the indexer client and the query service for index scans.
     *
     * @param int $arg the maximum buffered channel size
     * @return QueryOptions
     */
    public function scanCap(int $arg): QueryOptions
    {
    }

    /**
     * Sets the maximum number of items each execution operator can buffer between various operators.
     *
     * @param int $arg the maximum number of items each execution operation can buffer
     * @return QueryOptions
     */
    public function pipelineCap(int $arg): QueryOptions
    {
    }

    /**
     * Sets the number of items execution operators can batch for fetch from the KV service.
     *
     * @param int $arg the pipeline batch size
     * @return QueryOptions
     */
    public function pipelineBatch(int $arg): QueryOptions
    {
    }

    /**
     * Sets the maximum number of index partitions, for computing aggregation in parallel.
     *
     * @param int $arg the number of index partitions
     * @return QueryOptions
     */
    public function maxParallelism(int $arg): QueryOptions
    {
    }

    /**
     * Sets the query profile mode to use.
     *
     * @param int $arg the query profile mode
     * @return QueryOptions
     */
    public function profile(int $arg): QueryOptions
    {
    }

    /**
     * Sets whether or not this query is readonly.
     *
     * @param bool $arg whether the query is readonly
     * @return QueryOptions
     */
    public function readonly(bool $arg): QueryOptions
    {
    }

    /**
     * Sets whether or not this query allowed to use FlexIndex (full text search integration).
     *
     * @param bool $arg whether the FlexIndex allowed
     * @return QueryOptions
     */
    public function flexIndex(bool $arg): QueryOptions
    {
    }

    /**
     * Sets whether or not this query is adhoc.
     *
     * @param bool $arg whether the query is adhoc
     * @return QueryOptions
     */
    public function adhoc(bool $arg): QueryOptions
    {
    }

    /**
     * Sets the named parameters for this query.
     *
     * @param array $pairs the associative array of parameters
     * @return QueryOptions
     */
    public function namedParameters(array $pairs): QueryOptions
    {
    }

    /**
     * Sets the positional parameters for this query.
     *
     * @param array $args the array of parameters
     * @return QueryOptions
     */
    public function positionalParameters(array $args): QueryOptions
    {
    }

    /**
     * Sets any extra query parameters that the SDK does not provide an option for.
     *
     * @param string $key the name of the parameter
     * @param string $value the value of the parameter
     * @return QueryOptions
     */
    public function raw(string $key, $value): QueryOptions
    {
    }

    /**
     * Sets the client context id for this query.
     *
     * @param string $arg the client context id
     * @return QueryOptions
     */
    public function clientContextId(string $arg): QueryOptions
    {
    }

    /**
     * Sets whether or not to return metrics with the query.
     *
     * @param bool $arg whether to return metrics
     * @return QueryOptions
     */
    public function metrics(bool $arg): QueryOptions
    {
    }

    /**
     * Associate scope name with query
     *
     * @param string $arg the name of the scope
     * @return QueryOptions
     */
    public function scopeName(string $arg): QueryOptions
    {
    }

    /**
     * Associate scope qualifier (also known as `query_context`) with the query.
     *
     * The qualifier must be in form `${bucketName}.${scopeName}` or `default:${bucketName}.${scopeName}`
     *
     * @param string $arg the scope qualifier
     * @return QueryOptions
     */
    public function scopeQualifier(string $arg): QueryOptions
    {
    }
}
