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

class ViewOptions
{
    public function timeout(int $arg): ViewOptions
    {
    }

    public function includeDocuments(bool $arg, int $maxConcurrentDocuments = 10): ViewOptions
    {
    }

    public function key($arg): ViewOptions
    {
    }

    public function keys(array $args): ViewOptions
    {
    }

    public function limit(int $arg): ViewOptions
    {
    }

    public function skip(int $arg): ViewOptions
    {
    }

    public function scanConsistency(int $arg): ViewOptions
    {
    }

    public function order(int $arg): ViewOptions
    {
    }

    public function reduce(bool $arg): ViewOptions
    {
    }

    public function group(bool $arg): ViewOptions
    {
    }

    public function groupLevel(int $arg): ViewOptions
    {
    }

    public function range($start, $end, $inclusiveEnd = false): ViewOptions
    {
    }

    public function idRange($start, $end, $inclusiveEnd = false): ViewOptions
    {
    }

    public function raw(string $key, $value): ViewOptions
    {
    }
}
