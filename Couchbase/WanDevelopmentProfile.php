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
 * Definition of the wan_development profile from RFC-0074
 */
class WanDevelopmentProfile implements ConfigProfile
{
    /**
     * @param ClusterOptions $options
     * @return void
     * @since 4.0.1
     */
    public function apply(ClusterOptions $options): void
    {
        $options->connectTimeout(20_000);
        $options->keyValueTimeout(20_000);
        $options->keyValueDurableTimeout(20_000);
        $options->viewTimeout(120_000);
        $options->queryTimeout(120_000);
        $options->analyticsTimeout(120_000);
        $options->searchTimeout(120_000);
        $options->managementTimeout(120_000);
    }
}
