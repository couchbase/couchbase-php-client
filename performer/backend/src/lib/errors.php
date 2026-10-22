<?php

declare(strict_types=1);

use Couchbase\Exception\CouchbaseException;

function exceptionToWire(\Throwable $e): array
{
    return [
        'exception' => [
            'name' => shortClassName(get_class($e)),
            'couchbase' => $e instanceof CouchbaseException,
            'serialized' => serializeException($e),
        ],
    ];
}

/**
 * The driver parses the JSON between the first '{' and last '}', so SDK errors must not include a
 * stack trace (its {closure}/{main} frames would be picked up instead of the context).
 */
function serializeException(\Throwable $e): string
{
    if (!$e instanceof CouchbaseException) {
        return (string) $e;
    }

    $serialized = sprintf('%s: %s', get_class($e), $e->getMessage());
    $context = $e->getContext();

    return $context === null ? $serialized : $serialized . ' ' . json_encode($context);
}

function shortClassName(string $fqcn): string
{
    $pos = strrpos($fqcn, '\\');

    return $pos === false ? $fqcn : substr($fqcn, $pos + 1);
}
