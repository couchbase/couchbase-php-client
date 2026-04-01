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

use Couchbase\Observability\ObservabilityContext;
use Couchbase\Observability\ObservabilityHandler;

class ObservabilityContextTest extends Helpers\CouchbaseObservabilityTestCase
{
    public function testCanCreateSpansFromCoreSpanData()
    {
        $observabilityCtx = new ObservabilityContext(null, $this->tracer(), $this->meter());

        $observabilityCtx->recordOperation(
            "testOp",
            $this->parentSpan(),
            function (ObservabilityHandler $handler) {
                $coreSpans = &$handler->getCoreSpansArray();
                $coreSpans[] = [
                 "name" => "dispatch_to_server",
                 "attributes" => [
                     "db.system.name" => "couchbase",
                     "network.transport" => "tcp",
                 ],
                 "start_timestamp" => 177272033896299000,
                 "end_timestamp" => 177272034132668000,
                ];
            }
        );

        $this->assertCount(1, $this->tracer()->getSpans(null, $this->parentSpan()));
        $operationSpan = $this->tracer()->getSpans(null, $this->parentSpan())[0];
        $this->assertEquals("testOp", $operationSpan->getName());

        $dispatchSpans = $this->tracer()->getSpans(null, $operationSpan);
        $this->assertCount(1, $dispatchSpans);
        $this->assertEquals("dispatch_to_server", $dispatchSpans[0]->getName());
        $this->assertEquals("couchbase", $dispatchSpans[0]->getTags()["db.system.name"]);
        $this->assertEquals("tcp", $dispatchSpans[0]->getTags()["network.transport"]);
        $this->assertEquals(177272033896299000, $dispatchSpans[0]->getStartTimestampNanoseconds());
        $this->assertEquals(177272034132668000, $dispatchSpans[0]->getEndTimestampNanoseconds());
    }
}
