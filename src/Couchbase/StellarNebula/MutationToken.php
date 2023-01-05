<?php

/*
 * Copyright 2022-Present Couchbase, Inc.
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

namespace Couchbase\StellarNebula;

class MutationToken
{
    private string $bucketName;
    private int $vbucketId;
    private string|int $vbucketUuid;
    private string|int $sequenceNumber;

    public function __construct(
        string $bucketName,
        int $vbucketId,
        int|string $vbucketUuid,
        int|string $sequenceNumber
    )
    {
        $this->bucketName = $bucketName;
        $this->vbucketId = $vbucketId;
        $this->vbucketUuid = $vbucketUuid;
        $this->sequenceNumber = $sequenceNumber;
    }

    public function bucket(): string
    {
        return $this->bucketName;
    }

    public function vbucketId(): int
    {
        return $this->vbucketId;
    }

    public function vbucketUuid(): string|int
    {
        return $this->vbucketUuid;
    }

    public function sequenceNumber(): string|int
    {
        return $this->sequenceNumber;
    }
}
