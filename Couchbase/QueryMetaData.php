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
 * Interface for retrieving metadata such as errors and metrics generated during N1QL queries.
 */
interface QueryMetaData
{
    /**
     * Returns the query execution status
     *
     * @return string|null
     */
    public function status(): ?string;

    /**
     * Returns the identifier associated with the query
     *
     * @return string|null
     */
    public function requestId(): ?string;

    /**
     * Returns the client context id associated with the query
     *
     * @return string|null
     */
    public function clientContextId(): ?string;

    /**
     * Returns the signature of the query
     *
     * @return array|null
     */
    public function signature(): ?array;

    /**
     * Returns any warnings generated during query execution
     *
     * @return array|null
     */
    public function warnings(): ?array;

    /**
     * Returns any errors generated during query execution
     *
     * @return array|null
     */
    public function errors(): ?array;

    /**
     * Returns metrics generated during query execution such as timings and counts
     *
     * @return array|null
     */
    public function metrics(): ?array;

    /**
     * Returns the profile of the query if enabled
     *
     * @return array|null
     */
    public function profile(): ?array;
}
