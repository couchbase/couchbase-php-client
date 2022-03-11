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

/**
 * BinaryCollection is an object containing functionality for performing KeyValue operations against the server with binary documents.
 */
class BinaryCollection
{
    /**
     * Get the name of the binary collection.
     *
     * @return string
     */
    public function name(): string
    {
    }

    /**
     * Appends a value to a document.
     *
     * @param string $id the key of the document
     * @param string $value the value to append
     * @param AppendOptions $options the options to use for the operation
     * @return MutationResult
     */
    public function append(string $id, string $value, AppendOptions $options = null): MutationResult
    {
    }

    /**
     * Prepends a value to a document.
     *
     * @param string $id the key of the document
     * @param string $value the value to prepend
     * @param PrependOptions $options the options to use for the operation
     * @return MutationResult
     */
    public function prepend(string $id, string $value, PrependOptions $options = null): MutationResult
    {
    }

    /**
     * Increments a counter document by a value.
     *
     * @param string $id the key of the document
     * @param IncrementOptions $options the options to use for the operation
     * @return CounterResult
     */
    public function increment(string $id, IncrementOptions $options = null): CounterResult
    {
    }

    /**
     * Decrements a counter document by a value.
     *
     * @param string $id the key of the document
     * @param DecrementOptions $options the options to use for the operation
     * @return CounterResult
     */
    public function decrement(string $id, DecrementOptions $options = null): CounterResult
    {
    }
}
