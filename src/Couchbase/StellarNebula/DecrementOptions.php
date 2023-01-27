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

namespace Couchbase\StellarNebula;

use DateTimeInterface;

class DecrementOptions
{
    private ?int $timeoutMilliseconds = null;
    private ?int $durabilityTimeoutSeconds = null;
    private int $delta = 1;
    private ?int $initialValue = null;
    private ?int $expirySeconds = null;
    private ?string $durabilityLevel = null;
    /**
     * @var null|array|int[]
     */
    private ?array $legacyDurability = null;

    public static function build(): DecrementOptions
    {
        return new DecrementOptions();
    }

    public function delta(int $decrement): DecrementOptions
    {
        $this->delta = $decrement;
        return $this;
    }

    public function initial(int $initialValue): DecrementOptions
    {
        $this->initialValue = $initialValue;
        return $this;
    }

    public function expiry($seconds): DecrementOptions
    {
        if ($seconds instanceof DateTimeInterface) {
            $this->expirySeconds = $seconds->getTimestamp();
        } else {
            $this->expirySeconds = (int)$seconds;
        }
        return $this;
    }

    public function timeout(int $milliseconds): DecrementOptions
    {
        $this->timeoutMilliseconds = $milliseconds;
        return $this;
    }

    public function durabilityLevel(string $level): DecrementOptions
    {
        $this->durabilityLevel = $level;
        return $this;
    }

    public function durability(int $replicateTo, int $persistTo): DecrementOptions
    {
        $this->legacyDurability = [
            'replicate_to' => $replicateTo,
            'persist_to' => $persistTo,
        ];
        return $this;
    }

    public static function export(?DecrementOptions $options): array
    {
        if ($options == null) {
            return [];
        }
        return [
            'timeoutMilliseconds' => $options->timeoutMilliseconds,
            'durabilityLevel' => $options->durabilityLevel,
            'legacyDurability' => $options->legacyDurability,
            'durabilityTimeoutSeconds' => $options->durabilityTimeoutSeconds,
            'delta' => $options->delta,
            'initialValue' => $options->initialValue,
            'expirySeconds' => $options->expirySeconds
        ];
    }
}
