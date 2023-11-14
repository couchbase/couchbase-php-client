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

namespace Couchbase\Protostellar\Retries;

use Couchbase\Exception\AmbiguousTimeoutException;
use Couchbase\Exception\UnambiguousTimeoutException;
use Couchbase\Exception\RequestcanceledException;
use Couchbase\Protostellar\ProtostellarRequest;
use Couchbase\Protostellar\RequestBehaviour;

class RetryOrchestrator
{
    public static function maybeRetry(ProtostellarRequest $request, RetryReason $reason): RequestBehaviour
    {
        if ($request->timeoutElapsed()) {
            if ($request->idempotent()) {
                return RequestBehaviour::fail(new UnambiguousTimeoutException(message: "Request timed out", context: $request->context()));
            }
            return RequestBehaviour::fail(new AmbiguousTimeoutException(message: "Request timed out", context: $request->context()));
        }
        if ($reason->alwaysRetry()) {
            return self::retryWithDuration($request, $reason, (new ControlledBackoff())->calculateBackoff($request));
        }
        $retryAction = $request->retryStrategy()->retryAfter($request, $reason);
        $duration = $retryAction->duration();
        if (!is_null($duration)) {
            return self::retryWithDuration($request, $reason, $duration);
        } else {
            return RequestBehaviour::fail(new RequestCanceledException(message: "No more retries allowed based on the retry strategy", context: $request->context()));
        }
    }

    private static function retryWithDuration(ProtostellarRequest $request, RetryReason $reason, int $duration): RequestBehaviour
    {
        $cappedDuration = self::capDuration($duration, $request);
        $request->incrementRetryAttempts($reason);
        return RequestBehaviour::retry($cappedDuration);
    }

    private static function capDuration(int $uncappedDuration, ProtostellarRequest $request): int
    {
        $theoreticalTimeout = round((microtime(true) * 1e6)) + $uncappedDuration;
        $absoluteTimeout = $request->absoluteTimeout();
        $timeoutDelta = $theoreticalTimeout - $absoluteTimeout;
        if ($timeoutDelta > 0) {
            $cappedDuration = $uncappedDuration - $timeoutDelta;
            if ($cappedDuration < 0) {
                return $uncappedDuration;
            }
        }
        return $uncappedDuration;
    }
}
