<?php

declare(strict_types=1);

use Couchbase\AppendOptions;
use Couchbase\DecrementOptions;
use Couchbase\DurabilityLevel;
use Couchbase\ExistsOptions;
use Couchbase\GetAllReplicasOptions;
use Couchbase\GetAndLockOptions;
use Couchbase\GetAndTouchOptions;
use Couchbase\GetAnyReplicaOptions;
use Couchbase\GetOptions;
use Couchbase\IncrementOptions;
use Couchbase\InsertOptions;
use Couchbase\JsonTranscoder;
use Couchbase\LookupInAllReplicasOptions;
use Couchbase\LookupInAnyReplicaOptions;
use Couchbase\LookupInOptions;
use Couchbase\MutateInOptions;
use Couchbase\PrependOptions;
use Couchbase\RawBinaryTranscoder;
use Couchbase\RawJsonTranscoder;
use Couchbase\RawStringTranscoder;
use Couchbase\ReadPreference;
use Couchbase\RemoveOptions;
use Couchbase\ReplaceOptions;
use Couchbase\StoreSemantics;
use Couchbase\TouchOptions;
use Couchbase\Transcoder;
use Couchbase\UnlockOptions;
use Couchbase\UpsertOptions;


function getOptions(array $o): GetOptions
{
    $options = new GetOptions();
    if (isset($o['timeoutMillis'])) {
        $options->timeout($o['timeoutMillis']);
    }
    if (isset($o['withExpiry'])) {
        $options->withExpiry($o['withExpiry']);
    }
    // Empty means "do not project", not "project nothing".
    if (!empty($o['projections'])) {
        $options->project($o['projections']);
    }
    $options->transcoder(readTranscoder($o));

    return $options;
}

function insertOptions(array $o): ?InsertOptions
{
    if (!$o) {
        return null;
    }

    $options = new InsertOptions();
    if (isset($o['timeoutMillis'])) {
        $options->timeout($o['timeoutMillis']);
    }
    if (isset($o['expiry'])) {
        $options->expiry(expiry($o['expiry']));
    }
    if (isset($o['durability'])) {
        $options->durabilityLevel(durabilityLevel($o['durability']), null);
    }
    if (isset($o['transcoder'])) {
        $options->transcoder(transcoder($o['transcoder']));
    }

    return $options;
}

function upsertOptions(array $o): ?UpsertOptions
{
    if (!$o) {
        return null;
    }

    $options = new UpsertOptions();
    if (isset($o['timeoutMillis'])) {
        $options->timeout($o['timeoutMillis']);
    }
    if (isset($o['expiry'])) {
        $options->expiry(expiry($o['expiry']));
    }
    if (isset($o['preserveExpiry'])) {
        $options->preserveExpiry($o['preserveExpiry']);
    }
    if (isset($o['durability'])) {
        $options->durabilityLevel(durabilityLevel($o['durability']));
    }
    if (isset($o['transcoder'])) {
        $options->transcoder(transcoder($o['transcoder']));
    }

    return $options;
}

function replaceOptions(array $o): ?ReplaceOptions
{
    if (!$o) {
        return null;
    }

    $options = new ReplaceOptions();
    if (isset($o['timeoutMillis'])) {
        $options->timeout($o['timeoutMillis']);
    }
    if (isset($o['expiry'])) {
        $options->expiry(expiry($o['expiry']));
    }
    if (isset($o['preserveExpiry'])) {
        $options->preserveExpiry($o['preserveExpiry']);
    }
    if (isset($o['durability'])) {
        $options->durabilityLevel(durabilityLevel($o['durability']));
    }
    if (isset($o['cas'])) {
        $options->cas($o['cas']);
    }
    if (isset($o['transcoder'])) {
        $options->transcoder(transcoder($o['transcoder']));
    }

    return $options;
}

function appendOptions(array $o): ?AppendOptions
{
    if (!$o) {
        return null;
    }

    $options = new AppendOptions();
    if (isset($o['timeoutMillis'])) {
        $options->timeout($o['timeoutMillis']);
    }
    if (isset($o['cas'])) {
        $options->cas($o['cas']);
    }
    if (isset($o['durability'])) {
        $options->durabilityLevel(durabilityLevel($o['durability']));
    }

    return $options;
}

function prependOptions(array $o): ?PrependOptions
{
    if (!$o) {
        return null;
    }

    $options = new PrependOptions();
    if (isset($o['timeoutMillis'])) {
        $options->timeout($o['timeoutMillis']);
    }
    if (isset($o['cas'])) {
        $options->cas($o['cas']);
    }
    if (isset($o['durability'])) {
        $options->durabilityLevel(durabilityLevel($o['durability']));
    }

    return $options;
}

function incrementOptions(array $o): ?IncrementOptions
{
    if (!$o) {
        return null;
    }

    $options = new IncrementOptions();
    applyCounterOptions($options, $o);

    return $options;
}

function decrementOptions(array $o): ?DecrementOptions
{
    if (!$o) {
        return null;
    }

    $options = new DecrementOptions();
    applyCounterOptions($options, $o);

    return $options;
}

function applyCounterOptions(IncrementOptions|DecrementOptions $options, array $o): void
{
    if (isset($o['timeoutMillis'])) {
        $options->timeout($o['timeoutMillis']);
    }
    if (isset($o['expiry'])) {
        $options->expiry(expiry($o['expiry']));
    }
    if (isset($o['delta'])) {
        $options->delta($o['delta']);
    }
    if (isset($o['initial'])) {
        $options->initial($o['initial']);
    }
    if (isset($o['durability'])) {
        $options->durabilityLevel(durabilityLevel($o['durability']));
    }
}

function lookupInOptions(array $o): LookupInOptions
{
    if (isset($o['accessDeleted'])) {
        throw new \RuntimeException('accessDeleted is not exposed by the PHP SDK LookupInOptions');
    }

    $options = new LookupInOptions();
    $options->transcoder(readTranscoder($o));
    if (isset($o['timeoutMillis'])) {
        $options->timeout($o['timeoutMillis']);
    }

    return $options;
}

function lookupInAllReplicasOptions(array $o): LookupInAllReplicasOptions
{
    $options = new LookupInAllReplicasOptions();
    $options->transcoder(readTranscoder($o));
    if (isset($o['timeoutMillis'])) {
        $options->timeout($o['timeoutMillis']);
    }
    if (isset($o['readPreference'])) {
        $options->readPreference(readPreference($o['readPreference']));
    }

    return $options;
}

function lookupInAnyReplicaOptions(array $o): LookupInAnyReplicaOptions
{
    $options = new LookupInAnyReplicaOptions();
    $options->transcoder(readTranscoder($o));
    if (isset($o['timeoutMillis'])) {
        $options->timeout($o['timeoutMillis']);
    }
    if (isset($o['readPreference'])) {
        $options->readPreference(readPreference($o['readPreference']));
    }

    return $options;
}

function mutateInOptions(array $o): ?MutateInOptions
{
    if (!$o) {
        return null;
    }

    if (isset($o['accessDeleted'])) {
        throw new \RuntimeException('accessDeleted is not exposed by the PHP SDK MutateInOptions');
    }
    if (isset($o['createAsDeleted'])) {
        throw new \RuntimeException('createAsDeleted is not exposed by the PHP SDK MutateInOptions');
    }

    $options = new MutateInOptions();
    if (isset($o['timeoutMillis'])) {
        $options->timeout($o['timeoutMillis']);
    }
    if (isset($o['expiry'])) {
        $options->expiry(expiry($o['expiry']));
    }
    if (isset($o['cas'])) {
        $options->cas($o['cas']);
    }
    if (isset($o['durability'])) {
        $options->durabilityLevel(durabilityLevel($o['durability']));
    }
    if (isset($o['preserveExpiry'])) {
        $options->preserveExpiry($o['preserveExpiry']);
    }
    if (isset($o['storeSemantics'])) {
        $options->storeSemantics(storeSemantics($o['storeSemantics']));
    }

    return $options;
}

function storeSemantics(string $semantics): string
{
    return match ($semantics) {
        'INSERT' => StoreSemantics::INSERT,
        'REPLACE' => StoreSemantics::REPLACE,
        'UPSERT' => StoreSemantics::UPSERT,
        default => throw new \RuntimeException("unknown store semantics {$semantics}"),
    };
}

function getAndLockOptions(array $o): GetAndLockOptions
{
    $options = new GetAndLockOptions();
    if (isset($o['timeoutMillis'])) {
        $options->timeout($o['timeoutMillis']);
    }
    $options->transcoder(readTranscoder($o));

    return $options;
}

function getAndTouchOptions(array $o): GetAndTouchOptions
{
    $options = new GetAndTouchOptions();
    if (isset($o['timeoutMillis'])) {
        $options->timeout($o['timeoutMillis']);
    }
    $options->transcoder(readTranscoder($o));

    return $options;
}

function getAllReplicasOptions(array $o): GetAllReplicasOptions
{
    $options = new GetAllReplicasOptions();
    if (isset($o['timeoutMillis'])) {
        $options->timeout($o['timeoutMillis']);
    }
    $options->transcoder(readTranscoder($o));
    if (isset($o['readPreference'])) {
        $options->readPreference(readPreference($o['readPreference']));
    }

    return $options;
}

function getAnyReplicaOptions(array $o): GetAnyReplicaOptions
{
    $options = new GetAnyReplicaOptions();
    if (isset($o['timeoutMillis'])) {
        $options->timeout($o['timeoutMillis']);
    }
    $options->transcoder(readTranscoder($o));
    if (isset($o['readPreference'])) {
        $options->readPreference(readPreference($o['readPreference']));
    }

    return $options;
}

function touchOptions(array $o): ?TouchOptions
{
    if (!$o) {
        return null;
    }

    $options = new TouchOptions();
    if (isset($o['timeoutMillis'])) {
        $options->timeout($o['timeoutMillis']);
    }

    return $options;
}

function unlockOptions(array $o): ?UnlockOptions
{
    if (!$o) {
        return null;
    }

    $options = new UnlockOptions();
    if (isset($o['timeoutMillis'])) {
        $options->timeout($o['timeoutMillis']);
    }

    return $options;
}

function existsOptions(array $o): ?ExistsOptions
{
    if (!$o) {
        return null;
    }

    $options = new ExistsOptions();
    if (isset($o['timeoutMillis'])) {
        $options->timeout($o['timeoutMillis']);
    }

    return $options;
}

function removeOptions(array $o): ?RemoveOptions
{
    if (!$o) {
        return null;
    }

    $options = new RemoveOptions();
    if (isset($o['timeoutMillis'])) {
        $options->timeout($o['timeoutMillis']);
    }
    if (isset($o['durability'])) {
        $options->durabilityLevel(durabilityLevel($o['durability']));
    }
    if (isset($o['cas'])) {
        $options->cas($o['cas']);
    }

    return $options;
}

/** @return int|DateTimeInterface */
function expiry(array $expiry)
{
    if (isset($expiry['relativeSecs'])) {
        return $expiry['relativeSecs'];
    }
    if (isset($expiry['absoluteEpochSecs'])) {
        return (new DateTimeImmutable())->setTimestamp($expiry['absoluteEpochSecs']);
    }

    throw new \RuntimeException('expiry had neither a relative nor an absolute value');
}

function durabilityLevel(array $durability): string
{
    if (isset($durability['observe'])) {
        throw new \RuntimeException('observe-based durability is not supported by the PHP SDK');
    }

    $level = $durability['level'] ?? throw new \RuntimeException('durability had no level');

    return match ($level) {
        'NONE' => DurabilityLevel::NONE,
        'MAJORITY' => DurabilityLevel::MAJORITY,
        'MAJORITY_AND_PERSIST_TO_ACTIVE' => DurabilityLevel::MAJORITY_AND_PERSIST_TO_ACTIVE,
        'PERSIST_TO_MAJORITY' => DurabilityLevel::PERSIST_TO_MAJORITY,
        default => throw new \RuntimeException("unknown durability level {$level}"),
    };
}

function readPreference(string $preference): string
{
    return match ($preference) {
        'NO_PREFERENCE' => ReadPreference::NO_PREFERENCE,
        'SELECTED_SERVER_GROUP' => ReadPreference::SELECTED_SERVER_GROUP,
        default => throw new \RuntimeException("unsupported read preference {$preference}"),
    };
}

function transcoder(string $name): Transcoder
{
    return match ($name) {
        'json' => objectModeJsonTranscoder(),
        'raw_json' => RawJsonTranscoder::getInstance(),
        'raw_string' => RawStringTranscoder::getInstance(),
        'raw_binary' => RawBinaryTranscoder::getInstance(),
        default => throw new \RuntimeException("unsupported transcoder {$name}"),
    };
}

/** Always set: the SDK's default JsonTranscoder is associative, which turns {} into []. */
function readTranscoder(array $o): Transcoder
{
    return transcoder($o['transcoder'] ?? 'json');
}

function objectModeJsonTranscoder(): JsonTranscoder
{
    static $instance = null;

    return $instance ??= new JsonTranscoder(decodeAssociative: false);
}
