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

namespace Couchbase\Protostellar\Internal;

use Couchbase\DurabilityLevel;
use Couchbase\Exception\InvalidArgumentException;
use Couchbase\Protostellar\Generated\KV\V1\DocumentContentType;
use Couchbase\Protostellar\Generated\KV\V1\LookupInRequest\Flags;
use Couchbase\Protostellar\Generated\KV\V1\LookupInRequest\Spec;
use Couchbase\Protostellar\Generated\KV\V1\MutateInRequest\Spec\Operation;
use Couchbase\Protostellar\Generated\KV\V1\MutateInRequest\StoreSemantic;
use Couchbase\StoreSemantics;
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

    public static function convertLookupInOptions($exportedOptions): array
    {
        $options = [];
        if (isset($exportedOptions["accessDeleted"])) { //TODO accessDeleted doesn't exist in LookupInOptions
            $options["flags"] = new Flags(["access_deleted" => $exportedOptions["accessDeleted"]]);
        }
        return $options;
    }

    /**
     * @throws InvalidArgumentException
     */
    public static function convertMutateInOptions($exportedOptions): array
    {
        $options = [];
        if (isset($exportedOptions["storeSemantics"])) {
            $options["store_semantic"] = self::convertStoreSemantic($exportedOptions["storeSemantics"]);
        }
        if (isset($exportedOptions["durabilityLevel"])) {
            $options["durability_level"] = self::convertDurabilityLevel($exportedOptions["durabilityLevel"]);
        }
        if (isset($exportedOptions["cas"])) {
            $options["cas"] = $exportedOptions["cas"];
        }
        if (isset($exportedOptions["accessDeleted"])) { //TODO accessDeleted doesn't exist in MutateInOptions
            $options["flags"] = new \Couchbase\Protostellar\Generated\KV\V1\MutateInRequest\Flags(["access_deleted" => $exportedOptions["accessDeleted"]]);
        }
        if (isset($exportedOptions["expirySeconds"])) {
            $options["expiry_secs"] = $exportedOptions["expirySeconds"];
        }
        return $options;
    }

    public static function getLookupInSpec(array $exportedSpecs): array
    {
        $opcodeToSpecOperation = [
            'get' => Spec\Operation::OPERATION_GET,
            'getDocument' => Spec\Operation::OPERATION_GET,
            'exists' => Spec\Operation::OPERATION_EXISTS,
            'getCount' => Spec\Operation::OPERATION_COUNT
        ];
        $specs = [];
        foreach ($exportedSpecs as $spec) {
            $newSpec = new Spec(
                [
                    "operation" => $opcodeToSpecOperation[$spec['opcode']],
                    "path" => $spec["path"] ?? "",
                    "flags" => new Spec\Flags(["xattr" => $spec["isXattr"]])
                ]
            );
            $specs[] = $newSpec;
        }
        return $specs;
    }

    public static function getMutateInSpec(array $exportedSpecs): array
    {
        $opcodeToSpecOperation = [
            'dictionaryAdd' => Operation::OPERATION_INSERT,
            'dictionaryUpsert' => Operation::OPERATION_UPSERT,
            'replace' => Operation::OPERATION_REPLACE,
            'remove' => Operation::OPERATION_REMOVE,
            'arrayPushLast' => Operation::OPERATION_ARRAY_APPEND,
            'arrayPushFirst' => Operation::OPERATION_ARRAY_PREPEND,
            'arrayInsert' => Operation::OPERATION_ARRAY_INSERT,
            'arrayAddUnique' => Operation::OPERATION_ARRAY_ADD_UNIQUE,
            'counter' => Operation::OPERATION_COUNTER
        ];
        $specs = [];
        foreach ($exportedSpecs as $spec) {
            $newSpec = new \Couchbase\Protostellar\Generated\KV\V1\MutateInRequest\Spec(
                [
                    "operation" => $opcodeToSpecOperation[$spec['opcode']],
                    "path" => $spec['path'],
                    "content" => $spec["value"] ?? "",
                    "flags" => new \Couchbase\Protostellar\Generated\KV\V1\MutateInRequest\Spec\Flags(
                        ["xattr" => $spec["isXattr"], "create_path" => $spec["createPath"]]
                    )
                ]
            );
            $specs[] = $newSpec;
        }
        return $specs;
    }

    private static function convertStoreSemantic(string $storeSemantic): int
    {
        switch ($storeSemantic) {
            case StoreSemantics::INSERT:
                return StoreSemantic::STORE_SEMANTIC_INSERT;
            case StoreSemantics::UPSERT:
                return StoreSemantic::STORE_SEMANTIC_UPSERT;
            case StoreSemantics::REPLACE:
                return StoreSemantic::STORE_SEMANTIC_REPLACE;
            default:
                throw new InvalidArgumentException("Unknown store semantic option");
        }
    }

    public static function convertLookupInRes(array $specs, array $specsReq): array
    {
        $fields = [];
        for ($i = 0; $i < count($specs); $i++) {
            $res = [];
            $res["path"] = $specsReq[$i]->getPath();
            if (!$specs[$i]->getStatus()) { //TODO: exists doesn't work atm, verify this logic works
                $res["exists"] = "1";
            } else {
                $res["exists"] = null;
            }
            if (!empty($specs[$i]->getContent())) {
                $res["value"] = $specs[$i]->getContent();
            }
            $fields[] = $res;
        }
        return $fields;
    }

    public static function convertMutateInRes(array $specs, array $specsReq): array
    {
        $fields = [];
        for ($i = 0; $i < count($specs); $i++) {
            $res = [];
            $res["path"] = $specsReq[$i]->getPath();
            if (!empty($specs[$i]->getContent())) {
                $res["value"] = $specs[$i]->getContent();
            }
            $fields[] = $res;
        }
        return $fields;
    }

    /**
     * @param string $durabilityLevel
     * @return int|null
     * @throws InvalidArgumentException
     * @internal
     */
    public static function convertDurabilityLevel(string $durabilityLevel): ?int
    {
        switch ($durabilityLevel) {
            case DurabilityLevel::MAJORITY:
                return \Couchbase\Protostellar\Generated\KV\V1\DurabilityLevel::DURABILITY_LEVEL_MAJORITY;
            case DurabilityLevel::MAJORITY_AND_PERSIST_TO_ACTIVE:
                return \Couchbase\Protostellar\Generated\KV\V1\DurabilityLevel::DURABILITY_LEVEL_MAJORITY_AND_PERSIST_TO_ACTIVE;
            case DurabilityLevel::PERSIST_TO_MAJORITY:
                return \Couchbase\Protostellar\Generated\KV\V1\DurabilityLevel::DURABILITY_LEVEL_PERSIST_TO_MAJORITY;
            case DurabilityLevel::NONE:
                return null;
            default:
                throw new InvalidArgumentException("Unknown durability level specified");
        }
    }
}
