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

use Couchbase\Exception\AmbiguousTimeoutException;
use Couchbase\Exception\UnambiguousTimeoutException;
use Couchbase\Protostellar\Retries\RetryReason;
use Couchbase\Protostellar\Retries\RetryStrategy;

class ProtostellarRequest
{
    private bool $idempotent;
    private RetryStrategy $retryStrategy;
    private int $retryAttempts;
    private array $retryReasons;

    private float $absoluteTimeout;

    private mixed $grpcRequest;

    private ?array $context;

    public function __construct(
        bool $idempotent,
        RetryStrategy $retryStrategy,
        float $absoluteTimeout,
        mixed $grpcRequest
    )
    {
        $this->idempotent = $idempotent;
        $this->retryStrategy = $retryStrategy;
        $this->retryAttempts = 0;
        $this->absoluteTimeout = $absoluteTimeout;
        $this->grpcRequest = $grpcRequest;
        $this->retryReasons = [];
        $this->context = [];
    }

    public function idempotent(): bool
    {
        return $this->idempotent;
    }

    public function retryStrategy(): RetryStrategy
    {
        return $this->retryStrategy;
    }

    public function retryAttempts(): int
    {
        return $this->retryAttempts;
    }

    public function retryReasons(): ?array
    {
        return $this->retryReasons;
    }

    public function incrementRetryAttempts(RetryReason $reason): void
    {
        $this->retryAttempts++;
        if (!array_key_exists($reason->reason(), $this->retryReasons)) {
            $this->retryReasons[$reason->reason()] = $reason;
        }
    }

    public function timeoutElapsed(): bool
    {
        return $this->absoluteTimeout - (microtime(true) * 1e6) <= 0;
    }

    public function absoluteTimeout(): float
    {
        return $this->absoluteTimeout;
    }

    public function grpcRequest(): mixed
    {
        return $this->grpcRequest;
    }

    public function context(): ?array
    {
        return $this->context;
    }

    public function appendContext(string $key, string|array $value): void
    {
        $this->context[$key] = $value;
    }

    public function cancelDueToTimeout(): RequestBehaviour
    {
        $exception = $this->idempotent()
            ? new UnambiguousTimeoutException(message: "The operation timed out", context: $this->context())
            : new AmbiguousTimeoutException(
                message: "The operation timed out and the state might have been changed",
                context: $this->context()
            );
        return RequestBehaviour::fail($exception);
    }
}
