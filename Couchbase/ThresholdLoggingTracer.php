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

namespace Couchbase;

use Couchbase\Exception\UnsupportedOperationException;

/**
 * This implements a basic default tracer which keeps track of operations
 * which falls outside a specified threshold.  Note that to reduce the
 * performance impact of using this tracer, this class is not actually
 * used by the SDK, and simply acts as a placeholder which triggers a
 * native implementation to be used instead.
 */
class ThresholdLoggingTracer implements RequestTracer
{
    /**
     * @throws UnsupportedOperationException
     */
    public function requestSpan(string $name, RequestSpan $parent = null)
    {
        throw new UnsupportedOperationException();
    }
}
