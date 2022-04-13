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

class TransactionsCleanupConfiguration
{
    private ?int $cleanupWindowMilliseconds = null;
    private ?bool $disableLostAttemptCleanup = null;
    private ?bool $disableClientAttemptCleanup = null;

    /**
     * Specifies the period of the cleanup system.
     *
     * @param int $milliseconds
     *
     * @return TransactionsCleanupConfiguration
     * @since 4.0.0
     */
    public function cleanupWindow(int $milliseconds): TransactionsCleanupConfiguration
    {
        $this->cleanupWindowMilliseconds = $milliseconds;
        return $this;
    }

    /**
     * Specifies whether the cleanup system should clean lost attempts.
     *
     * @param bool $disable
     *
     * @return TransactionsCleanupConfiguration
     * @since 4.0.0
     */
    public function disableLostAttemptCleanup(bool $disable): TransactionsCleanupConfiguration
    {
        $this->disableLostAttemptCleanup = $disable;
        return $this;
    }

    /**
     * Specifies whether the cleanup system should clean client attempts.
     *
     * @param bool $disable
     *
     * @return TransactionsCleanupConfiguration
     * @since 4.0.0
     */
    public function disableClientAttemptCleanup(bool $disable): TransactionsCleanupConfiguration
    {
        $this->disableClientAttemptCleanup = $disable;
        return $this;
    }

    /**
     * @internal
     * @return array
     * @since 4.0.0
     */
    public function export(): array
    {
        return [
            'cleanupWindow' => $this->cleanupWindowMilliseconds,
            'disableLostAttemptCleanup' => $this->disableLostAttemptCleanup,
            'disableClientAttemptCleanup' => $this->disableClientAttemptCleanup,
        ];
    }
}
