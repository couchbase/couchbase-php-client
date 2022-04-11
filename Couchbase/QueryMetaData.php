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
class QueryMetaData
{
    private string $status;
    private string $requestId;
    private string $clientContextId;
    private ?string $signature = null;
    private ?string $profile = null;
    private array $warnings;
    private array $errors;
    private ?array $metrics = null;

    /**
     * @internal
     *
     * @param array $meta
     */
    public function __construct(array $meta)
    {
        $this->status = $meta["status"];
        $this->requestId = $meta["requestId"];
        $this->clientContextId = $meta["clientContextId"];
        if (array_key_exists("signature", $meta)) {
            $this->signature = $meta["signature"];
        }
        if (array_key_exists("profile", $meta)) {
            $this->profile = $meta["profile"];
        }
        $this->warnings = [];
        if (array_key_exists("warnings", $meta)) {
            foreach ($meta["warnings"] as $warning) {
                $this->warnings[] = new QueryWarning($warning);
            }
        }
        $this->errors = [];
        if (array_key_exists("errors", $meta)) {
            foreach ($meta["errors"] as $error) {
                $this->errors[] = new QueryWarning($error);
            }
        }
        if (array_key_exists("metrics", $meta)) {
            $this->metrics = $meta["metrics"];
        } else {
            $this->metrics = [
                "errorCount" => 0,
                "mutationCount" => 0,
                "resultCount" => 0,
                "resultSize" => 0,
                "sortCount" => 0,
                "warningCount" => 0,
                "elapsedTime" => 0,
                "executionTime" => 0,
            ];
        }
    }

    /**
     * Returns the query execution status
     *
     * @return string|null
     * @since 4.0.0
     */
    public function status(): ?string
    {
        return $this->status;
    }

    /**
     * Returns the identifier associated with the query
     *
     * @return string|null
     * @since 4.0.0
     */
    public function requestId(): ?string
    {
        return $this->requestId;
    }

    /**
     * Returns the client context id associated with the query
     *
     * @return string
     * @since 4.0.0
     */
    public function clientContextId(): ?string
    {
        return $this->clientContextId;
    }

    /**
     * Returns the signature of the query
     *
     * @return array|null
     * @since 4.0.0
     */
    public function signature(): ?array
    {
        if ($this->signature == null) {
            return null;
        }
        return json_decode($this->signature, true);
    }

    /**
     * Returns any warnings generated during query execution
     *
     * @return array|null
     * @since 4.0.0
     */
    public function warnings(): ?array
    {
        return $this->warnings;
    }

    /**
     * Returns any errors generated during query execution
     *
     * @return array|null
     * @since 4.0.0
     */
    public function errors(): ?array
    {
        return $this->errors;
    }

    /**
     * Returns metrics generated during query execution such as timings and counts.
     * If no metrics were returned then all values will be 0.
     *
     * @return array|null
     * @since 4.0.0
     */
    public function metrics(): ?array
    {
        return $this->metrics;
    }

    /**
     * Returns the profile of the query if enabled
     *
     * @return array|null
     * @since 4.0.0
     */
    public function profile(): ?array
    {
        if ($this->profile == null) {
            return null;
        }
        return json_decode($this->profile, true);
    }
}
