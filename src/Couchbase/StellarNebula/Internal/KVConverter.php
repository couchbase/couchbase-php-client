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

namespace Couchbase\StellarNebula\Internal;

use Couchbase\DurabilityLevel;
use Couchbase\Exception\InvalidArgumentException;
use Couchbase\StellarNebula\Generated\KV\V1\DocumentContentType;
use Couchbase\TranscoderFlags;
use Google\Protobuf\Timestamp;

class KVConverter
{
    /**
     * @param $exportedOptions
     * @return array
     * @internal
     */
    public static function convertUpsertOptions($exportedOptions): array
    {
        $options = [];
        if (isset($exportedOptions["expirySeconds"])) {
            $options["expiry"] = new Timestamp(["seconds" => $exportedOptions["expirySeconds"]]);
        }
        if (isset($exportedOptions["durabilityLevel"])) {
            $options["durability_level"] = self::convertDurabilityLevel($exportedOptions["durabilityLevel"]);
        }
        return $options;
    }

    public static function convertInsertOptions($exportedOptions): array
    {
        $options = [];
        if (isset($exportedOptions["expirySeconds"])) {
            $options["expiry"] = new Timestamp(["seconds" => $exportedOptions["expirySeconds"]]);
        }
        if (isset($exportedOptions["durabilityLevel"])) {
            $options["durability_level"] = self::convertDurabilityLevel($exportedOptions["durabilityLevel"]);
        }
        return $options;
    }

    public static function convertReplaceOptions($exportedOptions): array
    {
        $options = [];
        if (isset($exportedOptions["expirySeconds"])) {
            $options["expiry"] = new Timestamp(["seconds" => $exportedOptions["expirySeconds"]]);
        }
        if (isset($exportedOptions["cas"])) {
            $options["cas"] = $exportedOptions["cas"];
        }
        if (isset($exportedOptions["durabilityLevel"])) {
            $options["durability_level"] = self::convertDurabilityLevel($exportedOptions["durabilityLevel"]);
        }
        return $options;
    }

    public static function convertRemoveOptions($exportedOptions): array
    {
        $options = [];
        if (isset($exportedOptions["cas"])) {
            $options["cas"] = $exportedOptions["cas"];
        }
        if (isset($exportedOptions["durabilityLevel"])) {
            $options["durability_level"] = self::convertDurabilityLevel($exportedOptions["durabilityLevel"]);
        }
        return $options;
    }

    public static function convertAppendOptions($exportedOptions): array
    {
        $options = [];
        if (isset($exportedOptions["cas"])) { //TODO: AppendOptions do not include cas
            $options["cas"] = $exportedOptions["cas"];
        }
        if (isset($exportedOptions["durabilityLevel"])) {
            $options["durability_level"] = self::convertDurabilityLevel($exportedOptions["durabilityLevel"]);
        }
        return $options;
    }

    public static function convertPrependOptions($exportedOptions): array
    {
        $options = [];
        if (isset($exportedOptions["cas"])) { //TODO PrependOptions do not include cas
            $options["cas"] = $exportedOptions["cas"];
        }
        if (isset($exportedOptions["durabilityLevel"])) {
            $options["durability_level"] = self::convertDurabilityLevel($exportedOptions["durabilityLevel"]);
        }
        return $options;
    }

    public static function convertIncrementOptions($exportedOptions): array
    {
        $options = [];
        $options["delta"] = $exportedOptions["delta"] ?? 1;
        if (isset($exportedOptions["initialValue"])) {
            $options["initial"] = $exportedOptions["initialValue"];
        }
        if (isset($exportedOptions["expirySeconds"])) { //TODO IncrementOptions do not include expirySeconds
            $options["expiry"] = new Timestamp(["seconds" => $exportedOptions["expirySeconds"]]);
        }
        if (isset($exportedOptions["durabilityLevel"])) {
            $options["durability_level"] = self::convertDurabilityLevel($exportedOptions["durabilityLevel"]);
        }
        return $options;
    }

    public static function convertDecrementOptions($exportedOptions): array
    {
        $options = [];
        $options["delta"] = $exportedOptions["delta"] ?? 1;
        if (isset($exportedOptions["initialValue"])) {
            $options["initial"] = $exportedOptions["initialValue"];
        }
        if (isset($exportedOptions["expirySeconds"])) { //TODO DecrementOptions do not include expirySeconds
            $options["expiry"] = new Timestamp(["seconds" => $exportedOptions["expirySeconds"]]);
        }
        if (isset($exportedOptions["durabilityLevel"])) {
            $options["durability_level"] = self::convertDurabilityLevel($exportedOptions["durabilityLevel"]);
        }
        return $options;
    }

    /**
     * @param int $classicFlags
     * @return int
     * @throws InvalidArgumentException
     * @internal
     */
    public static function convertTranscoderFlagsToGRPC(int $classicFlags): int
    {
        switch ($classicFlags) {
            case TranscoderFlags::DATA_FORMAT_BINARY:
                return DocumentContentType::BINARY;
            case TranscoderFlags::DATA_FORMAT_JSON:
                return DocumentContentType::JSON;
            default:
                throw new InvalidArgumentException("Unsupported transcoder content flag");
        }
    }

    /**
     * @param int $grpcFlags
     * @return int
     * @internal
     */
    public static function convertTranscoderFlagsToClassic(int $grpcFlags): int
    {
        switch ($grpcFlags) {
            case DocumentContentType::BINARY:
                return TranscoderFlags::DATA_FORMAT_BINARY;
            case DocumentContentType::JSON:
                return TranscoderFlags::DATA_FORMAT_JSON;
            default:
                throw new InvalidArgumentException("Unexpected GRPC content Flag");
        }
    }

    private static function convertDurabilityLevel(string $durabilityLevel): ?int
    {
        switch ($durabilityLevel) {
            case DurabilityLevel::MAJORITY:
                return \Couchbase\StellarNebula\Generated\KV\V1\DurabilityLevel::MAJORITY;
            case DurabilityLevel::MAJORITY_AND_PERSIST_TO_ACTIVE:
                return \Couchbase\StellarNebula\Generated\KV\V1\DurabilityLevel::MAJORITY_AND_PERSIST_TO_ACTIVE;
            case DurabilityLevel::PERSIST_TO_MAJORITY:
                return \Couchbase\StellarNebula\Generated\KV\V1\DurabilityLevel::PERSIST_TO_MAJORITY;
            default:
                return null;
        }
    }
}
