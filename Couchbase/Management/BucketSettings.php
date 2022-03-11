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

class BucketSettings
{
    public function name(): string
    {
    }

    public function flushEnabled(): bool
    {
    }

    public function ramQuotaMb(): int
    {
    }

    public function numReplicas(): int
    {
    }

    public function replicaIndexes(): bool
    {
    }

    public function bucketType(): string
    {
    }

    public function evictionPolicy(): string
    {
    }

    public function storageBackend(): string
    {
    }

    public function maxTtl(): int
    {
    }

    public function compressionMode(): string
    {
    }

    public function setName(string $name): BucketSettings
    {
    }

    public function enableFlush(bool $enable): BucketSettings
    {
    }

    public function setRamQuotaMb(int $sizeInMb): BucketSettings
    {
    }

    public function setNumReplicas(int $numReplicas): BucketSettings
    {
    }

    public function enableReplicaIndexes(bool $enable): BucketSettings
    {
    }

    public function setBucketType(string $type): BucketSettings
    {
    }

    /**
     * Configures eviction policy for the bucket.
     *
     * @param string $policy eviction policy. Use constants FULL, VALUE_ONLY,
     *   NO_EVICTION, NOT_RECENTLY_USED.
     *
     * @see \EvictionPolicy::FULL
     * @see \EvictionPolicy::VALUE_ONLY
     * @see \EvictionPolicy::NO_EVICTION
     * @see \EvictionPolicy::NOT_RECENTLY_USED
     */
    public function setEvictionPolicy(string $policy): BucketSettings
    {
    }

    /**
     * Configures storage backend for the bucket.
     *
     * @param string $policy storage backend. Use constants COUCHSTORE, MAGMA.
     *
     * @see \StorageBackend::COUCHSTORE
     * @see \StorageBackend::MAGMA
     */
    public function setStorageBackend(string $policy): BucketSettings
    {
    }

    public function setMaxTtl(int $ttlSeconds): BucketSettings
    {
    }

    public function setCompressionMode(string $mode): BucketSettings
    {
    }

    /**
     * Retrieves minimal durability level configured for the bucket
     *
     * @see \DurabilityLevel::NONE
     * @see \DurabilityLevel::MAJORITY
     * @see \DurabilityLevel::MAJORITY_AND_PERSIST_TO_ACTIVE
     * @see \DurabilityLevel::PERSIST_TO_MAJORITY
     */
    public function minimalDurabilityLevel(): int
    {
    }

    /**
     * Configures minimal durability level for the bucket
     *
     * @param int $durabilityLevel durability level.
     *
     * @see \DurabilityLevel::NONE
     * @see \DurabilityLevel::MAJORITY
     * @see \DurabilityLevel::MAJORITY_AND_PERSIST_TO_ACTIVE
     * @see \DurabilityLevel::PERSIST_TO_MAJORITY
     */
    public function setMinimalDurabilityLevel(int $durabilityLevel): BucketSettings
    {
    }
}
