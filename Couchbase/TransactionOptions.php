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

class TransactionOptions
{
    private ?string $durabilityLevel = null;
    private ?int $timeoutMilliseconds = null;
    private ?int $keyValueTimeoutMilliseconds = null;

    /**
     * Specifies the timeout for the transaction.
     *
     * @param int $milliseconds the operation timeout to apply
     *
     * @return TransactionOptions
     * @since 4.0.0
     */
    public function timeout(int $milliseconds): TransactionOptions
    {
        $this->timeoutMilliseconds = $milliseconds;
        return $this;
    }

    /**
     * Specifies the default timeout for KV operations, in milliseconds.
     *
     * @param int $milliseconds
     *
     * @return TransactionsOptins
     * @since 4.1.4
     */
    public function keyValueTimeout(int $milliseconds): TransactionsOptions
    {
        $this->keyValueTimeoutMilliseconds = $milliseconds;
        return $this;
    }

    /**
     * Specifies the level of synchronous durability level.
     *
     * @param string|int $level the durability level to enforce
     * @param int|null $timeoutSeconds
     *
     * @return TransactionOptions
     * @throws Exception\InvalidArgumentException
     * @see DurabilityLevel
     * @since 4.0.0
     */
    public function durabilityLevel($level, ?int $timeoutSeconds): TransactionOptions
    {
        if (gettype($level) == "integer") {
            $level = Deprecations::convertDeprecatedDurabilityLevel(__METHOD__, $level);
        }
        $this->durabilityLevel = $level;
        return $this;
    }

    /**
     * @param TransactionOptions|null $options
     *
     * @return array
     * @internal
     * @since 4.0.0
     */
    public static function export(?TransactionOptions $options): array
    {
        if ($options == null) {
            return [];
        }
        return [
            'timeout' => $options->timeoutMilliseconds,
            'keyValueTimeout' => $options->keyValueTimeoutMilliseconds,
            'durabilityLevel' => $options->durabilityLevel,
        ];
    }
}
