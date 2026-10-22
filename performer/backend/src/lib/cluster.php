<?php

declare(strict_types=1);

use Couchbase\AppTelemetryConfiguration;
use Couchbase\Authenticator;
use Couchbase\CertificateAuthenticator;
use Couchbase\Cluster;
use Couchbase\ClusterOptions;
use Couchbase\JwtAuthenticator;
use Couchbase\PasswordAuthenticator;

// Runs per request. The SDK's connection cache keys only on connstr, authenticator and tracing
// flags (PCBC-1056), so connections differing only in cluster options share one.

function connectCluster(array $connection): Cluster
{
    return Cluster::connect($connection['connstr'], clusterOptions($connection));
}

function clusterOptions(array $connection): ClusterOptions
{
    $options = new ClusterOptions();
    $options->authenticator(clusterAuthenticator($connection['auth']));

    $c = $connection['clusterConfig'] ?? [];
    if (!$c) {
        return $options;
    }

    if (isset($c['transcoder'])) {
        throw new \RuntimeException('a cluster-level default transcoder is not supported by the PHP SDK');
    }
    if (isset($c['numKvConnections'])) {
        throw new \RuntimeException('numKvConnections is not supported by the PHP SDK');
    }
    if (isset($c['kvScanTimeoutSecs'])) {
        throw new \RuntimeException('kvScanTimeoutSecs is not supported by the PHP SDK');
    }
    if (!empty($c['useCustomSerializer'])) {
        throw new \RuntimeException('a custom JsonSerializer is not supported by the PHP SDK');
    }

    if (!empty($c['useTls'])) {
        $options->enableTls(true);
    }
    if (isset($c['certPath'])) {
        $options->trustCertificate($c['certPath']);
    }
    if (isset($c['cert'])) {
        $options->trustCertificate(certificateFile($c['cert'], 'ca'));
    }
    if (isset($c['insecure'])) {
        $options->tlsVerify($c['insecure'] ? 'none' : 'peer');
    }

    if (isset($c['kvConnectTimeoutSecs'])) {
        $options->connectTimeout($c['kvConnectTimeoutSecs'] * 1000);
    }
    if (isset($c['kvTimeoutMillis'])) {
        $options->keyValueTimeout($c['kvTimeoutMillis']);
    }
    if (isset($c['kvDurableTimeoutMillis'])) {
        $options->keyValueDurableTimeout($c['kvDurableTimeoutMillis']);
    }
    if (isset($c['viewTimeoutSecs'])) {
        $options->viewTimeout($c['viewTimeoutSecs'] * 1000);
    }
    if (isset($c['queryTimeoutSecs'])) {
        $options->queryTimeout($c['queryTimeoutSecs'] * 1000);
    }
    if (isset($c['analyticsTimeoutSecs'])) {
        $options->analyticsTimeout($c['analyticsTimeoutSecs'] * 1000);
    }
    if (isset($c['searchTimeoutSecs'])) {
        $options->searchTimeout($c['searchTimeoutSecs'] * 1000);
    }
    if (isset($c['managementTimeoutSecs'])) {
        $options->managementTimeout($c['managementTimeoutSecs'] * 1000);
    }

    if (isset($c['enableMutationTokens'])) {
        $options->enableMutationTokens($c['enableMutationTokens']);
    }
    if (isset($c['enableTcpKeepAlives'])) {
        $options->enableTcpKeepAlive($c['enableTcpKeepAlives']);
    }
    if (isset($c['tcpKeepAliveTimeMillis'])) {
        $options->tcpKeepAliveInterval($c['tcpKeepAliveTimeMillis']);
    }
    if (isset($c['forceIpv4'])) {
        $options->useIpProtocol($c['forceIpv4'] ? 'forceIpv4' : 'any');
    }
    if (isset($c['configPollIntervalSecs'])) {
        $options->configPollInterval($c['configPollIntervalSecs'] * 1000);
    }
    if (isset($c['configPollFloorIntervalSecs'])) {
        $options->configPollFloor($c['configPollFloorIntervalSecs'] * 1000);
    }
    if (isset($c['configIdleRedialTimeoutSecs'])) {
        $options->configIdleRedialTimeout($c['configIdleRedialTimeoutSecs'] * 1000);
    }
    if (isset($c['maxHttpConnections'])) {
        $options->maxHttpConnections($c['maxHttpConnections']);
    }
    if (isset($c['idleHttpConnectionTimeoutSecs'])) {
        $options->idleHttpConnectionTimeout($c['idleHttpConnectionTimeoutSecs'] * 1000);
    }
    if (isset($c['preferredServerGroup'])) {
        $options->preferredServerGroup($c['preferredServerGroup']);
    }

    $telemetry = appTelemetryConfiguration($c);
    if ($telemetry !== null) {
        $options->appTelemetryConfiguration($telemetry);
    }

    return $options;
}

function clusterAuthenticator(array $auth): Authenticator
{
    $kind = $auth['kind'] ?? throw new \RuntimeException('authenticator had no kind');

    return match ($kind) {
        'password' => new PasswordAuthenticator($auth['username'] ?? '', $auth['password'] ?? ''),
        'certificate' => new CertificateAuthenticator(
            certificateFile($auth['cert'], 'client-cert'),
            certificateFile($auth['key'], 'client-key')
        ),
        'jwt' => new JwtAuthenticator($auth['jwt']),
        default => throw new \RuntimeException("unknown authenticator kind {$kind}"),
    };
}

function appTelemetryConfiguration(array $c): ?AppTelemetryConfiguration
{
    $config = new AppTelemetryConfiguration();
    $set = false;

    if (isset($c['enableAppTelemetry'])) {
        $config->enabled($c['enableAppTelemetry']);
        $set = true;
    }
    if (isset($c['appTelemetryEndpoint'])) {
        $config->endpoint($c['appTelemetryEndpoint']);
        $set = true;
    }
    if (isset($c['appTelemetryBackoffSecs'])) {
        $config->backoff($c['appTelemetryBackoffSecs'] * 1000);
        $set = true;
    }
    if (isset($c['appTelemetryPingIntervalSecs'])) {
        $config->pingInterval($c['appTelemetryPingIntervalSecs'] * 1000);
        $set = true;
    }
    if (isset($c['appTelemetryPingTimeoutSecs'])) {
        $config->pingTimeout($c['appTelemetryPingTimeoutSecs'] * 1000);
        $set = true;
    }

    return $set ? $config : null;
}

/**
 * Content-addressed so the path is stable across requests: the SDK's connection cache key hashes
 * certificate paths, and a fresh name per request would reconnect every time.
 */
function certificateFile(string $pem, string $kind): string
{
    $dir = sys_get_temp_dir() . '/fit-php-performer';
    if (!is_dir($dir) && !@mkdir($dir, 0700, true) && !is_dir($dir)) {
        throw new \RuntimeException("could not create certificate directory {$dir}");
    }

    $path = sprintf('%s/%s-%s.pem', $dir, $kind, hash('sha256', $pem));
    if (is_file($path)) {
        return $path;
    }

    // Atomic rename so concurrent workers never see a half-written file.
    $tmp = sprintf('%s.%d.tmp', $path, getmypid());
    if (file_put_contents($tmp, $pem) === false) {
        throw new \RuntimeException("could not write {$kind} to {$tmp}");
    }
    chmod($tmp, 0600);
    if (!rename($tmp, $path)) {
        @unlink($tmp);
        throw new \RuntimeException("could not move {$kind} into place at {$path}");
    }

    return $path;
}
