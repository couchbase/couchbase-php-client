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

use DateTimeImmutable;
use DateTimeInterface;

class ExistsResult extends Result
{
    private bool $exists;
    private string $cas;
    private ?bool $deleted;
    private ?int $expiry;
    private ?int $flags;
    private ?string $sequenceNumber;

    /**
     * @internal
     *
     * @param array $response
     *
     * @since 4.0.0
     */
    public function __construct(array $response)
    {
        parent::__construct($response);
        $this->exists = $response["exists"];
        $this->cas = $response["cas"];
        if (array_key_exists("deleted", $response)) {
            $this->deleted = $response["deleted"];
        }
        if (array_key_exists("expiry", $response)) {
            $this->expiry = $response["expiry"];
        }
        if (array_key_exists("flags", $response)) {
            $this->flags = $response["flags"];
        }
        if (array_key_exists("sequenceNumber", $response)) {
            $this->sequenceNumber = $response["sequenceNumber"];
        }
    }

    /**
     * Returns whether the document exists
     *
     * @return bool
     * @since 4.0.0
     */
    public function exists(): bool
    {
        return $this->exists;
    }

    /**
     * Returns true if the document had been just deleted.
     *
     * @return bool|null
     * @since 4.0.0
     */
    public function deleted(): ?bool
    {
        return $this->deleted;
    }

    /**
     * @return string
     */
    public function cas(): string
    {
        return $this->cas;
    }

    /**
     * @return string|null
     */
    public function sequenceNumber(): ?string
    {
        return $this->sequenceNumber;
    }

    /**
     * @return int|null
     */
    public function flags(): ?int
    {
        return $this->flags;
    }

    /**
     * Returns the document expiration time or null if the document does not expire.
     *
     * Note, that this function will return expiry only when GetOptions had withExpiry set to true.
     *
     * @return DateTimeInterface|null
     * @since 4.0.0
     */
    public function expiryTime(): ?DateTimeInterface
    {
        if ($this->expiry == null) {
            return null;
        }
        try {
            return new DateTimeImmutable($this->expiry);
        } catch (\Exception $e) {
            return null;
        }
    }
}
