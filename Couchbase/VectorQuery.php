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
    private int $numCandidates;
    private ?array $vectorQuery = null;
    private ?string $base64VectorQuery = null;
    private ?float $boost = null;
    private ?SearchQuery $prefilter = null;

    /**
     * @param string $vectorFieldName the document field that contains the vector
     * @param array<float>|string $vectorQuery the vector query to run. Cannot be empty. Either a vector array,
     * or a base64-encoded sequence of little-endian IEEE 754 floats.
     *
     * @since 4.1.7
     *
     * @throws InvalidArgumentException
     *
     * @UNCOMMITTED: This API may change in the future.
     */
    public function __construct(string $vectorFieldName, array|string $vectorQuery)
    {
        if (empty($vectorQuery)) {
            throw new InvalidArgumentException("The vectorQuery cannot be empty");
        }

        if (is_array($vectorQuery)) {
            $this->vectorQuery = $vectorQuery;
        } else {
            $this->base64VectorQuery = $vectorQuery;
        }

        $this->vectorFieldName = $vectorFieldName;
        $this->numCandidates = 3;
    }

    /**
     * Static helper to keep code more readable
     *
     * @param string $vectorFieldName the document field that contains the vector
     * @param array<float>|string $vectorQuery the vector query to run. Cannot be empty. Either a vector array,
     * or the vector query encoded into a base64 string.
     *
     * @since 4.1.7
     * @return VectorQuery
     *
     * @throws InvalidArgumentException
     *
     * @UNCOMMITTED: This API may change in the future.
     */
    static function build(string $vectorFieldName, array|string $vectorQuery): VectorQuery
    {
        return new VectorQuery($vectorFieldName, $vectorQuery);
    }

    /**
     * Sets the number of results that will be returned from this vector query. Defaults to 3.
     *
     * @param int $numCandidates the number of results returned.
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
     * Sets the prefilter for this vector query.
     *
     * @param SearchQuery $prefilter the prefilter query to use
     *
     * @return VectorQuery
     * @since 4.4.0
     */
    public function prefilter(SearchQuery $prefilter): VectorQuery
    {
        $this->prefilter = $prefilter;
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

        if ($query->vectorQuery != null) {
            $vectorQueries = [];
            foreach ($query->vectorQuery as $value) {
                $vectorQueries[] = $value;
            }
            $json['vector'] = $vectorQueries;
        } else {
            $json['vector_base64'] = $query->base64VectorQuery;
        }

        if ($query->prefilter != null) {
            $json['filter'] = $query->prefilter->export();
        }

        $json['k'] = $query->numCandidates;
        return $json;
    }
}
