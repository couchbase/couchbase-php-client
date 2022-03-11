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
 * Interface for results created by the lookupIn operation.
 */
interface LookupInResult extends Result
{
    /**
     * Returns the value located at the index specified
     *
     * @param int $index the index to retrieve content from
     * @return object|null
     */
    public function content(int $index): ?object;

    /**
     * Returns whether or not the path at the index specified exists
     *
     * @param int $index the index to check for existence
     * @return bool
     */
    public function exists(int $index): bool;

    /**
     * Returns any error code for the path at the index specified
     *
     * @param int $index the index to retrieve the error code for
     * @return int
     */
    public function status(int $index): int;

    /**
     * Returns the document expiration time or null if the document does not expire.
     *
     * Note, that this function will return expiry only when LookupInOptions had withExpiry set to true.
     *
     * @return DateTimeInterface|null
     */
    public function expiryTime(): ?DateTimeInterface;
}
