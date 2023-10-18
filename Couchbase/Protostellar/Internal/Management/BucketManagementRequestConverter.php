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


declare(strict_types=1);

namespace Couchbase\Protostellar\Internal\Management;

use Couchbase\Exception\InvalidArgumentException;
use Couchbase\Management\EvictionPolicy;
use Couchbase\Management\StorageBackend;
use Couchbase\Protostellar\Generated\Admin\Bucket\V1\BucketType;
use Couchbase\Protostellar\Generated\Admin\Bucket\V1\CompressionMode;
use Couchbase\Protostellar\Generated\Admin\Bucket\V1\EvictionMode;
use Couchbase\Protostellar\Internal\KVConverter;

class BucketManagementRequestConverter
{
    /**
     * @throws InvalidArgumentException
     */
    public static function getCreateBucketRequest(array $exportedSettings): array
    {
        return self::getCommonBucketRequest($exportedSettings);
    }

    /**
     * @throws InvalidArgumentException
     */
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
            $request['ram_quota_mb'] = $exportedSettings['ramQuotaMB'];
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
}
