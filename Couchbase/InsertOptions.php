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

use Couchbase\Utilities\Deprecations;
use DateTimeInterface;

class InsertOptions
{
    private Transcoder $transcoder;
    private ?int $timeoutMilliseconds = null;
    private ?int $expirySeconds = null;
    private ?int $expiryTimestamp = null;
    private ?string $durabilityLevel = null;
    private ?int $durabilityTimeoutSeconds = null;

    /**
     * @since 4.0.0
     */
    public function __construct()
    {
        $this->transcoder = JsonTranscoder::getInstance();
    }

    /**
     * Static helper to keep code more readable
     *
     * @return InsertOptions
     * @since 4.0.0
     */
    public static function build(): InsertOptions
    {
        return new InsertOptions();
    }

    /**
     * Sets the operation timeout in milliseconds.
     *
     * @param int $milliseconds the operation timeout to apply
     *
     * @return InsertOptions
     * @since 4.0.0
     */
    public function timeout(int $milliseconds): InsertOptions
    {
        $this->timeoutMilliseconds = $milliseconds;
        return $this;
    }

    /**
     * Sets the expiry time for the document.
     *
     * @param int|DateTimeInterface $seconds the relative expiry time in seconds or DateTimeInterface object for
     *     absolute point in time
     *
     * @return InsertOptions
     * @since 4.0.0
     */
    public function expiry($seconds): InsertOptions
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
     * Sets the durability level to enforce when writing the document.
     *
     * @param string|int $level the durability level to enforce
     * @param int|null $timeoutSeconds
     *
     * @return InsertOptions
     * @throws Exception\InvalidArgumentException
     * @see DurabilityLevel
     * @since 4.0.0
     */
    public function durabilityLevel($level, ?int $timeoutSeconds): InsertOptions
    {
        if (gettype($level) == "integer") {
            $level = Deprecations::convertDeprecatedDurabilityLevel(__METHOD__, $level);
        }
        $this->durabilityLevel = $level;
        $this->durabilityTimeoutSeconds = $timeoutSeconds;
        return $this;
    }

    /**
     * Associate custom transcoder with the request.
     *
     * @param Transcoder $transcoder
     *
     * @return InsertOptions
     * @since 4.0.0
     */
    public function transcoder(Transcoder $transcoder): InsertOptions
    {
        $this->transcoder = $transcoder;
        return $this;
    }

    /**
     * Delegates encoding of the document to associated transcoder
     *
     * @param InsertOptions|null $options
     * @param                    $document
     *
     * @return array
     * @since 4.0.0
     */
    public static function encodeDocument(?InsertOptions $options, $document): array
    {
        if ($options == null) {
            return JsonTranscoder::getInstance()->encode($document);
        }
        return $options->transcoder->encode($document);
    }

    /**
     * @internal
     *
     * @param InsertOptions|null $options
     *
     * @return array
     * @since 4.0.0
     */
    public static function export(?InsertOptions $options): array
    {
        if ($options == null) {
            return [];
        }
        return [
            'timeoutMilliseconds' => $options->timeoutMilliseconds,
            'expirySeconds' => $options->expirySeconds,
            'expiryTimestamp' => $options->expiryTimestamp,
            'durabilityLevel' => $options->durabilityLevel,
            'durabilityTimeoutSeconds' => $options->durabilityTimeoutSeconds,
        ];
    }
}
