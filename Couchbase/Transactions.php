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

use Exception;

class Transactions
{
    private ?TransactionsConfiguration $options = null;

    /**
     * @var resource
     */
    private $core;

    /**
     * @private
     *
     * @param ?TransactionsConfiguration $options
     * @param $core
     *
     * @since 4.0.0
     */
    public function __construct($core, ?TransactionsConfiguration $options = null)
    {
        $this->core = $core;
        $this->options = $options;
    }

    /**
     * Executes a transactions
     *
     * @param callable $logic The transaction closure to execute
     * @param TransactionOptions|null $options configuration options for the transaction
     *
     * @return TransactionResult
     * @throws Exception
     */
    public function run(callable $logic, ?TransactionOptions $options): TransactionResult
    {
        $transaction = new TransactionAttemptContext($this, $options);

        while (true) {
            $transaction->newAttempt();
            try {
                $logic($transaction);
            } catch (Exception $exception) {
                $transaction->rollback();
                throw $exception;
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
