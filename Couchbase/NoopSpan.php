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

namespace Couchbase;

use Couchbase\Observability\StatusCode;

class NoopSpan implements RequestSpan
{
    private static ?NoopSpan $instance = null;

    public function addTag(string $key, int|string $value): void
    {
    }

    public function end(?int $endTimestampNanoseconds = null): void
    {
    }

    public function setStatus(StatusCode $statusCode): void
    {
    }

    public static function getInstance(): NoopSpan
    {
        if (self::$instance === null) {
            self::$instance = new NoopSpan();
        }
        return self::$instance;
    }
}
