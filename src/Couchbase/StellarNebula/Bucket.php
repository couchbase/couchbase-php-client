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

use Couchbase\StellarNebula\Internal\Client;

class Bucket
{
    private Client $client;
    private string $name;

    public function __construct(Client $client, string $name)
    {
        $this->client = $client;
        $this->name = $name;
    }

    public function name(): string
    {
        return $this->name;
    }

    public function scope(string $name): Scope
    {
        return new Scope($this->client, $this->name, $name);
    }

    public function defaultScope(): Scope
    {
        return $this->scope(Scope::DEFAULT_NAME);
    }

    public function collection(string $name): Collection
    {
        return $this->defaultScope()->collection($name);
    }

    public function defaultCollection(): Collection
    {
        return $this->collection(Collection::DEFAULT_NAME);
    }
}
