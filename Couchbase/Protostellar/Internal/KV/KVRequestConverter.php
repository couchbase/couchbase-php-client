<?php

namespace Couchbase\Protostellar\Internal\KV;

use Couchbase\DurabilityLevel;
use Couchbase\Exception\InvalidArgumentException;
use Couchbase\InsertOptions;
use Couchbase\LookupInMacro;
use Couchbase\LookupInOptions;
use Couchbase\LookupInSpec;
use Couchbase\MutateInOptions;
use Couchbase\MutateInSpec;
use Couchbase\Protostellar\Generated\KV\V1\LookupInRequest\Flags;
use Couchbase\Protostellar\Generated\KV\V1\LookupInRequest\Spec;
use Couchbase\Protostellar\Generated\KV\V1\MutateInRequest\Spec\Operation;
use Couchbase\Protostellar\Generated\KV\V1\MutateInRequest\StoreSemantic;
use Couchbase\ReplaceOptions;
use Couchbase\StoreSemantics;
use Couchbase\UpsertOptions;
use DateTimeInterface;
use Google\Protobuf\Timestamp;

class KVRequestConverter
{
    public static function getLocation(string $bucketName, string $scopeName, string $collectionName): array
    {
        return [
            "bucket_name" => $bucketName,
            "scope_name" => $scopeName,
            "collection_name" => $collectionName
        ];
    }

    /**
     * @throws InvalidArgumentException
     */
    public static function getUpsertRequest(string $key, $document, array $location, UpsertOptions $options = null): array
    {
        [$encodedDocument, $contentType] = UpsertOptions::encodeDocument($options, $document);
        $exportedOptions = UpsertOptions::export($options);
        $request = [
            "key" => $key,
            "content" => $encodedDocument,
            "content_flags" => $contentType,
        ];
        if (isset($exportedOptions["expiryTimestamp"])) {
            $request["expiry_time"] = new Timestamp(["seconds" => $exportedOptions["expiryTimestamp"]]);
        }
        if (isset($exportedOptions["expirySeconds"])) {
            $request["expiry_secs"] = $exportedOptions["expirySeconds"];
        }
        if (isset($exportedOptions["durabilityLevel"])) {
            $request["durability_level"] = self::convertDurabilityLevel($exportedOptions["durabilityLevel"]);
        }
        return array_merge($request, $location);
    }

    /**
     * @throws InvalidArgumentException
     */
    public static function getInsertRequest(string $key, $document, array $location, InsertOptions $options = null): array
    {
        [$encodedDocument, $contentType] = InsertOptions::encodeDocument($options, $document);
        $exportedOptions = InsertOptions::export($options);
        $request = [
            "key" => $key,
            "content" => $encodedDocument,
            "content_flags" => $contentType,
        ];
        if (isset($exportedOptions["expiryTimestamp"])) {
            $request["expiry_time"] = new Timestamp(["seconds" => $exportedOptions["expiryTimestamp"]]);
        }
        if (isset($exportedOptions["expirySeconds"])) {
            $request["expiry_secs"] = $exportedOptions["expirySeconds"];
        }
        if (isset($exportedOptions["durabilityLevel"])) {
            $request["durability_level"] = self::convertDurabilityLevel($exportedOptions["durabilityLevel"]);
        }
        return array_merge($request, $location);
    }

    /**
     * @throws InvalidArgumentException
     */
    public static function getReplaceRequest(string $key, $document, array $location, ReplaceOptions $options = null): array
    {
        [$encodedDocument, $contentType] = ReplaceOptions::encodeDocument($options, $document);
        $exportedOptions = ReplaceOptions::export($options);
        $request = [
            "key" => $key,
            "content" => $encodedDocument,
            "content_flags" => $contentType,
        ];
        if (isset($exportedOptions["expiryTimestamp"])) {
            $request["expiry_time"] = new Timestamp(["seconds" => $exportedOptions["expiryTimestamp"]]);
        }
        if (isset($exportedOptions["expirySeconds"])) {
            $request["expiry_secs"] = $exportedOptions["expirySeconds"];
        }
        if (isset($exportedOptions["cas"])) {
            $request["cas"] = $exportedOptions["cas"];
        }
        if (isset($exportedOptions["durabilityLevel"])) {
            $request["durability_level"] = self::convertDurabilityLevel($exportedOptions["durabilityLevel"]);
        }
        return array_merge($request, $location);
    }

    /**
     * @throws InvalidArgumentException
     */
    public static function getRemoveRequest(string $key, array $exportedOptions, array $location): array
    {
        $request = [
            "key" => $key,
        ];
        if (isset($exportedOptions["cas"])) {
            $request["cas"] = $exportedOptions["cas"];
        }
        if (isset($exportedOptions["durabilityLevel"])) {
            $request["durability_level"] = self::convertDurabilityLevel($exportedOptions["durabilityLevel"]);
        }
        return array_merge($request, $location);
    }

    public static function getGetRequest(string $key, array $exportedOptions, array $location): array
    {
        $request = [
            "key" => $key,
        ];
        if (isset($exportedOptions["projections"])) {
            $request["project"] = $exportedOptions["projections"];
        }
        return array_merge($request, $location);
    }

    public static function getExistsRequest(string $key, array $location): array
    {
        $request = [
            "key" => $key,
        ];
        return array_merge($request, $location);
    }

    public static function getGetAndTouchRequest(string $key, $expiry, array $location): array
    {
        $request = [
            "key" => $key,
        ];
        if ($expiry instanceof DateTimeInterface) {
            $expirySeconds = $expiry->getTimestamp();
            $request["expiry_time"] = new Timestamp(["seconds" => $expirySeconds]);
        } else {
            $expirySeconds = (int)$expiry;
            $request["expiry_secs"] = $expirySeconds;
        }
        return array_merge($request, $location);
    }

    public static function getGetAndLockRequest(string $key, int $lockTimeSeconds, array $location): array
    {
        $request = [
            "key" => $key,
            "lock_time" => $lockTimeSeconds
        ];
        return array_merge($request, $location);
    }

    public static function getUnlockRequest(string $key, string $cas, array $location): array
    {
        $request = [
            "key" => $key,
            "cas" => $cas
        ];
        return array_merge($request, $location);
    }

    public static function getTouchRequest(string $key, $expiry, array $location): array
    {
        $request = [
            "key" => $key,
        ];
        if ($expiry instanceof DateTimeInterface) {
            $expirySeconds = $expiry->getTimestamp();
            $request["expiry_time"] = new Timestamp(["seconds" => $expirySeconds]);
        } else {
            $expirySeconds = (int)$expiry;
            $request["expiry_secs"] = $expirySeconds;
        }
        return array_merge($request, $location);
    }

    public static function getLookupInRequest(string $key, array $specs, array $location, LookupInOptions $options = null): array
    {
        $exportedOptions = LookupInOptions::export($options);
        $encoded = array_map(
            function (LookupInSpec $item) {
                return $item->export();
            },
            $specs
        );
        if ($options != null && $options->needToFetchExpiry()) {
            $encoded[] = ['opcode' => 'get', 'isXattr' => true, 'path' => LookupInMacro::EXPIRY_TIME];
        }
        [$specsReq, $order] = self::getLookupInSpec($encoded);
        $request = [
            "key" => $key,
            "specs" => $specsReq
        ];
        if (isset($exportedOptions["accessDeleted"])) { //TODO accessDeleted doesn't exist in LookupInOptions
            $request["flags"] = new Flags(["access_deleted" => $exportedOptions["accessDeleted"]]);
        }
        return [array_merge($request, $location), $order];
    }

    /**
     * @throws InvalidArgumentException
     */
    public static function getMutateInRequest(string $key, array $specs, array $location, MutateInOptions $options = null): array
    {
        $exportedOptions = MutateInOptions::export($options);
        $encoded = array_map(
            function (MutateInSpec $item) use ($options) {
                return $item->export($options);
            },
            $specs
        );
        [$specsReq, $order] = self::getMutateInSpec($encoded);
        $request = [
            "key" => $key,
            "specs" => $specsReq
        ];

        if (isset($exportedOptions["storeSemantics"])) {
            $request["store_semantic"] = self::convertStoreSemantic($exportedOptions["storeSemantics"]);
        }
        if (isset($exportedOptions["durabilityLevel"])) {
            $request["durability_level"] = self::convertDurabilityLevel($exportedOptions["durabilityLevel"]);
        }
        if (isset($exportedOptions["cas"])) {
            $request["cas"] = $exportedOptions["cas"];
        }
        if (isset($exportedOptions["accessDeleted"])) { //TODO accessDeleted doesn't exist in MutateInOptions
            $request["flags"] = new \Couchbase\Protostellar\Generated\KV\V1\MutateInRequest\Flags(["access_deleted" => $exportedOptions["accessDeleted"]]);
        }
        if (isset($exportedOptions["expiryTimestamp"])) {
            $request["expiry_time"] = new Timestamp(["seconds" => $exportedOptions["expiryTimestamp"]]);
        }
        if (isset($exportedOptions["expirySeconds"])) {
            $request["expiry_secs"] = $exportedOptions["expirySeconds"];
        }

        return [array_merge($request, $location), $order];
    }

    public static function getGetAllReplicasRequest(string $key, array $location): array
    {
        $request = [
            "key" => $key
        ];
        return array_merge($request, $location);
    }

    /**
     * @throws InvalidArgumentException
     */
    public static function getAppendRequest(string $key, string $value, array $exportedOptions, array $location): array
    {
        $request = [
            "key" => $key,
            "content" => $value
        ];
        if (isset($exportedOptions["cas"])) { //TODO: AppendOptions do not include cas
            $request["cas"] = $exportedOptions["cas"];
        }
        if (isset($exportedOptions["durabilityLevel"])) {
            $request["durability_level"] = self::convertDurabilityLevel($exportedOptions["durabilityLevel"]);
        }
        return array_merge($request, $location);
    }

    /**
     * @throws InvalidArgumentException
     */
    public static function getPrependRequest(string $key, string $value, array $exportedOptions, array $location): array
    {
        $request = [
            "key" => $key,
            "content" => $value
        ];
        if (isset($exportedOptions["cas"])) { //TODO PrependOptions do not include cas
            $request["cas"] = $exportedOptions["cas"];
        }
        if (isset($exportedOptions["durabilityLevel"])) {
            $request["durability_level"] = self::convertDurabilityLevel($exportedOptions["durabilityLevel"]);
        }
        return array_merge($request, $location);
    }

    /**
     * @throws InvalidArgumentException
     */
    public static function getIncrementRequest(string $key, array $exportedOptions, array $location): array
    {
        $request = [
            "key" => $key
        ];
        $request["delta"] = $exportedOptions["delta"] ?? 1;
        if (isset($exportedOptions["initialValue"])) {
            $request["initial"] = $exportedOptions["initialValue"];
        }
        if (isset($exportedOptions["expirySeconds"])) { //TODO IncrementOptions do not include expirySeconds
            $request["expiry_secs"] = $exportedOptions["expirySeconds"];
        }
        if (isset($exportedOptions["durabilityLevel"])) {
            $request["durability_level"] = self::convertDurabilityLevel($exportedOptions["durabilityLevel"]);
        }
        return array_merge($request, $location);
    }

    /**
     * @throws InvalidArgumentException
     */
    public static function getDecrementRequest(string $key, array $exportedOptions, array $location): array
    {
        $request = [
            "key" => $key
        ];
        $request["delta"] = $exportedOptions["delta"] ?? 1;
        if (isset($exportedOptions["initialValue"])) {
            $request["initial"] = $exportedOptions["initialValue"];
        }
        if (isset($exportedOptions["expirySeconds"])) { //TODO DecrementOptions do not include expirySeconds
            $request["expiry"] = new Timestamp(["seconds" => $exportedOptions["expirySeconds"]]);
        }
        if (isset($exportedOptions["durabilityLevel"])) {
            $request["durability_level"] = self::convertDurabilityLevel($exportedOptions["durabilityLevel"]);
        }
        return array_merge($request, $location);
    }

    public static function getMutateInSpec(array $exportedSpecs): array
    {
        [$orderedSpecs, $order] = self::orderSubdocSpecs($exportedSpecs);
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
        foreach ($orderedSpecs as $spec) {
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
        return [$specs, $order];
    }

    /**
     * @throws InvalidArgumentException
     */
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

    private static function getLookupInSpec(array $exportedSpecs): array
    {
        [$orderedSpecs, $order] = self::orderSubdocSpecs($exportedSpecs);
        $opcodeToSpecOperation = [
            'get' => Spec\Operation::OPERATION_GET,
            'getDocument' => Spec\Operation::OPERATION_GET,
            'exists' => Spec\Operation::OPERATION_EXISTS,
            'getCount' => Spec\Operation::OPERATION_COUNT
        ];
        $specs = [];
        foreach ($orderedSpecs as $spec) {
            $newSpec = new Spec(
                [
                    "operation" => $opcodeToSpecOperation[$spec['opcode']],
                    "path" => $spec["path"] ?? "",
                    "flags" => new Spec\Flags(["xattr" => $spec["isXattr"]])
                ]
            );
            $specs[] = $newSpec;
        }
        return [$specs, $order];
    }

    private static function orderSubdocSpecs(array $exportedSpecs): array
    {
        for ($i = 0; $i < sizeof($exportedSpecs); $i++) {
            $exportedSpecs[$i]["order"] = $i;
        }
        usort(
            $exportedSpecs,
            function ($left, $right) {
                return $right["isXattr"] - $left["isXattr"];
            }
        );
        $order = [];
        for ($i = 0; $i < sizeof($exportedSpecs); $i++) {
            $order[] = $exportedSpecs[$i]["order"];
            unset($exportedSpecs[$i]["order"]);
        }
        return [$exportedSpecs, $order];
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
