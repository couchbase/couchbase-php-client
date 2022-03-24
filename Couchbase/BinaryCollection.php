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

use Couchbase\Exception\UnsupportedOperationException;

/**
 * BinaryCollection is an object containing functionality for performing KeyValue operations against the server with binary documents.
 */
class BinaryCollection
{
    private string $bucketName;
    private string $scopeName;
    private string $name;
    private $core;

    /**
     * @private
     * @param string $name
     * @param string $scopeName
     * @param string $bucketName
     * @param $core
     */
    public function __construct(string $name, string $scopeName, string $bucketName, $core)
    {
        $this->name = $name;
        $this->scopeName = $scopeName;
        $this->bucketName = $bucketName;
        $this->core = $core;
    }

    /**
     * Get the name of the binary collection.
     *
     * @return string
     * @since 4.0.0
     */
    public function name(): string
    {
        return $this->name;
    }

    /**
     * Appends a value to a document.
     *
     * @param string $id the key of the document
     * @param string $value the value to append
     * @param AppendOptions|null $options the options to use for the operation
     * @return MutationResult
     * @throws UnsupportedOperationException
     * @since 4.0.0
     */
    public function append(string $id, string $value, AppendOptions $options = null): MutationResult
    {
        throw new UnsupportedOperationException();
    }

    /**
     * Prepends a value to a document.
     *
     * @param string $id the key of the document
     * @param string $value the value to prepend
     * @param PrependOptions|null $options the options to use for the operation
     * @return MutationResult
     * @throws UnsupportedOperationException
     * @since 4.0.0
     */
    public function prepend(string $id, string $value, PrependOptions $options = null): MutationResult
    {
        throw new UnsupportedOperationException();
    }

    /**
     * Increments a counter document by a value.
     *
     * @param string $id the key of the document
     * @param IncrementOptions|null $options the options to use for the operation
     * @return CounterResult
     * @throws UnsupportedOperationException
     * @since 4.0.0
     */
    public function increment(string $id, IncrementOptions $options = null): CounterResult
    {
        throw new UnsupportedOperationException();
    }

    /**
     * Decrements a counter document by a value.
     *
     * @param string $id the key of the document
     * @param DecrementOptions|null $options the options to use for the operation
     * @return CounterResult
     * @throws UnsupportedOperationException
     * @since 4.0.0
     */
    public function decrement(string $id, DecrementOptions $options = null): CounterResult
    {
        throw new UnsupportedOperationException();
    }
}
