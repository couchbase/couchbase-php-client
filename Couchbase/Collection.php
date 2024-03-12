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

use Couchbase\Exception\CasMismatchException;
use Couchbase\Exception\DocumentExistsException;
use Couchbase\Exception\DocumentIrretrievableException;
use Couchbase\Exception\DocumentNotFoundException;
use Couchbase\Exception\CouchbaseException;
use Couchbase\Exception\InvalidArgumentException;
use Couchbase\Exception\TimeoutException;
use Couchbase\Exception\UnsupportedOperationException;
use Couchbase\Management\CollectionQueryIndexManager;
use DateTimeInterface;

/**
 * Collection is an object containing functionality for performing KeyValue operations against the server.
 */
class Collection implements CollectionInterface
{
    private string $bucketName;
    private string $scopeName;
    private string $name;
    /**
     * @var resource
     */
    private $core;

    /**
     * @param string $name
     * @param string $scopeName
     * @param string $bucketName
     * @param resource $core
     *
     * @internal
     *
     * @since 4.0.0
     */
    public function __construct(string $name, string $scopeName, string $bucketName, $core)
    {
        $this->name = $name;
        $this->scopeName = $scopeName;
        $this->bucketName = $bucketName;
        $this->core = $core;
    }


    /**
     * Get the name of the bucket.
     *
     * @return string
     * @since 4.0.0
     */
    public function bucketName(): string
    {
        return $this->bucketName;
    }

    /**
     * Get the name of the scope.
     *
     * @return string
     * @since 4.0.0
     */
    public function scopeName(): string
    {
        return $this->scopeName;
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
     *
     * @return GetResult
     * @throws DocumentNotFoundException
     * @throws TimeoutException
     * @throws CouchbaseException
     * @since 4.0.0
     */
    public function get(string $id, GetOptions $options = null): GetResult
    {
        $response = Extension\documentGet(
            $this->core,
            $this->bucketName,
            $this->scopeName,
            $this->name,
            $id,
            GetOptions::export($options)
        );
        return new GetResult($response, GetOptions::getTranscoder($options));
    }

    /**
     * Checks if a document exists on the server.
     *
     * @param string $id the key of the document to check if exists
     * @param ExistsOptions|null $options the options to use for the operation
     *
     * @return ExistsResult
     * @throws TimeoutException
     * @throws CouchbaseException
     * @since 4.0.0
     */
    public function exists(string $id, ExistsOptions $options = null): ExistsResult
    {
        $response = Extension\documentExists(
            $this->core,
            $this->bucketName,
            $this->scopeName,
            $this->name,
            $id,
            ExistsOptions::export($options)
        );
        return new ExistsResult($response);
    }

    /**
     * Gets a document from the server, locking the document so that no other processes can
     * perform mutations against it.
     *
     * @param string $id the key of the document to get
     * @param int $lockTimeSeconds the length of time to lock the document in seconds
     * @param GetAndLockOptions|null $options the options to use for the operation
     *
     * @return GetResult
     * @throws DocumentNotFoundException
     * @throws TimeoutException
     * @throws CouchbaseException
     * @since 4.0.0
     */
    public function getAndLock(string $id, int $lockTimeSeconds, GetAndLockOptions $options = null): GetResult
    {
        $response = Extension\documentGetAndLock(
            $this->core,
            $this->bucketName,
            $this->scopeName,
            $this->name,
            $id,
            $lockTimeSeconds,
            GetAndLockOptions::export($options)
        );
        return new GetResult($response, GetAndLockOptions::getTranscoder($options));
    }

    /**
     * Gets a document from the server and simultaneously updates its expiry time.
     *
     * @param string $id the key of the document
     * @param int|DateTimeInterface $expiry the length of time to update the expiry to in seconds, or epoch timestamp
     * @param GetAndTouchOptions|null $options the options to use for the operation
     *
     * @return GetResult
     * @throws DocumentNotFoundException
     * @throws TimeoutException
     * @throws CouchbaseException
     * @since 4.0.0
     */
    public function getAndTouch(string $id, $expiry, GetAndTouchOptions $options = null): GetResult
    {
        if ($expiry instanceof DateTimeInterface) {
            $expirySeconds = $expiry->getTimestamp();
        } else {
            $expirySeconds = (int)$expiry;
        }
        $response = Extension\documentGetAndTouch(
            $this->core,
            $this->bucketName,
            $this->scopeName,
            $this->name,
            $id,
            $expirySeconds,
            GetAndTouchOptions::export($options)
        );
        return new GetResult($response, GetAndTouchOptions::getTranscoder($options));
    }

    /**
     * Gets a document from any replica server in the cluster.
     *
     * @param string $id the key of the document
     * @param GetAnyReplicaOptions|null $options the options to use for the operation
     *
     * @return GetReplicaResult
     *
     * @throws DocumentIrretrievableException
     * @throws CouchbaseException
     * @throws TimeoutException
     * @since 4.0.1
     */
    public function getAnyReplica(string $id, GetAnyReplicaOptions $options = null): GetReplicaResult
    {
        $response = Extension\documentGetAnyReplica(
            $this->core,
            $this->bucketName,
            $this->scopeName,
            $this->name,
            $id,
            GetAnyReplicaOptions::export($options)
        );
        return new GetReplicaResult($response, GetAnyReplicaOptions::getTranscoder($options));
    }

    /**
     * Gets a document from the active server and all replica servers in the cluster.
     * Returns an array of documents, one per server.
     *
     * @param string $id the key of the document
     * @param GetAllReplicasOptions|null $options the options to use for the operation
     *
     * @return array
     * @throws CouchbaseException
     * @throws TimeoutException
     * @since 4.0.0
     */
    public function getAllReplicas(string $id, GetAllReplicasOptions $options = null): array
    {
        $responses = Extension\documentGetAllReplicas(
            $this->core,
            $this->bucketName,
            $this->scopeName,
            $this->name,
            $id,
            GetAllReplicasOptions::export($options)
        );
        return array_map(
            function (array $response) use ($options) {
                return new GetReplicaResult($response, GetAllReplicasOptions::getTranscoder($options));
            },
            $responses
        );
    }

    /**
     * Creates a document if it doesn't exist, otherwise updates it.
     *
     * @param string $id the key of the document
     * @param mixed $value the value to use for the document
     * @param UpsertOptions|null $options the options to use for the operation
     *
     * @return MutationResult
     * @throws TimeoutException
     * @throws CouchbaseException
     * @since 4.0.0
     */
    public function upsert(string $id, $value, UpsertOptions $options = null): MutationResult
    {
        $encoded = UpsertOptions::encodeDocument($options, $value);
        $response = Extension\documentUpsert(
            $this->core,
            $this->bucketName,
            $this->scopeName,
            $this->name,
            $id,
            $encoded[0],
            $encoded[1],
            UpsertOptions::export($options)
        );
        return new MutationResult($response);
    }

    /**
     * Inserts a document if it doesn't exist, errors if it does exist.
     *
     * @param string $id the key of the document
     * @param mixed $value the value to use for the document
     * @param InsertOptions|null $options the options to use for the operation
     *
     * @return MutationResult
     * @throws DocumentExistsException
     * @throws TimeoutException
     * @throws CouchbaseException
     * @since 4.0.0
     */
    public function insert(string $id, $value, InsertOptions $options = null): MutationResult
    {
        $encoded = InsertOptions::encodeDocument($options, $value);
        $response = Extension\documentInsert(
            $this->core,
            $this->bucketName,
            $this->scopeName,
            $this->name,
            $id,
            $encoded[0],
            $encoded[1],
            InsertOptions::export($options)
        );
        return new MutationResult($response);
    }

    /**
     * Replaces a document if it exists, errors if it doesn't exist.
     *
     * @param string $id the key of the document
     * @param mixed $value the value to use for the document
     * @param ReplaceOptions|null $options the options to use for the operation
     *
     * @return MutationResult
     * @throws DocumentNotFoundException
     * @throws CasMismatchException
     * @throws TimeoutException
     * @throws CouchbaseException
     * @since 4.0.0
     */
    public function replace(string $id, $value, ReplaceOptions $options = null): MutationResult
    {
        $encoded = ReplaceOptions::encodeDocument($options, $value);
        $response = Extension\documentReplace(
            $this->core,
            $this->bucketName,
            $this->scopeName,
            $this->name,
            $id,
            $encoded[0],
            $encoded[1],
            ReplaceOptions::export($options)
        );
        return new MutationResult($response);
    }

    /**
     * Removes a document.
     *
     * @param string $id the key of the document
     * @param RemoveOptions|null $options the options to use for the operation
     *
     * @return MutationResult
     * @throws CasMismatchException
     * @throws TimeoutException
     * @throws CouchbaseException
     * @throws DocumentNotFoundException
     * @since 4.0.0
     */
    public function remove(string $id, RemoveOptions $options = null): MutationResult
    {
        $response = Extension\documentRemove(
            $this->core,
            $this->bucketName,
            $this->scopeName,
            $this->name,
            $id,
            RemoveOptions::export($options)
        );
        return new MutationResult($response);
    }

    /**
     * Unlocks a document which was locked using getAndLock. This frees the document to be
     * modified by other processes.
     *
     * @param string $id the key of the document
     * @param string $cas the current cas value of the document
     * @param UnlockOptions|null $options the options to use for the operation
     *
     * @return Result
     * @throws DocumentNotFoundException
     * @throws CasMismatchException
     * @throws TimeoutException
     * @throws CouchbaseException
     * @since 4.0.0
     */
    public function unlock(string $id, string $cas, UnlockOptions $options = null): Result
    {
        $response = Extension\documentUnlock(
            $this->core,
            $this->bucketName,
            $this->scopeName,
            $this->name,
            $id,
            $cas,
            UnlockOptions::export($options)
        );
        return new Result($response);
    }

    /**
     * Touches a document, setting a new expiry time.
     *
     * @param string $id the key of the document
     * @param int|DateTimeInterface $expiry the expiry time for the document in ms
     * @param TouchOptions|null $options the options to use for the operation
     *
     * @return MutationResult
     * @throws DocumentNotFoundException
     * @throws TimeoutException
     * @throws CouchbaseException
     * @since 4.0.0
     */
    public function touch(string $id, $expiry, TouchOptions $options = null): MutationResult
    {
        if ($expiry instanceof DateTimeInterface) {
            $expirySeconds = $expiry->getTimestamp();
        } else {
            $expirySeconds = (int)$expiry;
        }
        $response = Extension\documentTouch(
            $this->core,
            $this->bucketName,
            $this->scopeName,
            $this->name,
            $id,
            $expirySeconds,
            TouchOptions::export($options)
        );
        return new MutationResult($response);
    }

    /**
     * Performs a set of subdocument lookup operations against the document.
     *
     * @param string $id the key of the document
     * @param array<LookupInSpec> $specs the array of selectors to query against the document
     * @param LookupInOptions|null $options the options to use for the operation
     *
     * @return LookupInResult
     * @throws DocumentNotFoundException
     * @throws TimeoutException
     * @throws CouchbaseException
     * @since 4.0.0
     */
    public function lookupIn(string $id, array $specs, LookupInOptions $options = null): LookupInResult
    {
        $encoded = array_map(
            function (LookupInSpec $item) {
                return $item->export();
            },
            $specs
        );
        if ($options != null && $options->needToFetchExpiry()) {
            $encoded[] = ['opcode' => 'get', 'isXattr' => true, 'path' => LookupInMacro::EXPIRY_TIME];
        }
        $response = Extension\documentLookupIn(
            $this->core,
            $this->bucketName,
            $this->scopeName,
            $this->name,
            $id,
            $encoded,
            LookupInOptions::export($options)
        );
        return new LookupInResult($response, LookupInOptions::getTranscoder($options));
    }

    /**
     * Performs a set of subdocument lookup operations against the document from any replica server in the cluster.
     *
     * @param string $id the key of the document
     * @param array<LookupInSpec> $specs the array of selectors to query against the document
     * @param LookupInAnyReplicaOptions|null $options the options to use for the operation
     *
     * @return LookupInReplicaResult
     * @throws DocumentIrretrievableException
     * @throws TimeoutException
     * @throws CouchbaseException
     * @since 4.1.6
     */
    public function lookupInAnyReplica(string $id, array $specs, LookupInAnyReplicaOptions $options = null): LookupInReplicaResult
    {
        $encoded = array_map(
            function (LookupInSpec $item) {
                return $item->export();
            },
            $specs
        );
        if ($options != null && $options->needToFetchExpiry()) {
            $encoded[] = ['opcode' => 'get', 'isXattr' => true, 'path' => LookupInMacro::EXPIRY_TIME];
        }
        $response = Extension\documentLookupInAnyReplica(
            $this->core,
            $this->bucketName,
            $this->scopeName,
            $this->name,
            $id,
            $encoded,
            LookupInAnyReplicaOptions::export($options)
        );
        return new LookupInReplicaResult($response, LookupInAnyReplicaOptions::getTranscoder($options));
    }

    /**
     * Performs a set of subdocument lookup operations against the document from the active server and all replicas in the cluster.
     * Returns an array of LookupInReplicaResults, one per server.
     *
     * @param string $id the key of the document
     * @param array<LookupInSpec> $specs the array of selectors to query against the document
     * @param LookupInAllReplicasOptions|null $options the options to use for the operation
     *
     * @return array
     * @throws DocumentNotFoundException
     * @throws TimeoutException
     * @throws CouchbaseException
     * @since 4.1.6
     */
    public function lookupInAllReplicas(string $id, array $specs, LookupInAllReplicasOptions $options = null): array
    {
        $encoded = array_map(
            function (LookupInSpec $item) {
                return $item->export();
            },
            $specs
        );
        if ($options != null && $options->needToFetchExpiry()) {
            $encoded[] = ['opcode' => 'get', 'isXattr' => true, 'path' => LookupInMacro::EXPIRY_TIME];
        }
        $responses = Extension\documentLookupInAllReplicas(
            $this->core,
            $this->bucketName,
            $this->scopeName,
            $this->name,
            $id,
            $encoded,
            LookupInAllReplicasOptions::export($options)
        );
        return array_map(
            function (array $response) use ($options) {
                return new LookupInReplicaResult($response, LookupInAllReplicasOptions::getTranscoder($options));
            },
            $responses
        );
    }

    /**
     * Performs a set of subdocument lookup operations against the document.
     *
     * @param string $id the key of the document
     * @param array<MutateInSpec> $specs the array of modifications to perform against the document
     * @param MutateInOptions|null $options the options to use for the operation
     *
     * @return MutateInResult
     * @throws DocumentNotFoundException
     * @throws DocumentExistsException
     * @throws TimeoutException
     * @throws CouchbaseException
     * @since 4.0.0
     */
    public function mutateIn(string $id, array $specs, MutateInOptions $options = null): MutateInResult
    {
        $encoded = array_map(
            function (MutateInSpec $item) use ($options) {
                return $item->export($options);
            },
            $specs
        );
        $response = Extension\documentMutateIn(
            $this->core,
            $this->bucketName,
            $this->scopeName,
            $this->name,
            $id,
            $encoded,
            MutateInOptions::export($options)
        );
        return new MutateInResult($response);
    }

    /**
     * Retrieves a group of documents. If the document does not exist, it will not raise an exception, but rather fill
     * non-null value in error() property of the corresponding result object.
     *
     * @param array $ids array of IDs, organized like this ["key1", "key2", ...]
     * @param GetOptions|null $options the options to use for the operation
     *
     * @return array<GetResult> array of GetResult, one for each of the entries
     * @since 4.0.0
     */
    public function getMulti(array $ids, GetOptions $options = null): array
    {
        $responses = Extension\documentGetMulti(
            $this->core,
            $this->bucketName,
            $this->scopeName,
            $this->name,
            $ids,
            GetOptions::export($options)
        );
        return array_map(
            function (array $response) use ($options) {
                return new GetResult($response, GetOptions::getTranscoder($options));
            },
            $responses
        );
    }

    /**
     * Performs a key-value scan operation
     *
     * Use this API for low concurrency batch queries where latency is not a critical as the system
     * may have to scan a lot of documents to find the matching documents.
     * For low latency range queries, it is recommended that you use SQL++ with the necessary indexes.
     *
     * @param ScanType $scanType The type of scan to execute
     * @param ScanOptions|null $options The options to use for the operation
     *
     * @return ScanResults Object containing iterator over the scan results
     * @throws InvalidArgumentException
     * @since 4.1.6
     */
    public function scan(ScanType $scanType, ScanOptions $options = null): ScanResults
    {
        if ($scanType instanceof RangeScan) {
            $type = RangeScan::export($scanType);
        } elseif ($scanType instanceof SamplingScan) {
            $type = SamplingScan::export($scanType);
        } elseif ($scanType instanceof PrefixScan) {
            $type = PrefixScan::export($scanType);
        } else {
            throw new InvalidArgumentException("ScanType must be a RangeScan, SamplingScan, or PrefixScan");
        }
        return new ScanResults(
            $this->core,
            $this->bucketName,
            $this->scopeName,
            $this->name,
            $type,
            ScanOptions::export($options),
            ScanOptions::getTranscoder($options)
        );
    }

    /**
     * Removes a group of documents. If second element of the entry (CAS) is null, then the operation will
     * remove the document unconditionally.
     *
     * @param array $entries array of arrays, organized like this
     *   [["key1", "encodedCas1"], ["key2", "encodedCas2"], ...] or ["key1", "key2", ...]
     * @param RemoveOptions|null $options the options to use for the operation
     *
     * @return array<MutationResult> array of MutationResult, one for each of the entries
     * @throws UnsupportedOperationException
     * @since 4.0.0
     */
    public function removeMulti(array $entries, RemoveOptions $options = null): array
    {
        $responses = Extension\documentRemoveMulti(
            $this->core,
            $this->bucketName,
            $this->scopeName,
            $this->name,
            $entries,
            RemoveOptions::export($options)
        );
        return array_map(
            function (array $response) {
                return new MutationResult($response);
            },
            $responses
        );
    }

    /**
     * Creates a group of documents if they don't exist, otherwise updates them.
     *
     * @param array $entries array of arrays, organized like this [["key1", $value1], ["key2", $value2],
     *     ...]
     * @param UpsertOptions|null $options the options to use for the operation
     *
     * @return array<MutationResult> array of MutationResult, one for each of the entries
     * @throws InvalidArgumentException
     * @since 4.0.0
     */
    public function upsertMulti(array $entries, UpsertOptions $options = null): array
    {
        $encodedEntries = array_map(
            function (array $entry) use ($options) {
                if (count($entry) != 2) {
                    throw new InvalidArgumentException("expected ID-VALUE tuple to have exactly 2 entries");
                }
                if (!is_string($entry[0])) {
                    throw new InvalidArgumentException("expected first entry (ID) of ID-VALUE tuple to be a string");
                }
                $encoded = UpsertOptions::encodeDocument($options, $entry[1]);
                return [
                    $entry[0],   // id
                    $encoded[0], // value
                    $encoded[1], // flags
                ];
            },
            $entries
        );
        $responses = Extension\documentUpsertMulti(
            $this->core,
            $this->bucketName,
            $this->scopeName,
            $this->name,
            $encodedEntries,
            UpsertOptions::export($options)
        );
        return array_map(
            function (array $response) {
                return new MutationResult($response);
            },
            $responses
        );
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

    /**
     * Creates a new manager object for managing N1QL query indexes at the collection level
     *
     * @return CollectionQueryIndexManager
     * @since 4.1.2
     */
    public function queryIndexes(): CollectionQueryIndexManager
    {
        return new CollectionQueryIndexManager($this->name, $this->scopeName, $this->bucketName, $this->core);
    }
}
