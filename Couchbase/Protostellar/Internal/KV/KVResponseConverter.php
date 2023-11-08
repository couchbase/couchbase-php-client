<?php

namespace Couchbase\Protostellar\Internal\KV;

use Couchbase\CounterResult;
use Couchbase\Exception\DecodingFailureException;
use Couchbase\Exception\DocumentIrretrievableException;
use Couchbase\Exception\DocumentNotFoundException;
use Couchbase\ExistsResult;
use Couchbase\GetAllReplicasOptions;
use Couchbase\GetAndLockOptions;
use Couchbase\GetAndTouchOptions;
use Couchbase\GetAnyReplicaOptions;
use Couchbase\GetOptions;
use Couchbase\GetReplicaResult;
use Couchbase\GetResult;
use Couchbase\LookupInOptions;
use Couchbase\LookupInResult;
use Couchbase\MutateInResult;
use Couchbase\MutationResult;
use Couchbase\Protostellar\Generated\KV\V1\ExistsResponse;
use Couchbase\Protostellar\Generated\KV\V1\GetAndLockResponse;
use Couchbase\Protostellar\Generated\KV\V1\GetAndTouchResponse;
use Couchbase\Protostellar\Generated\KV\V1\GetResponse;
use Couchbase\Protostellar\Generated\KV\V1\LookupInResponse;
use Couchbase\Protostellar\Generated\KV\V1\MutateInResponse;
use Couchbase\Protostellar\Generated\KV\V1\TouchResponse;
use Couchbase\Protostellar\Internal\SharedUtils;
use Couchbase\Result;
use Couchbase\Transcoder;

class KVResponseConverter
{
    /**
     * @throws DecodingFailureException
     */
    public static function convertMutationResult(string $key, mixed $mutationResult): MutationResult
    {
        return new MutationResult(
            [
                "id" => $key,
                "cas" => SharedUtils::getCas($mutationResult->getCas()),
                "mutationToken" =>
                    [
                        "bucketName" => $mutationResult->getMutationToken()->getBucketName(),
                        "partitionId" => $mutationResult->getMutationToken()->getVbucketId(),
                        "partitionUuid" => strval($mutationResult->getMutationToken()->getVbucketUuid()),
                        "sequenceNumber" => strval($mutationResult->getMutationToken()->getSeqNo())
                    ]
            ]
        );
    }

    /**
     * @throws DecodingFailureException
     */
    public static function convertGetResult(string $key, GetResponse $result, GetOptions $options = null): GetResult
    {
        return new GetResult(
            [
                "id" => $key,
                "cas" => SharedUtils::getCas($result->getCas()),
                "value" => $result->getContentUncompressed(),
                "flags" => $result->getContentFlags(),
                "expiry" => $result->getExpiry()?->getSeconds(),
            ],
            GetOptions::getTranscoder($options)
        );
    }

    /**
     * @throws DecodingFailureException
     */
    public static function convertExistsResult(string $key, ExistsResponse $result): ExistsResult
    {
        return new ExistsResult( //TODO Other options in ExistsResult not returned by GRPC
            [
                "id" => $key,
                "cas" => SharedUtils::getCas($result->getCas()),
                "exists" => $result->getResult()
            ]
        );
    }

    /**
     * @throws DecodingFailureException
     */
    public static function convertGetAndTouchResult(string $key, GetAndTouchResponse $result, GetAndTouchOptions $options = null): GetResult
    {
        return new GetResult(
            [
                "id" => $key,
                "cas" => SharedUtils::getCas($result->getCas()),
                "value" => $result->getContentUncompressed(),
                "flags" => $result->getContentFlags(),
                "expiry" => $result->getExpiry()?->getSeconds(),
            ],
            GetAndTouchOptions::getTranscoder($options)
        );
    }

    /**
     * @throws DecodingFailureException
     */
    public static function convertGetAndLockResult(string $key, GetAndLockResponse $result, GetAndLockOptions $options = null): GetResult
    {
        return new GetResult(
            [
                "id" => $key,
                "cas" => SharedUtils::getCas($result->getCas()),
                "value" => $result->getContentUncompressed(),
                "flags" => $result->getContentFlags(),
                "expiry" => $result->getExpiry()?->getSeconds(),
            ],
            GetAndLockOptions::getTranscoder($options)
        );
    }

    public static function convertUnlockResult(string $key, string $cas): Result
    {
        return new Result(
            [
                "id" => $key,
                "cas" => $cas,
            ]
        );
    }

    /**
     * @throws DecodingFailureException
     */
    public static function convertTouchResult(string $key, TouchResponse $response): MutationResult
    {
        $resArr = [
            "id" => $key,
            "cas" => SharedUtils::getCas($response->getCas()),
        ];
        if ($response->hasMutationToken()) {
            $resArr["mutationToken"] =
                [
                    "bucketName" => $response->getMutationToken()->getBucketName(),
                    "partitionId" => $response->getMutationToken()->getVbucketId(),
                    "partitionUuid" => strval($response->getMutationToken()->getVbucketUuid()),
                    "sequenceNumber" => strval($response->getMutationToken()->getSeqNo())
                ];
        }
        return new MutationResult($resArr);
    }

    /**
     * @throws DecodingFailureException
     */
    public static function convertLookupInResult(
        string $key,
        LookupInResponse $result,
        array $specs,
        array $order,
        LookupInOptions $options = null
    ): LookupInResult
    {
        return new LookupInResult(
            [
                "id" => $key,
                "cas" => SharedUtils::getCas($result->getCas()),
                "fields" => self::getLookupInFields(SharedUtils::toArray($result->getSpecs()), $specs, $order),
            ],
            LookupInOptions::getTranscoder($options)
        );
    }

    /**
     * @throws DecodingFailureException
     */
    public static function convertMutateInResult(string $key, MutateInResponse $result, array $specs, array $order): MutateInResult
    {
        return new MutateInResult(
            [
                "id" => $key,
                "cas" => SharedUtils::getCas($result->getCas()),
                "mutationToken" =>
                    [
                        "bucketName" => $result->getMutationToken()->getBucketName(),
                        "partitionId" => $result->getMutationToken()->getVbucketId(),
                        "partitionUuid" => strval($result->getMutationToken()->getVbucketUuid()),
                        "sequenceNumber" => strval($result->getMutationToken()->getSeqNo())
                    ],
                "fields" => self::getMutateInFields(SharedUtils::toArray($result->getSpecs()), $specs, $order),
                "deleted" => false //TODO: No Deleted flag from grpc response
            ]
        );
    }

    /**
     * @throws DocumentIrretrievableException
     */
    public static function convertGetAnyReplicaResult(string $key, array $response, GetAnyReplicaOptions $options = null): array
    {
        if (count($response) == 0) {
            throw new DocumentIrretrievableException(sprintf("Document %s was not found", $key));
        }
        return self::convertCommonGetReplicaResult($key, $response, GetAnyReplicaOptions::getTranscoder($options));
    }

    /**
     * @throws DocumentNotFoundException
     */
    public static function convertGetAllReplicasResult(string $key, array $response, GetAllReplicasOptions $options = null): array
    {
        if (count($response) == 0) {
            throw new DocumentNotFoundException(sprintf("Document %s was not found", $key));
        }
        return self::convertCommonGetReplicaResult($key, $response, GetAllReplicasOptions::getTranscoder($options));
    }

    /**
     * @throws DecodingFailureException
     */
    public static function convertCommonGetReplicaResult(string $key, array $response, Transcoder $transcoder)
    {
        $finalArray = [];
        foreach ($response as $result) {
            $finalArray[] = new GetReplicaResult(
                [
                    "id" => $key,
                    "cas" => SharedUtils::getCas($result->getCas()),
                    "value" => $result->getContent(),
                    "flags" => $result->getContentFlags(),
                    "isReplica" => $result->GetIsReplica()
                ],
                $transcoder
            );
        }
        return $finalArray;
    }

    /**
     * @throws DecodingFailureException
     */
    public static function convertCounterResult(string $key, mixed $result): CounterResult
    {
        return new CounterResult(
            [
                "id" => $key,
                "cas" => SharedUtils::getCas($result->getCas()),
                "mutationToken" =>
                    [
                        "bucketName" => $result->getMutationToken()->getBucketName(),
                        "partitionId" => $result->getMutationToken()->getVbucketId(),
                        "partitionUuid" => strval($result->getMutationToken()->getVbucketUuid()),
                        "sequenceNumber" => strval($result->getMutationToken()->getSeqNo())
                    ],
                "value" => $result->getContent()
            ]
        );
    }

    private static function getLookupInFields(array $specs, array $specsReq, array $order): array
    {
        $specs = self::orderSubdocRes($specs, $order);
        $specsReq = self::orderSubdocRes($specsReq, $order);
        $fields = [];
        for ($i = 0; $i < count($specs); $i++) {
            $res = [];
            $res["path"] = $specsReq[$i]->getPath();
            if ($specs[$i]->getContent() == "true") {
                $res['exists'] = true;
            } elseif ($specs[$i]->getContent() == "false") {
                $res['exists'] = false;
            }
            if (!isset($res['exists']) && $specs[$i]->getContent() !== "") {
                $res["value"] = $specs[$i]->getContent();
                $res['exists'] = true;
            }
            $fields[] = $res;
        }
        return $fields;
    }

    private static function getMutateInFields(array $specs, array $specsReq, array $order): array
    {
        $specs = self::orderSubdocRes($specs, $order);
        $specsReq = self::orderSubdocRes($specsReq, $order);
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

    private static function orderSubdocRes(array $specs, array $order): array
    {
        $temp = [];
        for ($i = 0; $i < sizeof($specs); $i++) {
            $temp[$order[$i]] = $specs[$i];
        }

        for ($i = 0; $i < sizeof($specs); $i++) {
            $specs[$i] = $temp[$i];
            $order[$i] = $i;
        }
        return $specs;
    }
}
