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

use Couchbase\RequestSpan;

class DropAnalyticsIndexOptions
{
    private ?int $timeoutMilliseconds = null;
    private ?bool $ignoreIfDoesNotExist = null;
    private ?string $dataverseName = null;
    private ?RequestSpan $parentSpan = null;

    /**
     * Static helper to keep code more readable
     *
     * @return DropAnalyticsIndexOptions
     * @since 4.2.4
     */
    public static function build(): DropAnalyticsIndexOptions
    {
        return new DropAnalyticsIndexOptions();
    }

    /**
     * Customizes the dataverse from which this index should be created.
     *
     * @param string $dataverseName The name of the dataverse
     *
     * @return DropAnalyticsIndexOptions
     * @since 4.2.4
     */
    public function dataverseName(string $dataverseName): DropAnalyticsIndexOptions
    {
        $this->dataverseName = $dataverseName;
        return $this;
    }

    /**
     * @param bool $shouldIgnore
     *
     * @return DropAnalyticsIndexOptions
     * @since 4.2.4
     */
    public function ignoreIfDoesNotExist(bool $shouldIgnore): DropAnalyticsIndexOptions
    {
        $this->ignoreIfDoesNotExist = $shouldIgnore;
        return $this;
    }

    /**
     * Sets the operation timeout in milliseconds.
     *
     * @param int $milliseconds the operation timeout to apply
     *
     * @return DropAnalyticsIndexOptions
     * @since 4.2.4
     */
    public function timeout(int $milliseconds): DropAnalyticsIndexOptions
    {
        $this->timeoutMilliseconds = $milliseconds;
        return $this;
    }

    /**
     * Sets the parent span.
     *
     * @param RequestSpan $parentSpan the parent span
     *
     * @return DropAnalyticsIndexOptions
     * @since 4.5.0
     */
    public function parentSpan(RequestSpan $parentSpan): DropAnalyticsIndexOptions
    {
        $this->parentSpan = $parentSpan;
        return $this;
    }

    /**
     * @internal
     */
    public static function getParentSpan(?DropAnalyticsIndexOptions $options): ?RequestSpan
    {
        return $options?->parentSpan;
    }

    /**
     * @internal
     */
    public static function export(?DropAnalyticsIndexOptions $options): array
    {
        if ($options == null) {
            return [];
        }

        return [
            'timeoutMilliseconds' => $options->timeoutMilliseconds,
            'ignoreIfDoesNotExist' => $options->ignoreIfDoesNotExist,
            'dataverseName' => $options->dataverseName,
        ];
    }
}
