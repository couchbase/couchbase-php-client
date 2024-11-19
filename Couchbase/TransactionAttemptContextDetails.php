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

use Couchbase\Exception\TransactionException;
use Couchbase\Exception\UnsupportedOperationException;

/**
 * @internal
 */
class TransactionAttemptContextDetails
{
    /**
     * @var resource
     */
    private $transaction;

    /**
     * @param resource $transactions
     * @param TransactionOptions|null $options
     *
     * @internal
     */
    public function __construct($transactions, ?TransactionOptions $options = null)
    {
        $function = COUCHBASE_EXTENSION_NAMESPACE . '\\createTransactionContext';
        $this->transaction = $function($transactions, TransactionOptions::export($options));
    }

    /**
     * Returns user-facing API for transaction logic
     * @return TransactionAttemptContext
     * @internal
     */
    public function transactionAttemptContext(): TransactionAttemptContext
    {
        return new TransactionAttemptContext($this->transaction);
    }

    /**
     * @return void
     * @throws TransactionException
     * @internal
     */
    public function newAttempt()
    {
        $function = COUCHBASE_EXTENSION_NAMESPACE . '\\transactionNewAttempt';
        return $function($this->transaction);
    }

    /**
     * @return void
     * @throws TransactionException
     * @internal
     */
    public function rollback()
    {
        $function = COUCHBASE_EXTENSION_NAMESPACE . '\\transactionRollback';
        return $function($this->transaction);
    }

    /**
     * @return TransactionResult|null
     * @throws TransactionException
     * @internal
     */
    public function commit(): ?TransactionResult
    {
        $function = COUCHBASE_EXTENSION_NAMESPACE . '\\transactionCommit';
        $response = $function($this->transaction);
        if ($response) {
            return new TransactionResult($response);
        }
        return null;
    }
}
