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

class ExponentialBackoff implements BackoffCalculator
{
    private int $delayMicros;
    private int $maxDelayMicros;
    public function __construct(int $delayMicros = 1, int $maxDelayMicros = 500)
    {
        $this->delayMicros = $delayMicros * 1000;
        $this->maxDelayMicros = $maxDelayMicros * 1000;
    }

    /** With default values, backoff, as retry attempts increase:
     * 1.5ms, 3.5ms, 7.5ms, 15.5ms, 31.5ms, 63.5ms, 127.5ms, 255.5ms, 500ms
     * @param ProtostellarRequest $request
     * @return float Backoff in microseconds
     */
    public function calculateBackoff(ProtostellarRequest $request): int
    {
        $multiplier = pow(2, min(($request->retryAttempts() + 2), 30));
        $delay = ($this->delayMicros * ($multiplier - 1) / 2);
        return min($delay, $this->maxDelayMicros);
    }

    public static function build(int $delayMicros, int $maxDelayMicros): ExponentialBackoff
    {
        return new ExponentialBackoff($delayMicros, $maxDelayMicros);
    }
}
