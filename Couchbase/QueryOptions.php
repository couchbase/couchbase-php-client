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

use Couchbase\Exception\InvalidArgumentException;
use Couchbase\Utilities\Deprecations;

class QueryOptions
{
    private ?int $timeoutMilliseconds = null;
    private ?MutationState $consistentWith = null;
    private ?string $scanConsistency = null;
    private ?int $scanCap = null;
    private ?int $pipelineCap = null;
    private ?int $pipelineBatch = null;
    private ?int $maxParallelism = null;
    private ?string $profile = null;
    private ?int $scanWaitMilliseconds = null;
    private ?bool $readonly = null;
    private ?bool $flexIndex = null;
    private ?bool $adHoc = null;
    private ?array $namedParameters = null;
    private ?array $positionalParameters = null;
    private ?array $raw = null;
    private ?string $clientContextId = null;
    private ?bool $metrics = null;
    private ?bool $preserveExpiry = null;
    private ?string $queryContext = null;
    private ?bool $useReplica = null;
    private Transcoder $transcoder;

    /**
     * @since 4.0.0
     */
    public function __construct()
    {
        $this->transcoder = JsonTranscoder::getInstance();
    }

    /**
     * Static helper to keep code more readable
     *
     * @return QueryOptions
     * @since 4.0.0
     */
    public static function build(): QueryOptions
    {
        return new QueryOptions();
    }

    /**
     * Sets the operation timeout in milliseconds.
     *
     * @param int $milliseconds the operation timeout to apply
     *
     * @return QueryOptions
     * @since 4.0.0
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
     *
     * @return QueryOptions
     * @since 4.0.0
     */
    public function consistentWith(MutationState $state): QueryOptions
    {
        $this->consistentWith = $state;
        return $this;
    }

    /**
     * Sets the scan consistency.
     *
     * @param string|int $consistencyLevel the scan consistency level.
     *
     * @return QueryOptions
     * @throws InvalidArgumentException
     * @see QueryScanConsistency
     * @since 4.0.0
     */
    public function scanConsistency($consistencyLevel): QueryOptions
    {
        if (gettype($consistencyLevel) == "integer") {
            $consistencyLevel = Deprecations::convertDeprecatedQueryScanConsistency(__METHOD__, $consistencyLevel);
        }
        $this->scanConsistency = $consistencyLevel;
        return $this;
    }

    /**
     * Sets the maximum buffered channel size between the indexer client and the query service for index scans.
     *
     * @param int $cap the maximum buffered channel size
     *
     * @return QueryOptions
     * @since 4.0.0
     */
    public function scanCap(int $cap): QueryOptions
    {
        $this->scanCap = $cap;
        return $this;
    }

    /**
     * This is an advanced option, see the query service reference for more
     * information on the proper use and tuning of this option.
     *
     * @param int $milliseconds
     *
     * @return QueryOptions
     * @since 4.0.0
     */
    public function scanWait(int $milliseconds): QueryOptions
    {
        $this->scanWaitMilliseconds = $milliseconds;
        return $this;
    }

    /**
     * Sets the maximum number of items each execution operator can buffer between various operators.
     *
     * @param int $cap the maximum number of items each execution operation can buffer
     *
     * @return QueryOptions
     * @since 4.0.0
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
     *
     * @return QueryOptions
     * @since 4.0.0
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
     *
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
     * @param string|int $mode the query profile mode
     *
     * @return QueryOptions
     * @since 4.0.0
     */
    public function profile($mode): QueryOptions
    {
        if (gettype($mode) == "integer") {
            $mode = Deprecations::convertDeprecatedQueryProfile(__METHOD__, $mode);
        }
        $this->profile = $mode;
        return $this;
    }

    /**
     * Sets whether this query is readonly.
     *
     * @param bool $readonly whether the query is readonly
     *
     * @return QueryOptions
     * @since 4.0.0
     */
    public function readonly(bool $readonly): QueryOptions
    {
        $this->readonly = $readonly;
        return $this;
    }

    /**
     * Sets whether this query allowed to use FlexIndex (full text search integration).
     *
     * @param bool $enabled whether the FlexIndex allowed
     *
     * @return QueryOptions
     * @since 4.0.0
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
     *
     * @return QueryOptions
     * @since 4.0.0
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
     *
     * @return QueryOptions
     * @since 4.0.0
     */
    public function namedParameters(array $pairs): QueryOptions
    {
        $this->namedParameters = $pairs;
        return $this;
    }

    /**
     * Sets the positional parameters for this query.
     *
     * @param array $params the array of parameters
     *
     * @return QueryOptions
     * @since 4.0.0
     */
    public function positionalParameters(array $params): QueryOptions
    {
        $this->positionalParameters = $params;
        return $this;
    }

    /**
     * Sets any extra query parameters that the SDK does not provide an option for.
     *
     * @param string $key the name of the parameter
     * @param string $value the value of the parameter
     *
     * @return QueryOptions
     * @since 4.0.0
     */
    public function raw(string $key, $value): ViewOptions
    {
        if ($this->raw == null) {
            $this->raw = [];
        }

        $this->raw[$key] = $value;
        return $this;
    }

    /**
     * Sets the client context id for this query.
     *
     * @param string $id the client context id
     *
     * @return QueryOptions
     * @since 4.0.0
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
     *
     * @return QueryOptions
     * @since 4.0.0
     */
    public function metrics(bool $enabled): QueryOptions
    {
        $this->metrics = $enabled;
        return $this;
    }

    /**
     * Associate scope name with query
     *
     * @param string $name the name of the scope
     *
     * @return QueryOptions
     * @deprecated
     * @since 4.0.0
     */
    public function scopeName(string $name): QueryOptions
    {
        trigger_error(
            'Method ' . __METHOD__ . ' is deprecated, use scope level query()',
            E_USER_DEPRECATED
        );
        return $this;
    }

    /**
     * Associate scope qualifier (also known as `query_context`) with the query.
     *
     * The qualifier must be in form `${bucketName}.${scopeName}` or `default:${bucketName}.${scopeName}`
     *
     * @param string $qualifier the scope qualifier
     *
     * @return QueryOptions
     * @deprecated
     * @since 4.0.0
     */
    public function scopeQualifier(string $qualifier): QueryOptions
    {
        trigger_error(
            'Method ' . __METHOD__ . ' is deprecated, use scope level query()',
            E_USER_DEPRECATED
        );
        $this->queryContext = $qualifier;
        return $this;
    }

    /**
     * Sets whether to tell the query engine to preserve expiration values set on any documents modified by this query.
     *
     * @param bool $preserve whether to preserve expiration values.
     *
     * @return QueryOptions
     * @since 4.0.0
     */
    public function preserveExpiry(bool $preserve): QueryOptions
    {
        $this->preserveExpiry = $preserve;
        return $this;
    }

    /**
     * Sets whether the query engine should use replica nodes for KV fetches if the active node is down.
     *
     * @param bool $useReplica Whether to use replica nodes for KV fetches
     *
     * @return QueryOptions
     * @since 4.1.5
     */
    public function useReplica(bool $useReplica): QueryOptions
    {
        $this->useReplica = $useReplica;
        return $this;
    }

    /**
     * Associate custom transcoder with the request.
     *
     * @param Transcoder $transcoder
     *
     * @return QueryOptions
     * @since 4.0.0
     */
    public function transcoder(Transcoder $transcoder): QueryOptions
    {
        $this->transcoder = $transcoder;
        return $this;
    }

    /**
     * Returns associated transcoder.
     *
     * @param QueryOptions|null $options
     *
     * @return Transcoder
     * @since 4.0.0
     */
    public static function getTranscoder(?QueryOptions $options): Transcoder
    {
        if ($options == null) {
            return JsonTranscoder::getInstance();
        }
        return $options->transcoder;
    }

    public static function export(?QueryOptions $options, string $scopeName = null, string $bucketName = null): array
    {
        $defaultQueryContext = null;
        if ($scopeName != null && $bucketName != null) {
            $defaultQueryContext = sprintf("default:`%s`.`%s`", $bucketName, $scopeName);
        }

        if ($options == null) {
            return [
                'queryContext' => $defaultQueryContext
            ];
        }

        $positionalParameters = null;
        if ($options->positionalParameters != null) {
            foreach ($options->positionalParameters as $param) {
                $positionalParameters[] = json_encode($param);
            }
        }
        $namedParameters = null;
        if ($options->namedParameters != null) {
            foreach ($options->namedParameters as $key => $param) {
                $namedParameters[$key] = json_encode($param);
            }
        }
        $raw = null;
        if ($options->raw != null) {
            foreach ($options->raw as $key => $param) {
                $raw[$key] = json_encode($param);
            }
        }

        return [
            'timeoutMilliseconds' => $options->timeoutMilliseconds,

            "consistentWith" => $options->consistentWith == null ? null : $options->consistentWith->export(),
            'scanConsistency' => $options->scanConsistency,
            'scanWait' => $options->scanWaitMilliseconds,
            'scanCap' => $options->scanCap,
            'pipelineCap' => $options->pipelineCap,
            'pipelineBatch' => $options->pipelineBatch,
            'maxParallelism' => $options->maxParallelism,
            'profile' => $options->profile,
            'readonly' => $options->readonly,
            'flexIndex' => $options->flexIndex,
            'adHoc' => $options->adHoc,
            'namedParameters' => $namedParameters,
            'positionalParameters' => $positionalParameters,
            'raw' => $raw,
            'clientContextId' => $options->clientContextId,
            'metrics' => $options->metrics,
            'preserveExpiry' => $options->preserveExpiry,
            'useReplica' => $options->useReplica,
            'queryContext' => $options->queryContext == null ? $defaultQueryContext : $options->queryContext,
        ];
    }
}
