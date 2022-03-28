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
 * Collection is an object containing functionality for performing KeyValue operations against the server.
 */
class Collection
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
     * Get the name of the collection.
     *
     * @return string
     * @since 4.0.0
     */
    public function name(): string
    {
        return $this->name;
    }

    /**
     * Gets a document from the server.
     *
     * This can take 3 paths:
     * * a standard full document fetch,
     * * a subdocument full document fetch also fetching document expiry (when withExpiry is set),
     * * or a subdocument fetch (when projections are used).
     *
     * @param string $id the key of the document to fetch
     * @param GetOptions|null $options the options to use for the operation
     * @return GetResult
     * @throws UnsupportedOperationException
     * @since 4.0.0
     */
    public function get(string $id, GetOptions $options = null): GetResult
    {
        $response = Extension\documentGet($this->core, $this->bucketName, $this->scopeName, $this->name, $id, GetOptions::export($options));
        return new GetResult($response, GetOptions::getTranscoder($options));
    }

    /**
     * Checks if a document exists on the server.
     *
     * @param string $id the key of the document to check if exists
     * @param ExistsOptions|null $options the options to use for the operation
     * @return ExistsResult
     * @throws UnsupportedOperationException
     * @since 4.0.0
     */
    public function exists(string $id, ExistsOptions $options = null): ExistsResult
    {
        throw new UnsupportedOperationException();
    }

    /**
     * Gets a document from the server, locking the document so that no other processes can
     * perform mutations against it.
     *
     * @param string $id the key of the document to get
     * @param int $lockTime the length of time to lock the document in ms
     * @param GetAndLockOptions|null $options the options to use for the operation
     * @return GetResult
     * @throws UnsupportedOperationException
     * @since 4.0.0
     */
    public function getAndLock(string $id, int $lockTime, GetAndLockOptions $options = null): GetResult
    {
        throw new UnsupportedOperationException();
    }

    /**
     * Gets a document from the server and simultaneously updates its expiry time.
     *
     * @param string $id the key of the document
     * @param int $expiry the length of time to update the expiry to in ms
     * @param GetAndTouchOptions|null $options the options to use for the operation
     * @return GetResult
     * @throws UnsupportedOperationException
     * @since 4.0.0
     */
    public function getAndTouch(string $id, int $expiry, GetAndTouchOptions $options = null): GetResult
    {
        throw new UnsupportedOperationException();
    }

    /**
     * Gets a document from any replica server in the cluster.
     *
     * @param string $id the key of the document
     * @param GetAnyReplicaOptions|null $options the options to use for the operation
     * @return GetReplicaResult
     * @throws UnsupportedOperationException
     * @since 4.0.0
     */
    public function getAnyReplica(string $id, GetAnyReplicaOptions $options = null): GetReplicaResult
    {
        throw new UnsupportedOperationException();
    }

    /**
     * Gets a document from the active server and all replica servers in the cluster.
     * Returns an array of documents, one per server.
     *
     * @param string $id the key of the document
     * @param GetAllReplicasOptions|null $options the options to use for the operation
     * @return array
     * @throws UnsupportedOperationException
     * @since 4.0.0
     */
    public function getAllReplicas(string $id, GetAllReplicasOptions $options = null): array
    {
        throw new UnsupportedOperationException();
    }

    /**
     * Creates a document if it doesn't exist, otherwise updates it.
     *
     * @param string $id the key of the document
     * @param mixed $value the value to use for the document
     * @param UpsertOptions|null $options the options to use for the operation
     * @return MutationResult
     * @since 4.0.0
     */
    public function upsert(string $id, $value, UpsertOptions $options = null): MutationResult
    {
        $encoded = UpsertOptions::encodeDocument($options, $value);
        $response = Extension\documentUpsert($this->core, $this->bucketName, $this->scopeName, $this->name, $id, $encoded[0], $encoded[1], UpsertOptions::export($options));
        return new MutationResult($response);
    }

    /**
     * Inserts a document if it doesn't exist, errors if it does exist.
     *
     * @param string $id the key of the document
     * @param mixed $value the value to use for the document
     * @param InsertOptions|null $options the options to use for the operation
     * @return MutationResult
     * @throws UnsupportedOperationException
     * @since 4.0.0
     */
    public function insert(string $id, $value, InsertOptions $options = null): MutationResult
    {
        throw new UnsupportedOperationException();
    }

    /**
     * Replaces a document if it exists, errors if it doesn't exist.
     *
     * @param string $id the key of the document
     * @param mixed $value the value to use for the document
     * @param ReplaceOptions|null $options the options to use for the operation
     * @return MutationResult
     * @throws UnsupportedOperationException
     * @since 4.0.0
     */
    public function replace(string $id, $value, ReplaceOptions $options = null): MutationResult
    {
        throw new UnsupportedOperationException();
    }

    /**
     * Removes a document.
     *
     * @param string $id the key of the document
     * @param RemoveOptions|null $options the options to use for the operation
     * @return MutationResult
     * @throws UnsupportedOperationException
     * @since 4.0.0
     */
    public function remove(string $id, RemoveOptions $options = null): MutationResult
    {
        throw new UnsupportedOperationException();
    }

    /**
     * Unlocks a document which was locked using getAndLock. This frees the document to be
     * modified by other processes.
     *
     * @param string $id the key of the document
     * @param string $cas the current cas value of the document
     * @param UnlockOptions|null $options the options to use for the operation
     * @return Result
     * @throws UnsupportedOperationException
     * @since 4.0.0
     */
    public function unlock(string $id, string $cas, UnlockOptions $options = null): Result
    {
        throw new UnsupportedOperationException();
    }

    /**
     * Touches a document, setting a new expiry time.
     *
     * @param string $id the key of the document
     * @param int $expiry the expiry time for the document in ms
     * @param TouchOptions|null $options the options to use for the operation
     * @return MutationResult
     * @throws UnsupportedOperationException
     * @since 4.0.0
     */
    public function touch(string $id, int $expiry, TouchOptions $options = null): MutationResult
    {
        throw new UnsupportedOperationException();
    }

    /**
     * Performs a set of subdocument lookup operations against the document.
     *
     * @param string $id the key of the document
     * @param array $specs the LookupInSpecs to perform against the document
     * @param LookupInOptions|null $options the options to use for the operation
     * @return LookupInResult
     * @throws UnsupportedOperationException
     * @since 4.0.0
     */
    public function lookupIn(string $id, array $specs, LookupInOptions $options = null): LookupInResult
    {
        throw new UnsupportedOperationException();
    }

    /**
     * Performs a set of subdocument lookup operations against the document.
     *
     * @param string $id the key of the document
     * @param array $specs the MutateInSpecs to perform against the document
     * @param MutateInOptions|null $options the options to use for the operation
     * @return MutateInResult
     * @throws UnsupportedOperationException
     * @since 4.0.0
     */
    public function mutateIn(string $id, array $specs, MutateInOptions $options = null): MutateInResult
    {
        throw new UnsupportedOperationException();
    }

    /**
     * Retrieves a group of documents. If the document does not exist, it will not raise an exception, but rather fill
     * non-null value in error() property of the corresponding result object.
     *
     * @param array $ids array of IDs, organized like this ["key1", "key2", ...]
     * @param RemoveOptions|null $options the options to use for the operation
     * @return array array of GetResult, one for each of the entries
     * @throws UnsupportedOperationException
     * @since 4.0.0
     */
    public function getMulti(array $ids, RemoveOptions $options = null): array
    {
        throw new UnsupportedOperationException();
    }

    /**
     * Removes a group of documents. If second element of the entry (CAS) is null, then the operation will
     * remove the document unconditionally.
     *
     * @param array $entries array of arrays, organized like this
     *   [["key1", "encodedCas1"], ["key2", , "encodedCas2"], ...] or ["key1", "key2", ...]
     * @param RemoveOptions|null $options the options to use for the operation
     * @return array array of MutationResult, one for each of the entries
     * @throws UnsupportedOperationException
     * @since 4.0.0
     */
    public function removeMulti(array $entries, RemoveOptions $options = null): array
    {
        throw new UnsupportedOperationException();
    }

    /**
     * Creates a group of documents if they don't exist, otherwise updates them.
     *
     * @param array $entries array of arrays, organized like this [["key1", $value1], ["key2", $value2], ...]
     * @param UpsertOptions|null $options the options to use for the operation
     * @return array array of MutationResult, one for each of the entries
     * @throws UnsupportedOperationException
     * @since 4.0.0
     */
    public function upsertMulti(array $entries, UpsertOptions $options = null): array
    {
        throw new UnsupportedOperationException();
    }

    /**
     * Creates and returns a BinaryCollection object for use with binary type documents.
     *
     * @return BinaryCollection
     * @since 4.0.0
     */
    public function binary(): BinaryCollection
    {
        return new BinaryCollection($this->name, $this->scopeName, $this->bucketName, $this->core);
    }
}
