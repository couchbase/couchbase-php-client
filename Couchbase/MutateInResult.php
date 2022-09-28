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
use OutOfBoundsException;

/**
 * Results created by the mutateIn operation.
 */
class MutateInResult extends MutationResult
{
    private bool $deleted;
    private array $fields;

    /**
     * @param array $response raw response from the extension
     *
     * @internal
     *
     * @since 4.0.0
     */
    public function __construct(array $response)
    {
        parent::__construct($response);
        $this->deleted = $response['deleted'];
        $this->fields = $response['fields'];
    }

    /**
     * @return bool
     * @since 4.0.0
     */
    public function isDeleted(): bool
    {
        return $this->deleted;
    }

    /**
     * Returns any value located at the index specified
     *
     * @param int $index the index to retrieve content from
     *
     * @return mixed
     * @throws OutOfBoundsException
     * @since 4.0.0
     */
    public function content(int $index)
    {
        if (array_key_exists($index, $this->fields)) {
            $field = $this->fields[$index];
            return JsonTranscoder::getInstance()->decode($field['value'], 0);
        }
        throw new OutOfBoundsException(sprintf("MutateIn result index is out of bounds: %d", $index));
    }

    /**
     * @param string $path
     *
     * @return mixed
     * @throws OutOfBoundsException
     * @since 4.0.0
     */
    public function contentByPath(string $path)
    {
        foreach ($this->fields as $field) {
            if ($field['path'] == $path) {
                return JsonTranscoder::getInstance()->decode($field['value'], 0);
            }
        }
        throw new OutOfBoundsException(sprintf("MutateIn result does not have entry for path: %s", $path));
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
            return $this->fields[$index]['path'];
        }
        throw new OutOfBoundsException(sprintf("MutateIn result index is out of bounds: %d", $index));
    }
}
