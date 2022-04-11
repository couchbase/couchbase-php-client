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
 * A FTS query that matches all indexed documents (usually for debugging purposes).
 */
class MatchAllSearchQuery implements JsonSerializable, SearchQuery
{
    private ?float $boost = null;

    /**
     * Sets the boost for this query.
     *
     * @param float $boost the boost value to use.
     *
     * @return MatchAllSearchQuery
     * @since 4.0.0
     */
    public function boost(float $boost): MatchAllSearchQuery
    {
        $this->boost = $boost;
        return $this;
    }

    /**
     * @private
     * @return mixed
     */
    public function jsonSerialize(): mixed
    {
        return MatchAllSearchQuery::export($this);
    }

    /**
     * @private
     */
    public static function export(MatchAllSearchQuery $query): array
    {
        $json = [
            'match_all' => json_encode(null),
        ];
        if ($query->boost != null) {
            $json['boost'] = $query->boost;
        }

        return $json;
    }
}
