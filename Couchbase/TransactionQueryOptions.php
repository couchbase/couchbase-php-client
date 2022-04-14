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

class TransactionQueryOptions
{
    private ?int $timeoutMilliseconds = null;
    private ?MutationState $consistentWith = null;
    private ?string $scanConsistency = null;
    private ?int $scanCap = null;
    private ?int $pipelineCap = null;
    private ?int $pipelineBatch = null;
    private ?int $maxParallelism = null;
    private ?int $profile = null;
    private ?int $scanWaitMilliseconds = null;
    private ?bool $readonly = null;
    private ?bool $adHoc = null;
    private ?array $namedParameters = null;
    private ?array $positionalParameters = null;
    private ?array $raw = null;
    private ?string $clientContextId = null;
    private ?bool $metrics = null;
    private ?string $scopeName = null;
    private ?string $scopeQualifier = null;
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
     * @return TransactionQueryOptions
     * @since 4.0.0
     */
    public static function build(): TransactionQueryOptions
    {
        return new TransactionQueryOptions();
    }

    /**
     * Sets the operation timeout in milliseconds.
     *
     * @param int $milliseconds the operation timeout to apply
     *
     * @return TransactionQueryOptions
     * @since 4.0.0
     */
    public function timeout(int $milliseconds): TransactionQueryOptions
    {
        $this->timeoutMilliseconds = $milliseconds;
        return $this;
    }

    /**
     * Sets the mutation state to achieve consistency with for read your own writes (RYOW).
     *
     * @param MutationState $state the mutation state to achieve consistency with
     *
     * @return TransactionQueryOptions
     * @since 4.0.0
     */
    public function consistentWith(MutationState $state): TransactionQueryOptions
    {
        $this->consistentWith = $state;
        return $this;
    }

    /**
     * Sets the scan consistency.
     *
     * @param string $consistencyLevel the scan consistency level.
     *
     * @return TransactionQueryOptions
     * @see TransactionQueryOptions::SCAN_CONSISTENCY_REQUEST_PLUS
     * @see TransactionQueryOptions::SCAN_CONSISTENCY_NOT_BOUNDED
     * @since 4.0.0
     */
    public function scanConsistency(string $consistencyLevel): TransactionQueryOptions
    {
        $this->scanConsistency = $consistencyLevel;
        return $this;
    }

    /**
     * Sets the maximum buffered channel size between the indexer client and the query service for index scans.
     *
     * @param int $cap the maximum buffered channel size
     *
     * @return TransactionQueryOptions
     * @since 4.0.0
     */
    public function scanCap(int $cap): TransactionQueryOptions
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
     * @return TransactionQueryOptions
     * @since 4.0.0
     */
    public function scanWait(int $milliseconds): TransactionQueryOptions
    {
        $this->scanWaitMilliseconds = $milliseconds;
        return $this;
    }

    /**
     * Sets the maximum number of items each execution operator can buffer between various operators.
     *
     * @param int $cap the maximum number of items each execution operation can buffer
     *
     * @return TransactionQueryOptions
     * @since 4.0.0
     */
    public function pipelineCap(int $cap): TransactionQueryOptions
    {
        $this->pipelineCap = $cap;
        return $this;
    }

    /**
     * Sets the number of items execution operators can batch for fetch from the KV service.
     *
     * @param int $batchSize the pipeline batch size
     *
     * @return TransactionQueryOptions
     * @since 4.0.0
     */
    public function pipelineBatch(int $batchSize): TransactionQueryOptions
    {
        $this->pipelineBatch = $batchSize;
        return $this;
    }

    /**
     * Sets the maximum number of index partitions, for computing aggregation in parallel.
     *
     * @param int $max the number of index partitions
     *
     * @return TransactionQueryOptions
     */
    public function maxParallelism(int $max): TransactionQueryOptions
    {
        $this->maxParallelism = $max;
        return $this;
    }

    /**
     * Sets the query profile mode to use.
     *
     * @param int $mode the query profile mode
     *
     * @return TransactionQueryOptions
     * @since 4.0.0
     */
    public function profile(int $mode): TransactionQueryOptions
    {
        $this->profile = $mode;
        return $this;
    }

    /**
     * Sets whether this query is readonly.
     *
     * @param bool $readonly whether the query is readonly
     *
     * @return TransactionQueryOptions
     * @since 4.0.0
     */
    public function readonly(bool $readonly): TransactionQueryOptions
    {
        $this->readonly = $readonly;
        return $this;
    }

    /**
     * Sets whether this query is adhoc.
     *
     * @param bool $enabled whether the query is adhoc
     *
     * @return TransactionQueryOptions
     * @since 4.0.0
     */
    public function adhoc(bool $enabled): TransactionQueryOptions
    {
        $this->adHoc = $enabled;
        return $this;
    }

    /**
     * Sets the named parameters for this query.
     *
     * @param array $pairs the associative array of parameters
     *
     * @return TransactionQueryOptions
     * @since 4.0.0
     */
    public function namedParameters(array $pairs): TransactionQueryOptions
    {
        $this->namedParameters = $pairs;
        return $this;
    }

    /**
     * Sets the positional parameters for this query.
     *
     * @param array $params the array of parameters
     *
     * @return TransactionQueryOptions
     * @since 4.0.0
     */
    public function positionalParameters(array $params): TransactionQueryOptions
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
     * @return TransactionQueryOptions
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
     * @return TransactionQueryOptions
     * @since 4.0.0
     */
    public function clientContextId(string $id): TransactionQueryOptions
    {
        $this->clientContextId = $id;
        return $this;
    }

    /**
     * Sets whether to return metrics with the query.
     *
     * @param bool $enabled whether to return metrics
     *
     * @return TransactionQueryOptions
     * @since 4.0.0
     */
    public function metrics(bool $enabled): TransactionQueryOptions
    {
        $this->metrics = $enabled;
        return $this;
    }

    /**
     * Associate scope name with query
     *
     * @param string $name the name of the scope
     *
     * @return TransactionQueryOptions
     * @deprecated
     * @since 4.0.0
     */
    public function scopeName(string $name): TransactionQueryOptions
    {
        trigger_error(
            'Method ' . __METHOD__ . ' is deprecated, use scope level query()',
            E_USER_DEPRECATED
        );
        $this->scopeName = $name;
        return $this;
    }

    /**
     * Associate scope qualifier (also known as `query_context`) with the query.
     *
     * The qualifier must be in form `${bucketName}.${scopeName}` or `default:${bucketName}.${scopeName}`
     *
     * @param string $qualifier the scope qualifier
     *
     * @return TransactionQueryOptions
     * @deprecated
     * @since 4.0.0
     */
    public function scopeQualifier(string $qualifier): TransactionQueryOptions
    {
        trigger_error(
            'Method ' . __METHOD__ . ' is deprecated, use scope level query()',
            E_USER_DEPRECATED
        );
        $this->scopeQualifier = $qualifier;
        return $this;
    }

    /**
     * Associate custom transcoder with the request.
     *
     * @param Transcoder $transcoder
     *
     * @return TransactionQueryOptions
     * @since 4.0.0
     */
    public function transcoder(Transcoder $transcoder): TransactionQueryOptions
    {
        $this->transcoder = $transcoder;
        return $this;
    }

    /**
     * Returns associated transcoder.
     *
     * @param TransactionQueryOptions|null $options
     *
     * @return Transcoder
     * @since 4.0.0
     */
    public static function getTranscoder(?TransactionQueryOptions $options): Transcoder
    {
        if ($options == null) {
            return JsonTranscoder::getInstance();
        }
        return $options->transcoder;
    }

    public static function export(?TransactionQueryOptions $options, string $scopeName = null, string $bucketName = null): array
    {
        if ($options == null) {
            return [
                'scopeName' => $scopeName,
                'bucketName' => $bucketName,
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
        if ($scopeName == null && $options->scopeName != null) {
            $scopeName = $options->scopeName;
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
            'adHoc' => $options->adHoc,
            'namedParameters' => $namedParameters,
            'positionalParameters' => $positionalParameters,
            'raw' => $raw,
            'clientContextId' => $options->clientContextId,
            'metrics' => $options->metrics,
            'scopeName' => $scopeName,
            'bucketName' => $bucketName,
            'scopeQualifier' => $options->scopeQualifier,
        ];
    }
}
