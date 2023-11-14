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

namespace Couchbase\Protostellar\Internal\Query;

use Couchbase\Exception\InvalidArgumentException;
use Couchbase\Protostellar\Generated\KV\V1\MutationToken;
use Couchbase\Protostellar\Generated\Query\V1\QueryRequest;
use Couchbase\Protostellar\Generated\Query\V1\QueryRequest\ScanConsistency;
use Couchbase\QueryProfile;
use Couchbase\QueryScanConsistency;
use Google\Protobuf\Duration;

class QueryRequestConverter
{
    /**
     * @throws InvalidArgumentException
     */
    public static function getQueryRequest(string $statement, array $exportedOptions): QueryRequest
    {
        $request = [
            'statement' => $statement,
        ];
        if (isset($exportedOptions["bucketName"])) {
            $request["bucket_name"] = $exportedOptions["bucketName"];
        }
        if (isset($exportedOptions["scopeName"])) {
            $request["scope_name"] = $exportedOptions["scopeName"];
        }
        if (isset($exportedOptions["readonly"])) {
            $request["read_only"] = $exportedOptions["readonly"];
        }
        if (isset($exportedOptions["prepared"])) {
            $request["prepared"] = $exportedOptions["prepared"]; //TODO: prepared option does not exist in Couchbase\QueryOptions
        }
        $tuningOptions = self::getTuningOptions($exportedOptions);
        if ($tuningOptions) {
            $request["tuning_options"] = new QueryRequest\TuningOptions($tuningOptions);
        }
        if (isset($exportedOptions["clientContextId"])) {
            $request["client_context_id"] = $exportedOptions["clientContextId"];
        }
        if (isset($exportedOptions["scanConsistency"])) {
            $request["scan_consistency"] = self::convertScanConsistency($exportedOptions["scanConsistency"]);
        }
        if (isset($exportedOptions["positionalParameters"])) {
            $request["positional_parameters"] = $exportedOptions["positionalParameters"];
        }
        if (isset($exportedOptions["namedParameters"])) {
            $request["named_parameters"] = $exportedOptions["namedParameters"];
        }
        if (isset($exportedOptions["flexIndex"])) {
            $request["flex_index"] = $exportedOptions["flexIndex"];
        }
        if (isset($exportedOptions["preserveExpiry"])) {
            $request["preserve_expiry"] = $exportedOptions["preserveExpiry"];
        }
        if (isset($exportedOptions["consistentWith"])) {
            $request["consistent_with"] = self::convertConsistentWith($exportedOptions["consistentWith"]);
        }
        if (isset($exportedOptions["profile"])) {
            $request["profile_mode"] = self::convertQueryProfile($exportedOptions["profile"]);
        }
        return new QueryRequest($request);
    }

    private static function getTuningOptions(array $exportedOptions): array
    {
        $tuningOptions = [];
        if (isset($exportedOptions["maxParallelism"])) {
            $tuningOptions["max_parallelism"] = $exportedOptions["maxParallelism"];
        }
        if (isset($exportedOptions["pipelineBatch"])) {
            $tuningOptions["pipeline_batch"] = $exportedOptions["pipelineBatch"];
        }
        if (isset($exportedOptions["pipelineCap"])) {
            $tuningOptions["pipeline_cap"] = $exportedOptions["pipelineCap"];
        }
        if (isset($exportedOptions["scanWait"])) {
            $seconds = floor($exportedOptions["scanWait"] / 1000);
            $nanos = ($exportedOptions["scanWait"] % 1000) * 1e6;
            $tuningOptions["scan_wait"] = new Duration(['seconds' => $seconds, 'nanos' => $nanos]);
        }
        if (isset($exportedOptions["scanCap"])) {
            $tuningOptions["scan_cap"] = $exportedOptions["scanCap"];
        }
        if (isset($exportedOptions["metrics"])) {
            $tuningOptions["disable_metrics"] = !$exportedOptions["metrics"];
        }
        return $tuningOptions;
    }

    /**
     * @throws InvalidArgumentException
     */
    private static function convertScanConsistency(string $consistencyLevel): int
    {
        switch ($consistencyLevel) {
            case QueryScanConsistency::NOT_BOUNDED:
                return ScanConsistency::SCAN_CONSISTENCY_NOT_BOUNDED;
            case QueryScanConsistency::REQUEST_PLUS:
                return ScanConsistency::SCAN_CONSISTENCY_REQUEST_PLUS;
            default:
                throw new InvalidArgumentException(
                    "Value for query scan consistency must be QueryScanConsistency::NOT_BOUNDED or QueryScanConsistency::REQUEST_PLUS"
                );
        }
    }

    private static function convertConsistentWith(array $state): array
    {
        /**
         * @throws InvalidArgumentException
         */
        $getNewKey = function (string $key) {
            switch ($key) {
                case "partitionId":
                    return "vbucket_id";
                case "partitionUuid":
                    return "vbucket_uuid";
                case "sequenceNumber":
                    return "seq_no";
                case "bucketName":
                    return "bucket_name";
                default:
                    throw new InvalidArgumentException("Unexpected mutation token key");
            }
        };

        $stateArr = [];
        foreach ($state as $mutationToken) {
            foreach ($mutationToken as $key => $value) {
                $newKey = $getNewKey($key);
                $mutationToken[$newKey] = $value;
                unset($mutationToken[$key]);
            }
            $token = new MutationToken($mutationToken);
            $stateArr[] = $token;
        }
        return $stateArr;
    }

    /**
     * @throws InvalidArgumentException
     */
    private static function convertQueryProfile(string $profile): int
    {
        switch ($profile) {
            case QueryProfile::OFF:
                return QueryRequest\ProfileMode::PROFILE_MODE_OFF;
            case QueryProfile::PHASES:
                return QueryRequest\ProfileMode::PROFILE_MODE_PHASES;
            case QueryProfile::TIMINGS:
                return QueryRequest\ProfileMode::PROFILE_MODE_TIMINGS;
            default:
                throw new InvalidArgumentException("Unexpected query profile");
        }
    }
}
