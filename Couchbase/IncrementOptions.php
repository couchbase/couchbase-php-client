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

class IncrementOptions
{
    private ?int $timeoutMilliseconds = null;
    private ?string $durabilityLevel = null;
    private ?int $expirySeconds = null;
    private ?int $expiryTimestamp = null;
    private int $delta = 1;
    private ?int $initialValue = null;

    /**
     * Static helper to keep code more readable
     *
     * @return IncrementOptions
     * @since 4.0.0
     */
    public static function build(): IncrementOptions
    {
        return new IncrementOptions();
    }

    /**
     * Sets the value to increment the counter by.
     *
     * @param int $increment the value to increment by
     *
     * @return IncrementOptions
     * @since 4.0.0
     */
    public function delta(int $increment): IncrementOptions
    {
        $this->delta = $increment;
        return $this;
    }

    /**
     * Sets the value to initialize the counter to if the document does
     * not exist.
     *
     * @param int $initialValue the initial value to use if counter does not exist
     *
     * @return IncrementOptions
     * @since 4.0.0
     */
    public function initial(int $initialValue): IncrementOptions
    {
        $this->initialValue = $initialValue;
        return $this;
    }

    /**
     * Sets the operation timeout in milliseconds.
     *
     * @param int $milliseconds the operation timeout to apply
     *
     * @return IncrementOptions
     * @since 4.0.0
     */
    public function timeout(int $milliseconds): IncrementOptions
    {
        $this->timeoutMilliseconds = $milliseconds;
        return $this;
    }

    /**
     * Sets the durability level to enforce when writing the document.
     *
     * @param string|int $level the durability level to enforce
     *
     * @return IncrementOptions
     * @throws Exception\InvalidArgumentException
     * @see DurabilityLevel
     * @since 4.0.0
     */
    public function durabilityLevel($level): IncrementOptions
    {
        if (gettype($level) == "integer") {
            $level = Deprecations::convertDeprecatedDurabilityLevel(__METHOD__, $level);
        }
        $this->durabilityLevel = $level;
        return $this;
    }

    /**
     * Sets the expiry time for the document.
     *
     * @param int|DateTimeInterface $seconds the relative expiry time in seconds or DateTimeInterface object for
     *     absolute point in time
     *
     * @return IncrementOptions
     * @since 4.1.5
     */
    public function expiry($seconds): IncrementOptions
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
     * @internal
     *
     * @param IncrementOptions|null $options
     *
     * @return array
     * @since 4.0.0
     */
    public static function export(?IncrementOptions $options): array
    {
        if ($options == null) {
            return [];
        }
        return [
            'timeoutMilliseconds' => $options->timeoutMilliseconds,
            'durabilityLevel' => $options->durabilityLevel,
            'expirySeconds' => $options->expirySeconds,
            'expiryTimestamp' => $options->expiryTimestamp,
            'delta' => $options->delta,
            'initialValue' => $options->initialValue,
        ];
    }
}
