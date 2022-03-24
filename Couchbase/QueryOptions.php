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

class QueryOptions
{
    private ?int $timeoutMilliseconds = null;
    private ?MutationState $consistentWith = null;
    private ?int $scanConsistency = null;
    private ?int $scanCap = null;
    private ?int $pipelineCap = null;
    private ?int $pipelineBatch = null;
    private ?int $maxParallelism = null;
    private ?int $profile = null;
    private ?bool $readonly = null;
    private ?bool $flexIndex = null;
    private ?bool $adHoc = null;
    private ?array $namedParams = null;
    private ?array $posParams = null;
    private ?array $raw = null;
    private ?string $clientContextId = null;
    private ?bool $metrics = null;
    private ?bool $preserveExpiry = null;

    /**
     * Sets the operation timeout in milliseconds.
     *
     * @param int $milliseconds the operation timeout to apply
     * @return QueryOptions
     */
    public function timeout(int $milliseconds): QueryOptions
    {
        $this->timeoutMilliseconds = $milliseconds;
        return $this;
    }

    /**
     * Sets the mutation state to achieve consistency with for read your own writes (RYOW).
     *
     * @param MutationState $state the mutation state to achieve consistency with
     * @return QueryOptions
     */
    public function consistentWith(MutationState $state): QueryOptions
    {
        $this->consistentWith = $state;
        return $this;
    }

    /**
     * Sets the scan consistency.
     *
     * @param int $consistencyLevel the scan consistency level.
     * @return QueryOptions
     */
    public function scanConsistency(int $consistencyLevel): QueryOptions
    {
        $this->scanConsistency = $consistencyLevel;
        return $this;
    }

    /**
     * Sets the maximum buffered channel size between the indexer client and the query service for index scans.
     *
     * @param int $cap the maximum buffered channel size
     * @return QueryOptions
     */
    public function scanCap(int $cap): QueryOptions
    {
        $this->scanCap = $cap;
        return $this;
    }

    /**
     * Sets the maximum number of items each execution operator can buffer between various operators.
     *
     * @param int $cap the maximum number of items each execution operation can buffer
     * @return QueryOptions
     */
    public function pipelineCap(int $cap): QueryOptions
    {
        $this->pipelineCap = $cap;
        return $this;
    }

    /**
     * Sets the number of items execution operators can batch for fetch from the KV service.
     *
     * @param int $batchSize the pipeline batch size
     * @return QueryOptions
     */
    public function pipelineBatch(int $batchSize): QueryOptions
    {
        $this->pipelineBatch = $batchSize;
        return $this;
    }

    /**
     * Sets the maximum number of index partitions, for computing aggregation in parallel.
     *
     * @param int $max the number of index partitions
     * @return QueryOptions
     */
    public function maxParallelism(int $max): QueryOptions
    {
        $this->maxParallelism = $max;
        return $this;
    }

    /**
     * Sets the query profile mode to use.
     *
     * @param int $mode the query profile mode
     * @return QueryOptions
     */
    public function profile(int $mode): QueryOptions
    {
        $this->profile = $mode;
        return $this;
    }

    /**
     * Sets whether or not this query is readonly.
     *
     * @param bool $readonly whether the query is readonly
     * @return QueryOptions
     */
    public function readonly(bool $readonly): QueryOptions
    {
        $this->readonly = $readonly;
        return $this;
    }

    /**
     * Sets whether or not this query allowed to use FlexIndex (full text search integration).
     *
     * @param bool $enabled whether the FlexIndex allowed
     * @return QueryOptions
     */
    public function flexIndex(bool $enabled): QueryOptions
    {
        $this->flexIndex = $enabled;
        return $this;
    }

    /**
     * Sets whether this query is adhoc.
     *
     * @param bool $enabled whether the query is adhoc
     * @return QueryOptions
     */
    public function adhoc(bool $enabled): QueryOptions
    {
        $this->adHoc = $enabled;
        return $this;
    }

    /**
     * Sets the named parameters for this query.
     *
     * @param array $pairs the associative array of parameters
     * @return QueryOptions
     */
    public function namedParameters(array $pairs): QueryOptions
    {
        $this->namedParams = $pairs;
        return $this;
    }

    /**
     * Sets the positional parameters for this query.
     *
     * @param array $params the array of parameters
     * @return QueryOptions
     */
    public function positionalParameters(array $params): QueryOptions
    {
        $this->posParams = $params;
        return $this;
    }

    /**
     * Sets any extra query parameters that the SDK does not provide an option for.
     *
     * @param string $key the name of the parameter
     * @param string $value the value of the parameter
     * @return QueryOptions
     */
    public function raw(string $key, $value): ViewOptions
    {
        if ($this->raw == null) {
            $this->raw = array();
        }

        $this->raw[$key] = $value;
        return $this;
    }

    /**
     * Sets the client context id for this query.
     *
     * @param string $id the client context id
     * @return QueryOptions
     */
    public function clientContextId(string $id): QueryOptions
    {
        $this->clientContextId = $id;
        return $this;
    }

    /**
     * Sets whether or not to return metrics with the query.
     *
     * @param bool $enabled whether to return metrics
     * @return QueryOptions
     */
    public function metrics(bool $enabled): QueryOptions
    {
        $this->metrics = $enabled;
        return $this;
    }

    /**
     * Sets whether to tell the query engine to preserve expiration values set on any documents modified by this query.
     *
     * @param bool $preserve whether to preserve expiration values.
     * @return QueryOptions
     */
    public function preserveExpiry(bool $preserve): QueryOptions
    {
        $this->preserveExpiry = $preserve;
        return $this;
    }

    public function export(string $scopeName = null, string $scopeQualifier = null): array
    {
        $posParams = null;
        if ($this->posParams != null) {
            foreach ($this->posParams as $param) {
                $posParams[] = json_encode($param);
            }
        }
        $namedParams = null;
        if ($this->namedParams != null) {
            foreach ($this->namedParams as $key => $param) {
                $namedParams[$key] = json_encode($param);
            }
        }

        return [
            'timeoutMilliseconds' => $this->timeoutMilliseconds,

            "consistentWith" => $this->consistentWith == null ? null : $this->consistentWith->export(),
            'scanConsistency' => $this->scanConsistency,
            'scanCap' => $this->scanCap,
            'pipelineCap' => $this->pipelineCap,
            'pipelineBatch' => $this->pipelineBatch,
            'maxParallelism' => $this->maxParallelism,
            'profile' => $this->profile,
            'readonly' => $this->readonly,
            'flexIndex' => $this->flexIndex,
            'adHoc' => $this->adHoc,
            'namedParams' => $namedParams,
            'posParams' => $posParams,
            'raw' => $this->raw,
            'clientContextId' => $this->clientContextId,
            'metrics' => $this->metrics,
            'preserveExpiry' => $this->preserveExpiry,
            'scopeName' => $scopeName,
            'scopeQualifier' => $scopeQualifier
        ];
    }
}
