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
 * Interface for results created by the get operation.
 */
class GetResult extends Result
{
    private Transcoder $transcoder;
    private ?int $expiry = null;
    private string $value;
    private int $flags;

    /**
     * @internal
     *
     * @param array $response
     * @param Transcoder $transcoder
     *
     * @since 4.0.0
     */
    public function __construct(array $response, Transcoder $transcoder)
    {
        parent::__construct($response);
        $this->transcoder = $transcoder;
        $this->flags = $response["flags"];
        $this->value = $response["value"];
        if (array_key_exists("expiry", $response)) {
            $this->expiry = $response["expiry"];
        }
    }

    /**
     * Returns the content of the document decoded using associated transcoder
     *
     * @return mixed
     * @since 4.0.0
     */
    public function content()
    {
        return $this->transcoder->decode($this->value, $this->flags);
    }

    /**
     * Returns the content of the document decoded using custom transcoder
     *
     * @return mixed
     * @since 4.0.0
     */
    public function contentAs(Transcoder $transcoder, ?int $overrideFlags = null)
    {
        return $transcoder->decode($this->value, $overrideFlags == null ? $this->flags : $overrideFlags);
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
        if ($this->expiry == null || $this->expiry == 0) {
            return null;
        }
        return DateTimeImmutable::createFromFormat("U", sprintf("%d", $this->expiry)) ?: null;
    }
}
