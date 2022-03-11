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
 * Object for accessing a row returned as a part of the results from a viery query.
 */
class ViewRow
{
    /**
     * Returns the id of the row
     *
     * @return string|null
     */
    public function id(): ?string
    {
    }

    /**
     * Returns the key of the document
     */
    public function key()
    {
    }

    /**
     * Returns the value of the row
     */
    public function value()
    {
    }

    /**
     * Returns the corresponding document for the row, if enabled
     */
    public function document()
    {
    }
}
