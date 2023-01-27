<?php

declare(strict_types=1);

namespace Couchbase\StellarNebula;

use Exception;
use InvalidArgumentException;

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
    private ?int $scanWaitMilliseconds = null;
    private ?bool $readonly = null;
    private ?bool $prepared = null;
    private ?bool $flexIndex = null;
    private ?bool $adHoc = null;
    private ?array $namedParameters = null;
    private ?array $positionalParameters = null;
    private ?string $clientContextId = null;
    private ?bool $metrics = null;
    private ?bool $preserveExpiry = null;
    private ?string $scopeName = null;
    private ?string $scopeQualifier = null;
    private Transcoder $transcoder;

    public function __construct()
    {
        $this->transcoder = JsonTranscoder::getInstance();
    }

    public static function build(): QueryOptions
    {
        return new QueryOptions();
    }

    public function timeout(int $milliseconds): QueryOptions
    {
        $this->timeoutMilliseconds = $milliseconds;
        return $this;
    }

    public function consistentWith(MutationState $state): QueryOptions
    {
        $this->consistentWith = $state;
        return $this;
    }

    public function scanConsistency($consistencyLevel): QueryOptions
    {
        if (gettype($consistencyLevel) == "string") {
            $consistencyLevel = $this->convertConsistency($consistencyLevel);
        }
        $this->scanConsistency = $consistencyLevel;
        return $this;
    }

    public function scanCap(int $cap): QueryOptions
    {
        $this->scanCap = $cap;
        return $this;
    }

    public function scanWait(int $milliseconds): QueryOptions
    {
        $this->scanWaitMilliseconds = $milliseconds;
        return $this;
    }

    public function pipelineCap(int $cap): QueryOptions
    {
        $this->pipelineCap = $cap;
        return $this;
    }

    public function pipelineBatch(int $batchSize): QueryOptions
    {
        $this->pipelineBatch = $batchSize;
        return $this;
    }

    public function maxParallelism(int $max): QueryOptions
    {
        $this->maxParallelism = $max;
        return $this;
    }

    public function profile($mode): QueryOptions
    {
        if (gettype($mode) == "string") {
            $mode = $this->convertProfile($mode);
        }
        $this->profile = $mode;
        return $this;
    }

    public function readonly(bool $readonly): QueryOptions
    {
        $this->readonly = $readonly;
        return $this;
    }

    public function prepared(bool $prepared): QueryOptions
    {
        $this->prepared = $prepared;
        return $this;
    }

    public function flexIndex(bool $enabled): QueryOptions
    {
        $this->flexIndex = $enabled;
        return $this;
    }

    public function adhoc(bool $enabled): QueryOptions
    {
        $this->adHoc = $enabled;
        return $this;
    }

    public function namedParameters(array $pairs): QueryOptions
    {
        $this->namedParameters = $pairs;
        return $this;
    }

    public function positionalParameters(array $params): QueryOptions
    {
        $this->positionalParameters = $params;
        return $this;
    }

    public function raw(string $key, $value): QueryOptions
    {
        throw new Exception("Raw is no longer supported");
    }

    public function clientContextId(string $id): QueryOptions
    {
        $this->clientContextId = $id;
        return $this;
    }

    public function metrics(bool $enabled): QueryOptions
    {
        $this->metrics = $enabled;
        return $this;
    }

    public function scopeName(string $name): QueryOptions
    {
        trigger_error(
            'Method ' . __METHOD__ . ' is deprecated, use scope level query()',
            E_USER_DEPRECATED
        );
        $this->scopeName = $name;
        return $this;
    }

    public function scopeQualifier(string $qualifier): QueryOptions
    {
        trigger_error(
            'Method ' . __METHOD__ . ' is deprecated, use scope level query()',
            E_USER_DEPRECATED
        );
        $this->scopeQualifier = $qualifier;
        return $this;
    }

    public function preserveExpiry(bool $preserve): QueryOptions
    {
        $this->preserveExpiry = $preserve;
        return $this;
    }

    public function transcoder(Transcoder $transcoder): QueryOptions
    {
        $this->transcoder = $transcoder;
        return $this;
    }

    public static function getTranscoder(?QueryOptions $options): Transcoder
    {
        if ($options == null) {
            return JsonTranscoder::getInstance();
        }
        return $options->transcoder;
    }

    private function convertConsistency($consistencyLevel): int
    {
        switch ($consistencyLevel) {
            case QueryScanConsistency::NOT_BOUNDED:
                return 0;
            case QueryScanConsistency::REQUEST_PLUS:
                return 1;
            default:
                throw new InvalidArgumentException(
                    "Value for query scan consistency must be QueryScanConsistency::NOT_BOUNDED or QueryScanConsistency::REQUEST_PLUS"
                );
        }
    }

    private function convertProfile($mode): int
    {
        switch ($mode) {
            case QueryProfile::OFF:
                return 0;
            case QueryProfile::PHASES:
                return 1;
            case QueryProfile::TIMINGS:
                return 2;
            default:
                throw new InvalidArgumentException(
                    "Value for Query Profile must be QueryProfile::OFF, QueryProfile::PHASES or QueryProfile::TIMINGS."
                );
        }
    }

    public static function export(?QueryOptions $options, string $scopeName = null, string $bucketName = null): array
    {
        if ($options == null) {
            return [];
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
        return [
            'timeoutMilliseconds' => $options->timeoutMilliseconds,
            'consistentWith' => $options->consistentWith == null ? null : $options->consistentWith->export(),
            'scanConsistency' => $options->scanConsistency,
            'scanWait' => $options->scanWaitMilliseconds,
            'scanCap' => $options->scanCap,
            'pipelineCap' => $options->pipelineCap,
            'pipelineBatch' => $options->pipelineBatch,
            'maxParallelism' => $options->maxParallelism,
            'profile' => $options->profile,
            'readonly' => $options->readonly,
            'prepared' => $options->prepared,
            'flexIndex' => $options->flexIndex,
            'adHoc' => $options->adHoc,
            'namedParameters' => $namedParameters,
            'positionalParameters' => $positionalParameters,
            'clientContextId' => $options->clientContextId,
            'metrics' => $options->metrics,
            'preserveExpiry' => $options->preserveExpiry,
            'scopeName' => $scopeName,
            'bucketName' => $bucketName,
            'scopeQualifier' => $options->scopeQualifier,
        ];
    }
}
