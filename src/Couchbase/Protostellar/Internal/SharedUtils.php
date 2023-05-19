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

use Couchbase\Protostellar\ProtostellarRequest;
use Couchbase\Protostellar\Retries\BestEffortRetryStrategy;

class SharedUtils
{
    /** Convert Protobuf RepeatedField Object to PHP array
     * @param $rf
     * @return array
     */
    public static function toArray($rf): array
    {
        $ret = [];
        foreach ($rf as $elem) {
            $ret[] = $elem;
        }
        return $ret;
    }

    public static function toAssociativeArray($rf): array
    {
        $ret = [];
        foreach ($rf as $key => $value) {
            $ret[$key] = $value;
        }
        return $ret;
    }

    public static function convertStatus(string $PSStatus): string
    {
        $arr = [
            "STATUS_SUCCESS" => "success",
            "STATUS_RUNNING" => "running",
            "STATUS_ERRORS" => "errors",
            "STATUS_COMPLETED" => "completed",
            "STATUS_STOPPED" => "stopped",
            "STATUS_TIMEOUT" => "timeout",
            "STATUS_CLOSED" => "closed",
            "STATUS_FATAL" => "fatal",
            "STATUS_ABORTED" => "aborted",
            "STATUS_UNKNOWN" => "unknown"
        ];
        return $arr[$PSStatus];
    }

    public static function createProtostellarRequest(mixed $grpcRequest, bool $idempotent, float $timeout): ProtostellarRequest
    {
        return new ProtostellarRequest(
            $idempotent,
            BestEffortRetryStrategy::build(),
            (microtime(true) * 1e6) + $timeout,
            $grpcRequest
        );
    }
}
