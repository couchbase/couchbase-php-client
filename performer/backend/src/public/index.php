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

    // Stays 0 if the op fails before reaching the SDK.
    $elapsedNanos = 0;

    try {
        $result = performOperation($collection, $body, $elapsedNanos);
    } catch (\Throwable $e) {
        return ['ok' => false, 'elapsedNanos' => $elapsedNanos] + exceptionToWire($e);
    }

    return ['ok' => true, 'elapsedNanos' => $elapsedNanos] + $result;
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

// Arguments are built before timed() and results shaped after it, so elapsedNanos brackets only the SDK call.
function performOperation(CollectionInterface $collection, array $body, ?int &$elapsedNanos): array
{
    $id = $body['location']['id'];
    $opts = $body['options'] ?? [];

    switch ($body['op']) {
        case 'get':
            $options = getOptions($opts);
            return getResult(timed(fn() => $collection->get($id, $options), $elapsedNanos), $body);

        case 'get_and_lock':
            $options = getAndLockOptions($opts);
            $result = timed(fn() => $collection->getAndLock($id, $body['lockSeconds'], $options), $elapsedNanos);
            return getResult($result, $body);

        case 'get_and_touch':
            $expiry = expiry($body['expiry']);
            $options = getAndTouchOptions($opts);
            $result = timed(fn() => $collection->getAndTouch($id, $expiry, $options), $elapsedNanos);
            return getResult($result, $body);

        case 'get_any_replica':
            $options = getAnyReplicaOptions($opts);
            return getReplicaResult(timed(fn() => $collection->getAnyReplica($id, $options), $elapsedNanos), $body);

        case 'get_all_replicas':
            $options = getAllReplicasOptions($opts);
            $results = timed(fn() => $collection->getAllReplicas($id, $options), $elapsedNanos);
            return ['replicas' => array_map(fn($result) => getReplicaResult($result, $body), $results)];

        case 'exists':
            $options = existsOptions($opts);
            $result = timed(fn() => $collection->exists($id, $options), $elapsedNanos);
            // cas() is null for a missing doc; "" reads back as 0 (unset).
            return ['cas' => (string) $result->cas(), 'exists' => $result->exists()];

        case 'insert':
            $content = decodeContentInput($body['content']);
            $options = insertOptions($opts);
            return mutationResult(timed(fn() => $collection->insert($id, $content, $options), $elapsedNanos));

        case 'upsert':
            $content = decodeContentInput($body['content']);
            $options = upsertOptions($opts);
            return mutationResult(timed(fn() => $collection->upsert($id, $content, $options), $elapsedNanos));

        case 'replace':
            $content = decodeContentInput($body['content']);
            $options = replaceOptions($opts);
            return mutationResult(timed(fn() => $collection->replace($id, $content, $options), $elapsedNanos));

        case 'remove':
            $options = removeOptions($opts);
            return mutationResult(timed(fn() => $collection->remove($id, $options), $elapsedNanos));

        case 'touch':
            $expiry = expiry($body['expiry']);
            $options = touchOptions($opts);
            return mutationResult(timed(fn() => $collection->touch($id, $expiry, $options), $elapsedNanos));

        case 'append':
            $binary = $collection->binary();
            $content = decodeContentInput($body['content']);
            $options = appendOptions($opts);
            return mutationResult(timed(fn() => $binary->append($id, $content, $options), $elapsedNanos));

        case 'prepend':
            $binary = $collection->binary();
            $content = decodeContentInput($body['content']);
            $options = prependOptions($opts);
            return mutationResult(timed(fn() => $binary->prepend($id, $content, $options), $elapsedNanos));

        case 'increment':
            $binary = $collection->binary();
            $options = incrementOptions($opts);
            return counterResult(timed(fn() => $binary->increment($id, $options), $elapsedNanos));

        case 'decrement':
            $binary = $collection->binary();
            $options = decrementOptions($opts);
            return counterResult(timed(fn() => $binary->decrement($id, $options), $elapsedNanos));

        case 'lookup_in':
            $specs = $body['lookupSpecs'] ?? [];
            $sdkSpecs = lookupInSpecs($specs);
            $options = lookupInOptions($opts);
            $result = timed(fn() => $collection->lookupIn($id, $sdkSpecs, $options), $elapsedNanos);
            return [
                'cas' => (string) $result->cas(),
                'lookupResults' => lookupInSpecResults($result, $specs),
            ];

        case 'lookup_in_any_replica':
            $specs = $body['lookupSpecs'] ?? [];
            $sdkSpecs = lookupInSpecs($specs);
            $options = lookupInAnyReplicaOptions($opts);
            $result = timed(fn() => $collection->lookupInAnyReplica($id, $sdkSpecs, $options), $elapsedNanos);
            return [
                'cas' => (string) $result->cas(),
                'isReplica' => $result->isReplica(),
                'lookupResults' => lookupInSpecResults($result, $specs),
            ];

        case 'lookup_in_all_replicas':
            $specs = $body['lookupSpecs'] ?? [];
            $sdkSpecs = lookupInSpecs($specs);
            $options = lookupInAllReplicasOptions($opts);
            $results = timed(fn() => $collection->lookupInAllReplicas($id, $sdkSpecs, $options), $elapsedNanos);
            return ['lookupReplicas' => array_map(fn($result) => [
                'cas' => (string) $result->cas(),
                'isReplica' => $result->isReplica(),
                'results' => lookupInSpecResults($result, $specs),
            ], $results)];

        case 'mutate_in':
            $specs = $body['specs'] ?? [];
            $sdkSpecs = mutateInSpecs($specs);
            $options = mutateInOptions($opts);
            $result = timed(fn() => $collection->mutateIn($id, $sdkSpecs, $options), $elapsedNanos);
            return mutationResult($result) + ['specResults' => mutateInSpecResults($result, $specs)];

        case 'unlock':
            $options = unlockOptions($opts);
            timed(fn() => $collection->unlock($id, $body['cas'], $options), $elapsedNanos);
            return [];

        default:
            throw new \RuntimeException("unknown op {$body['op']}");
    }
}

// Set in finally so failed ops are timed too.
function timed(callable $sdkCall, ?int &$elapsedNanos)
{
    $start = hrtime(true);
    try {
        return $sdkCall();
    } finally {
        $elapsedNanos = hrtime(true) - $start;
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
