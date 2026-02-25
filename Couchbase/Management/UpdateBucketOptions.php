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

class UpdateBucketOptions
{
    private ?int $timeoutMilliseconds = null;
    private ?RequestSpan $parentSpan = null;

    /**
     * Static helper to keep code more readable
     *
     * @return UpdateBucketOptions
     * @since 4.0.0
     */
    public static function build(): UpdateBucketOptions
    {
        return new UpdateBucketOptions();
    }

    /**
     * Sets the operation timeout in milliseconds.
     *
     * @param int $milliseconds the operation timeout to apply
     *
     * @return UpdateBucketOptions
     * @since 4.0.0
     */
    public function timeout(int $milliseconds): UpdateBucketOptions
    {
        $this->timeoutMilliseconds = $milliseconds;
        return $this;
    }

    /**
     * Sets the parent span.
     *
     * @param RequestSpan $parentSpan the parent span
     *
     * @return UpdateBucketOptions
     * @since 4.5.0
     */
    public function parentSpan(RequestSpan $parentSpan): UpdateBucketOptions
    {
        $this->parentSpan = $parentSpan;
        return $this;
    }

    /**
     * @internal
     */
    public static function getParentSpan(?UpdateBucketOptions $options): ?RequestSpan
    {
        return $options?->parentSpan;
    }


    /**
     * @param UpdateBucketOptions|null $options
     *
     * @return array
     * @internal
     * @since 4.0.0
     */
    public static function export(?UpdateBucketOptions $options): array
    {
        if ($options == null) {
            return [];
        }
        return [
            'timeoutMilliseconds' => $options->timeoutMilliseconds,
        ];
    }
}
