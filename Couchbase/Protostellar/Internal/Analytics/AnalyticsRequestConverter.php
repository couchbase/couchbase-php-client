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

namespace Couchbase\Protostellar\Internal\Analytics;

use Couchbase\AnalyticsScanConsistency;
use Couchbase\Exception\InvalidArgumentException;
use Couchbase\Protostellar\Generated\Analytics\V1\AnalyticsQueryRequest;
use Couchbase\Protostellar\Generated\Analytics\V1\AnalyticsQueryRequest\ScanConsistency;

class AnalyticsRequestConverter
{
    /**
     * @throws InvalidArgumentException
     */
    public static function getAnalyticsRequest(string $statement, array $exportedOptions): AnalyticsQueryRequest
    {
        $request = [
            'statement' => $statement,
        ];
        if (isset($exportedOptions['bucketName'])) {
            $request["bucket_name"] = $exportedOptions["bucketName"];
        }
        if (isset($exportedOptions["scopeName"])) {
            $request["scope_name"] = $exportedOptions["scopeName"];
        }
        if (isset($exportedOptions["readonly"])) {
            $request["read_only"] = $exportedOptions["readonly"];
        }
        if (isset($exportedOptions["clientContextId"])) {
            $request["client_context_id"] = $exportedOptions["clientContextId"];
        }
        if (isset($exportedOptions["priority"])) {
            $request["priority"] = $exportedOptions["priority"];
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
        return new AnalyticsQueryRequest($request);
    }

    /**
     * @throws InvalidArgumentException
     */
    private static function convertScanConsistency(string $consistencyLevel): int
    {
        switch ($consistencyLevel) {
            case AnalyticsScanConsistency::NOT_BOUNDED:
                return ScanConsistency::SCAN_CONSISTENCY_NOT_BOUNDED;
            case AnalyticsScanConsistency::REQUEST_PLUS:
                return ScanConsistency::SCAN_CONSISTENCY_REQUEST_PLUS;
            default:
                throw new InvalidArgumentException(
                    "Value for analytics scan consistency must be AnalyticsScanConsistency::NOT_BOUNDED or
                     AnalyticsScanConsistency::REQUEST_PLUS"
                );
        }
    }
}
