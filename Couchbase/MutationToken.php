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
 * An object which contains meta information of the document needed to enforce query consistency.
 */
interface MutationToken
{
    /**
     * Returns bucket name
     *
     * @return string
     */
    public function bucketName();

    /**
     * Returns partition number
     *
     * @return int
     */
    public function partitionId();

    /**
     * Returns UUID of the partition
     *
     * @return string
     */
    public function partitionUuid();

    /**
     * Returns the sequence number inside partition
     *
     * @return string
     */
    public function sequenceNumber();
}
