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
 * Interface for retrieving results from analytics queries.
 */
class AnalyticsResult
{
    private Transcoder $transcoder;
    private AnalyticsMetaData $meta;
    private array $rows;

    /**
     * @private
     * @param array $result
     */
    public function __construct(array $result, Transcoder $transcoder)
    {
        $this->meta = new AnalyticsMetaData($result["meta"]);

        $this->rows = [];
        foreach ($result["rows"] as $row) {
            $this->rows[] = $transcoder->decode($row, 0);
        }
        $this->transcode = $transcoder;
    }

    /**
     * Returns metadata generated during query execution
     *
     * @return AnalyticsMetaData|null
     * @since 4.0.0
     */
    public function metaData(): ?AnalyticsMetaData
    {
        return $this->meta;
    }

    /**
     * Returns the rows returned during query execution
     *
     * @return array|null
     * @since 4.0.0
     */
    public function rows(): ?array
    {
        return $this->rows;
    }
}
