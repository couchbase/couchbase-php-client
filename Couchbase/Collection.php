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
 * Collection is an object containing functionality for performing KeyValue operations against the server.
 */
class Collection
{
    /**
     * Get the name of the collection.
     *
     * @return string
     */
    public function name(): string
    {
    }

    /**
     * Gets a document from the server.
     *
     * This can take 3 paths, a standard full document fetch, a subdocument full document fetch also
     * fetching document expiry (when withExpiry is set), or a subdocument fetch (when projections are
     * used).
     *
     * @param string $id the key of the document to fetch
     * @param GetOptions $options the options to use for the operation
     * @return GetResult
     */
    public function get(string $id, GetOptions $options = null): GetResult
    {
    }

    /**
     * Checks if a document exists on the server.
     *
     * @param string $id the key of the document to check if exists
     * @param ExistsOptions $options the options to use for the operation
     * @return ExistsResult
     */
    public function exists(string $id, ExistsOptions $options = null): ExistsResult
    {
    }

    /**
     * Gets a document from the server, locking the document so that no other processes can
     * perform mutations against it.
     *
     * @param string $id the key of the document to get
     * @param int $lockTime the length of time to lock the document in ms
     * @param GetAndLockOptions $options the options to use for the operation
     * @return GetResult
     */
    public function getAndLock(string $id, int $lockTime, GetAndLockOptions $options = null): GetResult
    {
    }

    /**
     * Gets a document from the server and simultaneously updates its expiry time.
     *
     * @param string $id the key of the document
     * @param int $expiry the length of time to update the expiry to in ms
     * @param GetAndTouchOptions $options the options to use for the operation
     * @return GetResult
     */
    public function getAndTouch(string $id, int $expiry, GetAndTouchOptions $options = null): GetResult
    {
    }

    /**
     * Gets a document from any replica server in the cluster.
     *
     * @param string $id the key of the document
     * @param GetAnyReplicaOptions $options the options to use for the operation
     * @return GetReplicaResult
     */
    public function getAnyReplica(string $id, GetAnyReplicaOptions $options = null): GetReplicaResult
    {
    }

    /**
     * Gets a document from the active server and all replica servers in the cluster.
     * Returns an array of documents, one per server.
     *
     * @param string $id the key of the document
     * @param GetAllReplicasOptions $options the options to use for the operation
     * @return array
     */
    public function getAllReplicas(string $id, GetAllReplicasOptions $options = null): array
    {
    }

    /**
     * Creates a document if it doesn't exist, otherwise updates it.
     *
     * @param string $id the key of the document
     * @param mixed $value the value to use for the document
     * @param UpsertOptions $options the options to use for the operation
     * @return MutationResult
     */
    public function upsert(string $id, $value, UpsertOptions $options = null): MutationResult
    {
    }

    /**
     * Inserts a document if it doesn't exist, errors if it does exist.
     *
     * @param string $id the key of the document
     * @param mixed $value the value to use for the document
     * @param InsertOptions $options the options to use for the operation
     * @return MutationResult
     */
    public function insert(string $id, $value, InsertOptions $options = null): MutationResult
    {
    }

    /**
     * Replaces a document if it exists, errors if it doesn't exist.
     *
     * @param string $id the key of the document
     * @param mixed $value the value to use for the document
     * @param ReplaceOptions $options the options to use for the operation
     * @return MutationResult
     */
    public function replace(string $id, $value, ReplaceOptions $options = null): MutationResult
    {
    }

    /**
     * Removes a document.
     *
     * @param string $id the key of the document
     * @param RemoveOptions $options the options to use for the operation
     * @return MutationResult
     */
    public function remove(string $id, RemoveOptions $options = null): MutationResult
    {
    }

    /**
     * Unlocks a document which was locked using getAndLock. This frees the document to be
     * modified by other processes.
     *
     * @param string $id the key of the document
     * @param string $cas the current cas value of the document
     * @param UnlockOptions $options the options to use for the operation
     * @return Result
     */
    public function unlock(string $id, string $cas, UnlockOptions $options = null): Result
    {
    }

    /**
     * Touches a document, setting a new expiry time.
     *
     * @param string $id the key of the document
     * @param int $expiry the expiry time for the document in ms
     * @param TouchOptions $options the options to use for the operation
     * @return MutationResult
     */
    public function touch(string $id, int $expiry, TouchOptions $options = null): MutationResult
    {
    }

    /**
     * Performs a set of subdocument lookup operations against the document.
     *
     * @param string $id the key of the document
     * @param array $specs the LookupInSpecs to perform against the document
     * @param LookupInOptions $options the options to use for the operation
     * @return LookupInResult
     */
    public function lookupIn(string $id, array $specs, LookupInOptions $options = null): LookupInResult
    {
    }

    /**
     * Performs a set of subdocument lookup operations against the document.
     *
     * @param string $id the key of the document
     * @param array $specs the MutateInSpecs to perform against the document
     * @param MutateInOptions $options the options to use for the operation
     * @return MutateInResult
     */
    public function mutateIn(string $id, array $specs, MutateInOptions $options = null): MutateInResult
    {
    }

    /**
     * Retrieves a group of documents. If the document does not exist, it will not raise an exception, but rather fill
     * non-null value in error() property of the corresponding result object.
     *
     * @param array $ids array of IDs, organized like this ["key1", "key2", ...]
     * @param GetOptions $options the options to use for the operation
     * @return array array of GetResult, one for each of the entries
     */
    public function getMulti(array $ids, RemoveOptions $options = null): array
    {
    }

    /**
     * Removes a group of documents. If second element of the entry (CAS) is null, then the operation will
     * remove the document unconditionally.
     *
     * @param array $entries array of arrays, organized like this
     *   [["key1", "encodedCas1"], ["key2", , "encodedCas2"], ...] or ["key1", "key2", ...]
     * @param RemoveOptions $options the options to use for the operation
     * @return array array of MutationResult, one for each of the entries
     */
    public function removeMulti(array $entries, RemoveOptions $options = null): array
    {
    }

    /**
     * Creates a group of documents if they don't exist, otherwise updates them.
     *
     * @param array $entries array of arrays, organized like this [["key1", $value1], ["key2", $value2], ...]
     * @param UpsertOptions $options the options to use for the operation
     * @return array array of MutationResult, one for each of the entries
     */
    public function upsertMulti(array $entries, UpsertOptions $options = null): array
    {
    }

    /**
     * Creates and returns a BinaryCollection object for use with binary type documents.
     *
     * @return BinaryCollection
     */
    public function binary(): BinaryCollection
    {
    }
}
