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

namespace Couchbase\Protostellar\Internal;

use Couchbase\DurabilityLevel;

class TimeoutHandler
{
    public const KV = "kv";
    public const KV_DURABLE = "kvDurable";
    public const ANALYTICS = "analytics";
    public const MANAGEMENT = "management";
    public const QUERY = "query";
    public const SEARCH = "search";
    public const VIEW = "view";

    private array $timeouts;

    public function __construct(array $setTimeouts)
    {
        $this->timeouts[self::ANALYTICS] = $setTimeouts["analyticsTimeout"] ?? 75_000;
        $this->timeouts[self::KV_DURABLE] = $setTimeouts["keyValueDurableTimeout"] ?? 10_000;
        $this->timeouts[self::KV] = $setTimeouts["keyValueTimeout"] ?? 2_500;
        $this->timeouts[self::MANAGEMENT] = $setTimeouts["managementTimeout"] ?? 75_000;
        $this->timeouts[self::QUERY] = $setTimeouts["queryTimeout"] ?? 75_000;
        $this->timeouts[self::SEARCH] = $setTimeouts["searchTimeout"] ?? 75_000;
        $this->timeouts[self::VIEW] = $setTimeouts["viewTimeout"] ?? 75_000;
    }

    public function getTimeout(string $service, array $options): float
    {
        if (isset($options["timeoutMilliseconds"])) {
            return $options["timeoutMilliseconds"] * 1000;
        }
        return $this->getDefaultTimeout($service, self::checkDurability($options)) * 1000;
    }

    private function getDefaultTimeout(string $service, bool $durable): int
    {
        if ($durable) {
            return $this->timeouts[self::KV_DURABLE];
        }
        return $this->timeouts[$service];
    }

    private static function checkDurability(array $options): bool
    {
        if (isset($options["durabilityLevel"])) {
            return $options["durabilityLevel"] !== DurabilityLevel::NONE;
        }
        return false;
    }
}
