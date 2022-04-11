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

class TransactionResult
{
    private string $transactionId;
    private bool $unstagingComplete;

    /**
     * @internal
     * @since 4.0.0
     */
    public function __construct(string $transactionId, bool $unstagingComplete)
    {
        $this->transactionId = $transactionId;
        $this->unstagingComplete = $unstagingComplete;
    }

    /**
     * The ID of the completed transaction.
     */
    public function transactionId(): string
    {
        return $this->transactionId;
    }

    /**
     * Whether all documents were successfully unstaged and are now available
     * for non-transactional operations to see.
     */
    public function unstagingComplete(): bool
    {
        return $this->unstagingComplete;
    }
}
