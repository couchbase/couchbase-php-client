<?php

/*
 * Copyright 2022-Present Couchbase, Inc.
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

namespace Couchbase\Protostellar;

use Exception;

class RequestBehaviour
{
    private ?int $retryDuration;
    private ?Exception $exception;
    private function __construct(?int $retryDuration, ?Exception $exception)
    {
        $this->retryDuration = $retryDuration;
        $this->exception = $exception;
    }

    public static function retry(int $retryDuration): RequestBehaviour
    {
        return new RequestBehaviour($retryDuration, null);
    }

    public static function fail(Exception $error): RequestBehaviour
    {
        return new RequestBehaviour(null, $error);
    }

    public function retryDuration(): ?int
    {
        return $this->retryDuration;
    }

    public function exception(): ?Exception
    {
        return $this->exception;
    }
}
