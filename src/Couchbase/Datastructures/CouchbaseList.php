<?php

declare(strict_types=1);

/*
 *   Copyright 2020-2021 Couchbase, Inc.
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

namespace Couchbase\Datastructures;

use ArrayAccess;
use ArrayIterator;
use Couchbase\Collection;
use Couchbase\DocumentNotFoundException;
use Couchbase\LookupCountSpec;
use Couchbase\LookupExistsSpec;
use Couchbase\LookupGetSpec;
use Couchbase\MutateArrayAppendSpec;
use Couchbase\MutateArrayInsertSpec;
use Couchbase\MutateArrayPrependSpec;
use Couchbase\MutateRemoveSpec;
use Couchbase\MutateReplaceSpec;
use Couchbase\StoreSemantics;
use Countable;
use EmptyIterator;
use IteratorAggregate;
use OutOfBoundsException;
use Traversable;

/**
 * Implementation of the List backed by JSON document in Couchbase collection
 */
class CouchbaseList implements Countable, IteratorAggregate, ArrayAccess
{
    private string $id;
    private Collection $collection;
    private Options\CouchbaseList $options;

    /**
     * CouchbaseList constructor.
     * @param string $id identifier of the backing document.
     * @param Collection $collection collection instance, where the document will be stored
     * @param Options\CouchbaseList|null $options
     */
    public function __construct(string $id, Collection $collection, ?Options\CouchbaseList $options = null)
    {
        $this->id = $id;
        $this->collection = $collection;
        if ($options) {
            $this->options = $options;
        } else {
            $this->options = new Options\CouchbaseList(null, null, null, null);
        }
    }

    /**
     * @return int number of elements in the list
     */
    public function count(): int
    {
        try {
            $result = $this->collection->lookupIn(
                $this->id,
                [new LookupCountSpec("")],
                $this->options->lookupInOptions()
            );
            return (int)$result->content(0);
        } catch (DocumentNotFoundException $ex) {
            return 0;
        }
    }

    /**
     * @return bool true if the list is empty
     */
    public function empty(): bool
    {
        return $this->count() == 0;
    }

    /**
     * Retrieves array value for given offset.
     * @param int $offset
     * @return mixed the value or null
     */
    public function at(int $offset)
    {
        try {
            $result = $this->collection->lookupIn(
                $this->id,
                [new LookupGetSpec(sprintf("[%d]", (int)$offset))],
                $this->options->lookupInOptions()
            );
            return $result->exists(0) ? $result->content(0) : null;
        } catch (DocumentNotFoundException $ex) {
            return null;
        }
    }

    /**
     * Replace entry by the given value.
     * @param int $offset offset of the entry to be replaced
     * @param mixed $value new value
     */
    public function replaceAt(int $offset, $value): void
    {
        $this->collection->mutateIn(
            $this->id,
            [new MutateReplaceSpec(sprintf("[%d]", (int)$offset), $value, false)],
            $this->options->mutateInOptions()
        );
    }

    /**
     * Remove entry by its offset.
     * @param int $offset offset of the entry to remove
     * @throws OutOfBoundsException if the index does not exist
     */
    public function deleteAt(int $offset): void
    {
        $result = $this->collection->mutateIn(
            $this->id,
            [new MutateRemoveSpec(sprintf("[%d]", (int)$offset), false)],
            $this->options->mutateInOptions()
        );
        if ($result->status(0) == COUCHBASE_ERR_SUBDOC_PATH_NOT_FOUND) {
            throw new OutOfBoundsException(sprintf("Index %d does not exist", (int)$offset));
        }
    }

    /**
     * Inserts new entry at given offset. It expands the list shifting all entries after offset to the right.
     * @param int $offset offset where to insert new value
     * @param mixed ...$values the values to insert
     */
    public function insertAt(int $offset, ...$values): void
    {
        $result = $this->collection->mutateIn(
            $this->id,
            [new MutateArrayInsertSpec(sprintf("[%d]", $offset), $values, false, false, false)],
            $this->options->mutateInOptions()
        );
        if ($result->status(0) == COUCHBASE_ERR_SUBDOC_PATH_NOT_FOUND) {
            throw new OutOfBoundsException(sprintf("Index %d does not exist", (int)$offset));
        }
    }

    /**
     * Inserts new entries in the end of the list.
     * @param mixed ...$values new values to prepend
     */
    public function append(...$values): void
    {
        $options = clone $this->options->mutateInOptions();
        $options->storeSemantics(StoreSemantics::UPSERT);
        $this->collection->mutateIn(
            $this->id,
            [new MutateArrayAppendSpec("", $values, false, false, false)],
            $options
        );
    }

    /**
     * Inserts new entries in the beginning of the list.
     * @param mixed ...$values new value to prepend
     */
    public function prepend(...$values): void
    {
        $options = clone $this->options->mutateInOptions();
        $options->storeSemantics(StoreSemantics::UPSERT);
        $this->collection->mutateIn(
            $this->id,
            [new MutateArrayPrependSpec("", $values, false, false, false)],
            $options
        );
    }

    /**
     * Checks whether an offset exists.
     * @param int $offset offset of the entry to check
     * @return bool true if there is an entry associated with the offset
     */
    public function existsAt(int $offset): bool
    {
        try {
            $result = $this->collection->lookupIn(
                $this->id,
                [new LookupExistsSpec(sprintf("[%d]", (int)$offset))],
                $this->options->lookupInOptions()
            );
            return $result->exists(0);
        } catch (DocumentNotFoundException $ex) {
            return false;
        }
    }

    /**
     * Clears the list. Effectively it removes backing document, because missing document is an equivalent of the empty collection.
     */
    public function clear(): void
    {
        try {
            $this->collection->remove($this->id, $this->options->removeOptions());
        } catch (DocumentNotFoundException $ex) {
            return;
        }
    }

    /**
     * Checks whether an offset exists.
     * Implementation of {@link ArrayAccess}.
     * @param mixed $offset offset of the entry to check
     * @return bool true if there is an entry associated with the offset
     */
    public function offsetExists($offset): bool
    {
        return $this->existsAt((int)$offset);
    }

    /**
     * Retrieves array value for given offset.
     * Implementation of {@link ArrayAccess}.
     * @param mixed $offset offset of the entry to get
     * @return mixed the value or null
     */
    public function offsetGet($offset)
    {
        return $this->at((int)$offset);
    }

    /**
     * Assign a value to the specified offset.
     * Implementation of {@link ArrayAccess}.
     * @param mixed $offset offset of the entry to replace
     * @param mixed $value new value
     * @throws OutOfBoundsException if the index does not exist
     */
    public function offsetSet($offset, $value): void
    {
        $this->replaceAt((int)$offset, $value);
    }

    /**
     * Unset an offset.
     * Implementation of {@link ArrayAccess}.
     * @param mixed $offset offset of the entry to remove
     * @throws OutOfBoundsException if the index does not exist
     */
    public function offsetUnset($offset): void
    {
        $this->deleteAt((int)$offset);
    }

    /**
     * Create new iterator to walk through the list.
     * Implementation of {@link IteratorAggregate}
     * @return Traversable iterator to enumerate elements of the list
     */
    public function getIterator(): Traversable
    {
        try {
            $result = $this->collection->get($this->id);
            if ($result->content() == null) {
                return new EmptyIterator();
            }
            return new ArrayIterator($result->content());
        } catch (DocumentNotFoundException $ex) {
            return new EmptyIterator();
        }
    }
}
