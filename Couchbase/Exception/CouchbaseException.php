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

namespace Couchbase\Exception;

use Exception;
use Throwable;

/**
 *  Base exception for exceptions that are thrown originating from Couchbase operations.
 */
class CouchbaseException extends Exception
{
    private ?array $context;

    public function __construct($message = "", $code = 0, Throwable $previous = null, array $context = null)
    {
        parent::__construct($message, $code, $previous);
        $this->context = $context;
    }

    /**
     * Returns error details.
     *
     * @return array|null
     * @since 4.0.0
     */
    public function getContext(): ?array
    {
        return $this->context;
    }
}
