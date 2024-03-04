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

namespace Couchbase\Management;

use Couchbase\Exception\InvalidArgumentException;
use Couchbase\Exception\UnambiguousTimeoutException;
use Couchbase\Extension;

class CollectionQueryIndexManager implements CollectionQueryIndexManagerInterface
{
    private string $collectionName;
    private string $scopeName;
    private string $bucketName;
    /**
     * @var resource
     */
    private $core;


    /**
     * @param string $collectionName
     * @param string $scopeName
     * @param string $bucketName
     * @param $core
     *
     * @internal
     * @since 4.1.2
     */
    public function __construct(string $collectionName, string $scopeName, string $bucketName, $core)
    {
        $this->collectionName = $collectionName;
        $this->scopeName = $scopeName;
        $this->bucketName = $bucketName;
        $this->core = $core;
    }

    /**
     * Fetches all indexes from the server.
     *
     * @param GetAllQueryIndexesOptions|null $options
     *
     * @return array
     * @throws InvalidArgumentException
     * @since 4.1.2
     */
    public function getAllIndexes(GetAllQueryIndexesOptions $options = null): array
    {
        $exported = GetAllQueryIndexesOptions::export($options);
        $this->checkOptions($exported);
        $responses = Extension\collectionQueryIndexGetAll($this->core, $this->bucketName, $this->scopeName, $this->collectionName, $exported);
        return array_map(
            function (array $response) {
                return new QueryIndex($response);
            },
            $responses
        );
    }

    /**
     * Creates a new index
     *
     * @param string $indexName
     * @param array $keys
     * @param CreateQueryIndexOptions|null $options
     *
     * @throws InvalidArgumentException
     * @since 4.1.2
     */
    public function createIndex(string $indexName, array $keys, CreateQueryIndexOptions $options = null)
    {
        $exported = CreateQueryIndexOptions::export($options);
        $this->checkOptions($exported);
        Extension\collectionQueryIndexCreate($this->core, $this->bucketName, $this->scopeName, $this->collectionName, $indexName, $keys, $exported);
    }

    /**
     * Creates a new primary index
     *
     * @param CreateQueryPrimaryIndexOptions|null $options
     *
     * @throws InvalidArgumentException
     * @since 4.1.2
     */
    public function createPrimaryIndex(CreateQueryPrimaryIndexOptions $options = null)
    {
        $exported = CreateQueryPrimaryIndexOptions::export($options);
        $this->checkOptions($exported);
        Extension\collectionQueryIndexCreatePrimary($this->core, $this->bucketName, $this->scopeName, $this->collectionName, $exported);
    }

    /**
     * Drops an index
     *
     * @param string $indexName
     * @param DropQueryIndexOptions|null $options
     *
     * @throws InvalidArgumentException
     * @since 4.1.2
     */
    public function dropIndex(string $indexName, DropQueryIndexOptions $options = null)
    {
        $exported = DropQueryIndexOptions::export($options);
        $this->checkOptions($exported);
        Extension\collectionQueryIndexDrop($this->core, $this->bucketName, $this->scopeName, $this->collectionName, $indexName, $exported);
    }

    /**
     * Drops a primary index
     *
     * @param DropQueryPrimaryIndexOptions|null $options
     *
     * @throws InvalidArgumentException
     * @since 4.1.2
     */
    public function dropPrimaryIndex(DropQueryPrimaryIndexOptions $options = null)
    {
        $exported = DropQueryPrimaryIndexOptions::export($options);
        $this->checkOptions($exported);
        Extension\collectionQueryIndexDropPrimary($this->core, $this->bucketName, $this->scopeName, $this->collectionName, $exported);
    }

    /**
     * Build Deferred builds all indexes which are currently in deferred state.
     *
     * @param BuildQueryIndexesOptions|null $options
     *
     * @throws InvalidArgumentException
     * @since 4.1.2
     */
    public function buildDeferredIndexes(BuildQueryIndexesOptions $options = null)
    {
        $exported = BuildQueryIndexesOptions::export($options);
        $this->checkOptions($exported);
        Extension\collectionQueryIndexBuildDeferred($this->core, $this->bucketName, $this->scopeName, $this->collectionName, $exported);
    }

    /**
     * Watch polls indexes until they are online.
     *
     * @param array $indexNames
     * @param int $timeoutMilliseconds
     * @param WatchQueryIndexesOptions|null $options
     *
     * @throws UnambiguousTimeoutException|InvalidArgumentException
     * @since 4.1.2
     */
    public function watchIndexes(array $indexNames, int $timeoutMilliseconds, WatchQueryIndexesOptions $options = null)
    {
        $exported = WatchQueryIndexesOptions::export($options);
        $this->checkOptions($exported);
        if (array_key_exists("watchPrimary", $exported) && $exported["watchPrimary"]) {
            $indexNames [] = "#primary";
        }
        $deadline = (int)(microtime(true) * 1000) + $timeoutMilliseconds;

        while (true) {
            $now = (int)(microtime(true) * 1000);
            if ($now >= $deadline) {
                throw new UnambiguousTimeoutException(
                    sprintf(
                        "Failed to find all indexes online within the allotted time (%dms)",
                        $timeoutMilliseconds
                    )
                );
            }
            $options = GetAllQueryIndexesOptions::build()->timeout($deadline - $now);
            $foundIndexes = $this->getAllIndexes($options);
            $onlineIndexes = [];
            /**
             * @var QueryIndex $index
             */
            foreach ($foundIndexes as $index) {
                if ($index->state() == "online") {
                    $onlineIndexes[$index->name()] = true;
                }
            }
            $allOnline = true;
            /**
             * @var string $name
             */
            foreach ($indexNames as $name) {
                if (!array_key_exists($name, $onlineIndexes)) {
                    $allOnline = false;
                    break;
                }
            }
            if ($allOnline) {
                break;
            }

            usleep(100_000); /* wait for 100ms */
        }
    }

    /**
     * @throws InvalidArgumentException
     */
    private function checkOptions(array $exportedOpts)
    {
        if (isset($exportedOpts['scopeName']) || isset($exportedOpts['collectionName'])) {
            throw new InvalidArgumentException("Scope and Collection options cannot be set when using the Query Index Manager at the collection level");
        }
    }
}
