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

class IncrementOptions
{
    /**
     * Sets the operation timeout in milliseconds.
     *
     * @param int $arg the operation timeout to apply
     * @return IncrementOptions
     */
    public function timeout(int $arg): IncrementOptions
    {
    }

    /**
     * Sets the expiry time for the document.
     *
     * @param int|DateTimeInterface $arg the relative expiry time in seconds or DateTimeInterface object for absolute point in time
     * @return IncrementOptions
     */
    public function expiry(mixed $arg): IncrementOptions
    {
    }

    /**
     * Sets the durability level to enforce when writing the document.
     *
     * @param int $arg the durability level to enforce
     * @return IncrementOptions
     */
    public function durabilityLevel(int $arg): IncrementOptions
    {
    }

    /**
     * Sets the value to increment the counter by.
     *
     * @param int $arg the value to increment by
     * @return IncrementOptions
     */
    public function delta(int $arg): IncrementOptions
    {
    }

    /**
     * Sets the value to initialize the counter to if the document does
     * not exist.
     *
     * @param int $arg the initial value to use if counter does not exist
     * @return IncrementOptions
     */
    public function initial(int $arg): IncrementOptions
    {
    }
}
