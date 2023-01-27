<?php

/**
 * Copyright 2014-Present Couchbase, Inc.
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

use Couchbase\StellarNebula\Generated\KV\V1\MutateInRequest\StoreSemantic;
use http\Exception\InvalidArgumentException;

class MutateInOptions
{
    private Transcoder $transcoder;
    private ?int $timeoutMilliseconds = null;
    private ?string $cas = null;
//    private ?int $expirySeconds = null;
//    private ?bool $preserveExpiry = null;
    private int $storeSemantics;
    private ?string $durabilityLevel = null;
    /**
     * @var null|array|int[]
     */
    private ?array $legacyDurability = null;

    public function __construct()
    {
        $this->transcoder = JsonTranscoder::getInstance();
        $this->storeSemantics = StoreSemantic::REPLACE;
    }

    public static function build(): MutateInOptions
    {
        return new MutateInOptions();
    }

    public function timeout(int $milliseconds): MutateInOptions
    {
        $this->timeoutMilliseconds = $milliseconds;
        return $this;
    }

    public function cas(string $cas): MutateInOptions
    {
        $this->cas = $cas;
        return $this;
    }

    /**
     * @param string $level see DurabilityLevel enumeration
     */
    public function durabilityLevel(string $level): MutateInOptions
    {
        $this->durabilityLevel = $level;
        return $this;
    }

    public function durability(int $replicateTo, int $persistTo): MutateInOptions
    {
        $this->legacyDurability = [
            'replicate_to' => $replicateTo,
            'persist_to' => $persistTo,
        ];
        return $this;
    }
    //TODO: Check approach here, int was deprecated but works fine for SN
    public function storeSemantics($semantics): MutateInOptions
    {
        if (gettype($semantics) == "integer") {
            $this->storeSemantics = $semantics;
        }
        $this->storeSemantics = self::convertSemantics($semantics);
        return $this;
    }
    private static function convertSemantics(string $semantics): int
    {
        switch (strtolower($semantics)) {
            case "replace":
                return 0;
            case "upsert":
                return 1;
            case "insert":
                return 2;
            default:
                throw new InvalidArgumentException("Store semantics value must be either 'replace', 'upsert', or 'insert'");
        }
    }

    public function transcoder(Transcoder $transcoder): MutateInOptions
    {
        $this->transcoder = $transcoder;
        return $this;
    }

    public static function encodeValue(?MutateInOptions $options, $document): string
    {
        if ($options == null) {
            return JsonTranscoder::getInstance()->encode($document)[0];
        }
        return $options->transcoder->encode($document)[0];
    }

    public static function export(?MutateInOptions $options): array
    {
        if ($options == null) {
            return [];
        }
        return [
            'timeoutMilliseconds' => $options->timeoutMilliseconds,
            'cas' => $options->cas,
            'durabilityLevel' => $options->durabilityLevel,
            'legacyDurability' => $options->legacyDurability,
            'store_semantic' => $options->storeSemantics,
        ];
    }
}
