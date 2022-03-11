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
 * Bucket is an object containing functionality for performing bucket level operations
 * against a cluster and for access to scopes and collections.
 */
class Bucket
{
    /**
     * Returns a new Scope object representing the default scope.
     *
     * @return Scope
     */
    public function defaultScope(): Scope
    {
    }

    /**
     * Returns a new Collection object representing the default collectiom.
     *
     * @return Collection
     */
    public function defaultCollection(): Collection
    {
    }

    /**
     * Returns a new Scope object representing the given scope.
     *
     * @param string $name the name of the scope
     * @return Scope
     */
    public function scope(string $name): Scope
    {
    }

    /**
     * Sets the default transcoder to be used when fetching or sending data.
     *
     * @param callable $encoder the encoder to use to encode data when sending data to the server
     * @param callable $decoder the decoder to use to decode data when retrieving data from the server
     */
    public function setTranscoder(callable $encoder, callable $decoder)
    {
    }

    /**
     * Returns the name of the Bucket.
     *
     * @return string
     */
    public function name(): string
    {
    }

    /**
     * Executes a view query against the cluster.
     *
     * @param string $designDoc the design document to use for the query
     * @param string $viewName the view to use for the query
     * @param ViewOptions $options the options to use when executing the query
     * @return ViewResult
     */
    public function viewQuery(string $designDoc, string $viewName, ViewOptions $options = null): ViewResult
    {
    }

    /**
     * Creates a new CollectionManager object for managing collections and scopes.
     *
     * @return CollectionManager
     */
    public function collections(): CollectionManager
    {
    }

    /**
     * Creates a new ViewIndexManager object for managing views and design documents.
     *
     * @return ViewIndexManager
     */
    public function viewIndexes(): ViewIndexManager
    {
    }

    /**
     * Executes a ping for each service against each node in the cluster. This can be used for determining
     * the current health of the cluster.
     *
     * @param mixed $services the services to ping against
     * @param mixed $reportId a name which will be included within the ping result
     */
    public function ping($services, $reportId)
    {
    }

    /**
     * Returns diagnostics information about connections that the SDK has to the cluster. This does not perform
     * any operations.
     *
     * @param mixed $reportId a name which will be included within the ping result
     */
    public function diagnostics($reportId)
    {
    }
}
