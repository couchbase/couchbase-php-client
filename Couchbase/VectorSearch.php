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

class VectorSearch implements JsonSerializable
{
    private array $vectorQueries;
    private ?VectorSearchOptions $options;

    /**
     * @param array<VectorQuery> $vectorQueries The vector queries to be run.
     * @param VectorSearchOptions|null $options The options to use on the vector queries
     *
     * @since 4.1.7
     *
     * @UNCOMMITTED: This API may change in the future.
     */
    public function __construct(array $vectorQueries, VectorSearchOptions $options = null)
    {
        $this->vectorQueries = $vectorQueries;
        $this->options = $options;
    }

    /**
     * Static helper to keep code more readable.
     *
     * @param array<VectorQuery> $vectorQueries The vector queries to be run
     * @param VectorSearchOptions|null $options The options to use on the vector queries
     *
     * @since 4.1.7
     * @return VectorSearch
     *
     * @UNCOMMITTED: This API may change in the future.
     */
    public static function build(array $vectorQueries, VectorSearchOptions $options = null): VectorSearch
    {
        return new VectorSearch($vectorQueries, $options);
    }


    /**
     * @internal
     *
     * @since 4.1.7
     * @return VectorSearchOptions|null
     */
    public function options(): ?VectorSearchOptions
    {
        return $this->options;
    }

    /**
     * @internal
     * @return mixed
     */
    public function jsonSerialize(): mixed
    {
        return VectorSearch::export($this);
    }

    /**
     * @internal
     *
     * @param VectorSearch $search
     *
     * @return array
     * @since 4.1.7
     */
    public static function export(VectorSearch $search): array
    {
        $json = [];

        foreach ($search->vectorQueries as $query) {
            $encoded = VectorQuery::export($query);
            $json[] = $encoded;
        }
        return $json;
    }
}
