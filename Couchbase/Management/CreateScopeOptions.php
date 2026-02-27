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

class CreateScopeOptions
{
    private ?int $timeoutMilliseconds;
    private ?RequestSpan $parentSpan = null;

    /**
     * Static helper to make code more readable
     *
     * @return CreateScopeOptions
     * @since 4.1.3
     */
    public static function build(): CreateScopeOptions
    {
        return new CreateScopeOptions();
    }
    /**
     * Sets the operation timeout in milliseconds
     *
     * @param int $milliseconds the operation timeout to apply
     *
     * @return CreateScopeOptions
     * @since 4.1.3
     */
    public function timeout(int $milliseconds): CreateScopeOptions
    {
        $this->timeoutMilliseconds = $milliseconds;
        return $this;
    }

    /**
     * Sets the parent span.
     *
     * @param RequestSpan $parentSpan the parent span
     *
     * @return CreateScopeOptions
     * @since 4.5.0
     */
    public function parentSpan(RequestSpan $parentSpan): CreateScopeOptions
    {
        $this->parentSpan = $parentSpan;
        return $this;
    }

    /**
     * @internal
     */
    public static function getParentSpan(?CreateScopeOptions $options): ?RequestSpan
    {
        return $options?->parentSpan;
    }

    /**
     * @param CreateScopeOptions|null $options
     * @return array
     * @internal
     * @since 4.1.3
     */
    public static function export(?CreateScopeOptions $options): array
    {
        if ($options == null) {
            return [];
        }
        return [
            'timeoutMilliseconds' => $options->timeoutMilliseconds,
        ];
    }
}
