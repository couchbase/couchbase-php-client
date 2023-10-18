<?php
# Generated by the protocol buffer compiler.  DO NOT EDIT!
# source: couchbase/view/v1/view.proto

namespace Couchbase\Protostellar\Generated\View\V1\ViewQueryRequest;

use UnexpectedValueException;

/**
 * Protobuf type <code>couchbase.view.v1.ViewQueryRequest.ScanConsistency</code>
 */
class ScanConsistency
{
    /**
     * Generated from protobuf enum <code>SCAN_CONSISTENCY_NOT_BOUNDED = 0;</code>
     */
    const SCAN_CONSISTENCY_NOT_BOUNDED = 0;
    /**
     * Generated from protobuf enum <code>SCAN_CONSISTENCY_REQUEST_PLUS = 1;</code>
     */
    const SCAN_CONSISTENCY_REQUEST_PLUS = 1;
    /**
     * Generated from protobuf enum <code>SCAN_CONSISTENCY_UPDATE_AFTER = 2;</code>
     */
    const SCAN_CONSISTENCY_UPDATE_AFTER = 2;

    private static $valueToName = [
        self::SCAN_CONSISTENCY_NOT_BOUNDED => 'SCAN_CONSISTENCY_NOT_BOUNDED',
        self::SCAN_CONSISTENCY_REQUEST_PLUS => 'SCAN_CONSISTENCY_REQUEST_PLUS',
        self::SCAN_CONSISTENCY_UPDATE_AFTER => 'SCAN_CONSISTENCY_UPDATE_AFTER',
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
class_alias(ScanConsistency::class, \Couchbase\Protostellar\Generated\View\V1\ViewQueryRequest_ScanConsistency::class);

