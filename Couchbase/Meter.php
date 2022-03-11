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

/**
 * Providers an interface to create value recorders for recording metrics.
 */
interface Meter
{
    /**
     * Creates a new value recorder for a metric with the specified tags.
     *
     * @param string $name The name of the metric.
     * @param array $tags The tags to associate with the metric.
     *
     * @return ValueRecorder
     */
    public function valueRecorder(string $name, array $tags): ValueRecorder;
}
