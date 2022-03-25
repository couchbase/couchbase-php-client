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
 * @since 4.0.0
 */
class AnalyticsOptions
{
    private ?int $timeoutMilliseconds = null;
    private ?array $namedParameters = null;
    private ?array $positionalParameters = null;
    private ?array $raw = null;
    private ?string $clientContextId = null;
    private ?bool $priority = null;
    private ?bool $readonly = null;
    private ?string $scanConsistency = null;

    /**
     * Sets the operation timeout in milliseconds.
     *
     * @param int $milliseconds the operation timeout to apply
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
     * @return AnalyticsOptions
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
     * @param string $consistencyLevel the scan consistency level
     * @return AnalyticsOptions
     */
    public function scanConsistency(string $consistencyLevel): AnalyticsOptions
    {
        $this->scanConsistency = $consistencyLevel;
        return $this;
    }

    public function export(string $scopeName = null, string $scopeQualifier = null): array
    {
        $positionalParameters = null;
        if ($this->positionalParameters != null) {
            foreach ($this->positionalParameters as $param) {
                $positionalParameters[] = json_encode($param);
            }
        }
        $namedParameters = null;
        if ($this->namedParameters != null) {
            foreach ($this->namedParameters as $key => $param) {
                $namedParameters[$key] = json_encode($param);
            }
        }

        return [
            'timeoutMilliseconds' => $this->timeoutMilliseconds,

            'priority' => $this->priority,
            'scanConsistency' => $this->scanConsistency,
            'readonly' => $this->readonly,
            'namedParameters' => $namedParameters,
            'positionalParameters' => $positionalParameters,
            'raw' => $this->raw,
            'clientContextId' => $this->clientContextId,
            'scopeName' => $scopeName,
            'scopeQualifier' => $scopeQualifier
        ];
    }
}
