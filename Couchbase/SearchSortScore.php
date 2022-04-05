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

use JsonSerializable;

/**
 * Sort by the hit score.
 */
class SearchSortScore implements JsonSerializable, SearchSort
{
    private ?bool $descending = null;

    public function jsonSerialize(): mixed
    {
        return SearchSortScore::export($this);
    }

    public function __construct()
    {
    }

    /**
     * Direction of the sort
     *
     * @param bool $descending
     *
     * @return SearchSortScore
     * @since 4.0.0
     */
    public function descending(bool $descending): SearchSortScore
    {
        $this->descending = $descending;
        return $this;
    }

    /**
     * @private
     */
    public static function export(SearchSortScore $sort): array
    {
        $json = [
            'by' => 'score'
        ];

        if ($sort->descending != null) {
            $json['desc'] = $sort->descending;
        }

        return $json;
    }
}
