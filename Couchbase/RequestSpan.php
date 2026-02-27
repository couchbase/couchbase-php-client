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

use Couchbase\Observability\StatusCode;

/**
 * Represents a span of time an event occurs over.
 */
interface RequestSpan
{
    /**
     * Adds a tag to this span.
     *
     * @param string $key The key of the tag to add.
     * @param int|string $value The value to assign to the tag.
     */
    public function addTag(string $key, int|string $value): void;

    /**
     * Ends this span.
     */
    public function end(?int $endTimestampNanoseconds = null): void;

    /**
     * Sets the status for this span.
     *
     * @param \Couchbase\Observability\StatusCode $statusCode The status to set.
     */
    public function setStatus(StatusCode $statusCode): void;
}
