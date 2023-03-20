<?php
# Generated by the protocol buffer compiler.  DO NOT EDIT!
# source: couchbase/admin/bucket/v1/bucket.proto

namespace Couchbase\Protostellar\Generated\Admin\Bucket\V1;

use UnexpectedValueException;

/**
 * Protobuf type <code>couchbase.admin.bucket.v1.EvictionMode</code>
 */
class EvictionMode
{
    /**
     * Generated from protobuf enum <code>EVICTION_MODE_FULL = 0;</code>
     */
    const EVICTION_MODE_FULL = 0;
    /**
     * Generated from protobuf enum <code>EVICTION_MODE_NOT_RECENTLY_USED = 1;</code>
     */
    const EVICTION_MODE_NOT_RECENTLY_USED = 1;
    /**
     * Generated from protobuf enum <code>EVICTION_MODE_VALUE_ONLY = 2;</code>
     */
    const EVICTION_MODE_VALUE_ONLY = 2;
    /**
     * Generated from protobuf enum <code>EVICTION_MODE_NONE = 3;</code>
     */
    const EVICTION_MODE_NONE = 3;

    private static $valueToName = [
        self::EVICTION_MODE_FULL => 'EVICTION_MODE_FULL',
        self::EVICTION_MODE_NOT_RECENTLY_USED => 'EVICTION_MODE_NOT_RECENTLY_USED',
        self::EVICTION_MODE_VALUE_ONLY => 'EVICTION_MODE_VALUE_ONLY',
        self::EVICTION_MODE_NONE => 'EVICTION_MODE_NONE',
    ];

    public static function name($value)
    {
        if (!isset(self::$valueToName[$value])) {
            throw new UnexpectedValueException(sprintf(
                    'Enum %s has no name defined for value %s', __CLASS__, $value));
        }
        return self::$valueToName[$value];
    }


    public static function value($name)
    {
        $const = __CLASS__ . '::' . strtoupper($name);
        if (!defined($const)) {
            throw new UnexpectedValueException(sprintf(
                    'Enum %s has no value defined for name %s', __CLASS__, $name));
        }
        return constant($const);
    }
}

