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

class ControlledBackoff implements BackoffCalculator
{
    public function calculateBackoff(ProtostellarRequest $request): int
    {
        switch ($request->retryAttempts()) {
            case 0:
                return 1_000;
            case 1:
                return 10_000;
            case 2:
                return 50_000;
            case 3:
                return 100_000;
            case 4:
                return 500_000;
            default:
                return 1000_000;
        }
    }
}
