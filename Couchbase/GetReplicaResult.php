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
 * Interface for results created by the getReplica operation.
 */
class GetReplicaResult extends Result
{
    private Transcoder $transcoder;
    private string $value;
    private int $flags;
    private bool $isReplica;

    /**
     * @internal
     *
     * @param array $response
     * @param Transcoder $transcoder
     *
     * @since 4.0.0
     */
    public function __construct(array $response, Transcoder $transcoder)
    {
        parent::__construct($response);
        $this->transcoder = $transcoder;
        $this->flags = $response["flags"];
        $this->value = $response["value"];
        $this->isReplica = $response["isReplica"];
    }

    /**
     * Returns whether the document came from a replica server
     *
     * @return bool
     * @since 4.0.1
     */
    public function isReplica(): bool
    {
        return $this->isReplica;
    }

    /**
     * Returns the content of the document decoded using associated transcoder
     *
     * @return mixed
     * @since 4.0.1
     */
    public function content()
    {
        return $this->transcoder->decode($this->value, $this->flags);
    }

    /**
     * Returns the content of the document decoded using custom transcoder
     *
     * @return mixed
     * @since 4.0.1
     */
    public function contentAs(Transcoder $transcoder, ?int $overrideFlags = null)
    {
        return $transcoder->decode($this->value, $overrideFlags == null ? $this->flags : $overrideFlags);
    }
}
