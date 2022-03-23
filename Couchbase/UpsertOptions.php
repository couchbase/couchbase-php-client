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

use DateTimeInterface;

class UpsertOptions
{
    private ?int $timeoutMilliseconds = null;
    private ?int $expirySeconds = null;
    private ?bool $preserveExpiry = null;
    private ?string $durabilityLevel = null;
    private ?int $durabilityTimeoutSeconds = null;

    /**
     * Sets the operation timeout in milliseconds.
     *
     * @param int $milliseconds the operation timeout to apply
     * @return UpsertOptions
     */
    public function timeout(int $milliseconds): UpsertOptions
    {
        $this->timeoutMilliseconds = $milliseconds;
        return $this;
    }

    /**
     * Sets the expiry time for the document.
     *
     * @param int|DateTimeInterface $seconds the relative expiry time in seconds or DateTimeInterface object for absolute point in time
     * @return UpsertOptions
     */
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
     * Sets whether the original expiration should be preserved (by default Replace operation updates expiration).
     *
     * @param bool $shouldPreserve if true, the expiration time will not be updated
     * @return UpsertOptions
     */
    public function preserveExpiry(bool $shouldPreserve): UpsertOptions
    {
        $this->preserveExpiry = $shouldPreserve;
        return $this;
    }

    /**
     * Sets the durability level to enforce when writing the document.
     *
     * @param string $level the durability level to enforce
     * @param int|null $timeoutSeconds
     * @return UpsertOptions
     */
    public function durabilityLevel(string $level, ?int $timeoutSeconds): UpsertOptions
    {
        $this->durabilityLevel = $level;
        $this->durabilityTimeoutSeconds = $timeoutSeconds;
        return $this;
    }

    /**
     * Associate custom transcoder with the request.
     *
     * @param callable $arg encoding function with signature (returns tuple of bytes, flags and datatype):
     *
     *   `function encoder($value): [string $bytes, int $flags, int $datatype]`
     */
    public function encoder(callable $arg): UpsertOptions
    {
        return $this;
    }

    public function export(): array
    {
        return [
            'timeoutMilliseconds' => $this->timeoutMilliseconds,
            'expirySeconds' => $this->expirySeconds,
            'preserveExpiry' => $this->preserveExpiry,
            'durabilityLevel' => $this->durabilityLevel,
            'durabilityTimeoutSeconds' => $this->durabilityTimeoutSeconds,
        ];
    }
}
