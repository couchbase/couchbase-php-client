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

use ArrayIterator;
use Couchbase\CollectionInterface;
use Couchbase\Exception\DocumentNotFoundException;
use Couchbase\Exception\InvalidArgumentException;
use Couchbase\Exception\PathNotFoundException;
use Couchbase\LookupCountSpec;
use Couchbase\LookupGetSpec;
use Couchbase\MutateArrayPrependSpec;
use Couchbase\MutateRemoveSpec;
use Couchbase\StoreSemantics;
use Countable;
use EmptyIterator;
use IteratorAggregate;
use Traversable;

/**
 * Implementation of the List backed by JSON document in Couchbase collection
 */
class CouchbaseQueue implements Countable, IteratorAggregate
{
    private string $id;
    private CollectionInterface $collection;
    private Options\CouchbaseQueue $options;

    /**
     * CouchbaseQueue constructor.
     *
     * @param string $id identifier of the backing document.
     * @param CollectionInterface $collection collection instance, where the document will be stored
     * @param Options\CouchbaseQueue|null $options
     *
     * @since 4.0.0
     */
    public function __construct(string $id, CollectionInterface $collection, ?Options\CouchbaseQueue $options = null)
    {
        $this->id = $id;
        $this->collection = $collection;
        if ($options) {
            $this->options = $options;
        } else {
            $this->options = new Options\CouchbaseQueue(null, null, null, null);
        }
    }

    /**
     * @return int number of elements in the list
     * @since 4.0.0
     */
    public function count(): int
    {
        try {
            $result = $this->collection->lookupIn(
                $this->id,
                [new LookupCountSpec("")],
                $this->options->lookupInOptions()
            );
            if (!$result->exists(0)) {
                return 0;
            }
            return (int)$result->content(0);
        } catch (DocumentNotFoundException $ex) {
            return 0;
        }
    }

    /**
     * @return bool true if the list is empty
     * @since 4.0.0
     */
    public function empty(): bool
    {
        return $this->count() == 0;
    }

    /**
     * Pop entry from the FIFO queue
     * @return mixed return the oldest value in the queue or null
     * @since 4.0.0
     */
    public function pop()
    {
        try {
            $result = $this->collection->lookupIn(
                $this->id,
                [new LookupGetSpec("[-1]", false)],
                $this->options->lookupInOptions()
            );
            if (!$result->exists(0)) {
                return null;
            }
            $value = $result->content(0);
            $options = clone $this->options->mutateInOptions();
            $options->cas($result->cas());
            $this->collection->mutateIn(
                $this->id,
                [new MutateRemoveSpec("[-1]", false)],
                $options
            );
            return $value;
        } catch (DocumentNotFoundException | PathNotFoundException $ex) {
            return null;
        }
    }

    /**
     * Enqueue new value to the FIFO queue
     *
     * @param mixed $value the value to insert
     *
     * @throws InvalidArgumentException
     * @since 4.0.0
     */
    public function push($value): void
    {
        $options = clone $this->options->mutateInOptions();
        $options->storeSemantics(StoreSemantics::UPSERT);
        $this->collection->mutateIn(
            $this->id,
            [new MutateArrayPrependSpec("", [$value], false, false, false)],
            $options
        );
    }

    /**
     * Clears the queue. Effectively it removes backing document, because missing document is an equivalent of the
     * empty collection.
     * @since 4.0.0
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
     * Create new iterator to walk through the list.
     * Implementation of {@link IteratorAggregate}
     * @return Traversable iterator to enumerate elements of the list
     * @since 4.0.0
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
