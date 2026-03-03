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

use Couchbase\Exception\UnambiguousTimeoutException;
use Couchbase\Extension;
use Couchbase\Observability\ObservabilityContext;
use Couchbase\Observability\ObservabilityConstants;

class QueryIndexManager implements QueryIndexManagerInterface
{
    /**
     * @var resource
     */
    private $core;

    private ObservabilityContext $observability;

    /**
     * @param $core
     * @param ObservabilityContext $observability
     *
     * @internal
     * @since 4.0.0
     */
    public function __construct($core, ObservabilityContext $observability)
    {
        $this->core = $core;
        $this->observability = ObservabilityContext::from(
            $observability,
            service: ObservabilityConstants::ATTR_VALUE_SERVICE_QUERY
        );
    }

    /**
     * Fetches all indexes from the server.
     *
     * @param string $bucketName
     * @param GetAllQueryIndexesOptions|null $options
     *
     * @return array
     * @since 4.0.0
     */
    public function getAllIndexes(string $bucketName, ?GetAllQueryIndexesOptions $options = null): array
    {
        return $this->observability->recordOperation(
            ObservabilityConstants::OP_QM_GET_ALL_INDEXES,
            GetAllQueryIndexesOptions::getParentSpan($options),
            function ($obsHandler) use ($bucketName, $options) {
                $obsHandler->addBucketName($bucketName);

                $function = COUCHBASE_EXTENSION_NAMESPACE . '\\queryIndexGetAll';
                $responses = $function($this->core, $bucketName, GetAllQueryIndexesOptions::export($options));
                return array_map(
                    function (array $response) {
                        return new QueryIndex($response);
                    },
                    $responses
                );
            }
        );
    }

    /**
     * Creates a new index
     *
     * @param string $bucketName
     * @param string $indexName
     * @param array $keys
     * @param CreateQueryIndexOptions|null $options
     *
     * @since 4.0.0
     */
    public function createIndex(string $bucketName, string $indexName, array $keys, ?CreateQueryIndexOptions $options = null)
    {
        $this->observability->recordOperation(
            ObservabilityConstants::OP_QM_CREATE_INDEX,
            CreateQueryIndexOptions::getParentSpan($options),
            function ($obsHandler) use ($bucketName, $indexName, $keys, $options) {
                $obsHandler->addBucketName($bucketName);

                $function = COUCHBASE_EXTENSION_NAMESPACE . '\\queryIndexCreate';
                $function($this->core, $bucketName, $indexName, $keys, CreateQueryIndexOptions::export($options));
            }
        );
    }

    /**
     * Creates a new primary index
     *
     * @param string $bucketName
     * @param CreateQueryPrimaryIndexOptions|null $options
     *
     * @since 4.0.0
     */
    public function createPrimaryIndex(string $bucketName, ?CreateQueryPrimaryIndexOptions $options = null)
    {
        $this->observability->recordOperation(
            ObservabilityConstants::OP_QM_CREATE_PRIMARY_INDEX,
            CreateQueryPrimaryIndexOptions::getParentSpan($options),
            function ($obsHandler) use ($bucketName, $options) {
                $obsHandler->addBucketName($bucketName);

                $function = COUCHBASE_EXTENSION_NAMESPACE . '\\queryIndexCreatePrimary';
                $function($this->core, $bucketName, CreateQueryPrimaryIndexOptions::export($options));
            }
        );
    }

    /**
     * Drops an index
     *
     * @param string $bucketName
     * @param string $indexName
     * @param DropQueryIndexOptions|null $options
     *
     * @since 4.0.0
     */
    public function dropIndex(string $bucketName, string $indexName, ?DropQueryIndexOptions $options = null)
    {
        $this->observability->recordOperation(
            ObservabilityConstants::OP_QM_DROP_INDEX,
            DropQueryIndexOptions::getParentSpan($options),
            function ($obsHandler) use ($bucketName, $indexName, $options) {
                $obsHandler->addBucketName($bucketName);

                $function = COUCHBASE_EXTENSION_NAMESPACE . '\\queryIndexDrop';
                $function($this->core, $bucketName, $indexName, DropQueryIndexOptions::export($options));
            }
        );
    }

    /**
     * Drops a primary index
     *
     * @param string $bucketName
     * @param DropQueryPrimaryIndexOptions|null $options
     *
     * @since 4.0.0
     */
    public function dropPrimaryIndex(string $bucketName, ?DropQueryPrimaryIndexOptions $options = null)
    {
        $this->observability->recordOperation(
            ObservabilityConstants::OP_QM_DROP_PRIMARY_INDEX,
            DropQueryPrimaryIndexOptions::getParentSpan($options),
            function ($obsHandler) use ($bucketName, $options) {
                $obsHandler->addBucketName($bucketName);

                $function = COUCHBASE_EXTENSION_NAMESPACE . '\\queryIndexDropPrimary';
                $function($this->core, $bucketName, DropQueryPrimaryIndexOptions::export($options));
            }
        );
    }

    /**
     * Build Deferred builds all indexes which are currently in deferred state.
     *
     * @param string $bucketName
     * @param BuildQueryIndexesOptions|null $options
     *
     * @since 4.0.0
     */
    public function buildDeferredIndexes(string $bucketName, ?BuildQueryIndexesOptions $options = null)
    {
        $this->observability->recordOperation(
            ObservabilityConstants::OP_QM_BUILD_DEFERRED_INDEXES,
            BuildQueryIndexesOptions::getParentSpan($options),
            function ($obsHandler) use ($bucketName, $options) {
                $obsHandler->addBucketName($bucketName);

                $function = COUCHBASE_EXTENSION_NAMESPACE . '\\queryIndexBuildDeferred';
                $function($this->core, $bucketName, BuildQueryIndexesOptions::export($options));
            }
        );
    }

    /**
     * Watch polls indexes until they are online.
     *
     * @param string $bucketName
     * @param array $indexNames
     * @param int $timeoutMilliseconds
     * @param WatchQueryIndexesOptions|null $options
     *
     * @throws UnambiguousTimeoutException
     * @since 4.0.0
     */
    public function watchIndexes(string $bucketName, array $indexNames, int $timeoutMilliseconds, ?WatchQueryIndexesOptions $options = null)
    {
        $this->observability->recordOperation(
            ObservabilityConstants::OP_QM_WATCH_INDEXES,
            WatchQueryIndexesOptions::getParentSpan($options),
            function ($obsHandler) use ($bucketName, $indexNames, $timeoutMilliseconds, $options) {
                $obsHandler->addBucketName($bucketName);

                $exported = WatchQueryIndexesOptions::export($options);
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
                    $getAllOptions = GetAllQueryIndexesOptions::build()
                        ->timeout($deadline - $now)
                        ->parentSpan($obsHandler->getOpSpan());
                    if (array_key_exists("scopeName", $exported) && $exported["scopeName"]) {
                        $getAllOptions->scopeName($exported["scopeName"]);
                    }
                    if (array_key_exists("collectionName", $exported) && $exported["collectionName"]) {
                        $getAllOptions->collectionName($exported["collectionName"]);
                    }
                    $foundIndexes = $this->getAllIndexes($bucketName, $getAllOptions);
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
        );
    }
}
