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

use Couchbase\Protostellar\Generated\Query\V1\QueryResponse\MetaData;
use Couchbase\Protostellar\Internal\SharedUtils;

class QueryResponseConverter
{
    public static function convertQueryResult(array $response): array
    {
        $finalArray = [];
        foreach ($response as $result) {
            $finalArray["rows"][] = SharedUtils::toArray($result->getRows());
        }
        $finalArray["rows"] = call_user_func_array('array_merge', $finalArray["rows"]);
        $finalArray["meta"] = self::convertMetaData(end($response)->getMetaData());
        return $finalArray;
    }

    private static function convertMetaData(MetaData $metadata): array
    {
        $finalMetaData = [];
        $finalMetaData["requestId"] = $metadata->getRequestId();
        $finalMetaData["clientContextId"] = $metadata->getClientContextId();
        $finalMetaData["signature"] = $metadata->getSignature();
        if ($metadata->hasMetrics()) {
            $finalMetaData["metrics"] = self::convertMetrics($metadata->getMetrics());
        }
        $finalMetaData["status"] = SharedUtils::convertStatus(MetaData\Status::name($metadata->getStatus()));
        if ($metadata->getWarnings()->count()) {
            $finalMetaData["warnings"] = self::convertWarnings(SharedUtils::toArray($metadata->getWarnings()));
        }
        if ($metadata->hasProfile()) {
            $finalMetaData["profile"] = $metadata->getProfile();
        }
        return $finalMetaData;
    }

    private static function convertWarnings(array $warnings): array
    {
        $warningsArr = [];
        foreach ($warnings as $warning) {
            $warningsArr[] = ["code" => $warning->getCode(), "message" => $warning->getMessage()];
        }
        return $warningsArr;
    }

    private static function convertMetrics(MetaData\Metrics $metrics): array
    {
        $finalMetrics = [];
        $finalMetrics["elapsedTime"] = (intval($metrics->getElapsedTime()->getSeconds()) * 1e9) + $metrics->getElapsedTime()->getNanos();
        $finalMetrics["executionTime"] = (intval($metrics->getExecutionTime()->getSeconds()) * 1e9) + (($metrics->getExecutionTime()->getNanos()));
        $finalMetrics["resultCount"] = intval($metrics->getResultCount());
        $finalMetrics["resultSize"] = intval($metrics->getResultSize());
        $finalMetrics["mutationCount"] = intval($metrics->getMutationCount());
        $finalMetrics["sortCount"] = intval($metrics->getSortCount());
        $finalMetrics["errorCount"] = intval($metrics->getErrorCount());
        $finalMetrics["warningCount"] = intval($metrics->getWarningCount());
        return $finalMetrics;
    }
}
