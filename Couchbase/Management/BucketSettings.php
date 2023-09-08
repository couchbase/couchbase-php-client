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
    private string $name;
    private ?bool $flushEnabled = null;
    private ?int $ramQuotaMb = null;
    private ?int $numReplicas = null;
    private ?bool $replicaIndexes = null;
    private ?string $bucketType = null;
    private ?string $evictionPolicy = null;
    private ?string $storageBackend = null;
    private ?int $maxExpiry = null;
    private ?string $compressionMode = null;
    private ?string $minimumDurabilityLevel = null;
    private ?string $conflictResolutionType = null;
    private ?bool $historyRetentionCollectionDefault = null;
    private ?int $historyRetentionBytes = null;
    private ?int $historyRetentionDuration = null;

    /**
     * @param string $name the name of the bucket
     *
     * @since 4.0.0
     */
    public function __construct(string $name)
    {
        $this->name = $name;
    }

    /**
     * @param string $name the name of the bucket
     *
     * @return BucketSettings
     * @since 4.0.0
     */
    public static function build(string $name): BucketSettings
    {
        return new BucketSettings($name);
    }

    /**
     * Get the name of the bucket.
     *
     * @return string
     * @since 4.0.0
     */
    public function name(): string
    {
        return $this->name;
    }

    /**
     * Whether flush is enabled for the bucket.
     *
     * @return bool|null
     * @since 4.0.0
     */
    public function flushEnabled(): ?bool
    {
        return $this->flushEnabled;
    }

    /**
     * Get the ram quota of the bucket.
     *
     * @return int|null
     * @since 4.0.0
     */
    public function ramQuotaMb(): ?int
    {
        return $this->ramQuotaMb;
    }

    /**
     * Get the number of replicas for the bucket.
     *
     * @return int|null
     * @since 4.0.0
     */
    public function numReplicas(): ?int
    {
        return $this->numReplicas;
    }

    /**
     * Whether replicas are enabled for the bucket.
     *
     * @return bool|null
     * @since 4.0.0
     */
    public function replicaIndexes(): ?bool
    {
        return $this->replicaIndexes;
    }

    /**
     * Get the type of bucket that the bucket is.
     *
     * @return string|null
     *
     * @see \BucketType::COUCHBASE
     * @see \BucketType::MEMCACHED
     * @see \BucketType::EPHEMERAL
     * @since 4.0.0
     */
    public function bucketType(): ?string
    {
        return $this->bucketType;
    }

    /**
     * Get the eviction policy used by the bucket.
     *
     * @return string|null
     *
     * @see \EvictionPolicy::FULL
     * @see \EvictionPolicy::VALUE_ONLY
     * @see \EvictionPolicy::NO_EVICTION
     * @see \EvictionPolicy::NOT_RECENTLY_USED
     * @since 4.0.0
     */
    public function evictionPolicy(): ?string
    {
        return $this->evictionPolicy;
    }

    /**
     * Get the storage backend used by the bucket.
     *
     * @return string|null
     *
     * @see \StorageBackend::COUCHSTORE
     * @see \StorageBackend::MAGMA
     * @since 4.0.0
     */
    public function storageBackend(): ?string
    {
        return $this->storageBackend;
    }

    /**
     * Get the maximum expiry to set on documents created within the bucket.
     *
     * @return int|null
     * @deprecated see maxExpiry
     * @since 4.0.0
     */
    public function maxTtl(): ?int
    {
        return $this->maxExpiry();
    }

    /**
     * Get the maximum expiry to set on documents created within the bucket.
     *
     * @return int|null
     * @since 4.0.0
     */
    public function maxExpiry(): ?int
    {
        return $this->maxExpiry;
    }

    /**
     * Get the compression mode used by the bucket.
     *
     * @return string|null
     *
     * @see \CompressionMode::OFF
     * @see \CompressionMode::ACTIVE
     * @see \CompressionMode::PASSIVE
     * @since 4.0.0
     */
    public function compressionMode(): ?string
    {
        return $this->compressionMode;
    }

    /**
     * Get the minimum durability level used by the bucket when modifying documents.
     *
     * @return string|null
     *
     * @see \DurabilityLevel::NONE
     * @see \DurabilityLevel::MAJORITY
     * @see \DurabilityLevel::MAJORITY_AND_PERSIST_TO_ACTIVE
     * @see \DurabilityLevel::PERSIST_TO_MAJORITY
     * @since 4.0.0
     */
    public function minimumDurabilityLevel(): ?string
    {
        return $this->minimumDurabilityLevel;
    }

    /**
     * Get the conflict resolution type used by the bucket.
     *
     * @return string|null
     *
     * @see \ConflictResolutionType::TIMESTAMP
     * @see \ConflictResolutionType::SEQUENCE_NUMBER
     * @see \ConflictResolutionType::CUSTOM
     * @since 4.0.0
     */
    public function conflictResolutionType(): ?string
    {
        return $this->conflictResolutionType;
    }

    /**
     * Get the default history retention on all collections in this bucket
     *
     * @return bool|null
     * @since 4.1.6
     */
    public function historyRetentionCollectionDefault(): ?bool
    {
        return $this->historyRetentionCollectionDefault;
    }

    /**
     * Get the maximum history retention in bytes on all collections in this bucket
     *
     * @return int|null
     * @since 4.1.6
     */
    public function historyRetentionBytes(): ?int
    {
        return $this->historyRetentionBytes;
    }

    /**
     * Get the maximum duration in seconds to be covered by the change history that is written to disk for all collections in this bucket
     * @return int|null
     * @since 4.1.6
     */
    public function historyRetentionDuration(): ?int
    {
        return $this->historyRetentionDuration;
    }

    /**
     * Sets the name of the bucket.
     *
     * @param string $name the name of the bucket
     *
     * @return BucketSettings
     * @deprecated use constructor argument instead
     *
     * @since 4.0.0
     */
    public function setName(string $name): BucketSettings
    {
        $this->name = $name;
        return $this;
    }

    /**
     * Sets whether flush is enabled.
     *
     * @param bool $enable whether flush is enabled
     *
     * @return BucketSettings
     * @since 4.0.0
     */
    public function enableFlush(bool $enable): BucketSettings
    {
        $this->flushEnabled = $enable;
        return $this;
    }

    /**
     * Sets the ram quota of the bucket.
     *
     * @param int $sizeInMb the ram quota in mb.
     *
     * @return BucketSettings
     * @since 4.0.0
     */
    public function setRamQuotaMb(int $sizeInMb): BucketSettings
    {
        $this->ramQuotaMb = $sizeInMb;
        return $this;
    }

    /**
     * Sets the number of replicas for the bucket.
     *
     * @param int $numReplicas the number of replicas
     *
     * @return BucketSettings
     * @since 4.0.0
     */
    public function setNumReplicas(int $numReplicas): BucketSettings
    {
        $this->numReplicas = $numReplicas;
        return $this;
    }

    /**
     * Sets whether replicas are enabled.
     *
     * @param bool $enable whether to enable replicas
     *
     * @return BucketSettings
     * @since 4.0.0
     */
    public function enableReplicaIndexes(bool $enable): BucketSettings
    {
        $this->replicaIndexes = $enable;
        return $this;
    }

    /**
     * Sets the type of the bucket.
     *
     * @param string $type the type of the bucket
     *
     * @return BucketSettings
     *
     * @see \BucketType::COUCHBASE
     * @see \BucketType::MEMCACHED
     * @see \BucketType::EPHEMERAL
     * @since 4.0.0
     */
    public function setBucketType(string $type): BucketSettings
    {
        $this->bucketType = $type;
        return $this;
    }

    /**
     * Configures eviction policy for the bucket.
     *
     * @param string $policy eviction policy. Use constants FULL, VALUE_ONLY,
     *   NO_EVICTION, NOT_RECENTLY_USED.
     *
     * @return BucketSettings
     *
     * @see \EvictionPolicy::FULL
     * @see \EvictionPolicy::VALUE_ONLY
     * @see \EvictionPolicy::NO_EVICTION
     * @see \EvictionPolicy::NOT_RECENTLY_USED
     * @since 4.0.0
     */
    public function setEvictionPolicy(string $policy): BucketSettings
    {
        $this->evictionPolicy = $policy;
        return $this;
    }

    /**
     * Configures storage backend for the bucket.
     *
     * @param string $backend storage backend. Use constants COUCHSTORE, MAGMA.
     *
     * @return BucketSettings
     *
     * @see \StorageBackend::COUCHSTORE
     * @see \StorageBackend::MAGMA
     */
    public function setStorageBackend(string $backend): BucketSettings
    {
        $this->storageBackend = $backend;
        return $this;
    }

    /**
     * Sets the default max expiry time for documents in the bucket.
     *
     * @param int $expirySeconds the default expiry time.
     *
     * @return BucketSettings
     * @deprecated
     * @see setMaxExpiry
     * @since 4.0.0
     */
    public function setMaxTtl(int $expirySeconds): BucketSettings
    {
        return $this->setMaxExpiry($expirySeconds);
    }

    /**
     * Sets the default max expiry time for documents in the bucket.
     *
     * @param int $expirySeconds the default expiry time.
     *
     * @return BucketSettings
     * @since 4.0.0
     */
    public function setMaxExpiry(int $expirySeconds): BucketSettings
    {
        $this->maxExpiry = $expirySeconds;
        return $this;
    }

    /**
     * Configures compression mode for the bucket.
     *
     * @param string $mode
     *
     * @return BucketSettings
     *
     * @see \CompressionMode::OFF
     * @see \CompressionMode::ACTIVE
     * @see \CompressionMode::PASSIVE
     * @since 4.0.0
     */
    public function setCompressionMode(string $mode): BucketSettings
    {
        $this->compressionMode = $mode;
        return $this;
    }

    /**
     * Retrieves minimal durability level configured for the bucket
     *
     * @return string|null
     *
     * @see \DurabilityLevel::NONE
     * @see \DurabilityLevel::MAJORITY
     * @see \DurabilityLevel::MAJORITY_AND_PERSIST_TO_ACTIVE
     * @see \DurabilityLevel::PERSIST_TO_MAJORITY
     *
     * @deprecated
     * @see minimumDurabilityLevel
     * @since 4.0.0
     */
    public function minimalDurabilityLevel(): ?string
    {
        return $this->minimumDurabilityLevel();
    }

    /**
     * Configures minimal durability level for the bucket.
     *
     * @param int|string $durabilityLevel durability level.
     *
     * @return BucketSettings
     *
     * @see \DurabilityLevel::NONE
     * @see \DurabilityLevel::MAJORITY
     * @see \DurabilityLevel::MAJORITY_AND_PERSIST_TO_ACTIVE
     * @see \DurabilityLevel::PERSIST_TO_MAJORITY
     *
     * @deprecated
     * @see setMinimumDurabilityLevel
     * @since 4.0.0
     */
    public function setMinimalDurabilityLevel($durabilityLevel): BucketSettings
    {
        return $this->setMinimumDurabilityLevel($durabilityLevel);
    }

    /**
     * Configures minimum durability level for the bucket.
     *
     * @param int|string $durabilityLevel durability level.
     *
     * @return BucketSettings
     *
     * @see \DurabilityLevel::NONE
     * @see \DurabilityLevel::MAJORITY
     * @see \DurabilityLevel::MAJORITY_AND_PERSIST_TO_ACTIVE
     * @see \DurabilityLevel::PERSIST_TO_MAJORITY
     * @since 4.0.0
     */
    public function setMinimumDurabilityLevel($durabilityLevel): BucketSettings
    {
        $this->minimumDurabilityLevel = $durabilityLevel;
        return $this;
    }

    /**
     * Set the conflict resolution type used by the bucket.
     *
     * @param string $resolutionType the conflict resolution type.
     *
     * @return BucketSettings
     *
     * @see \ConflictResolutionType::TIMESTAMP
     * @see \ConflictResolutionType::SEQUENCE_NUMBER
     * @see \ConflictResolutionType::CUSTOM
     * @since 4.0.0
     */
    public function setConflictResolutionType(string $resolutionType): BucketSettings
    {
        $this->conflictResolutionType = $resolutionType;
        return $this;
    }

    /**
     * Sets whether to enable history on collections by default
     *
     * @param bool $historyRetentionCollectionDefault
     *
     * @return BucketSettings
     *
     * @since 4.1.6
     */
    public function enableHistoryRetentionCollectionDefault(bool $historyRetentionCollectionDefault): BucketSettings
    {
        $this->historyRetentionCollectionDefault = $historyRetentionCollectionDefault;
        return $this;
    }

    /**
     * Sets the maximum size, in bytes, o the change history that is written to disk for all collections in this bucket
     *
     * @param int $historyRetentionBytes
     *
     * @return BucketSettings
     *
     * @since 4.1.6
     */
    public function setHistoryRetentionBytes(int $historyRetentionBytes): BucketSettings
    {
        $this->historyRetentionBytes = $historyRetentionBytes;
        return $this;
    }

    /**
     * Sets teh maximum number of seconds to be covered by the change history that is written to disk for all collections
     * in this bucket
     *
     * @param int $historyRetentionDuration duration in seconds
     *
     * @return BucketSettings
     *
     * @since 4.1.6
     */
    public function setHistoryRetentionDuration(int $historyRetentionDuration): BucketSettings
    {
        $this->historyRetentionDuration = $historyRetentionDuration;
        return $this;
    }

    /**
     * @internal
     * @since 4.0.0
     */
    public static function export(BucketSettings $bucket): array
    {
        return [
            'name' => $bucket->name,
            'flushEnabled' => $bucket->flushEnabled,
            'ramQuotaMB' => $bucket->ramQuotaMb,
            'numReplicas' => $bucket->numReplicas,
            'replicaIndexes' => $bucket->replicaIndexes,
            'bucketType' => $bucket->bucketType,
            'evictionPolicy' => $bucket->evictionPolicy,
            'storageBackend' => $bucket->storageBackend,
            'maxExpiry' => $bucket->maxExpiry,
            'compressionMode' => $bucket->compressionMode,
            'minimumDurabilityLevel' => $bucket->minimumDurabilityLevel,
            'conflictResolutionType' => $bucket->conflictResolutionType,
            'historyRetentionCollectionDefault' => $bucket->historyRetentionCollectionDefault,
            'historyRetentionBytes' => $bucket->historyRetentionBytes,
            'historyRetentionDuration' => $bucket->historyRetentionDuration,
        ];
    }

    /**
     * @internal
     * @since 4.0.0
     */
    public static function import(array $bucket): BucketSettings
    {
        $settings = new BucketSettings($bucket['name']);
        if (array_key_exists('bucketType', $bucket)) {
            $settings->setBucketType($bucket['bucketType']);
        }
        if (array_key_exists('ramQuotaMB', $bucket)) {
            $settings->setRamQuotaMb($bucket['ramQuotaMB']);
        }
        if (array_key_exists('maxExpiry', $bucket)) {
            $settings->setMaxExpiry($bucket['maxExpiry']);
        }
        if (array_key_exists('compressionMode', $bucket)) {
            $settings->setCompressionMode($bucket['compressionMode']);
        }
        if (array_key_exists('minimumDurabilityLevel', $bucket)) {
            $settings->setMinimumDurabilityLevel($bucket['minimumDurabilityLevel']);
        }
        if (array_key_exists('numReplicas', $bucket)) {
            $settings->setNumReplicas($bucket['numReplicas']);
        }
        if (array_key_exists('replicaIndexes', $bucket)) {
            $settings->enableReplicaIndexes($bucket['replicaIndexes']);
        }
        if (array_key_exists('flushEnabled', $bucket)) {
            $settings->enableFlush($bucket['flushEnabled']);
        }
        if (array_key_exists('evictionPolicy', $bucket)) {
            $settings->setEvictionPolicy($bucket['evictionPolicy']);
        }
        if (array_key_exists('conflictResolutionType', $bucket)) {
            $settings->setConflictResolutionType($bucket['conflictResolutionType']);
        }
        if (array_key_exists('storageBackend', $bucket)) {
            $settings->setStorageBackend($bucket['storageBackend']);
        }
        if (array_key_exists('historyRetentionCollectionDefault', $bucket)) {
            $settings->enableHistoryRetentionCollectionDefault($bucket['historyRetentionCollectionDefault']);
        }
        if (array_key_exists('historyRetentionBytes', $bucket)) {
            $settings->setHistoryRetentionBytes($bucket['historyRetentionBytes']);
        }
        if (array_key_exists('historyRetentionDuration', $bucket)) {
            $settings->setHistoryRetentionDuration($bucket['historyRetentionDuration']);
        }

        return $settings;
    }
}
