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
 * Sort by a field in the hits.
 */
class SearchSortField implements JsonSerializable, SearchSort
{
    private ?bool $descending = null;
    private ?string $type = null;
    private ?string $mode = null;
    private ?string $missing = null;
    private string $field;

    /**
     * @param string $field
     */
    public function __construct(string $field)
    {
        $this->field = $field;
    }

    /**
     * Direction of the sort
     *
     * @param bool $descending
     *
     * @return SearchSortField
     * @since 4.0.0
     */
    public function descending(bool $descending): SearchSortField
    {
        $this->descending = $descending;
        return $this;
    }

    /**
     * Set type of the field
     *
     * @param string type the type
     *
     * @see SearchSortType::AUTO
     * @see SearchSortType::STRING
     * @see SearchSortType::NUMBER
     * @see SearchSortType::DATE
     * @since 4.0.0
     */
    public function type(string $type): SearchSortField
    {
        $this->type = $type;
        return $this;
    }

    /**
     * Set mode of the sort
     *
     * @param string mode the mode
     *
     * @see SearchSortMode::MIN
     * @see SearchSortMode::MAX
     * @since 4.0.0
     */
    public function mode(string $mode): SearchSortField
    {
        $this->mode = $mode;
        return $this;
    }

    /**
     * Set where the hits with missing field will be inserted
     *
     * @param string missing strategy for hits with missing fields
     *
     * @see SearchSortMissing::FIRST
     * @see SearchSortMissing::LAST
     * @since 4.0.0
     */
    public function missing(string $missing): SearchSortField
    {
        $this->missing = $missing;
        return $this;
    }

    /**
     * @internal
     * @return mixed
     */
    public function jsonSerialize()
    {
        return SearchSortField::export($this);
    }

    /**
     * @internal
     */
    public static function export(SearchSortField $sort): array
    {
        $json = [
            'by' => 'field',
            'field' => $sort->field,
        ];

        if ($sort->descending != null) {
            $json['desc'] = $sort->descending;
        }
        if ($sort->type != null) {
            $json['type'] = $sort->type;
        }
        if ($sort->mode != null) {
            $json['mode'] = $sort->mode;
        }
        if ($sort->missing != null) {
            $json['missing'] = $sort->missing;
        }

        return $json;
    }
}
