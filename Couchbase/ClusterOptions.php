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

class ClusterOptions
{
    private ?int $analyticsTimeoutMilliseconds = null;
    private ?int $bootstrapTimeoutMilliseconds = null;
    private ?int $connectTimeoutMilliseconds = null;
    private ?int $dnsSrvTimeoutMilliseconds = null;
    private ?int $keyValueDurableTimeoutMilliseconds = null;
    private ?int $keyValueTimeoutMilliseconds = null;
    private ?int $managementTimeoutMilliseconds = null;
    private ?int $queryTimeoutMilliseconds = null;
    private ?int $resolveTimeoutMilliseconds = null;
    private ?int $searchTimeoutMilliseconds = null;
    private ?int $viewTimeoutMilliseconds = null;

    private ?int $maxHttpConnections = null;

    private ?int $configIdleRedialTimeoutMilliseconds = null;
    private ?int $configPollFloorMilliseconds = null;
    private ?int $configPollIntervalMilliseconds = null;
    private ?int $idleHttpConnectionTimeoutMilliseconds = null;
    private ?int $tcpKeepAliveIntervalMilliseconds = null;

    private ?bool $enableClustermapNotification = null;
    private ?bool $enableCompression = null;
    private ?bool $enableDnsSrv = null;
    private ?bool $enableMetrics = null;
    private ?bool $enableMutationTokens = null;
    private ?bool $enableTcpKeepAlive = null;
    private ?bool $enableTls = null;
    private ?bool $enableTracing = null;
    private ?bool $enableUnorderedExecution = null;
    private ?bool $forceIpv4 = null;
    private ?bool $showQueries = null;

    private ?string $network = null;
    private ?string $trustCertificate = null;
    private ?string $userAgentExtra = null;

    private ?string $tlsVerifyMode = null;

    private ?ThresholdLoggingOptions $thresholdLoggingTracerOptions = null;
    private ?LoggingMeterOptions $loggingMeterOptions = null;

    private ?Authenticator $authenticator;

    public function credentials(string $username, string $password): ClusterOptions
    {
        $this->authenticator = new PasswordAuthenticator($username, $password);
        return $this;
    }

    public function analyticsTimeout(int $milliseconds): ClusterOptions
    {
        $this->analyticsTimeoutMilliseconds = $milliseconds;
        return $this;
    }

    public function bootstrapTimeout(int $milliseconds): ClusterOptions
    {
        $this->bootstrapTimeoutMilliseconds = $milliseconds;
        return $this;
    }

    public function connectTimeout(int $milliseconds): ClusterOptions
    {
        $this->connectTimeoutMilliseconds = $milliseconds;
        return $this;
    }

    public function dnsSrvTimeout(int $milliseconds): ClusterOptions
    {
        $this->dnsSrvTimeoutMilliseconds = $milliseconds;
        return $this;
    }

    public function keyValueDurableTimeout(int $milliseconds): ClusterOptions
    {
        $this->keyValueDurableTimeoutMilliseconds = $milliseconds;
        return $this;
    }

    public function keyValueTimeout(int $milliseconds): ClusterOptions
    {
        $this->keyValueTimeoutMilliseconds = $milliseconds;
        return $this;
    }

    public function managementTimeout(int $milliseconds): ClusterOptions
    {
        $this->searchTimeoutMilliseconds = $milliseconds;
        return $this;
    }

    public function queryTimeout(int $milliseconds): ClusterOptions
    {
        $this->queryTimeoutMilliseconds = $milliseconds;
        return $this;
    }

    public function resolveTimeout(int $milliseconds): ClusterOptions
    {
        $this->resolveTimeoutMilliseconds = $milliseconds;
        return $this;
    }

    public function searchTimeout(int $milliseconds): ClusterOptions
    {
        $this->searchTimeoutMilliseconds = $milliseconds;
        return $this;
    }

    public function viewTimeout(int $milliseconds): ClusterOptions
    {
        $this->viewTimeoutMilliseconds = $milliseconds;
        return $this;
    }

    public function maxHttpConnections(int $numberOfConnections): ClusterOptions
    {
        $this->maxHttpConnections = $numberOfConnections;
        return $this;
    }

    public function configIdleRedialTimeout(int $milliseconds): ClusterOptions
    {
        $this->configIdleRedialTimeoutMilliseconds = $milliseconds;
        return $this;
    }

    public function configPollFloor(int $milliseconds): ClusterOptions
    {
        $this->configPollFloorMilliseconds = $milliseconds;
        return $this;
    }

    public function configPollInterval(int $milliseconds): ClusterOptions
    {
        $this->configPollIntervalMilliseconds = $milliseconds;
        return $this;
    }

    public function tcpKeepAliveInterval(int $milliseconds): ClusterOptions
    {
        $this->tcpKeepAliveIntervalMilliseconds = $milliseconds;
        return $this;
    }

    public function enableClustermapNotification(bool $enable): ClusterOptions
    {
        $this->enableClustermapNotification = $enable;
        return $this;
    }

    public function enableCompression(bool $enable): ClusterOptions
    {
        $this->enableCompression = $enable;
        return $this;
    }

    public function enableDnsSrv(bool $enable): ClusterOptions
    {
        $this->enableDnsSrv = $enable;
        return $this;
    }

    public function enableMetrics(bool $enable): ClusterOptions
    {
        $this->enableMetrics = $enable;
        return $this;
    }

    public function enableMutationTokens(bool $enable): ClusterOptions
    {
        $this->enableMutationTokens = $enable;
        return $this;
    }

    public function enableTcpKeepAlive(bool $enable): ClusterOptions
    {
        $this->enableTcpKeepAlive = $enable;
        return $this;
    }

    public function enableTls(bool $enable): ClusterOptions
    {
        $this->enableTls = $enable;
        return $this;
    }

    public function enableTracing(bool $enable): ClusterOptions
    {
        $this->enableTracing = $enable;
        return $this;
    }

    public function enableUnorderedExecution(bool $enable): ClusterOptions
    {
        $this->enableUnorderedExecution = $enable;
        return $this;
    }

    public function forceIpv4(bool $enable): ClusterOptions
    {
        $this->forceIpv4 = $enable;
        return $this;
    }

    public function showQueries(bool $enable): ClusterOptions
    {
        $this->showQueries = $enable;
        return $this;
    }

    public function network(string $networkSelector): ClusterOptions
    {
        $this->network = $networkSelector;
        return $this;
    }

    public function trustCertificate(string $certificatePath): ClusterOptions
    {
        $this->trustCertificate = $certificatePath;
        return $this;
    }

    public function userAgentExtra(string $userAgentExtraString): ClusterOptions
    {
        $this->userAgentExtra = $userAgentExtraString;
        return $this;
    }

    public function tlsVerify(string $mode): ClusterOptions
    {
        $this->tlsVerifyMode = $mode;
        return $this;
    }

    public function thresholdLoggingTracerOptions(ThresholdLoggingOptions $options): ClusterOptions
    {
        $this->thresholdLoggingTracerOptions = $options;
        return $this;
    }

    public function export(): array
    {
        return [
            'authenticator' => $this->authenticator->export(),

            'analyticsTimeout' => $this->analyticsTimeoutMilliseconds,
            'bootstrapTimeout' => $this->bootstrapTimeoutMilliseconds,
            'connectTimeout' => $this->connectTimeoutMilliseconds,
            'dnsSrvTimeout' => $this->dnsSrvTimeoutMilliseconds,
            'keyValueDurableTimeout' => $this->keyValueDurableTimeoutMilliseconds,
            'keyValueTimeout' => $this->keyValueTimeoutMilliseconds,
            'managementTimeout' => $this->managementTimeoutMilliseconds,
            'queryTimeout' => $this->queryTimeoutMilliseconds,
            'resolveTimeout' => $this->resolveTimeoutMilliseconds,
            'searchTimeout' => $this->searchTimeoutMilliseconds,
            'viewTimeout' => $this->viewTimeoutMilliseconds,

            'maxHttpConnections' => $this->maxHttpConnections,

            'configIdleRedialTimeout' => $this->configIdleRedialTimeoutMilliseconds,
            'configPollFloor' => $this->configPollFloorMilliseconds,
            'configPollInterval' => $this->configPollIntervalMilliseconds,
            'idleHttpConnectionTimeout' => $this->idleHttpConnectionTimeoutMilliseconds,
            'tcpKeepAliveInterval' => $this->tcpKeepAliveIntervalMilliseconds,

            'enableClustermapNotification' => $this->enableClustermapNotification,
            'enableCompression' => $this->enableCompression,
            'enableDnsSrv' => $this->enableDnsSrv,
            'enableMetrics' => $this->enableMetrics,
            'enableMutationTokens' => $this->enableMutationTokens,
            'enableTcpKeepAlive' => $this->enableTcpKeepAlive,
            'enableTls' => $this->enableTls,
            'enableTracing' => $this->enableTracing,
            'enableUnorderedExecution' => $this->enableUnorderedExecution,
            'forceIpv4' => $this->forceIpv4,
            'showQueries' => $this->showQueries,

            'network' => $this->network,
            'trustCertificate' => $this->trustCertificate,
            'userAgentExtra' => $this->userAgentExtra,

            'tlsVerify' => $this->tlsVerifyMode,

            'thresholdLoggingTracerOptions' => $this->thresholdLoggingTracerOptions->export(),
            'loggingMeterOptions' => $this->loggingMeterOptions->export(),
        ];
    }

    public function username(): string
    {
        return $this->username;
    }

    public function password(): string
    {
        return $this->password;
    }
}
