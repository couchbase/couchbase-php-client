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

namespace Helpers\Metrics;

include_once __DIR__ . "/../CouchbaseTestCase.php";

use Couchbase\ValueRecorder;

class TestValueRecorder implements ValueRecorder
{
    private string $name;
    private array $tags = [];
    private array $values = [];

    public function __construct(string $name, array $tags)
    {
        $this->name = $name;
        $this->tags = $tags;
    }

    public function recordValue(int $value): void
    {
        fprintf(STDERR, "Recording value %d for metric '%s' with tags %s\n", $value, $this->name, json_encode($this->tags));
        $this->values[] = $value;
    }

    public function getValues(): array
    {
        return $this->values;
    }
}
