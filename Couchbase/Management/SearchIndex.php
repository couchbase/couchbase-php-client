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

namespace Couchbase\Management;

class SearchIndex implements JsonSerializable
{
    public function jsonSerialize(): mixed
    {
    }

    public function type(): string
    {
    }

    public function uuid(): string
    {
    }

    public function params(): array
    {
    }

    public function sourceType(): string
    {
    }

    public function sourceUuid(): string
    {
    }

    public function sourceName(): string
    {
    }

    public function sourceParams(): array
    {
    }

    public function setType(string $type): SearchIndex
    {
    }

    public function setUuid(string $uuid): SearchIndex
    {
    }

    public function setParams(string $params): SearchIndex
    {
    }

    public function setSourceType(string $type): SearchIndex
    {
    }

    public function setSourceUuid(string $uuid): SearchIndex
    {
    }

    public function setSourcename(string $params): SearchIndex
    {
    }

    public function setSourceParams(string $params): SearchIndex
    {
    }
}
