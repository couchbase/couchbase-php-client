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
 * Common interface for all classes, which could be used as a body of SearchQuery
 *
 * Represents full text search query
 *
 * @see https://docs.couchbase.com/php-sdk/current/howtos/full-text-searching-with-sdk.html
 *   Searching from the SDK
 */
interface SearchQuery
{
    function export(): array;
}
