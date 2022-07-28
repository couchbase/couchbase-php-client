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

use Couchbase\Utilities\Deprecations;

class TransactionsConfiguration
{
    private ?string $durabilityLevel = null;
    private ?int $keyValueTimeoutMilliseconds = null;
    private ?int $timeoutMilliseconds = null;
    private ?TransactionsQueryConfiguration $queryOptions = null;
    private ?TransactionsCleanupConfiguration $cleanupOptions = null;
    private ?TransactionKeyspace $metadataCollection = null;

    /**
     * Specifies the level of synchronous durability level.
     *
     * @param string|int $level the durability level to enforce
     *
     * @return TransactionsConfiguration
     * @throws Exception\InvalidArgumentException
     * @see DurabilityLevel
     * @since 4.0.0
     */
    public function durabilityLevel($level): TransactionsConfiguration
    {
        if (gettype($level) == "integer") {
            $level = Deprecations::convertDeprecatedDurabilityLevel(__METHOD__, $level);
        }
        $this->durabilityLevel = $level;
        return $this;
    }

    /**
     * Specifies the default timeout for KV operations, specified in milliseconds.
     *
     * @param int $milliseconds
     *
     * @return TransactionsConfiguration
     * @since 4.0.0
     */
    public function keyValueTimeout(int $milliseconds): TransactionsConfiguration
    {
        $this->keyValueTimeoutMilliseconds = $milliseconds;
        return $this;
    }

    /**
     * Specifies the default timeout for transactions.
     *
     * @param int $milliseconds
     *
     * @return TransactionsConfiguration
     * @since 4.0.0
     */
    public function timeout(int $milliseconds): TransactionsConfiguration
    {
        $this->timeoutMilliseconds = $milliseconds;
        return $this;
    }

    /**
     * Specifies the configuration for queries.
     *
     * @param TransactionsQueryConfiguration $options
     *
     * @return TransactionsConfiguration
     * @since 4.0.0
     */
    public function queryOptions(TransactionsQueryConfiguration $options): TransactionsConfiguration
    {
        $this->queryOptions = $options;
        return $this;
    }

    /**
     * Specifies the configuration for the cleanup system.
     *
     * @param TransactionsCleanupConfiguration $options
     *
     * @return TransactionsConfiguration
     * @since 4.0.0
     */
    public function cleanupOptions(TransactionsCleanupConfiguration $options): TransactionsConfiguration
    {
        $this->cleanupOptions = $options;
        return $this;
    }


    /**
     * Specifies the collection to use for any metadata (ATR and client-record) access.
     *
     * @param TransactionKeyspace $collection
     *
     * @return TransactionsConfiguration
     * @since 4.0.1
     */
    public function metadataCollection(TransactionKeyspace $collection): TransactionsConfiguration
    {
        $this->metadataCollection = $collection;
        return $this;
    }

    /**
     * @param TransactionsConfiguration|null $configuration
     *
     * @return array
     * @internal
     * @since 4.0.0
     */
    public static function export(?TransactionsConfiguration $configuration): array
    {
        if ($configuration == null) {
            return [];
        }
        return [
            'durabilityLevel' => $configuration->durabilityLevel,
            'keyValueTimeout' => $configuration->keyValueTimeoutMilliseconds,
            'timeout' => $configuration->timeoutMilliseconds,
            'queryOptions' => $configuration->queryOptions == null ? null : $configuration->queryOptions->export(),
            'cleanupOptions' => $configuration->cleanupOptions == null ? null : $configuration->cleanupOptions->export(),
            'metadataCollection' => TransactionKeyspace::export($configuration->metadataCollection),
        ];
    }
}
