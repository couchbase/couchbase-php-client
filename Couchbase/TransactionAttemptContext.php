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

class TransactionAttemptContext
{
    /**
     * @private
     */
    public function __construct(Transactions $transactions, ?TransactionOptions $options)
    {
    }

    /**
     * Retrieves the value of a document from the collection.
     *
     * @param Collection $collection The collection the document lives in.
     * @param string $id The document key to retrieve.
     * @return TransactionGetResult
     * @throws UnsupportedOperationException
     * @since 4.0.0
     */
    public function get(Collection $collection, string $id): TransactionGetResult
    {
        throw new UnsupportedOperationException();
    }

    /**
     * Inserts a new document to the collection, failing if the document already exists.
     *
     * @param Collection $collection The collection the document lives in.
     * @param string $id The document key to insert.
     * @param mixed $value the document content to insert
     * @return TransactionGetResult
     * @throws UnsupportedOperationException
     * @since 4.0.0
     */
    public function insert(Collection $collection, string $id, $value): TransactionGetResult
    {
        throw new UnsupportedOperationException();
    }

    /**
     * Replaces a document in a collection
     *
     * @param TransactionGetResult $document the document to replace
     * @param mixed $value the document content to replace
     * @return TransactionGetResult
     * @throws UnsupportedOperationException
     * @since 4.0.0
     */
    public function replace(TransactionGetResult $document, $value): TransactionGetResult
    {
        throw new UnsupportedOperationException();
    }

    /**
     * Removes a document from a collection.
     *
     * @param TransactionGetResult $document
     * @return void
     * @throws UnsupportedOperationException
     * @since 4.0.0
     */
    public function remove(TransactionGetResult $document)
    {
        throw new UnsupportedOperationException();
    }

    /**
     * Executes a query in the context of this transaction.
     *
     * @param string $statement
     * @param TransactionQueryOptions|null $options
     * @return mixed
     * @throws UnsupportedOperationException
     */
    public function query(string $statement, ?TransactionQueryOptions $options = null)
    {
        throw new UnsupportedOperationException();
    }
}
