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

use Couchbase\Exception\TracerException;
use Couchbase\Observability\StatusCode;

/**
 * @internal This class is not intended to be used directly. No parent spans should be provided to operations
 *  when using the default ThresholdLoggingTracer.
 */
class ThresholdLoggingSpan implements RequestSpan
{
    /**
     * @var resource
     */
    private $coreSpan;

    public function __construct($coreSpan)
    {
        $this->coreSpan = $coreSpan;
    }

    public function addTag(string $key, $value): void
    {
        $function = COUCHBASE_EXTENSION_NAMESPACE . '\\coreSpanAddTag';
        $function($this->coreSpan, $key, $value);
    }

    public function setStatus(StatusCode $statusCode): void
    {
    }

    public function end(?int $endTimestampNanoseconds = null): void
    {
        if (!is_null($endTimestampNanoseconds)) {
            throw new TracerException('ThresholdLoggingSpan does not support custom end timestamps');
        }
         $function = COUCHBASE_EXTENSION_NAMESPACE . '\\coreSpanEnd';
         $function($this->coreSpan);
    }

    /**
     * @return resource
     * @internal
     */
    public function core()
    {
        return $this->coreSpan;
    }
}
