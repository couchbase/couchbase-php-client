<?php

declare(strict_types=1);

namespace Couchbase\StellarNebula;

class ScanOptions
{
    private ?int $timeoutMilliseconds;
    private Transcoder $transcoder;
    private ?bool $idsOnly;
    private ?MutationState $consistentWith;
    private ?ScanSort $sort;
    private int $batchByteLimit;
    private int $batchItemLimit;
    private ?int $batchTimeLimit;

    public function __construct()
    {
        $this->transcoder = JsonTranscoder::getInstance();
        $this->batchByteLimit = 15000;
        $this->batchItemLimit = 50;
    }

    public static function build(): ScanOptions
    {
        return new ScanOptions();
    }

    public function transcoder(Transcoder $transcoder): ScanOptions
    {
        $this->transcoder = $transcoder;
        return $this;
    }

    public function timeout(int $milliseconds): ScanOptions
    {
        $this->timeoutMilliseconds = $milliseconds;
        return $this;
    }

    public function idsOnly(bool $idsOnly): ScanOptions
    {
        $this->idsOnly = $idsOnly;
        return $this;
    }

    public function consistentWith(MutationState $state): ScanOptions
    {
        $this->consistentWith = $state;
        return $this;
    }

    public function scanSort(ScanSort $sort): ScanOptions
    {
        $this->sort = $sort;
        return $this;
    }

    public function batchByteLimit(int $limit): ScanOptions
    {
        $this->batchByteLimit = $limit;
        return $this;
    }

    public function batchItemLimit(int $limit): ScanOptions
    {
        $this->batchItemLimit = $limit;
        return $this;
    }

    public function batchTimeLimit(int $limit): ScanOptions
    {
        $this->batchTimeLimit = $limit;
        return $this;
    }

    public static function encodeDocument(?ScanOptions $options, $document): array
    {
        if ($options == null) {
            return JsonTranscoder::getInstance()->encode($document);
        }
        return $options->transcoder->encode($document);
    }

    public static function export(?ScanOptions $options): array
    {
        if ($options == null) {
            return [];
        }
        return [
            'timeoutMilliseconds' => $options->timeoutMilliseconds,
            'idsOnly' => $options->idsOnly,
            'consistentWith' => $options->consistentWith,
            'scanSort' => $options->sort,
            'batchByteLimit' => $options->batchByteLimit,
            'batchItemLimit' => $options->batchItemLimit,
            'batchTimeLimit' => $options->batchTimeLimit
        ];
    }
}
