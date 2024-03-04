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

use Couchbase\Exception\InvalidArgumentException;

/**
 * A FTS query that matches on Couchbase document IDs. Useful to restrict the search space to a list of keys (by using
 * this in a compound query).
 */
class DocIdSearchQuery implements SearchQuery
{
    private array $documentIds;
    private ?float $boost = null;
    private ?string $field = null;

    public function __construct()
    {
    }

    /**
     * Static helper to keep code more readable
     *
     * @return DocIdSearchQuery
     * @since 4.1.7
     */
    public static function build(): DocIdSearchQuery
    {
        return new DocIdSearchQuery();
    }

    /**
     * Sets the boost for this query.
     *
     * @param float $boost the boost value to use.
     *
     * @return DocIdSearchQuery
     * @since 4.0.0
     */
    public function boost(float $boost): DocIdSearchQuery
    {
        $this->boost = $boost;
        return $this;
    }

    /**
     * Sets the field for this query.
     *
     * @param string $field the field to use.
     *
     * @return DocIdSearchQuery
     * @since 4.0.0
     */
    public function field(string $field): DocIdSearchQuery
    {
        $this->field = $field;
        return $this;
    }

    /**
     * Sets the document ids to restrict the search to.
     *
     * @param string ...$documentIds the document ids to restrict the search to.
     *
     * @return DocIdSearchQuery
     * @since 4.0.0
     */
    public function docIds(string ...$documentIds): DocIdSearchQuery
    {
        $this->documentIds = $documentIds;
        return $this;
    }

    /**
     * @internal
     * @throws InvalidArgumentException
     */
    public static function export(DocIdSearchQuery $query): array
    {
        if (count($query->documentIds) == 0) {
            throw new InvalidArgumentException();
        }

        $json = [
            'ids' => $query->documentIds,
        ];
        if ($query->boost != null) {
            $json['boost'] = $query->boost;
        }
        if ($query->field != null) {
            $json['field'] = $query->field;
        }

        return $json;
    }
}
