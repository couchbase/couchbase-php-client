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
 * Merges the FTS and vector result sets by rank rather than by raw score.
 *
 * It works well with the server defaults, and is the recommended strategy.
 *
 * Note: available from Couchbase Server 8.1.
 *
 * @since 4.6.0
 *
 * @UNCOMMITTED: This API may change in the future.
 */
class SearchScoringReciprocalRankFusion implements JsonSerializable, SearchScoring
{
    private ?int $rankConstant = null;
    private ?int $windowSize = null;

    /**
     * Static helper to keep code more readable
     *
     * @return SearchScoringReciprocalRankFusion
     * @since 4.6.0
     *
     * @UNCOMMITTED: This API may change in the future.
     */
    public static function build(): SearchScoringReciprocalRankFusion
    {
        return new SearchScoringReciprocalRankFusion();
    }

    /**
     * Sets the rank constant of the Reciprocal Rank Fusion formula.
     *
     * @param int $rankConstant the rank constant
     *
     * @return SearchScoringReciprocalRankFusion
     * @since 4.6.0
     *
     * @UNCOMMITTED: This API may change in the future.
     */
    public function rankConstant(int $rankConstant): SearchScoringReciprocalRankFusion
    {
        $this->rankConstant = $rankConstant;
        return $this;
    }

    /**
     * Sets how many results per list are considered for fusion.
     *
     * @param int $windowSize the window size
     *
     * @return SearchScoringReciprocalRankFusion
     * @since 4.6.0
     *
     * @UNCOMMITTED: This API may change in the future.
     */
    public function windowSize(int $windowSize): SearchScoringReciprocalRankFusion
    {
        $this->windowSize = $windowSize;
        return $this;
    }

    /**
     * @internal
     * @return mixed
     */
    public function jsonSerialize(): mixed
    {
        return $this->export();
    }

    /**
     * @internal
     */
    public function export(): array
    {
        $json = [
            'strategy' => 'rrf',
        ];
        if ($this->rankConstant !== null) {
            $json['rankConstant'] = $this->rankConstant;
        }
        if ($this->windowSize !== null) {
            $json['windowSize'] = $this->windowSize;
        }
        return $json;
    }
}
