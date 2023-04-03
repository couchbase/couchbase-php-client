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

namespace Couchbase;

use Couchbase\Exception\InvalidArgumentException;
use Couchbase\Utilities\Deprecations;
use DateTimeInterface;

class MutateInOptions
{
    private Transcoder $transcoder;
    private ?int $timeoutMilliseconds = null;
    private ?string $durabilityLevel = null;
    private ?string $cas = null;
    private ?int $expirySeconds = null;
    private ?int $expiryTimestamp = null;
    private ?bool $preserveExpiry = null;
    private string $storeSemantics;

    /**
     * @since 4.0.0
     */
    public function __construct()
    {
        $this->transcoder = JsonTranscoder::getInstance();
        $this->storeSemantics = StoreSemantics::REPLACE;
    }

    /**
     * Static helper to keep code more readable
     *
     * @return MutateInOptions
     * @since 4.0.0
     */
    public static function build(): MutateInOptions
    {
        return new MutateInOptions();
    }

    /**
     * Sets the operation timeout in milliseconds.
     *
     * @param int $milliseconds the operation timeout to apply
     *
     * @return MutateInOptions
     * @since 4.0.0
     */
    public function timeout(int $milliseconds): MutateInOptions
    {
        $this->timeoutMilliseconds = $milliseconds;
        return $this;
    }

    /**
     * Sets the cas value to use when performing this operation.
     *
     * @param string $cas the CAS value to use
     *
     * @return MutateInOptions
     * @since 4.0.0
     */
    public function cas(string $cas): MutateInOptions
    {
        $this->cas = $cas;
        return $this;
    }

    /**
     * Sets the expiry time for the document.
     *
     * @param int|DateTimeInterface $seconds the relative expiry time in seconds or DateTimeInterface object for
     *     absolute point in time
     *
     * @return MutateInOptions
     * @since 4.0.0
     */
    public function expiry($seconds): MutateInOptions
    {
        if ($seconds instanceof DateTimeInterface) {
            $this->expirySeconds = null;
            $this->expiryTimestamp = $seconds->getTimestamp();
        } else {
            $this->expiryTimestamp = null;
            $this->expirySeconds = (int)$seconds;
        }
        return $this;
    }

    /**
     * Sets whether the original expiration should be preserved (by default Replace operation updates expiration).
     *
     * @param bool $shouldPreserve if true, the expiration time will not be updated
     *
     * @return MutateInOptions
     * @since 4.0.0
     */
    public function preserveExpiry(bool $shouldPreserve): MutateInOptions
    {
        $this->preserveExpiry = $shouldPreserve;
        return $this;
    }

    /**
     * Sets the durability level to enforce when writing the document.
     *
     * @param string|int $level the durability level to enforce
     * @param int|null $timeoutSeconds
     *
     * @return MutateInOptions
     * @throws InvalidArgumentException
     * @see DurabilityLevel
     * @since 4.0.0
     */
    public function durabilityLevel($level): MutateInOptions
    {
        if (gettype($level) == "integer") {
            $level = Deprecations::convertDeprecatedDurabilityLevel(__METHOD__, $level);
        }
        $this->durabilityLevel = $level;
        return $this;
    }

    /**
     * Sets the document level action to use when performing the operation.
     *
     * @param int|string $semantics the store semantic to use, int parameter is now @deprecated
     *
     * @return MutateInOptions
     * @throws InvalidArgumentException
     * @see StoreSemantics
     * @since 4.0.0
     */
    public function storeSemantics($semantics): MutateInOptions
    {
        if (gettype($semantics) == "integer") {
            $semantics = Deprecations::convertDeprecatedStoreSemantics(__METHOD__, $semantics);
        }
        $this->storeSemantics = $semantics;
        return $this;
    }

    /**
     * Associate custom transcoder with the request.
     *
     * @param Transcoder $transcoder
     *
     * @return MutateInOptions
     * @since 4.0.0
     */
    public function transcoder(Transcoder $transcoder): MutateInOptions
    {
        $this->transcoder = $transcoder;
        return $this;
    }

    /**
     * Delegates encoding of the value to associated transcoder
     *
     * @param MutateInOptions|null $options
     * @param                      $document
     *
     * @return string
     * @since 4.0.0
     */
    public static function encodeValue(?MutateInOptions $options, $document): string
    {
        if ($options == null) {
            return JsonTranscoder::getInstance()->encode($document)[0];
        }
        return $options->transcoder->encode($document)[0];
    }

    /**
     * @internal
     *
     * @param MutateInOptions|null $options
     *
     * @return array
     * @since 4.0.0
     */
    public static function export(?MutateInOptions $options): array
    {
        if ($options == null) {
            return [];
        }
        return [
            'timeoutMilliseconds' => $options->timeoutMilliseconds,
            'expirySeconds' => $options->expirySeconds,
            'expiryTimestamp' => $options->expiryTimestamp,
            'preserveExpiry' => $options->preserveExpiry,
            'cas' => $options->cas,
            'durabilityLevel' => $options->durabilityLevel,
            'storeSemantics' => $options->storeSemantics,
        ];
    }
}
