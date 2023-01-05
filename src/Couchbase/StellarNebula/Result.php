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

namespace Couchbase\StellarNebula;

class Result
{
    private string $bucket;
    private string $scope;
    private string $collection;
    private string $id;
    private int|string $cas;

    public function __construct(string $bucket, string $scope, string $collection, string $id, int|string $cas)
    {
        $this->bucket = $bucket;
        $this->scope = $scope;
        $this->collection = $collection;
        $this->id = $id;
        $this->cas = $cas;
    }

    public function bucket(): string
    {
        return $this->bucket;
    }

    public function scope(): string
    {
        return $this->scope;
    }

    public function collection(): string
    {
        return $this->collection;
    }

    public function id(): string
    {
        return $this->id;
    }

    public function cas(): int|string
    {
        return $this->cas;
    }
}
