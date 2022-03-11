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

class InsertOptions
{
    /**
     * Sets the operation timeout in milliseconds.
     *
     * @param int $arg the operation timeout to apply
     * @return InsertOptions
     */
    public function timeout(int $arg): InsertOptions
    {
    }

    /**
     * Sets the expiry time for the document.
     *
     * @param int $arg the expiry time in ms
     * @return InsertOptions
     */
    public function expiry(int $arg): InsertOptions
    {
    }

    /**
     * Sets the durability level to enforce when writing the document.
     *
     * @param int $arg the durability level to enforce
     * @return InsertOptions
     */
    public function durabilityLevel(int $arg): InsertOptions
    {
    }

    /**
     * Associate custom transcoder with the request.
     *
     * @param callable $arg encoding function with signature (returns tuple of bytes, flags and datatype):
     *
     *   `function encoder($value): [string $bytes, int $flags, int $datatype]`
     */
    public function encoder(callable $arg): InsertOptions
    {
    }
}
