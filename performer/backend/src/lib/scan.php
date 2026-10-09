<?php

declare(strict_types=1);

use Couchbase\MutationResult;
use Couchbase\MutationState;
use Couchbase\PrefixScan;
use Couchbase\RangeScan;
use Couchbase\SamplingScan;
use Couchbase\ScanOptions;
use Couchbase\ScanResult;
use Couchbase\ScanTerm;
use Couchbase\ScanType;

function scanType(array $type): ScanType
{
    if (isset($type['prefix'])) {
        return new PrefixScan($type['prefix']);
    }

    if (isset($type['sampling'])) {
        $sampling = $type['sampling'];
        return new SamplingScan($sampling['limit'], $sampling['seed'] ?? null);
    }

    if (isset($type['range'])) {
        $range = $type['range'];
        return new RangeScan(scanTerm($range['from'] ?? null), scanTerm($range['to'] ?? null));
    }

    throw new \RuntimeException('scan type had none of range, sampling or prefix');
}

function scanTerm(?array $term): ?ScanTerm
{
    if ($term === null) {
        return null;
    }

    return new ScanTerm($term['term'], $term['exclusive'] ?? null);
}

function scanOptions(array $o): ScanOptions
{
    if (isset($o['batchTimeLimit'])) {
        throw new \RuntimeException('batchTimeLimit is not supported by the PHP SDK');
    }
    if (isset($o['sort'])) {
        throw new \RuntimeException('scan sort was removed from the RFC and the PHP SDK has no equivalent');
    }

    $options = new ScanOptions();
    if (isset($o['timeoutMillis'])) {
        $options->timeout($o['timeoutMillis']);
    }
    if (isset($o['idsOnly'])) {
        $options->idsOnly($o['idsOnly']);
    }
    if (isset($o['batchByteLimit'])) {
        $options->batchByteLimit($o['batchByteLimit']);
    }
    if (isset($o['batchItemLimit'])) {
        $options->batchItemLimit($o['batchItemLimit']);
    }
    if (isset($o['concurrency'])) {
        $options->concurrency($o['concurrency']);
    }
    $options->transcoder(readTranscoder($o));
    if (isset($o['consistentWith'])) {
        $options->consistentWith(mutationState($o['consistentWith']));
    }

    return $options;
}

function mutationState(array $tokens): MutationState
{
    $state = new MutationState();

    foreach ($tokens as $token) {
        $state->add(new MutationResult([
            'id' => '',
            'mutationToken' => [
                'bucketName' => $token['bucketName'],
                'partitionId' => $token['partitionId'],
                'partitionUuid' => $token['partitionUuid'],
                'sequenceNumber' => $token['sequenceNumber'],
            ],
        ]));
    }

    return $state;
}

function scanResultToWire(ScanResult $result, array $body): array
{
    $entry = ['id' => $result->id(), 'idOnly' => $result->idsOnly()];

    if ($result->idsOnly()) {
        return $entry;
    }

    $entry['cas'] = (string) $result->cas();
    $entry['expiryTime'] = $result->expiryTime()?->getTimestamp();

    if (($body['contentAs'] ?? '') !== '') {
        $entry['content'] = encodeContentAs($result->content(), $body['contentAs']);
    }

    return $entry;
}
