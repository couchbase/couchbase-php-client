<?php
# Generated by the protocol buffer compiler.  DO NOT EDIT!
# source: couchbase/query.v1.proto

namespace Couchbase\Protostellar\Generated\Query\V1\QueryResponse\MetaData;

use UnexpectedValueException;

/**
 * Protobuf type <code>couchbase.query.v1.QueryResponse.MetaData.MetaDataStatus</code>
 */
class MetaDataStatus
{
    /**
     * Generated from protobuf enum <code>RUNNING = 0;</code>
     */
    const RUNNING = 0;
    /**
     * Generated from protobuf enum <code>SUCCESS = 1;</code>
     */
    const SUCCESS = 1;
    /**
     * Generated from protobuf enum <code>ERRORS = 2;</code>
     */
    const ERRORS = 2;
    /**
     * Generated from protobuf enum <code>COMPLETED = 3;</code>
     */
    const COMPLETED = 3;
    /**
     * Generated from protobuf enum <code>STOPPED = 4;</code>
     */
    const STOPPED = 4;
    /**
     * Generated from protobuf enum <code>TIMEOUT = 5;</code>
     */
    const TIMEOUT = 5;
    /**
     * Generated from protobuf enum <code>CLOSED = 6;</code>
     */
    const CLOSED = 6;
    /**
     * Generated from protobuf enum <code>FATAL = 7;</code>
     */
    const FATAL = 7;
    /**
     * Generated from protobuf enum <code>ABORTED = 8;</code>
     */
    const ABORTED = 8;
    /**
     * Generated from protobuf enum <code>UNKNOWN = 9;</code>
     */
    const UNKNOWN = 9;

    private static $valueToName = [
        self::RUNNING => 'RUNNING',
        self::SUCCESS => 'SUCCESS',
        self::ERRORS => 'ERRORS',
        self::COMPLETED => 'COMPLETED',
        self::STOPPED => 'STOPPED',
        self::TIMEOUT => 'TIMEOUT',
        self::CLOSED => 'CLOSED',
        self::FATAL => 'FATAL',
        self::ABORTED => 'ABORTED',
        self::UNKNOWN => 'UNKNOWN',
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
class_alias(MetaDataStatus::class, \Couchbase\Protostellar\Generated\Query\V1\QueryResponse_MetaData_MetaDataStatus::class);

