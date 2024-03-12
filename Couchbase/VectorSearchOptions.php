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

class VectorSearchOptions
{
    private ?string $vectorQueryCombination = null;

    /**
     * Static helper to keep code more readable
     *
     * @return VectorSearchOptions
     * @since 4.1.7
     *
     * @UNCOMMITTED: This API may change in the future.
     */
    public static function build(): VectorSearchOptions
    {
        return new VectorSearchOptions();
    }

    /**
     * Sets how the vector query results are combined.
     *
     * @param string $vectorQueryCombination
     * @return VectorSearchOptions
     * @since 4.1.7
     *
     * @see VectorQueryCombination::AND
     * @see VectorQueryCombination::OR
     *
     * @UNCOMMITTED: This API may change in the future.
     */
    public function vectorQueryCombination(string $vectorQueryCombination): VectorSearchOptions
    {
        $this->vectorQueryCombination = $vectorQueryCombination;
        return $this;
    }

    /**
     * @internal
     *
     * @param VectorSearchOptions|null $options
     *
     * @return array
     * @since 4.1.7
     */
    public static function export(?VectorSearchOptions $options): array
    {
        if ($options == null) {
            return [];
        }
        return [
            "vectorQueryCombination" => $options->vectorQueryCombination,
        ];
    }
}
