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

use Couchbase\Utilities\Deprecations;

class AppendOptions
{
    private ?int $timeoutMilliseconds = null;
    private ?string $durabilityLevel = null;

    /**
     * Static helper to keep code more readable
     *
     * @return AppendOptions
     * @since 4.0.0
     */
    public static function build(): AppendOptions
    {
        return new AppendOptions();
    }

    /**
     * Sets the operation timeout in milliseconds.
     *
     * @param int $milliseconds the operation timeout to apply
     *
     * @return AppendOptions
     * @since 4.0.0
     */
    public function timeout(int $milliseconds): AppendOptions
    {
        $this->timeoutMilliseconds = $milliseconds;
        return $this;
    }

    /**
     * Sets the durability level to enforce when writing the document.
     *
     * @param string|int $level the durability level to enforce
     * @param int|null $timeoutSeconds
     *
     * @return AppendOptions
     * @throws Exception\InvalidArgumentException
     * @see DurabilityLevel
     * @since 4.0.0
     */
    public function durabilityLevel($level): AppendOptions
    {
        if (gettype($level) == "integer") {
            $level = Deprecations::convertDeprecatedDurabilityLevel(__METHOD__, $level);
        }
        $this->durabilityLevel = $level;
        return $this;
    }

    /**
     * @internal
     *
     * @param AppendOptions|null $options
     *
     * @return array
     * @since 4.0.0
     */
    public static function export(?AppendOptions $options): array
    {
        if ($options == null) {
            return [];
        }
        return [
            'timeoutMilliseconds' => $options->timeoutMilliseconds,
            'durabilityLevel' => $options->durabilityLevel,
        ];
    }
}
