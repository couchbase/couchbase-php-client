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
 * Merges the FTS and vector result sets by normalized score rather than by rank.
 *
 * Note: available from Couchbase Server 8.1. Setting it makes the SDK check for the score
 * fusion cluster capability, and fail the operation with a FeatureNotAvailableException if the
 * cluster does not advertise it.
 *
 * @since 4.6.0
 *
 * @UNCOMMITTED: This API may change in the future.
 */
class SearchScoringRelativeScoreFusion implements JsonSerializable, SearchScoring
{
    private ?int $windowSize = null;

    /**
     * Static helper to keep code more readable
     *
     * @return SearchScoringRelativeScoreFusion
     * @since 4.6.0
     *
     * @UNCOMMITTED: This API may change in the future.
     */
    public static function build(): SearchScoringRelativeScoreFusion
    {
        return new SearchScoringRelativeScoreFusion();
    }

    /**
     * Sets how many results per list are considered for fusion.
     *
     * @param int $windowSize the window size
     *
     * @return SearchScoringRelativeScoreFusion
     * @since 4.6.0
     *
     * @UNCOMMITTED: This API may change in the future.
     */
    public function windowSize(int $windowSize): SearchScoringRelativeScoreFusion
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
            'strategy' => 'rsf',
        ];
        if ($this->windowSize !== null) {
            $json['windowSize'] = $this->windowSize;
        }
        return $json;
    }
}
