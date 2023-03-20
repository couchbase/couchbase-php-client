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

use Couchbase\Exception\TransactionFailedException;
use Exception;

class Transactions
{
    /**
     * @var resource
     */
    private $transactions;

    /**
     * @param ?TransactionsConfiguration $configuration
     * @param resource $core
     *
     * @internal
     *
     * @since 4.0.0
     */
    public function __construct($core, ?TransactionsConfiguration $configuration = null)
    {
        $this->transactions = Extension\createTransactions($core, TransactionsConfiguration::export($configuration));
    }

    /**
     * Executes a transactions
     *
     * @param callable $logic The transaction closure to execute. The callable receives single argument of type
     *     {TransactionAttemptContext}.
     * @param TransactionOptions|null $options configuration options for the transaction
     *
     * @return TransactionResult
     * @throws Exception
     */
    public function run(callable $logic, ?TransactionOptions $options = null): TransactionResult
    {
        $transaction = new TransactionAttemptContextDetails($this->transactions, $options);

        while (true) {
            $transaction->newAttempt();
            try {
                $logic($transaction->transactionAttemptContext());
            } catch (Exception $exception) {
                $transaction->rollback();
                throw new TransactionFailedException("Exception caught during execution of transaction logic. " . $exception->getMessage(), 0, $exception);
            }

            try {
                $result = $transaction->commit(); // this is actually finalize internally
                if ($result == null) {
                    // no result and no error, try again
                    continue;
                }

                return $result;
            } catch (Exception $exception) {
                // commit failed, retry...
            }
        }
    }
}
