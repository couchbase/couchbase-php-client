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

class UpsertOptions
{
    private Transcoder $transcoder;
    private ?int $timeoutMilliseconds = null;
    private ?int $expirySeconds = null;
    private ?string $durabilityLevel = null;
    /**
     * @var null|array|int[]
     */
    private ?array $legacyDurability = null;

    public function __construct()
    {
        $this->transcoder = JsonTranscoder::getInstance();
    }

    public static function build(): UpsertOptions
    {
        return new UpsertOptions();
    }

    public function transcoder(Transcoder $transcoder): UpsertOptions
    {
        $this->transcoder = $transcoder;
        return $this;
    }

    public function expiry($seconds): UpsertOptions
    {
        if ($seconds instanceof DateTimeInterface) {
            $this->expirySeconds = $seconds->getTimestamp();
        } else {
            $this->expirySeconds = (int)$seconds;
        }
        return $this;
    }

    /**
     * @param string $level see DurabilityLevel enumeration
     */
    public function durabilityLevel(string $level): UpsertOptions
    {
        $this->durabilityLevel = $level;
        return $this;
    }

    public function durability(int $replicateTo, int $persistTo): UpsertOptions
    {
        $this->legacyDurability = [
            'replicate_to' => $replicateTo,
            'persist_to' => $persistTo,
        ];
        return $this;
    }

    public static function encodeDocument(?UpsertOptions $options, $document): array
    {
        if ($options == null) {
            return JsonTranscoder::getInstance()->encode($document);
        }
        return $options->transcoder->encode($document);
    }

    public static function export(?UpsertOptions $options): array
    {
        if ($options == null) {
            return [];
        }
        return [
            'expirySeconds' => $options->expirySeconds,
            'timeoutMilliseconds' => $options->timeoutMilliseconds,
            'durabilityLevel' => $options->durabilityLevel,
            'legacyDurability' => $options->legacyDurability,
        ];
    }
}