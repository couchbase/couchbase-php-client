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

/**
 * Interface for results created by the scan operation.
 */
class ScanResult extends Result
{
    private Transcoder $transcoder;
    private bool $idsOnly;
    private ?int $flags = null;
    private ?string $value = null;
    private ?int $expiry = null;

    /**
     * @internal
     *
     * @param array $responses
     * @param Transcoder $transcoder
     *
     * @since 4.1.6
     */
    public function __construct(array $responses, Transcoder $transcoder)
    {
        parent::__construct($responses);
        $this->transcoder = $transcoder;
        $this->idsOnly = $responses['idsOnly'];
        if (array_key_exists('flags', $responses)) {
            $this->flags = $responses['flags'];
        }
        if (array_key_exists('value', $responses)) {
            $this->value = $responses['value'];
        }
        if (array_key_exists('expiry', $responses)) {
            $this->expiry = $responses['expiry'];
        }
    }

    /**
     * Returns whether only ids are returned from the scan operation
     *
     * @return bool
     * @since 4.1.6
     */
    public function idsOnly(): bool
    {
        return $this->idsOnly;
    }

    /**
     * Returns the content of the document decoded using associated transcoder
     *
     * @return mixed
     * @since 4.1.6
     */
    public function content()
    {
        return $this->transcoder->decode($this->value, $this->flags);
    }

    /**
     * Returns the content of the document decoded using custom transcoder
     *
     * @return mixed
     * @since 4.1.6
     */
    public function contentAs(Transcoder $transcoder, ?int $overrideFlags = null)
    {
        return $transcoder->decode($this->value, $overrideFlags == null ? $this->flags : $overrideFlags);
    }

    /**
     * Returns the document expiration time or null if the document does not expire.
     *
     * @return DateTimeInterface|null
     * @since 4.1.6
     */
    public function expiryTime(): ?DateTimeInterface
    {
        if ($this->expiry == null || $this->expiry == 0) {
            return null;
        }
        return DateTimeImmutable::createFromFormat("U", sprintf("%d", $this->expiry)) ?: null;
    }
}
