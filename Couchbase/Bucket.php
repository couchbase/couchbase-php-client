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
use Couchbase\Management\CollectionManager;
use Couchbase\Management\ViewIndexManager;

/**
 * Bucket is an object containing functionality for performing bucket level operations
 * against a cluster and for access to scopes and collections.
 *
 * @since 4.0.0
 */
class Bucket implements BucketInterface
{
    private string $name;
    /**
     * @var resource
     */
    private $core;

    /**
     * @param string $name
     * @param resource $core
     *
     * @internal
     *
     * @since 4.0.0
     */
    public function __construct(string $name, $core)
    {
        $this->name = $name;
        $this->core = $core;
        Extension\openBucket($this->core, $this->name);
    }

    /**
     * Returns a new Scope object representing the default scope.
     *
     * @return Scope
     * @since 4.0.0
     */
    public function defaultScope(): ScopeInterface
    {
        return new Scope("_default", $this->name, $this->core);
    }

    /**
     * Returns a new Collection object representing the default collection.
     *
     * @return Collection
     * @since 4.0.0
     */
    public function defaultCollection(): CollectionInterface
    {
        return new Collection("_default", "_default", $this->name, $this->core);
    }

    /**
     * Returns a new Scope object representing the given scope.
     *
     * @param string $name the name of the scope
     *
     * @return Scope
     * @since 4.0.0
     */
    public function scope(string $name): ScopeInterface
    {
        return new Scope($name, $this->name, $this->core);
    }

    /**
     * Sets the default transcoder to be used when fetching or sending data.
     *
     * @param callable $encoder the encoder to use to encode data when sending data to the server
     * @param callable $decoder the decoder to use to decode data when retrieving data from the server
     *
     * @throws UnsupportedOperationException
     * @since 4.0.0
     */
    public function setTranscoder(callable $encoder, callable $decoder)
    {
        throw new UnsupportedOperationException();
    }

    /**
     * Returns the name of the Bucket.
     *
     * @return string
     * @since 4.0.0
     */
    public function name(): string
    {
        return $this->name;
    }

    /**
     * Executes a view query against the cluster.
     *
     * @param string $designDoc the design document to use for the query
     * @param string $viewName the view to use for the query
     * @param ViewOptions|null $options the options to use when executing the query
     *
     * @return ViewResult
     * @since 4.0.0
     */
    public function viewQuery(string $designDoc, string $viewName, ViewOptions $options = null): ViewResult
    {
        $opts = ViewOptions::export($options);
        $namespace = $opts["namespace"];

        $result = Extension\viewQuery($this->core, $this->name, $designDoc, $viewName, $namespace, $opts);

        return new ViewResult($result);
    }

    /**
     * Creates a new CollectionManager object for managing collections and scopes.
     *
     * @return CollectionManager
     * @since 4.0.0
     */
    public function collections(): CollectionManager
    {
        return new CollectionManager($this->core, $this->name);
    }

    /**
     * Creates a new ViewIndexManager object for managing views and design documents.
     *
     * @return ViewIndexManager
     * @throws UnsupportedOperationException
     * @since 4.0.0
     */
    public function viewIndexes(): ViewIndexManager
    {
        throw new UnsupportedOperationException();
    }

    /**
     * Executes a ping for each service against each node in the cluster. This can be used for determining
     * the current health of the cluster.
     *
     * @param mixed|null $services the services to ping against
     * @param mixed|null $reportId a name which will be included within the ping result
     *
     * @see ServiceType::KEY_VALUE
     * @see ServiceType::QUERY
     * @see ServiceType::ANALYTICS
     * @see ServiceType::SEARCH
     * @see ServiceType::VIEWS
     * @see ServiceType::MANAGEMENT
     * @see ServiceType::EVENTING
     * @since 4.0.0
     */
    public function ping($services = null, $reportId = null)
    {
        $options = [
            'bucketName' => $this->name,
        ];
        if ($services != null) {
            $options['serviceTypes'] = $services;
        }
        if ($reportId != null) {
            $options['reportId'] = $reportId;
        }
        return Extension\ping($this->core, $options);
    }

    /**
     * Returns diagnostics information about connections that the SDK has to the cluster. This does not perform
     * any operations.
     *
     * @param string|null $reportId a name which will be included within the ping result
     *
     * @deprecated - see cluster->diagnostics
     * @since 4.0.0
     */
    public function diagnostics(string $reportId = null)
    {
        if ($reportId == null) {
            $reportId = uniqid();
        }
        return Extension\diagnostics($this->core, $reportId);
    }
}
