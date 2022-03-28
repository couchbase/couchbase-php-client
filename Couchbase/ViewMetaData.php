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
 * Class for retrieving metadata generated during view queries.
 */
class ViewMetaData
{
    private ?int $totalRows = null;
    private ?array $debug = null;

    public function __construct(?array $meta = null)
    {
        if ($meta != null) {
            if (array_key_exists("totalRows", $meta)) {
                $this->totalRows = $meta["totalRows"];
            }
            if (array_key_exists("debug", $meta)) {
                $this->debug = $meta["debug"];
            }
        }
    }

    /**
     * Returns the total number of rows returned by this view query
     *
     * @return int|null
     */
    public function totalRows(): ?int
    {
        return $this->totalRows;
    }

    /**
     * Returns debug information for this view query if enabled
     *
     * @return array|null
     */
    public function debug(): ?array
    {
        return $this->debug;
    }
}
