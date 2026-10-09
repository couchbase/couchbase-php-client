<?php

declare(strict_types=1);

use Couchbase\LookupCountSpec;
use Couchbase\LookupExistsSpec;
use Couchbase\LookupGetSpec;
use Couchbase\LookupInResult;
use Couchbase\LookupInSpec;
use Couchbase\MutateArrayAddUniqueSpec;
use Couchbase\MutateArrayAppendSpec;
use Couchbase\MutateArrayInsertSpec;
use Couchbase\MutateArrayPrependSpec;
use Couchbase\MutateCounterSpec;
use Couchbase\MutateInResult;
use Couchbase\MutateInSpec;
use Couchbase\MutateInsertSpec;
use Couchbase\MutateRemoveSpec;
use Couchbase\MutateReplaceSpec;
use Couchbase\MutateUpsertSpec;

/** @return LookupInSpec[] */
function lookupInSpecs(array $specs): array
{
    return array_map('lookupInSpec', $specs);
}

function lookupInSpec(array $spec): LookupInSpec
{
    $path = $spec['path'] ?? '';
    $xattr = $spec['xattr'] ?? false;
    $kind = $spec['kind'] ?? throw new \RuntimeException('lookup-in spec had no kind');

    return match ($kind) {
        'get' => new LookupGetSpec($path, $xattr),
        'exists' => new LookupExistsSpec($path, $xattr),
        'count' => new LookupCountSpec($path, $xattr),
        default => throw new \RuntimeException("unknown lookup-in spec kind {$kind}"),
    };
}

function lookupInSpecResults(LookupInResult $result, array $specs): array
{
    $out = [];

    foreach ($specs as $index => $spec) {
        $entry = [];

        try {
            $entry['exists'] = $result->exists($index);
        } catch (\Throwable $e) {
            $entry['existsException'] = exceptionToWire($e)['exception'];
        }

        if (($spec['kind'] ?? '') === 'exists') {
            // An exists spec's content is exists() itself; content() would throw PathNotFound.
            if (isset($entry['existsException'])) {
                $entry['contentException'] = $entry['existsException'];
            } else {
                $entry['content'] = encodeContentAs($entry['exists'], $spec['contentAs'] ?? 'boolean');
            }

            $out[] = $entry;
            continue;
        }

        try {
            $entry['content'] = encodeContentAs($result->content($index), $spec['contentAs'] ?? 'json_object');
        } catch (\Throwable $e) {
            $entry['contentException'] = exceptionToWire($e)['exception'];
        }

        $out[] = $entry;
    }

    return $out;
}

/** @return MutateInSpec[] */
function mutateInSpecs(array $specs): array
{
    return array_map('mutateInSpec', $specs);
}

function mutateInSpec(array $spec): MutateInSpec
{
    $path = $spec['path'] ?? '';
    $xattr = $spec['xattr'] ?? false;
    $createPath = $spec['createPath'] ?? false;
    $kind = $spec['kind'] ?? throw new \RuntimeException('mutate-in spec had no kind');

    switch ($kind) {
        case 'upsert':
            [$value, $macro] = specValue($spec['content']);
            return new MutateUpsertSpec($path, $value, $xattr, $createPath, $macro);

        case 'insert':
            [$value, $macro] = specValue($spec['content']);
            return new MutateInsertSpec($path, $value, $xattr, $createPath, $macro);

        case 'replace':
            [$value, $macro] = specValue($spec['content']);
            return new MutateReplaceSpec($path, $value, $xattr, false, $macro);

        case 'remove':
            return new MutateRemoveSpec($path, $xattr);

        case 'array_append':
            [$values, $macro] = specValues($spec['contents']);
            return new MutateArrayAppendSpec($path, $values, $xattr, $createPath, $macro);

        case 'array_prepend':
            [$values, $macro] = specValues($spec['contents']);
            return new MutateArrayPrependSpec($path, $values, $xattr, $createPath, $macro);

        case 'array_insert':
            [$values, $macro] = specValues($spec['contents']);
            return new MutateArrayInsertSpec($path, $values, $xattr, $createPath, $macro);

        case 'array_add_unique':
            [$value, $macro] = specValue($spec['content']);
            return new MutateArrayAddUniqueSpec($path, $value, $xattr, $createPath, $macro);

        case 'increment':
            return new MutateCounterSpec($path, $spec['delta'], $xattr, $createPath);

        case 'decrement':
            return new MutateCounterSpec($path, -$spec['delta'], $xattr, $createPath);

        default:
            throw new \RuntimeException("unknown mutate-in spec kind {$kind}");
    }
}

/** @return array{0: mixed, 1: bool} value, isMacro */
function specValue(array $contentOrMacro): array
{
    if (isset($contentOrMacro['macro'])) {
        return [mutateInMacro($contentOrMacro['macro']), true];
    }

    return [decodeContentInput($contentOrMacro['content']), false];
}

/** expandMacros is per spec, not per value, so any macro expands the whole list. */
function specValues(array $contentsOrMacros): array
{
    $values = [];
    $macro = false;

    foreach ($contentsOrMacros as $contentOrMacro) {
        [$value, $isMacro] = specValue($contentOrMacro);
        $values[] = $value;
        $macro = $macro || $isMacro;
    }

    return [$values, $macro];
}

/** The PHP SDK has no macro constants; these are the literals the core matches in to_mutate_in_macro(). */
function mutateInMacro(string $macro): string
{
    return match ($macro) {
        'CAS' => '${Mutation.CAS}',
        'SEQ_NO' => '${Mutation.seqno}',
        'VALUE_CRC_32C' => '${Mutation.value_crc32c}',
        default => throw new \RuntimeException("unknown mutate-in macro {$macro}"),
    };
}

function mutateInSpecResults(MutateInResult $result, array $specs): array
{
    $out = [];

    foreach ($specs as $index => $spec) {
        if (!isset($spec['contentAs'])) {
            // stdClass so it encodes as {} rather than [].
            $out[] = new \stdClass();
            continue;
        }

        try {
            $out[] = ['content' => encodeContentAs($result->content($index), $spec['contentAs'])];
        } catch (\Throwable $e) {
            $out[] = exceptionToWire($e);
        }
    }

    return $out;
}
