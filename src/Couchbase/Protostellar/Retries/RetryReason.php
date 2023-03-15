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

namespace Couchbase\Protostellar\Retries;

use Couchbase\Exception\InvalidArgumentException;

class RetryReason
{
    public const UNKNOWN = "UNKNOWN";
    public const SOCKET_NOT_AVAILABLE = "SOCKET_NOT_AVAILABLE";
    public const SERVICE_NOT_AVAILABLE = "SERVICE_NOT_AVAILABLE";
    public const NODE_NOT_AVAILABLE = "NODE_NOT_AVAILABLE";
    public const KV_NOT_MY_VBUCKET = "KV_NOT_MY_VBUCKET";
    public const KV_COLLECTION_OUTDATED = "KV_COLLECTION_OUTDATED";
    public const KV_ERROR_MAP_RETRY_INDICATED = "KV_ERROR_MAP_RETRY_INDICATED";
    public const KV_LOCKED = "KV_LOCKED";
    public const KV_TEMPORARY_FAILURE = "KV_TEMPORARY_FAILURE";
    public const KV_SYNC_WRITE_IN_PROGRESS = "KV_SYNC_WRITE_IN_PROGRESS";
    public const KV_SYNC_WRITE_RE_COMMIT_IN_PROGRESS = "KV_SYNC_WRITE_RE_COMMIT_IN_PROGRESS";
    public const SERVICE_RESPONSE_CODE_INDICATED = "SERVICE_RESPONSE_CODE_INDICATED";
    public const SOCKET_CLOSED_WHILE_IN_FLIGHT = "SOCKET_CLOSED_WHILE_IN_FLIGHT";
    public const CIRCUIT_BREAKER_OPEN = "CIRCUIT_BREAKER_OPEN";
    public const QUERY_PREPARED_STATEMENT_FAILURE = "QUERY_PREPARED_STATEMENT_FAILURE";
    public const QUERY_INDEX_NOT_FOUND = "QUERY_INDEX_NOT_FOUND";
    public const ANALYTICS_TEMPORARY_FAILURE = "ANALYTICS_TEMPORARY_FAILURE";
    public const SEARCH_TOO_MANY_REQUESTS = "SEARCH_TOO_MANY_REQUESTS";
    public const VIEWS_TEMPORARY_FAILURE = "VIEWS_TEMPORARY_FAILURE";
    public const VIEWS_NO_ACTIVE_PARTITION = "VIEWS_NO_ACTIVE_PARTITION";
    private const REASONS = [
        self::UNKNOWN => [false, false],
        self::SOCKET_NOT_AVAILABLE => [true, false],
        self::SERVICE_NOT_AVAILABLE => [true, false],
        self::NODE_NOT_AVAILABLE => [true, false],
        self::KV_NOT_MY_VBUCKET => [true, false],
        self::KV_COLLECTION_OUTDATED => [true, true],
        self::KV_ERROR_MAP_RETRY_INDICATED => [true, false],
        self::KV_LOCKED => [true, false],
        self::KV_TEMPORARY_FAILURE => [true, false],
        self::KV_SYNC_WRITE_IN_PROGRESS => [true, false],
        self::KV_SYNC_WRITE_RE_COMMIT_IN_PROGRESS => [true, false],
        self::SERVICE_RESPONSE_CODE_INDICATED => [true, false],
        self::SOCKET_CLOSED_WHILE_IN_FLIGHT => [false, false],
        self::CIRCUIT_BREAKER_OPEN => [true, false],
        self::QUERY_PREPARED_STATEMENT_FAILURE => [true, false],
        self::QUERY_INDEX_NOT_FOUND => [true, false],
        self::ANALYTICS_TEMPORARY_FAILURE => [true, false],
        self::SEARCH_TOO_MANY_REQUESTS => [true, false],
        self::VIEWS_TEMPORARY_FAILURE => [true, false],
        self::VIEWS_NO_ACTIVE_PARTITION => [true, true],
    ];

    private bool $allowsNonIdempotentRetry;
    private bool $alwaysRetry;
    private string $reason;

    /**
     * @throws InvalidArgumentException
     */
    public function __construct(string $reason)
    {
        if (array_key_exists($reason, self::REASONS)) {
            $this->allowsNonIdempotentRetry = self::REASONS[$reason][0];
            $this->alwaysRetry = self::REASONS[$reason][1];
            $this->reason = $reason;
        } else {
            throw new InvalidArgumentException("Invalid Retry Reason provided");
        }
    }

    public static function build(string $reason): RetryReason
    {
        return new RetryReason($reason);
    }

    public function reason(): string
    {
        return $this->reason;
    }

    public function allowsNonIdempotentRetry()
    {
        return $this->allowsNonIdempotentRetry;
    }
    public function alwaysRetry()
    {
        return $this->alwaysRetry;
    }
}
