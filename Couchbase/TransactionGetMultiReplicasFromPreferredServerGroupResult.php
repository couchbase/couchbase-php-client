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
use Couchbase\Exception\DocumentNotFoundException;

class TransactionGetMultiReplicasFromPreferredServerGroupResult
{
    private array $entries;
    private array $transcoders;

    /**
     * @internal
     * @since 4.3.0
     */
    public function __construct(array $response, array $transcoders)
    {
        $this->entries = $response;
        $this->transcoders = $transcoders;
    }

    /**
     * Returns the content of the document decoded using associated transcoder
     *
     * @param int $index the index that corresponds to spec position in request
     *
     * @return mixed
     * @since 4.3.0
     */
    public function content(int $index)
    {
        if ($this->exists($index)) {
            return $this->transcoders[$index]->decode($this->entries[$index]["value"], $this->entries[$index]["flags"]);
        } else {
            throw new DocumentNotFoundException("Document does not exist");
        }
    }

    /**
     * Check if the operation has document for the specific spec index.
     *
     * @param int $index the index that corresponds to spec position in request
     *
     * @return bool true, if there is a content for $index
     * @since 4.3.0
     */
    public function exists(int $index): bool
    {
        if ($index >= count($this->entries) || !array_key_exists($index, $this->entries)) {
            throw new InvalidArgumentException("Unknown index of TransactionGetMultiReplicasFromPreferredServerGroupResult");
        }
        return $this->entries[$index] != null;
    }

    /**
     * Returns the content of the document decoded using custom transcoder
     *
     * @return mixed
     * @since 4.3.0
     */
    public function contentAs(int $index, Transcoder $transcoder, ?int $overrideFlags = null)
    {
        if ($this->exists($index)) {
            return $transcoder->decode($this->entries[$index]["value"], $overrideFlags == null ? $overrideFlags : $this->entries[$index]["flags"]);
        } else {
            throw new DocumentNotFoundException("Document does not exist");
        }
    }
}
