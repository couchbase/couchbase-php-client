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

use Couchbase\Utilities\Deprecations;

/**
 * @since 4.0.0
 */
class AnalyticsOptions
{
    private Transcoder $transcoder;
    private ?int $timeoutMilliseconds = null;
    private ?array $namedParameters = null;
    private ?array $positionalParameters = null;
    private ?array $raw = null;
    private ?string $clientContextId = null;
    private ?bool $priority = null;
    private ?bool $readonly = null;
    private ?string $scanConsistency = null;

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
     * @return AnalyticsOptions
     * @since 4.0.0
     */
    public static function build(): AnalyticsOptions
    {
        return new AnalyticsOptions();
    }

    /**
     * Sets the operation timeout in milliseconds.
     *
     * @param int $milliseconds the operation timeout to apply
     *
     * @return AnalyticsOptions
     * @since 4.0.0
     */
    public function timeout(int $milliseconds): AnalyticsOptions
    {
        $this->timeoutMilliseconds = $milliseconds;
        return $this;
    }

    /**
     * Sets the named parameters for this query.
     *
     * @param array $pairs the associative array of parameters
     *
     * @return AnalyticsOptions
     */
    public function namedParameters(array $pairs): AnalyticsOptions
    {
        $this->namedParameters = $pairs;
        return $this;
    }

    /**
     * Sets the positional parameters for this query.
     *
     * @param array $params the array of parameters
     *
     * @return AnalyticsOptions
     */
    public function positionalParameters(array $params): AnalyticsOptions
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
     * @return AnalyticsOptions
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
     * @return AnalyticsOptions
     */
    public function clientContextId(string $id): AnalyticsOptions
    {
        $this->clientContextId = $id;
        return $this;
    }

    /**
     * Sets whether this query should be assigned as high priority by the analytics engine.
     *
     * @param bool $isPriority whether this query should be assiged as high priority.
     *
     * @return AnalyticsOptions
     * @since 4.0.0
     */
    public function priority(bool $isPriority): AnalyticsOptions
    {
        $this->priority = $isPriority;
        return $this;
    }

    /**
     * Sets whether this query should be readonly.
     *
     * @param bool $readonly whether this query should be readonly.
     *
     * @return AnalyticsOptions
     * @since 4.0.0
     */
    public function readonly(bool $readonly): AnalyticsOptions
    {
        $this->readonly = $readonly;
        return $this;
    }

    /**
     * Sets the scan consistency.
     *
     * @param string|int $consistencyLevel the scan consistency level
     *
     * @return AnalyticsOptions
     * @throws Exception\InvalidArgumentException
     * @see AnalyticsScanConsistency
     * @since 4.0.0
     */
    public function scanConsistency($consistencyLevel): AnalyticsOptions
    {
        if (gettype($consistencyLevel) == "integer") {
            $consistencyLevel = Deprecations::convertDeprecatedAnalyticsScanConsistency(__METHOD__, $consistencyLevel);
        }
        $this->scanConsistency = $consistencyLevel;
        return $this;
    }

    /**
     * Associate custom transcoder with the request.
     *
     * @param Transcoder $transcoder
     *
     * @return AnalyticsOptions
     * @since 4.0.0
     */
    public function transcoder(Transcoder $transcoder): AnalyticsOptions
    {
        $this->transcoder = $transcoder;
        return $this;
    }

    /**
     * Returns associated transcoder.
     *
     * @param AnalyticsOptions|null $options
     *
     * @return Transcoder
     * @since 4.0.0
     */
    public static function getTranscoder(?AnalyticsOptions $options): Transcoder
    {
        if ($options == null) {
            return JsonTranscoder::getInstance();
        }
        return $options->transcoder;
    }

    public static function export(?AnalyticsOptions $options, string $scopeName = null, string $bucketName = null): array
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

        return [
            'timeoutMilliseconds' => $options->timeoutMilliseconds,

            'priority' => $options->priority,
            'scanConsistency' => $options->scanConsistency,
            'readonly' => $options->readonly,
            'namedParameters' => $namedParameters,
            'positionalParameters' => $positionalParameters,
            'raw' => $raw,
            'clientContextId' => $options->clientContextId,
            'scopeName' => $scopeName,
            'bucketName' => $bucketName,
        ];
    }
}
