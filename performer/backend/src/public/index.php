<?php

declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';

require_once __DIR__ . '/../lib/cluster.php';
require_once __DIR__ . '/../lib/options.php';
require_once __DIR__ . '/../lib/content.php';
require_once __DIR__ . '/../lib/errors.php';
require_once __DIR__ . '/../lib/subdoc.php';
require_once __DIR__ . '/../lib/scan.php';

use Couchbase\CollectionInterface;
use Couchbase\CounterResult;
use Couchbase\GetReplicaResult;
use Couchbase\GetResult;
use Couchbase\MutationResult;
use Couchbase\MutationToken;

header('Content-Type: application/json');

// Warnings would otherwise land in the body ahead of the JSON; promote them to exceptions instead.
ini_set('display_errors', '0');
ini_set('log_errors', '1');
set_error_handler(static function (int $severity, string $message, string $file, int $line): bool {
    if (!(error_reporting() & $severity)) {
        return false;
    }
    throw new \ErrorException($message, 0, $severity, $file, $line);
});

$path = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH);
$body = json_decode((string) file_get_contents('php://input'), true) ?? [];

try {
    switch ($path) {
        case '/connection/check':
            echo encodeResponse(handleConnectionCheck($body));
            break;
        case '/connection/close':
            echo encodeResponse(handleConnectionClose($body));
            break;
        case '/execute':
            echo encodeResponse(handleExecute($body));
            break;
        case '/execute/stream':
            handleExecuteStream($body);
            break;
        default:
            http_response_code(404);
            echo encodeResponse(['ok' => false, 'error' => "unknown route {$path}"]);
    }
} catch (\Throwable $e) {
    // Substitutes rather than throws: an empty body would hide the failure entirely.
    echo json_encode(['ok' => false] + exceptionToWire($e), JSON_INVALID_UTF8_SUBSTITUTE);
}

function handleConnectionCheck(array $body): array
{
    // Surfaces unsupported options and bootstrap failures at ClusterConnectionCreate.
    connectCluster($body['connection']);
    return ['ok' => true];
}

function handleConnectionClose(array $body): array
{
    // The SDK's persistent connection cache has no forced-evict API; the proxy just stops routing here.
    return ['ok' => true];
}

function handleExecute(array $body): array
{
    $cluster = connectCluster($body['connection']);
    $loc = $body['location'];
    $collection = $cluster->bucket($loc['bucket'])->scope($loc['scope'])->collection($loc['collection']);

    $start = hrtime(true);

    try {
        $result = performOperation($collection, $body);
    } catch (\Throwable $e) {
        return ['ok' => false, 'elapsedMicros' => elapsedMicros($start)] + exceptionToWire($e);
    }

    return ['ok' => true, 'elapsedMicros' => elapsedMicros($start)] + $result;
}

/**
 * NDJSON: created, item*, then error or an explicit complete (so truncation isn't mistaken for the end).
 * Cancelled by the proxy closing the connection: the next flush aborts the script and drops the scan.
 */
function handleExecuteStream(array $body): void
{
    if ($body['op'] !== 'scan') {
        throw new \RuntimeException("op {$body['op']} is not a streaming operation");
    }

    $cluster = connectCluster($body['connection']);
    $loc = $body['location'];
    $collection = $cluster->bucket($loc['bucket'])->scope($loc['scope'])->collection($loc['collection']);

    $results = $collection->scan(scanType($body['scanType']), scanOptions($body['options'] ?? []));

    header('Content-Type: application/x-ndjson');
    while (ob_get_level() > 0) {
        ob_end_flush();
    }
    ob_implicit_flush(true);

    writeStreamLine(['created' => true]);

    try {
        foreach ($results as $result) {
            // Per the scan protocol a failed contentAs replaces that one item; the scan carries on.
            try {
                $line = encodeResponse(['item' => scanResultToWire($result, $body)]);
            } catch (\Throwable $e) {
                $line = encodeResponse(['itemError' => exceptionToWire($e)['exception']]);
            }
            echo $line, "\n";
            flush();
        }
    } catch (\Throwable $e) {
        writeStreamLine(['error' => exceptionToWire($e)['exception']]);
        return;
    }

    writeStreamLine(['complete' => true]);
}

function writeStreamLine(array $line): void
{
    echo encodeResponse($line), "\n";
    flush();
}

// json_encode returns false on invalid UTF-8, and echoing false sends nothing.
function encodeResponse(array $response): string
{
    return json_encode($response, JSON_THROW_ON_ERROR);
}

function performOperation(CollectionInterface $collection, array $body): array
{
    $loc = $body['location'];
    $opts = $body['options'] ?? [];

    switch ($body['op']) {
        case 'get':
            return getResult($collection->get($loc['id'], getOptions($opts)), $body);

        case 'get_and_lock':
            $result = $collection->getAndLock($loc['id'], $body['lockSeconds'], getAndLockOptions($opts));
            return getResult($result, $body);

        case 'get_and_touch':
            $result = $collection->getAndTouch($loc['id'], expiry($body['expiry']), getAndTouchOptions($opts));
            return getResult($result, $body);

        case 'get_any_replica':
            return getReplicaResult($collection->getAnyReplica($loc['id'], getAnyReplicaOptions($opts)), $body);

        case 'get_all_replicas':
            $results = $collection->getAllReplicas($loc['id'], getAllReplicasOptions($opts));
            return ['replicas' => array_map(fn($result) => getReplicaResult($result, $body), $results)];

        case 'exists':
            $result = $collection->exists($loc['id'], existsOptions($opts));
            // cas() is null for a missing doc; "" reads back as 0 (unset).
            return ['cas' => (string) $result->cas(), 'exists' => $result->exists()];

        case 'insert':
            return mutationResult($collection->insert($loc['id'], decodeContentInput($body['content']), insertOptions($opts)));

        case 'upsert':
            return mutationResult($collection->upsert($loc['id'], decodeContentInput($body['content']), upsertOptions($opts)));

        case 'replace':
            return mutationResult($collection->replace($loc['id'], decodeContentInput($body['content']), replaceOptions($opts)));

        case 'remove':
            return mutationResult($collection->remove($loc['id'], removeOptions($opts)));

        case 'touch':
            return mutationResult($collection->touch($loc['id'], expiry($body['expiry']), touchOptions($opts)));

        case 'append':
            return mutationResult($collection->binary()->append($loc['id'], decodeContentInput($body['content']), appendOptions($opts)));

        case 'prepend':
            return mutationResult($collection->binary()->prepend($loc['id'], decodeContentInput($body['content']), prependOptions($opts)));

        case 'increment':
            return counterResult($collection->binary()->increment($loc['id'], incrementOptions($opts)));

        case 'decrement':
            return counterResult($collection->binary()->decrement($loc['id'], decrementOptions($opts)));

        case 'lookup_in':
            $specs = $body['lookupSpecs'] ?? [];
            $result = $collection->lookupIn($loc['id'], lookupInSpecs($specs), lookupInOptions($opts));
            return [
                'cas' => (string) $result->cas(),
                'lookupResults' => lookupInSpecResults($result, $specs),
            ];

        case 'lookup_in_any_replica':
            $specs = $body['lookupSpecs'] ?? [];
            $result = $collection->lookupInAnyReplica($loc['id'], lookupInSpecs($specs), lookupInAnyReplicaOptions($opts));
            return [
                'cas' => (string) $result->cas(),
                'isReplica' => $result->isReplica(),
                'lookupResults' => lookupInSpecResults($result, $specs),
            ];

        case 'lookup_in_all_replicas':
            $specs = $body['lookupSpecs'] ?? [];
            $results = $collection->lookupInAllReplicas($loc['id'], lookupInSpecs($specs), lookupInAllReplicasOptions($opts));
            return ['lookupReplicas' => array_map(fn($result) => [
                'cas' => (string) $result->cas(),
                'isReplica' => $result->isReplica(),
                'results' => lookupInSpecResults($result, $specs),
            ], $results)];

        case 'mutate_in':
            $specs = $body['specs'] ?? [];
            $result = $collection->mutateIn($loc['id'], mutateInSpecs($specs), mutateInOptions($opts));
            return mutationResult($result) + ['specResults' => mutateInSpecResults($result, $specs)];

        case 'unlock':
            $collection->unlock($loc['id'], $body['cas'], unlockOptions($opts));
            return [];

        default:
            throw new \RuntimeException("unknown op {$body['op']}");
    }
}

function mutationResult(MutationResult $result): array
{
    return [
        'cas' => (string) $result->cas(),
        'mutationToken' => mutationToken($result->mutationToken()),
    ];
}

function counterResult(CounterResult $result): array
{
    return mutationResult($result) + ['counter' => $result->content()];
}

function mutationToken(?MutationToken $token): ?array
{
    if ($token === null) {
        return null;
    }

    return [
        'partitionId' => $token->partitionId(),
        'partitionUuid' => $token->partitionUuid(),
        'sequenceNumber' => $token->sequenceNumber(),
        'bucketName' => $token->bucketName(),
    ];
}

function getResult(GetResult $result, array $body): array
{
    return [
        'cas' => (string) $result->cas(),
        'expiryTime' => $result->expiryTime()?->getTimestamp(),
        'content' => contentOf($result, $body),
    ];
}

function getReplicaResult(GetReplicaResult $result, array $body): array
{
    return [
        'cas' => (string) $result->cas(),
        'isReplica' => $result->isReplica(),
        'content' => contentOf($result, $body),
    ];
}

function contentOf(GetResult|GetReplicaResult $result, array $body): array
{
    return encodeContentAs($result->content(), $body['contentAs'] ?? 'json_object');
}

function elapsedMicros(int $startNanos): int
{
    return intdiv(hrtime(true) - $startNanos, 1000);
}
