<?php
# Generated by the protocol buffer compiler.  DO NOT EDIT!
# source: couchbase/query/v1/query.proto

namespace Couchbase\Protostellar\Generated\Query\V1\QueryRequest;

use UnexpectedValueException;

/**
 * Protobuf type <code>couchbase.query.v1.QueryRequest.ProfileMode</code>
 */
class ProfileMode
{
    /**
     * Generated from protobuf enum <code>PROFILE_MODE_OFF = 0;</code>
     */
    const PROFILE_MODE_OFF = 0;
    /**
     * Generated from protobuf enum <code>PROFILE_MODE_PHASES = 1;</code>
     */
    const PROFILE_MODE_PHASES = 1;
    /**
     * Generated from protobuf enum <code>PROFILE_MODE_TIMINGS = 2;</code>
     */
    const PROFILE_MODE_TIMINGS = 2;

    private static $valueToName = [
        self::PROFILE_MODE_OFF => 'PROFILE_MODE_OFF',
        self::PROFILE_MODE_PHASES => 'PROFILE_MODE_PHASES',
        self::PROFILE_MODE_TIMINGS => 'PROFILE_MODE_TIMINGS',
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

// Adding a class alias for backwards compatibility with the previous class name.
class_alias(ProfileMode::class, \Couchbase\Protostellar\Generated\Query\V1\QueryRequest_ProfileMode::class);

