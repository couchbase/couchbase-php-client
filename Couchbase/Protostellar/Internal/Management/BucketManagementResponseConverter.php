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

use Couchbase\Exception\DecodingFailureException;
use Couchbase\Management\EvictionPolicy;
use Couchbase\Protostellar\Generated\Admin\Bucket\V1\ConflictResolutionType;
use Couchbase\Protostellar\Generated\Admin\Bucket\V1\StorageBackend;
use Couchbase\Protostellar\Generated\Admin\Bucket\V1\BucketType;
use Couchbase\Protostellar\Generated\Admin\Bucket\V1\CompressionMode;
use Couchbase\Protostellar\Generated\Admin\Bucket\V1\EvictionMode;
use Couchbase\Protostellar\Generated\Admin\Bucket\V1\ListBucketsResponse\Bucket;
use Couchbase\Protostellar\Internal\SharedUtils;

class BucketManagementResponseConverter
{
    /**
     * @throws DecodingFailureException
     */
    public static function convertGetBucketResponse(Bucket $bucket): array
    {
        $convertedBucket = [
            "name" => $bucket->getBucketName(),
            "flushEnabled" => $bucket->getFlushEnabled(),
            "bucketType" => self::convertBucketType($bucket->getBucketType()),
            "ramQuotaMB" => intval($bucket->getRamQuotaMb()),
            "numReplicas" => $bucket->getNumReplicas(),
            "replicaIndexes" => $bucket->getReplicaIndexes(),
            "evictionPolicy" => self::convertEvictionMode($bucket->getEvictionMode()),
            "maxExpiry" => $bucket->getMaxExpirySecs(),
            "compressionMode" => self::convertCompressionMode($bucket->getCompressionMode()),
            "conflictResolutionType" => self::convertConflictResolutionType($bucket->getConflictResolutionType())
        ];
        if ($bucket->hasMinimumDurabilityLevel()) {
            $convertedBucket["minimumDurabilityLevel"] = SharedUtils::convertDurabilityLevelToCB($bucket->getMinimumDurabilityLevel());
        } else {
            $convertedBucket["minimumDurabilityLevel"] = \Couchbase\DurabilityLevel::NONE;
        }
        if ($bucket->hasStorageBackend()) {
            $convertedBucket["storageBackend"] = self::convertStorageBackend($bucket->getStorageBackend());
        }
        return $convertedBucket;
    }

    /**
     * @throws DecodingFailureException
     */
    private static function convertBucketType(int $bucketType): string
    {
        switch ($bucketType) {
            case BucketType::BUCKET_TYPE_COUCHBASE:
                return \Couchbase\Management\BucketType::COUCHBASE;
            case BucketType::BUCKET_TYPE_EPHEMERAL:
                return \Couchbase\Management\BucketType::EPHEMERAL;
            default:
                throw new DecodingFailureException("Unknown bucket type received from GRPC");
        }
    }

    /**
     * @throws DecodingFailureException
     */
    private static function convertEvictionMode(int $evictionMode): string
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
     * @throws DecodingFailureException
     */
    private static function convertCompressionMode(int $compressionMode): string
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
     * @throws DecodingFailureException
     */
    private static function convertStorageBackend(int $storageBackend): string
    {

        switch ($storageBackend) {
            case StorageBackend::STORAGE_BACKEND_COUCHSTORE:
                return \Couchbase\Management\StorageBackend::COUCHSTORE;
            case StorageBackend::STORAGE_BACKEND_MAGMA:
                return \Couchbase\Management\StorageBackend::MAGMA;
            default:
                throw new DecodingFailureException("Unknown storage backend received from GRPC");
        }
    }

    /**
     * @throws DecodingFailureException
     */
    private static function convertConflictResolutionType(int $conflictResolution): string
    {
        switch ($conflictResolution) {
            case ConflictResolutionType::CONFLICT_RESOLUTION_TYPE_CUSTOM:
                return \Couchbase\Management\ConflictResolutionType::CUSTOM;
            case ConflictResolutionType::CONFLICT_RESOLUTION_TYPE_TIMESTAMP:
                return \Couchbase\Management\ConflictResolutionType::TIMESTAMP;
            case ConflictResolutionType::CONFLICT_RESOLUTION_TYPE_SEQUENCE_NUMBER:
                return \Couchbase\Management\ConflictResolutionType::SEQUENCE_NUMBER;
            default:
                throw new DecodingFailureException("Unknown conflict resolution type received from GRPC");
        }
    }
}
