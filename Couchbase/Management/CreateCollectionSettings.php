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

namespace Couchbase\Management;

use Couchbase\Exception\InvalidArgumentException;

class CreateCollectionSettings
{
    private ?int $maxExpiry;
    private ?bool $history;


    /**
     * @throws InvalidArgumentException
     */
    public function __construct(int $maxExpiry = null, bool $history = null)
    {
        if ($maxExpiry && $maxExpiry < -1) {
            throw new InvalidArgumentException("Collection max expiry must be greater than or equal to -1.");
        }
        $this->maxExpiry = $maxExpiry;
        $this->history = $history;
    }

    /**
     * @throws InvalidArgumentException
     */
    public static function build(int $maxExpiry = null, bool $history = null): CreateCollectionSettings
    {
        return new CreateCollectionSettings($maxExpiry, $history);
    }

    /**
     * @return int|null
     */
    public function maxExpiry(): ?int
    {
        return $this->maxExpiry;
    }

    /**
     * @return bool|null
     */
    public function history(): ?bool
    {
        return $this->history;
    }

    /**
     * @param CreateCollectionSettings|null $settings
     * @return array
     *
     * @internal
     */
    public static function export(?CreateCollectionSettings $settings): array
    {
        if ($settings == null) {
            return [];
        }
        return [
            'maxExpiry' => $settings->maxExpiry,
            'history' => $settings->history
        ];
    }
}
