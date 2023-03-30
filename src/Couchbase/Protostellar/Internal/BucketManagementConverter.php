<?php

/*
 * Copyright 2022-Present Couchbase, Inc.
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

namespace Couchbase\Protostellar\Internal;

use Couchbase\Exception\DecodingFailureException;
use Couchbase\Exception\InvalidArgumentException;
use Couchbase\Management\EvictionPolicy;
use Couchbase\Management\StorageBackend;
use Couchbase\Protostellar\Generated\Admin\Bucket\V1\BucketType;
use Couchbase\Protostellar\Generated\Admin\Bucket\V1\CompressionMode;
use Couchbase\Protostellar\Generated\Admin\Bucket\V1\EvictionMode;
use Couchbase\Protostellar\Generated\Admin\Bucket\V1\ListBucketsResponse\Bucket;
use Couchbase\Protostellar\Generated\KV\V1\DurabilityLevel;

class BucketManagementConverter
{
    /**
     * @throws InvalidArgumentException
     */
    public static function getCreateBucketRequest(array $exportedSettings): array
    {
        return self::getCommonBucketRequest($exportedSettings);
    }

    public static function getUpdateBucketRequest(array $exportedSettings): array
    {
        return self::getCommonBucketRequest($exportedSettings);
    }

    /**
     * @throws InvalidArgumentException
     */
    private static function getCommonBucketRequest(array $exportedSettings): array
    {
        $request = [
            'bucket_name' => $exportedSettings['name'],
        ];
        if (isset($exportedSettings['bucketType'])) {
            $request['bucket_type'] = self::convertBucketType($exportedSettings['bucketType']);
        }
        if (isset($exportedSettings['ramQuotaMB'])) {
            $request['ram_quota_bytes'] = $exportedSettings['ramQuotaMB'] * 1e6;
        }
        if (isset($exportedSettings['numReplicas'])) {
            $request['num_replicas'] = $exportedSettings['numReplicas'];
        }
        if (isset($exportedSettings['flushEnabled'])) {
            $request['flush_enabled'] = $exportedSettings['flushEnabled'];
        }
        if (isset($exportedSettings['replicaIndexes'])) {
            $request['replica_indexes'] = $exportedSettings['replicaIndexes'];
        }
        if (isset($exportedSettings['evictionPolicy'])) {
            $request['eviction_mode'] = self::convertEvictionMode($exportedSettings['evictionPolicy']);
        }
        if (isset($exportedSettings['maxExpiry'])) {
            $request['max_expiry_secs'] = $exportedSettings['maxExpiry'];
        }
        if (isset($exportedSettings['compressionMode'])) {
            $request['compression_mode'] = self::convertCompressionMode($exportedSettings['compressionMode']);
        }
        if (isset($exportedSettings['minimumDurabilityLevel'])) {
            $request['minimum_durability_level'] = KVConverter::convertDurabilityLevel($exportedSettings['minimumDurabilityLevel']);
        }
        if (isset($exportedSettings['storageBackend'])) {
            $request['storage_backend'] = self::convertStorageBackend($exportedSettings['storageBackend']);
        }
        return $request;
    }

    /**
     * @throws DecodingFailureException
     */
    public static function convertBucketRequest(Bucket $bucket): array
    {
        return [
            "name" => $bucket->getBucketName(),
            "bucketType" => self::convertBucketTypeToCb($bucket->getBucketType()),
            "ramQuotaMB" => intval($bucket->getRamQuotaBytes() / 1e6),
            "numReplicas" => $bucket->getNumReplicas(),
            "replicaIndexes" => $bucket->getReplicaIndexes(),
            "evictionPolicy" => self::convertEvictionModeToCb($bucket->getEvictionMode()),
            "maxExpiry" => $bucket->getMaxExpirySecs(),
            "compressionMode" => self::convertCompressionModeToCb($bucket->getCompressionMode()),
            "minimumDurabilityLevel" => self::convertDurabilityLevelToCb($bucket->getMinimumDurabilityLevel()),
            "storageBackend" => self::convertStorageBackendToCb($bucket->getStorageBackend())
        ];
    }

    /**
     * @throws InvalidArgumentException
     */
    private static function convertBucketType(string $bucketType): int
    {
        switch ($bucketType) {
            case \Couchbase\Management\BucketType::COUCHBASE:
                return BucketType::BUCKET_TYPE_COUCHBASE;
            case \Couchbase\Management\BucketType::EPHEMERAL:
                return BucketType::BUCKET_TYPE_EPHEMERAL;
            case \Couchbase\Management\BucketType::MEMCACHED:
                return BucketType::BUCKET_TYPE_MEMCACHED;
            default:
                throw new InvalidArgumentException("Unknown bucket type specified");
        }
    }

    private static function convertBucketTypeToCb(int $bucketType): string
    {
        switch ($bucketType) {
            case BucketType::BUCKET_TYPE_COUCHBASE:
                return \Couchbase\Management\BucketType::COUCHBASE;
            case BucketType::BUCKET_TYPE_EPHEMERAL:
                return \Couchbase\Management\BucketType::EPHEMERAL;
            case BucketType::BUCKET_TYPE_MEMCACHED:
                return \Couchbase\Management\BucketType::MEMCACHED;
            default:
                throw new DecodingFailureException("Unknown bucket type received from GRPC");
        }
    }

    /**
     * @throws InvalidArgumentException
     */
    private static function convertEvictionMode(string $evictionPolicy): int
    {
        switch ($evictionPolicy) {
            case EvictionPolicy::FULL:
                return EvictionMode::EVICTION_MODE_FULL;
            case EvictionPolicy::VALUE_ONLY:
                return EvictionMode::EVICTION_MODE_VALUE_ONLY;
            case EvictionPolicy::NO_EVICTION:
                return EvictionMode::EVICTION_MODE_NONE;
            case EvictionPolicy::NOT_RECENTLY_USED:
                return EvictionMode::EVICTION_MODE_NOT_RECENTLY_USED;
            default:
                throw new InvalidArgumentException("Unknown eviction policy specified");
        }
    }

    /**
     * @throws DecodingFailureException
     */
    private static function convertEvictionModeToCb(int $evictionMode): string
    {
        switch ($evictionMode) {
            case EvictionMode::EVICTION_MODE_FULL:
                return EvictionPolicy::FULL;
            case EvictionMode::EVICTION_MODE_VALUE_ONLY:
                return EvictionPolicy::VALUE_ONLY;
            case EvictionMode::EVICTION_MODE_NONE:
                return EvictionPolicy::NO_EVICTION;
            case EvictionMode::EVICTION_MODE_NOT_RECENTLY_USED:
                return EvictionPolicy::NOT_RECENTLY_USED;
            default:
                throw new DecodingFailureException("Unrecognised eviction policy received from GRPC");
        }
    }

    /**
     * @throws InvalidArgumentException
     */
    private static function convertCompressionMode(string $compressionMode): int
    {
        switch ($compressionMode) {
            case \Couchbase\Management\CompressionMode::OFF:
                return CompressionMode::COMPRESSION_MODE_OFF;
            case \Couchbase\Management\CompressionMode::ACTIVE:
                return CompressionMode::COMPRESSION_MODE_ACTIVE;
            case \Couchbase\Management\CompressionMode::PASSIVE:
                return CompressionMode::COMPRESSION_MODE_PASSIVE;
            default:
                throw new InvalidArgumentException("Unknown compression mode specified");
        }
    }

    /**
     * @throws DecodingFailureException
     */
    private static function convertCompressionModeToCb(int $compressionMode): string
    {
        switch ($compressionMode) {
            case CompressionMode::COMPRESSION_MODE_OFF:
                return \Couchbase\Management\CompressionMode::OFF;
            case CompressionMode::COMPRESSION_MODE_ACTIVE:
                return \Couchbase\Management\CompressionMode::ACTIVE;
            case CompressionMode::COMPRESSION_MODE_PASSIVE:
                return \Couchbase\Management\CompressionMode::PASSIVE;
            default:
                throw new DecodingFailureException("Unknown compression mode received from GRPC");
        }
    }

    /**
     * @throws InvalidArgumentException
     */
    private static function convertStorageBackend(string $storageBackend): int
    {
        switch ($storageBackend) {
            case StorageBackend::COUCHSTORE:
                return \Couchbase\Protostellar\Generated\Admin\Bucket\V1\StorageBackend::STORAGE_BACKEND_COUCHSTORE;
            case StorageBackend::MAGMA:
                return \Couchbase\Protostellar\Generated\Admin\Bucket\V1\StorageBackend::STORAGE_BACKEND_MAGMA;
            default:
                throw new InvalidArgumentException("Unknown storage backend specified");
        }
    }

    /**
     * @throws DecodingFailureException
     */
    private static function convertStorageBackendToCb(int $storageBackend): string
    {
        switch ($storageBackend) {
            case StorageBackend::COUCHSTORE:
                return StorageBackend::COUCHSTORE;
            case StorageBackend::MAGMA:
                return StorageBackend::MAGMA;
            default:
                throw new DecodingFailureException("Unknown storage backend received from GRPC");
        }
    }

    /**
     * @throws DecodingFailureException
     */
    private static function convertDurabilityLevelToCb(int $durabilityLevel): string
    {
        switch ($durabilityLevel) {
            case DurabilityLevel::DURABILITY_LEVEL_MAJORITY:
                return \Couchbase\DurabilityLevel::MAJORITY;
            case DurabilityLevel::DURABILITY_LEVEL_MAJORITY_AND_PERSIST_TO_ACTIVE:
                return \Couchbase\DurabilityLevel::MAJORITY_AND_PERSIST_TO_ACTIVE;
            case DurabilityLevel::DURABILITY_LEVEL_PERSIST_TO_MAJORITY:
                return \Couchbase\DurabilityLevel::PERSIST_TO_MAJORITY;
            default:
                throw new DecodingFailureException("Unknown durability level received from GRPC");
        }
    }
}
