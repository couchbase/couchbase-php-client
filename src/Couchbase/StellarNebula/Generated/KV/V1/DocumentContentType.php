<?php

# Generated by the protocol buffer compiler.  DO NOT EDIT!
# source: couchbase/kv.v1.proto

namespace Couchbase\StellarNebula\Generated\KV\V1;

use UnexpectedValueException;

/**
 * Protobuf type <code>couchbase.kv.v1.DocumentContentType</code>
 */
class DocumentContentType
{
    /**
     * Indicates that we are not able to determine the document content type.
     *
     * Generated from protobuf enum <code>UNKNOWN = 0;</code>
     */
    const UNKNOWN = 0;
    /**
     * Indicates that the content is raw binary data.
     *
     * Generated from protobuf enum <code>BINARY = 1;</code>
     */
    const BINARY = 1;
    /**
     * Indicates that the content is JSON
     *
     * Generated from protobuf enum <code>JSON = 2;</code>
     */
    const JSON = 2;

    private static $valueToName = [
        self::UNKNOWN => 'UNKNOWN',
        self::BINARY => 'BINARY',
        self::JSON => 'JSON',
    ];

    public static function name($value)
    {
        if (!isset(self::$valueToName[$value])) {
            throw new UnexpectedValueException(
                sprintf(
                    'Enum %s has no name defined for value %s',
                    __CLASS__,
                    $value
                )
            );
        }
        return self::$valueToName[$value];
    }


    public static function value($name)
    {
        $const = __CLASS__ . '::' . strtoupper($name);
        if (!defined($const)) {
            throw new UnexpectedValueException(
                sprintf(
                    'Enum %s has no value defined for name %s',
                    __CLASS__,
                    $name
                )
            );
        }
        return constant($const);
    }
}