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
 * A facet that gives the number of occurrences of the most recurring terms in all hits.
 */
class TermSearchFacet implements JsonSerializable, SearchFacet
{
    private string $field;
    private int $limit;

    /**
     * @param string $field
     * @param int $limit
     */
    public function __construct(string $field, int $limit)
    {
        $this->field = $field;
        $this->limit = $limit;
    }

    /**
     * @internal
     * @return mixed
     */
    public function jsonSerialize()
    {
        return TermSearchFacet::export($this);
    }

    /**
     * @internal
     *
     * @param TermSearchFacet $facet
     *
     * @return array
     */
    public static function export(TermSearchFacet $facet): array
    {
        return [
            'field' => $facet->field,
            'size' => $facet->limit,
        ];
    }
}
