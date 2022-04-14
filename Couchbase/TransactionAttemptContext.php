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

use Couchbase\Exception\TransactionException;
use Couchbase\Exception\UnsupportedOperationException;

class TransactionAttemptContext
{
    /**
     * @var resource
     */
    private $transaction;

    /**
     * @param resource $transaction
     *
     * @internal
     */
    public function __construct($transaction)
    {
        $this->transaction = $transaction;
    }

    /**
     * Retrieves the value of a document from the collection.
     *
     * @param Collection $collection The collection the document lives in.
     * @param string $id The document key to retrieve.
     *
     * @return TransactionGetResult
     * @since 4.0.0
     */
    public function get(Collection $collection, string $id): TransactionGetResult
    {
        $response = Extension\transactionGet(
            $this->transaction,
            $collection->bucketName(),
            $collection->scopeName(),
            $collection->name(),
            $id
        );

        return new TransactionGetResult($response, GetOptions::getTranscoder(null));
    }

    /**
     * Inserts a new document to the collection, failing if the document already exists.
     *
     * @param Collection $collection The collection the document lives in.
     * @param string $id The document key to insert.
     * @param mixed $value the document content to insert
     *
     * @return TransactionGetResult
     * @since 4.0.0
     */
    public function insert(Collection $collection, string $id, $value): TransactionGetResult
    {
        $encoded = InsertOptions::encodeDocument(null, $value);
        $response = Extension\transactionInsert(
            $this->transaction,
            $collection->bucketName(),
            $collection->scopeName(),
            $collection->name(),
            $id,
            $encoded[0] /* ignore flags */
        );

        return new TransactionGetResult($response, GetOptions::getTranscoder(null));
    }

    /**
     * Replaces a document in a collection
     *
     * @param TransactionGetResult $document the document to replace
     * @param mixed $value the document content to replace
     *
     * @return TransactionGetResult
     * @since 4.0.0
     */
    public function replace(TransactionGetResult $document, $value): TransactionGetResult
    {
        $encoded = ReplaceOptions::encodeDocument(null, $value);
        $response = Extension\transactionReplace(
            $this->transaction,
            $document->export(),
            $encoded[0] /* ignore flags */
        );

        return new TransactionGetResult($response, GetOptions::getTranscoder(null));
    }

    /**
     * Removes a document from a collection.
     *
     * @param TransactionGetResult $document
     *
     * @return void
     * @since 4.0.0
     */
    public function remove(TransactionGetResult $document)
    {
        Extension\transactionRemove(
            $this->transaction,
            $document->export(),
        );
    }

    /**
     * Executes a query in the context of this transaction.
     *
     * @param string $statement
     * @param TransactionQueryOptions|null $options
     *
     * @return mixed
     * @since 4.0.0
     */
    public function query(string $statement, ?TransactionQueryOptions $options = null)
    {
        $result = Extension\transactionQuery($this->transaction, $statement, TransactionQueryOptions::export($options));

        return new QueryResult($result, TransactionQueryOptions::getTranscoder($options));
    }
}
