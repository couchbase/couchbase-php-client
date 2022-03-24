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
 * QueryResult is an object for retrieving results from N1QL queries.
 */
class QueryResult
{
    private QueryMetaData $meta;
    private array $rows;

    public function __construct(array $result)
    {
        $this->meta = new QueryMetaData($result["meta"]);
        $this->rows = $result["rows"];
    }

    /**
     * Returns metadata generated during query execution such as errors and metrics
     *
     * @return QueryMetaData
     */
    public function metaData(): QueryMetaData {
        return $this->meta;
    }

    /**
     * Returns the rows returns during query execution
     *
     * @return array
     */
    public function rows(): array {
        return $this->rows;
    }
}
