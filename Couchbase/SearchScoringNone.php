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
 * Disables scoring, so that the server does not perform any scoring on the hits.
 *
 * This sends the same "none" that the deprecated SearchOptions::disableScoring() sends. It is
 * not a fusion strategy: "none" predates score fusion, so it works on older server versions.
 *
 * @since 4.6.0
 */
class SearchScoringNone implements JsonSerializable, SearchScoring
{
    /**
     * Static helper to keep code more readable
     *
     * @return SearchScoringNone
     * @since 4.6.0
     */
    public static function build(): SearchScoringNone
    {
        return new SearchScoringNone();
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
        return [
            'strategy' => 'none',
        ];
    }
}
