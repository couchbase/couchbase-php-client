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
class MutationToken
{
    private string $bucketName;
    private int $partitionId;
    private string $partitionUuid;
    private string $sequenceNumber;

    /**
     * @internal
     *
     * @param array $response
     */
    public function __construct(array $response)
    {
        $this->bucketName = $response["bucketName"];
        $this->partitionId = $response["partitionId"];
        $this->partitionUuid = $response["partitionUuid"];
        $this->sequenceNumber = $response["sequenceNumber"];
    }

    /**
     * Returns bucket name
     *
     * @return string
     * @since 4.0.0
     */
    public function bucketName(): string
    {
        return $this->bucketName;
    }

    /**
     * Returns partition number
     *
     * @return int
     * @since 4.0.0
     */
    public function partitionId(): int
    {
        return $this->partitionId;
    }

    /**
     * Returns UUID of the partition
     *
     * @return string
     * @since 4.0.0
     */
    public function partitionUuid(): string
    {
        return $this->partitionUuid;
    }

    /**
     * Returns the sequence number inside partition
     *
     * @return string
     * @since 4.0.0
     */
    public function sequenceNumber(): string
    {
        return $this->sequenceNumber;
    }
}
