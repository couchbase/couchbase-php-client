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

use Couchbase\Exception\CouchbaseException;
use Couchbase\Exception\PathNotFoundException;
use DateTimeImmutable;
use DateTimeInterface;
use OutOfBoundsException;

/**
 * Interface for results created by the lookupIn operation.
 */
class LookupInResult extends Result
{
    private Transcoder $transcoder;
    private array $fields;

    /**
     * @param array $response raw response from the extension
     *
     * @internal
     *
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
     *
     * @return mixed
     * @throws OutOfBoundsException
     * @throws PathNotFoundException
     * @since 4.0.0
     */
    public function content(int $index)
    {
        if (array_key_exists($index, $this->fields)) {
            $field = $this->fields[$index];
            if (!array_key_exists('exists', $field) || !$field['exists']) {
                throw new PathNotFoundException(sprintf("LookupIn path is not found for index: %d", $index));
            }
            return $this->transcoder->decode($field['value'], 0);
        }
        throw new OutOfBoundsException(sprintf("LookupIn result index is out of bounds: %d", $index));
    }

    /**
     * @param string $path
     *
     * @return mixed
     * @throws OutOfBoundsException
     * @throws PathNotFoundException
     * @since 4.0.0
     */
    public function contentByPath(string $path)
    {
        foreach ($this->fields as $field) {
            if (array_key_exists('path', $field)) {
                if ($field['path'] == $path) {
                    if (!array_key_exists('exists', $field) || !$field['exists']) {
                        throw new PathNotFoundException(sprintf("LookupIn path is not found for path: %s", $path));
                    }
                    return $this->transcoder->decode($field['value'], 0);
                }
            }
        }
        throw new OutOfBoundsException(sprintf("LookupIn result does not have entry for path: %s", $path));
    }

    /**
     * Returns whether the path at the index specified exists
     *
     * @param int $index the index to check for existence
     *
     * @return bool
     * @since 4.0.0
     */
    public function exists(int $index): bool
    {
        if (array_key_exists($index, $this->fields)) {
            if (array_key_exists('exists', $this->fields[$index])) {
                return $this->fields[$index]['exists'];
            }
        }
        return false;
    }

    /**
     * @param string $path
     *
     * @return bool
     * @since 4.0.0
     */
    public function existsByPath(string $path): bool
    {
        foreach ($this->fields as $field) {
            if ($field['path'] == $path) {
                if (array_key_exists('exists', $field)) {
                    return $field['exists'];
                }
            }
        }
        return false;
    }

    /**
     * @param int $index
     *
     * @return string|null
     * @since 4.0.0
     */
    public function path(int $index): ?string
    {
        if (array_key_exists($index, $this->fields)) {
            if (array_key_exists('path', $this->fields[$index])) {
                return $this->fields[$index]['path'];
            }
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
        try {
            $expiry = $this->contentByPath(LookupInMacro::EXPIRY_TIME);
            if ($expiry != null) {
                return DateTimeImmutable::createFromFormat("U", sprintf("%d", $expiry)) ?: null;
            }
        } catch (OutOfBoundsException $e) {
            return null;
        }
        return null;
    }
}
