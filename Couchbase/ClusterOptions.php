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
    private ?bool $showQueries = null;

    private ?string $network = null;
    private ?string $trustCertificate = null;
    private ?string $userAgentExtra = null;

    private ?string $tlsVerifyMode = null;
    private ?string $useIpProtocol = null;

    private ?ThresholdLoggingOptions $thresholdLoggingTracerOptions = null;
    private ?LoggingMeterOptions $loggingMeterOptions = null;
    private ?TransactionsConfiguration $transactionsConfiguration = null;

    private ?Authenticator $authenticator;

    /**
     * @param Authenticator $authenticator
     *
     * @return ClusterOptions
     * @since 4.0.0
     */
    public function authenticator(Authenticator $authenticator): ClusterOptions
    {
        $this->authenticator = $authenticator;
        return $this;
    }

    /**
     * @param string $username
     * @param string $password
     *
     * @return ClusterOptions
     * @since 4.0.0
     */
    public function credentials(string $username, string $password): ClusterOptions
    {
        $this->authenticator = new PasswordAuthenticator($username, $password);
        return $this;
    }

    /**
     * @param int $milliseconds
     *
     * @return ClusterOptions
     * @since 4.0.0
     */
    public function analyticsTimeout(int $milliseconds): ClusterOptions
    {
        $this->analyticsTimeoutMilliseconds = $milliseconds;
        return $this;
    }

    /**
     * @param int $milliseconds
     *
     * @return ClusterOptions
     * @since 4.0.0
     */
    public function bootstrapTimeout(int $milliseconds): ClusterOptions
    {
        $this->bootstrapTimeoutMilliseconds = $milliseconds;
        return $this;
    }

    /**
     * @param int $milliseconds
     *
     * @return ClusterOptions
     * @since 4.0.0
     */
    public function connectTimeout(int $milliseconds): ClusterOptions
    {
        $this->connectTimeoutMilliseconds = $milliseconds;
        return $this;
    }

    /**
     * @param int $milliseconds
     *
     * @return ClusterOptions
     * @since 4.0.0
     */
    public function dnsSrvTimeout(int $milliseconds): ClusterOptions
    {
        $this->dnsSrvTimeoutMilliseconds = $milliseconds;
        return $this;
    }

    /**
     * @param int $milliseconds
     *
     * @return ClusterOptions
     * @since 4.0.0
     */
    public function keyValueDurableTimeout(int $milliseconds): ClusterOptions
    {
        $this->keyValueDurableTimeoutMilliseconds = $milliseconds;
        return $this;
    }

    /**
     * @param int $milliseconds
     *
     * @return ClusterOptions
     * @since 4.0.0
     */
    public function keyValueTimeout(int $milliseconds): ClusterOptions
    {
        $this->keyValueTimeoutMilliseconds = $milliseconds;
        return $this;
    }

    /**
     * @param int $milliseconds
     *
     * @return ClusterOptions
     * @since 4.0.0
     */
    public function managementTimeout(int $milliseconds): ClusterOptions
    {
        $this->managementTimeoutMilliseconds = $milliseconds;
        return $this;
    }

    /**
     * @param int $milliseconds
     *
     * @return ClusterOptions
     * @since 4.0.0
     */
    public function queryTimeout(int $milliseconds): ClusterOptions
    {
        $this->queryTimeoutMilliseconds = $milliseconds;
        return $this;
    }

    /**
     * @param int $milliseconds
     *
     * @return ClusterOptions
     * @since 4.0.0
     */
    public function resolveTimeout(int $milliseconds): ClusterOptions
    {
        $this->resolveTimeoutMilliseconds = $milliseconds;
        return $this;
    }

    /**
     * @param int $milliseconds
     *
     * @return ClusterOptions
     * @since 4.0.0
     */
    public function searchTimeout(int $milliseconds): ClusterOptions
    {
        $this->searchTimeoutMilliseconds = $milliseconds;
        return $this;
    }

    /**
     * @param int $milliseconds
     *
     * @return ClusterOptions
     * @since 4.0.0
     */
    public function viewTimeout(int $milliseconds): ClusterOptions
    {
        $this->viewTimeoutMilliseconds = $milliseconds;
        return $this;
    }

    /**
     * @param int $numberOfConnections
     *
     * @return ClusterOptions
     * @since 4.0.0
     */
    public function maxHttpConnections(int $numberOfConnections): ClusterOptions
    {
        $this->maxHttpConnections = $numberOfConnections;
        return $this;
    }

    /**
     * @param int $milliseconds
     *
     * @return ClusterOptions
     * @since 4.0.0
     */
    public function configIdleRedialTimeout(int $milliseconds): ClusterOptions
    {
        $this->configIdleRedialTimeoutMilliseconds = $milliseconds;
        return $this;
    }

    /**
     * @param int $milliseconds
     *
     * @return ClusterOptions
     * @since 4.0.0
     */
    public function configPollFloor(int $milliseconds): ClusterOptions
    {
        $this->configPollFloorMilliseconds = $milliseconds;
        return $this;
    }

    /**
     * @param int $milliseconds
     *
     * @return ClusterOptions
     * @since 4.0.0
     */
    public function configPollInterval(int $milliseconds): ClusterOptions
    {
        $this->configPollIntervalMilliseconds = $milliseconds;
        return $this;
    }

    /**
     * @param int $milliseconds
     *
     * @return ClusterOptions
     * @since 4.0.0
     */
    public function tcpKeepAliveInterval(int $milliseconds): ClusterOptions
    {
        $this->tcpKeepAliveIntervalMilliseconds = $milliseconds;
        return $this;
    }

    /**
     * @param bool $enable
     *
     * @return ClusterOptions
     * @since 4.0.0
     */
    public function enableClustermapNotification(bool $enable): ClusterOptions
    {
        $this->enableClustermapNotification = $enable;
        return $this;
    }

    /**
     * @param bool $enable
     *
     * @return ClusterOptions
     * @since 4.0.0
     */
    public function enableCompression(bool $enable): ClusterOptions
    {
        $this->enableCompression = $enable;
        return $this;
    }

    /**
     * @param bool $enable
     *
     * @return ClusterOptions
     * @since 4.0.0
     */
    public function enableDnsSrv(bool $enable): ClusterOptions
    {
        $this->enableDnsSrv = $enable;
        return $this;
    }

    /**
     * @param bool $enable
     *
     * @return ClusterOptions
     * @since 4.0.0
     */
    public function enableMetrics(bool $enable): ClusterOptions
    {
        $this->enableMetrics = $enable;
        return $this;
    }

    /**
     * @param bool $enable
     *
     * @return ClusterOptions
     * @since 4.0.0
     */
    public function enableMutationTokens(bool $enable): ClusterOptions
    {
        $this->enableMutationTokens = $enable;
        return $this;
    }

    /**
     * @param bool $enable
     *
     * @return ClusterOptions
     * @since 4.0.0
     */
    public function enableTcpKeepAlive(bool $enable): ClusterOptions
    {
        $this->enableTcpKeepAlive = $enable;
        return $this;
    }

    /**
     * @param bool $enable
     *
     * @return ClusterOptions
     * @since 4.0.0
     */
    public function enableTls(bool $enable): ClusterOptions
    {
        $this->enableTls = $enable;
        return $this;
    }

    /**
     * @param bool $enable
     *
     * @return ClusterOptions
     * @since 4.0.0
     */
    public function enableTracing(bool $enable): ClusterOptions
    {
        $this->enableTracing = $enable;
        return $this;
    }

    /**
     * @param bool $enable
     *
     * @return ClusterOptions
     * @since 4.0.0
     */
    public function enableUnorderedExecution(bool $enable): ClusterOptions
    {
        $this->enableUnorderedExecution = $enable;
        return $this;
    }

    /**
     * @param string $mode "any", "forceIpv4" or "forceIpv6"
     *
     * @return ClusterOptions
     * @since 4.0.0
     */
    public function useIpProtocol(string $mode): ClusterOptions
    {
        $this->useIpProtocol = $mode;
        return $this;
    }

    /**
     * @param bool $enable
     *
     * @return ClusterOptions
     * @since 4.0.0
     */
    public function showQueries(bool $enable): ClusterOptions
    {
        $this->showQueries = $enable;
        return $this;
    }

    /**
     * @param string $networkSelector
     *
     * @return ClusterOptions
     * @since 4.0.0
     */
    public function network(string $networkSelector): ClusterOptions
    {
        $this->network = $networkSelector;
        return $this;
    }

    /**
     * @param string $certificatePath
     *
     * @return ClusterOptions
     * @since 4.0.0
     */
    public function trustCertificate(string $certificatePath): ClusterOptions
    {
        $this->trustCertificate = $certificatePath;
        return $this;
    }

    /**
     * @param string $userAgentExtraString
     *
     * @return ClusterOptions
     * @since 4.0.0
     */
    public function userAgentExtra(string $userAgentExtraString): ClusterOptions
    {
        $this->userAgentExtra = $userAgentExtraString;
        return $this;
    }

    /**
     * @param string $mode
     *
     * @return ClusterOptions
     * @since 4.0.0
     */
    public function tlsVerify(string $mode): ClusterOptions
    {
        $this->tlsVerifyMode = $mode;
        return $this;
    }

    /**
     * @param ThresholdLoggingOptions $options
     *
     * @return ClusterOptions
     * @since 4.0.0
     */
    public function thresholdLoggingTracerOptions(ThresholdLoggingOptions $options): ClusterOptions
    {
        $this->thresholdLoggingTracerOptions = $options;
        return $this;
    }

    /**
     * @param TransactionsConfiguration $options
     *
     * @return ClusterOptions
     * @since 4.0.0
     */
    public function transactionsConfiguration(TransactionsConfiguration $options): ClusterOptions
    {
        $this->transactionsConfiguration = $options;
        return $this;
    }

    /**
     * Applies configuration profile to ClusterOptions associating string to range of options
     * @param string $profile name of config profile to apply (e.g. wan_development)
     * @throws InvalidArgumentException
     * @since 4.0.1
     */
    public function applyProfile(string $profile): void
    {
        ConfigProfiles::getInstance()->checkProfiles($profile, $this);
    }

    /**
     * @return TransactionsConfiguration|null
     * @since 4.0.0
     */
    public function getTransactionsConfiguration(): ?TransactionsConfiguration
    {
        return $this->transactionsConfiguration;
    }

    /**
     * @return string the string that uniquely identifies particular authenticator layout
     * @throws InvalidArgumentException
     * @internal
     */
    public function authenticatorHash(): string
    {
        if ($this->authenticator == null) {
            throw new InvalidArgumentException("missing authenticator");
        }
        $exported = $this->authenticator->export();
        if ($exported['type'] == 'password') {
            return hash("sha256", sprintf("--%s--%s--", $exported['username'], $exported['password']));
        } elseif ($exported['type'] == 'certificate') {
            return hash("sha256", sprintf("--%s--%s--", $exported['certificatePath'], $exported['keyPath']));
        } else {
            throw new InvalidArgumentException("unknown type of the authenticator: " . $exported['type']);
        }
    }

    /**
     * @return array
     * @throws InvalidArgumentException
     * @internal
     */
    public function export(): array
    {
        if ($this->authenticator == null) {
            throw new InvalidArgumentException("missing authenticator");
        }
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
            'useIpProtocol' => $this->useIpProtocol,
            'showQueries' => $this->showQueries,

            'network' => $this->network,
            'trustCertificate' => $this->trustCertificate,
            'userAgentExtra' => $this->userAgentExtra,

            'tlsVerify' => $this->tlsVerifyMode,

            'thresholdLoggingTracerOptions' =>
                $this->thresholdLoggingTracerOptions == null ? null : $this->thresholdLoggingTracerOptions->export(),
            'loggingMeterOptions' => $this->loggingMeterOptions == null ? null : $this->loggingMeterOptions->export(),
        ];
    }
}
