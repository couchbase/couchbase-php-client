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
 * Sort by the document identifier.
 */
class SearchSortId implements JsonSerializable, SearchSort
{
    private ?bool $descending = null;

    /**
     * Direction of the sort
     *
     * @param bool $descending
     *
     * @return SearchSortId
     * @since 4.0.0
     */
    public function descending(bool $descending): SearchSortId
    {
        $this->descending = $descending;
        return $this;
    }

    /**
     * @internal
     * @return mixed
     */
    public function jsonSerialize()
    {
        return SearchSortId::export($this);
    }

    /**
     * @internal
     */
    public static function export(SearchSortId $sort): array
    {
        $json = [
            'by' => 'id',
        ];

        if ($sort->descending != null) {
            $json['desc'] = $sort->descending;
        }

        return $json;
    }
}
