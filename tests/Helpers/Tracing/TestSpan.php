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

namespace Helpers\Tracing;

include_once __DIR__ . "/../CouchbaseTestCase.php";

use Couchbase\RequestSpan;
use Couchbase\Observability\StatusCode;

class TestSpan implements RequestSpan
{
    private string $name;
    private ?RequestSpan $parent;
    private ?int $startTimestampNanoseconds;
    private ?int $endTimestampNanoseconds = null;
    private array $tags = [];
    private StatusCode $status = StatusCode::UNSET;

    public function __construct(string $name, ?RequestSpan $parent = null, ?int $startTimestampNanoseconds = null)
    {
        $this->name = $name;
        $this->parent = $parent;
        if (is_null($startTimestampNanoseconds)) {
            $this->startTimestampNanoseconds = (int) (microtime(true) * 1e9);
        } else {
            $this->startTimestampNanoseconds = $startTimestampNanoseconds;
        }
    }

    public function addTag(string $key, int|string $value): void
    {
        $this->tags[$key] = $value;
    }

    public function setStatus(StatusCode $statusCode): void
    {
        $this->status = $statusCode;
    }

    public function end(?int $endTimestampNanoseconds = null): void
    {
        if (is_null($endTimestampNanoseconds)) {
            $this->endTimestampNanoseconds = (int) (microtime(true) * 1e9);
        } else {
            $this->endTimestampNanoseconds = $endTimestampNanoseconds;
        }
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getParent(): ?TestSpan
    {
        if (is_null($this->parent)) {
            return null;
        }
        if ($this->parent instanceof TestSpan) {
            return $this->parent;
        }
        throw new \RuntimeException("Parent span is not a TestSpan");
    }

    public function getStartTimestampNanoseconds(): int
    {
        return $this->startTimestampNanoseconds;
    }

    public function getEndTimestampNanoseconds(): ?int
    {
        return $this->endTimestampNanoseconds;
    }

    public function getTags(): array
    {
        return $this->tags;
    }

    public function getStatus(): StatusCode
    {
        return $this->status;
    }
}
