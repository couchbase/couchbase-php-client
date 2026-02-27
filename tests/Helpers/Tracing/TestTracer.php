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

namespace Helpers\Tracing;

include_once __DIR__ . "/../CouchbaseTestCase.php";

use Couchbase\RequestTracer;
use Couchbase\RequestSpan;

class TestTracer implements RequestTracer
{
    private array $spans = [];

    public function reset(): void
    {
        $this->spans = [];
    }

    public function requestSpan(string $name, ?RequestSpan $parent = null, ?int $startTimestampNanoseconds = null): RequestSpan
    {
        $span = new TestSpan($name, $parent, $startTimestampNanoseconds);
        $this->spans[] = $span;
        return $span;
    }

    public function close(): void
    {
    }

    public function getSpans(?string $name = null, TestSpan|ParentSpanRequirement $parent = ParentSpanRequirement::ANY): array
    {
        return array_values(
            array_filter(
                $this->spans,
                function ($span) use ($name, $parent) {
                    if (!is_null($name) && ($span->getName() != $name)) {
                        return false;
                    }

                    return match ($parent) {
                        ParentSpanRequirement::ANY => true,
                        ParentSpanRequirement::ROOT => is_null($span->getParent()),
                        default => !is_null($span->getParent()) && $span->getParent() == $parent,
                    };
                }
            )
        );
    }
}
