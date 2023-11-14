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

use Couchbase\Protostellar\ProtostellarRequest;

class BestEffortRetryStrategy implements RetryStrategy
{
    private BackoffCalculator $calculator;

    public function __construct(BackoffCalculator $calculator = null)
    {
        if (is_null($calculator)) {
            $this->calculator = new ExponentialBackoff();
        } else {
            $this->calculator = $calculator;
        }
    }

    public static function build(): BestEffortRetryStrategy
    {
        return new BestEffortRetryStrategy();
    }

    public function retryAfter(ProtostellarRequest $request, RetryReason $reason): RetryAction
    {
        if ($request->idempotent() || $reason->allowsNonIdempotentRetry()) {
            $backoffDuration = $this->calculator->calculateBackoff($request);
            return RetryAction::build($backoffDuration);
        }
        return RetryAction::build(null);
    }
}
