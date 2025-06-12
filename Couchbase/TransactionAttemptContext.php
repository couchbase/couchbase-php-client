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
     * @param TransactionGetOptions|null $options The options to use for the operation
     *
     * @return TransactionGetResult
     * @since 4.0.0
     */
    public function get(Collection $collection, string $id, TransactionGetOptions $options = null): TransactionGetResult
    {
        $function = COUCHBASE_EXTENSION_NAMESPACE . '\\transactionGet';
        $response = $function(
            $this->transaction,
            $collection->bucketName(),
            $collection->scopeName(),
            $collection->name(),
            $id
        );

        return new TransactionGetResult($response, TransactionGetOptions::getTranscoder($options));
    }

    /**
     * Get a document copy from the selected server group.
     *
     * Fetch the document contents, in the form of a @ref transaction_get_result.
     * It might be either replica or active copy of the document. One of the use
     * cases for this method is to save on network costs by deploying SDK in the
     * same availability zone as corresponding server group of the nodes.
     *
     * @param Collection $collection The collection the document lives in.
     * @param string $id The document key to retrieve
     * @param TransactionGetReplicaOptions|null $options The options to use for the operation
     *
     * @return TransactionGetResult
     * @since 4.2.6
     */
    public function getReplicaFromPreferredServerGroup(Collection $collection, string $id, TransactionGetReplicaOptions $options = null): TransactionGetResult
    {
        $function = COUCHBASE_EXTENSION_NAMESPACE . '\\transactionGetReplicaFromPreferredServerGroup';
        $response = $function(
            $this->transaction,
            $collection->bucketName(),
            $collection->scopeName(),
            $collection->name(),
            $id
        );

        return new TransactionGetResult($response, TransactionGetReplicaOptions::getTranscoder($options));
    }

    /**
     * Retrieves a group of documents.
     *
     * @param array<TransactionGetMultiSpec> $specs The specs describing each entry to fetch
     * @param TransactionGetMultiOptions|null $options The options to use for the operation
     *
     * @return TransactionGetMultiResult result that contains set of the documents.
     * @since 4.3.0
     */
    public function getMulti(array $specs, TransactionGetMultiOptions $options = null): TransactionGetMultiResult
    {
        $function = COUCHBASE_EXTENSION_NAMESPACE . '\\transactionGetMulti';
        $response = $function(
            $this->transaction,
            array_map(
                function ($spec) {
                    return TransactionGetMultiSpec::export($spec);
                },
                $specs
            ),
            TransactionGetMultiOptions::export($options),
        );

        return new TransactionGetMultiResult(
            $response,
            array_map(
                function ($index, $entry) use ($specs, $options) {
                    // use transcoder from the spec but fallback to one from the options
                    if ($entry != null) {
                        return TransactionGetMultiSpec::getTranscoder(
                            $specs,
                            $index,
                            TransactionGetMultiOptions::getTranscoder($options)
                        );
                    }
                    return null;
                },
                array_keys($response),
                array_values($response),
            )
        );
    }

    /**
     * Retrieves a group of documents, but give priority to copies from preferred server group.
     *
     * @param array<TransactionGetMultiReplicasFromPreferredServerGroupSpec> $specs The specs describing each entry to fetch
     * @param TransactionGetMultiReplicasFromPreferredServerGroupOptions|null $options The options to use for the operation
     *
     * @return TransactionGetMultiReplicasFromPreferredServerGroupResult result that contains set of the documents.
     * @since 4.3.0
     */
    public function getMultiReplicasFromPreferredServerGroup(array $specs, TransactionGetMultiReplicasFromPreferredServerGroupOptions $options = null): TransactionGetMultiReplicasFromPreferredServerGroupResult
    {
        $function = COUCHBASE_EXTENSION_NAMESPACE . '\\transactionGetMultiReplicasFromPreferredServerGroup';
        $response = $function(
            $this->transaction,
            array_map(
                function ($spec) {
                    return TransactionGetMultiReplicasFromPreferredServerGroupSpec::export($spec);
                },
                $specs
            ),
            TransactionGetMultiReplicasFromPreferredServerGroupOptions::export($options),
        );

        return new TransactionGetMultiReplicasFromPreferredServerGroupResult(
            $response,
            array_map(
                function ($index, $entry) use ($specs, $options) {
                    // use transcoder from the spec but fallback to one from the options
                    if ($entry != null) {
                        return TransactionGetMultiReplicasFromPreferredServerGroupSpec::getTranscoder(
                            $specs,
                            $index,
                            TransactionGetMultiReplicasFromPreferredServerGroupOptions::getTranscoder($options)
                        );
                    }
                    return null;
                },
                array_keys($response),
                array_values($response),
            )
        );
    }

    /**
     * Inserts a new document to the collection, failing if the document already exists.
     *
     * @param Collection $collection The collection the document lives in.
     * @param string $id The document key to insert.
     * @param mixed $value the document content to insert
     * @param TransactionInsertOptions|null $options The options to use for the operation

     *
     * @return TransactionGetResult
     * @since 4.0.0
     */
    public function insert(Collection $collection, string $id, $value, TransactionInsertOptions $options = null): TransactionGetResult
    {
        $encoded = TransactionInsertOptions::encodeDocument($options, $value);
        $function = COUCHBASE_EXTENSION_NAMESPACE . '\\transactionInsert';
        $response = $function(
            $this->transaction,
            $collection->bucketName(),
            $collection->scopeName(),
            $collection->name(),
            $id,
            $encoded[0],
            $encoded[1],
        );

        return new TransactionGetResult($response, TransactionInsertOptions::getTranscoder($options));
    }

    /**
     * Replaces a document in a collection
     *
     * @param TransactionGetResult $document the document to replace
     * @param mixed $value the document content to replace
     * @param TransactionReplaceOptions|null $options The options to use for the operation
     *
     * @return TransactionGetResult
     * @since 4.0.0
     */
    public function replace(TransactionGetResult $document, $value, TransactionReplaceOptions $options = null): TransactionGetResult
    {
        $encoded = TransactionReplaceOptions::encodeDocument($options, $value);
        $function = COUCHBASE_EXTENSION_NAMESPACE . '\\transactionReplace';
        $response = $function(
            $this->transaction,
            $document->export(),
            $encoded[0],
            $encoded[1],
        );

        return new TransactionGetResult($response, TransactionReplaceOptions::getTranscoder($options));
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
        $function = COUCHBASE_EXTENSION_NAMESPACE . '\\transactionRemove';
        $function(
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
        $function = COUCHBASE_EXTENSION_NAMESPACE . '\\transactionQuery';
        $result = $function($this->transaction, $statement, TransactionQueryOptions::export($options));

        return new QueryResult($result, TransactionQueryOptions::getTranscoder($options));
    }
}
