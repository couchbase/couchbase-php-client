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

include_once __DIR__ . "/Helpers/CouchbaseObservabilityTestCase.php";

use Couchbase\IncrementOptions;
use Couchbase\DecrementOptions;
use Couchbase\AppendOptions;
use Couchbase\PrependOptions;
use Couchbase\RawBinaryTranscoder;
use Couchbase\UpsertOptions;
use Couchbase\BinaryCollection;

class ObservabilityKeyValueBinaryOperationsTest extends Helpers\CouchbaseObservabilityTestCase
{
    private const EXISTING_BINARY_DOC_ID = "observability-binary-kv-test";

    private BinaryCollection $binaryCollection;

    public function setUp(): void
    {
        parent::setUp();
        $this->defaultCollection()->upsert(
            self::EXISTING_BINARY_DOC_ID,
            "foo",
            UpsertOptions::build()
            ->transcoder(new RawBinaryTranscoder())
        );
        $this->tracer()->reset();

        $this->binaryCollection = $this->defaultCollection()->binary();
    }

    public function testIncrement()
    {
        $result = $this->binaryCollection->increment(
            $this->uniqueId(),
            IncrementOptions::build()
                ->initial(0)
                ->delta(2)
                ->parentSpan($this->parentSpan())
        );
        $this->assertEquals(0, $result->content());

        $getSpan = $this->tracer()->getSpans(null, $this->parentSpan())[0];
        $this->assertKvOperationSpan($getSpan, "increment", $this->parentSpan());
    }

    public function testDecrement()
    {
        $result = $this->binaryCollection->decrement(
            $this->uniqueId(),
            DecrementOptions::build()
                ->initial(0)
                ->delta(2)
                ->parentSpan($this->parentSpan())
        );
        $this->assertEquals(0, $result->content());

        $getSpan = $this->tracer()->getSpans(null, $this->parentSpan())[0];
        $this->assertKvOperationSpan($getSpan, "decrement", $this->parentSpan());
    }

    public function testAppend()
    {
        $this->binaryCollection->append(
            self::EXISTING_BINARY_DOC_ID,
            "bar",
            AppendOptions::build()
                ->parentSpan($this->parentSpan())
        );

        $appendSpan = $this->tracer()->getSpans(null, $this->parentSpan())[0];
        $this->assertKvOperationSpan($appendSpan, "append", $this->parentSpan());
    }

    public function testPrepend()
    {
        $this->binaryCollection->prepend(
            self::EXISTING_BINARY_DOC_ID,
            "bar",
            PrependOptions::build()
                ->parentSpan($this->parentSpan())
        );

        $prependSpan = $this->tracer()->getSpans(null, $this->parentSpan())[0];
        $this->assertKvOperationSpan($prependSpan, "prepend", $this->parentSpan());
    }
}
