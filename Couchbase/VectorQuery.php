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

class VectorQuery
{
    private string $vectorFieldName;
    private array $vectorQuery;
    private int $numCandidates;
    private ?float $boost = null;

    /**
     * @param string $vectorFieldName the document field that contains the vector
     * @param array $vectorQuery the vector query to run. Cannot be empty.
     *
     * @since 4.1.7
     *
     * @throws InvalidArgumentException
     *
     * @UNCOMMITTED: This API may change in the future.
     */
    public function __construct(string $vectorFieldName, array $vectorQuery)
    {
        if (empty($vectorQuery)) {
            throw new InvalidArgumentException("The vectorQuery cannot be empty");
        }
        $this->vectorFieldName = $vectorFieldName;
        $this->vectorQuery = $vectorQuery;
        $this->numCandidates = 3;
    }

    /**
     * Static helper to keep code more readable
     *
     * @param string $vectorFieldName the document field that contains the vector
     * @param array $vectorQuery the vector query to run. Cannot be empty.
     *
     * @since 4.1.7
     * @return VectorQuery
     *
     * @throws InvalidArgumentException
     *
     * @UNCOMMITTED: This API may change in the future.
     */
    static function build(string $vectorFieldName, array $vectorQuery): VectorQuery
    {
        return new VectorQuery($vectorFieldName, $vectorQuery);
    }

    /**
     * Sets the number of results that will be returned from this vector query. Defaults to 3.
     *
     * @param int|null $numCandidates the number of results returned.
     *
     * @since 4.1.7
     * @return VectorQuery
     *
     * @throws InvalidArgumentException
     *
     * @UNCOMMITTED: This API may change in the future.
     */
    public function numCandidates(int $numCandidates): VectorQuery
    {
        if ($numCandidates < 1) {
            throw new InvalidArgumentException("The numCandidates cannot be less than 1");
        }
        $this->numCandidates = $numCandidates;
        return $this;
    }

    /**
     * Sets the boost for this query.
     *
     * @param float $boost the boost value to use.
     *
     * @return VectorQuery
     * @since 4.1.7
     *
     * @UNCOMMITTED: This API may change in the future.
     */
    public function boost(float $boost): VectorQuery
    {
        $this->boost = $boost;
        return $this;
    }

    /**
     * @internal
     *
     * @param VectorQuery $query
     *
     * @return array
     * @since 4.1.7
     */
    public static function export(VectorQuery $query): array
    {
        $json = [
            'field' => $query->vectorFieldName,
        ];

        if ($query->boost != null) {
            $json['boost'] = $query->boost;
        }

        $vectorQueries = [];
        foreach ($query->vectorQuery as $value) {
            $vectorQueries[] = $value;
        }
        $json['vector'] = $vectorQueries;
        $json['k'] = $query->numCandidates;
        return $json;
    }
}
