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
 * Interface for results created by the lookupIn operation.
 */
class LookupInResult extends Result
{
    private Transcoder $transcoder;
    private array $fields;

    /**
     * @private
     * @param array $response raw response from the extension
     * @since 4.0.0
     */
    public function __construct(array $response, Transcoder $transcoder)
    {
        parent::__construct($response);
        $this->transcoder = $transcoder;
        $this->fields = $response['fields'];
    }

    /**
     * Returns the value located at the index specified
     *
     * @param int $index the index to retrieve content from
     * @return mixed|null
     */
    public function content(int $index)
    {
        if (array_key_exists($index, $this->fields)) {
            return $this->transcoder->decode($this->fields[$index]['value'], 0);
        }
        return null;
    }

    /**
     * @param string $path
     * @return mixed|null
     * @since 4.0.0
     */
    public function contentByPath(string $path)
    {
        foreach ($this->fields as $field) {
            if ($field['path'] == $path) {
                return $this->transcoder->decode($field['value'], 0);
            }
        }
        return null;
    }

    /**
     * Returns whether the path at the index specified exists
     *
     * @param int $index the index to check for existence
     * @return bool
     */
    public function exists(int $index): bool
    {
        if (array_key_exists($index, $this->fields)) {
            return $this->fields[$index]['exists'];
        }
        return false;
    }

    /**
     * @param string $path
     * @return bool
     * @since 4.0.0
     */
    public function existsByPath(string $path): bool
    {
        foreach ($this->fields as $field) {
            if ($field['path'] == $path) {
                return $field['exists'];
            }
        }
        return false;
    }

    /**
     * @param int $index
     * @return string|null
     * @since 4.0.0
     */
    public function path(int $index): ?string
    {
        if (array_key_exists($index, $this->fields)) {
            return $this->fields[$index]['path'];
        }
        return null;
    }

    /**
     * @param int $index
     * @return int|null
     * @since 4.0.0
     */
    public function errorCode(int $index): ?int
    {
        if (array_key_exists($index, $this->fields)) {
            return $this->fields[$index]['errorCode'];
        }
        return null;
    }

    /**
     * @param int $index
     * @return string|null
     * @since 4.0.0
     */
    public function errorMessage(int $index): ?string
    {
        if (array_key_exists($index, $this->fields)) {
            return $this->fields[$index]['errorMessage'];
        }
        return null;
    }

    /**
     * Returns any error code for the path at the index specified
     *
     * @param int $index the index to retrieve the error code for
     * @return ?int
     */
    public function status(int $index): ?int
    {
        if (array_key_exists($index, $this->fields)) {
            return $this->fields[$index]['status'];
        }
        return null;
    }

    /**
     * Returns the document expiration time or null if the document does not expire.
     *
     * Note, that this function will return expiry only when LookupInOptions had withExpiry set to true.
     *
     * @return DateTimeInterface|null
     */
    public function expiryTime(): ?DateTimeInterface
    {
        $expiry = $this->contentByPath('$document.exptime');
        if ($expiry != null) {
            return DateTimeImmutable::createFromFormat("U", sprintf("%d", $expiry)) ?: null;
        }
        return null;
    }
}
