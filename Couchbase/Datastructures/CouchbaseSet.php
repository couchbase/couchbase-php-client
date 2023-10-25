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
use Couchbase\Exception\PathExistsException;
use Couchbase\LookupCountSpec;
use Couchbase\MutateArrayAddUniqueSpec;
use Couchbase\MutateRemoveSpec;
use Couchbase\StoreSemantics;
use Countable;
use EmptyIterator;
use IteratorAggregate;
use Traversable;

/**
 * Implementation of the List backed by JSON document in Couchbase collection
 */
class CouchbaseSet implements Countable, IteratorAggregate
{
    private string $id;
    private CollectionInterface $collection;
    private Options\CouchbaseSet $options;

    /**
     * CouchbaseSet constructor.
     *
     * @param string $id identifier of the backing document.
     * @param CollectionInterface $collection collection instance, where the document will be stored
     * @param Options\CouchbaseSet|null $options
     */
    public function __construct(string $id, CollectionInterface $collection, ?Options\CouchbaseSet $options = null)
    {
        $this->id = $id;
        $this->collection = $collection;
        if ($options) {
            $this->options = $options;
        } else {
            $this->options = new Options\CouchbaseSet(null, null, null, null);
        }
    }

    /**
     * @return int number of elements in the set
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
     * @return bool true if the set is empty
     */
    public function empty(): bool
    {
        return $this->count() == 0;
    }

    /**
     * Remove entry from the set
     *
     * @param mixed $value the value to remove (if exists in the Set)
     *
     * @return bool true if the value has been removed
     */
    public function remove($value): bool
    {
        try {
            $result = $this->collection->get($this->id, $this->options->getOptions());
            foreach ($result->content() as $offset => $entry) {
                if ($entry == $value) {
                    $options = clone $this->options->mutateInOptions();
                    $options->cas($result->cas());
                    $this->collection->mutateIn(
                        $this->id,
                        [new MutateRemoveSpec(sprintf("[%d]", (int)$offset), false)],
                        $options
                    );
                    return true;
                }
            }
            return false;
        } catch (DocumentNotFoundException $ex) {
            return false;
        }
    }

    /**
     * Adds new value to the set
     *
     * @param mixed $value the value to insert
     *
     * @throws InvalidArgumentException
     */
    public function add($value): void
    {
        $options = clone $this->options->mutateInOptions();
        $options->storeSemantics(StoreSemantics::UPSERT);
        try {
            $this->collection->mutateIn(
                $this->id,
                [new MutateArrayAddUniqueSpec("", $value, false, false, false)],
                $options
            );
        } catch (PathExistsException $ex) {
            return;
        }
    }

    /**
     * Checks whether an offset exists.
     *
     * @param mixed $value the value to check for existence
     *
     * @return bool true if there is an entry associated with the offset
     */
    public function contains($value): bool
    {
        try {
            $result = $this->collection->get($this->id, $this->options->getOptions());
            foreach ($result->content() as $entry) {
                if ($entry == $value) {
                    return true;
                }
            }
            return false;
        } catch (DocumentNotFoundException $ex) {
            return false;
        }
    }

    /**
     * Clears the set. Effectively it removes backing document, because missing document is an equivalent of the empty
     * collection.
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
     * Create new iterator to walk through the set.
     * Implementation of {@link IteratorAggregate}
     * @return Traversable iterator to enumerate elements of the set
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
