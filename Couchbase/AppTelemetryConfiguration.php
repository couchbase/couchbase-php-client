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

class AppTelemetryConfiguration
{
    private ?bool $enabled = null;
    private ?string $endpoint = null;
    private ?int $backoffMilliseconds = null;
    private ?int $pingIntervalMilliseconds = null;
    private ?int $pingTimeoutMilliseconds = null;

    /**
     * Specifies if the application telemetry feature should be enabled or not.
     *
     * @param bool $enabled
     *
     * @return AppTelemetryConfiguration
     * @since 4.5.0
     */
    public function enabled(bool $enabled): AppTelemetryConfiguration
    {
        $this->enabled = $enabled;
        return $this;
    }

    /**
     * Specifies an endpoint to override the application metrics endpoint discovered during configuration.
     *
     * @param string $endpoint
     *
     * @return AppTelemetryConfiguration
     * @since 4.5.0
     */
    public function endpoint(string $endpoint): AppTelemetryConfiguration
    {
        $this->endpoint = $endpoint;
        return $this;
    }

    /**
     * Specifies the time to wait before attempting a websocket reconnection, specified in millseconds.
     *
     * @param int $milliseconds
     *
     * @return AppTelemetryConfiguration
     * @since 4.5.0
     */
    public function backoff(int $milliseconds): AppTelemetryConfiguration
    {
        $this->backoffMilliseconds = $milliseconds;
        return $this;
    }

    /**
     * Specifies the time to wait between sending consecutive websocket PING commands to the server, specified in millseconds.
     *
     * @param int $milliseconds
     *
     * @return AppTelemetryConfiguration
     * @since 4.5.0
     */
    public function pingInterval(int $milliseconds): AppTelemetryConfiguration
    {
        $this->pingIntervalMilliseconds = $milliseconds;
        return $this;
    }

    /**
     * Specifies the time allowed for the server to respond to websocket PING command, specified in millseconds.
     *
     * @param int $milliseconds
     *
     * @return AppTelemetryConfiguration
     * @since 4.5.0
     */
    public function pingTimeout(int $milliseconds): AppTelemetryConfiguration
    {
        $this->pingTimeoutMilliseconds = $milliseconds;
        return $this;
    }

    /**
     * @internal
     */
    public function export(): array
    {
        return [
            'enabled' => $this->enabled,
            'endpoint' => $this->endpoint,
            'backoff' => $this->backoffMilliseconds,
            'pingInterval' => $this->pingIntervalMilliseconds,
            'pingTimeout' => $this->pingTimeoutMilliseconds,
        ];
    }
}
