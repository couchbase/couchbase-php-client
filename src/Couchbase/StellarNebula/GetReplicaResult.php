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

namespace Couchbase\StellarNebula;

class GetReplicaResult extends Result
{
    private Transcoder $transcoder;
    private string $value;
    private int $flags;
    private bool $isReplica;

    public function __construct(
        string $bucket,
        string $scope,
        string $collection,
        string $id,
        int|string|null $cas,
        Transcoder $transcoder,
        string $content,
        int $contentType,
        int $compressionType,
        ?int $expiry
    )
    {
        parent::__construct($bucket, $scope, $collection, $id, $cas);
        $this->transcoder = $transcoder;
        //TODO figure out how the GetResponse maps to value, flags, isReplica
//        $this->value = $content["value"];
//        $this->flags = $content["flags"];
//        $this->isReplica = $content["isReplica"];
    }

    public function isReplica(): bool
    {
        return $this->isReplica;
    }

    public function content()
    {
        return $this->transcoder->decode($this->value, $this->flags);
    }

    public function contentAs(Transcoder $transcoder, ?int $overrideFlags = null)
    {
        return $transcoder->decode($this->value, $overrideFlags == null ? $this->flags : $overrideFlags);
    }
}
