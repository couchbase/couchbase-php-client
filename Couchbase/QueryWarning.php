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

/**
 * Set of warning or error messages returned by a query.
 */
class QueryWarning
{
    private int $code;
    private string $message;

    /**
     * @internal
     *
     * @param array $warning
     */
    public function __construct(array $warning)
    {
        $this->code = $warning["code"];
        $this->message = $warning["message"];
    }

    /**
     * Returns the error code.
     *
     * @return int
     * @since 4.0.0
     */
    public function code(): int
    {
        return $this->code;
    }

    /**
     * Returns the error message.
     *
     * @return int
     * @since 4.0.0
     */
    public function message(): int
    {
        return $this->message;
    }
}
