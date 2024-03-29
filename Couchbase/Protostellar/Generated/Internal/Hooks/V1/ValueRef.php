<?php
# Generated by the protocol buffer compiler.  DO NOT EDIT!
# source: couchbase/internal/hooks/v1/hooks.proto

namespace Couchbase\Protostellar\Generated\Internal\Hooks\V1;

use Google\Protobuf\Internal\GPBType;
use Google\Protobuf\Internal\RepeatedField;
use Google\Protobuf\Internal\GPBUtil;

/**
 * Generated from protobuf message <code>couchbase.internal.hooks.v1.ValueRef</code>
 */
class ValueRef extends \Google\Protobuf\Internal\Message
{
    protected $value;

    /**
     * Constructor.
     *
     * @param array $data {
     *     Optional. Data for populating the Message object.
     *
     *     @type string $request_field
     *     @type string $counter_value
     *     @type string $json_value
     * }
     */
    public function __construct($data = NULL) {
        \GPBMetadata\Couchbase\Internal\Hooks\V1\Hooks::initOnce();
        parent::__construct($data);
    }

    /**
     * Generated from protobuf field <code>string request_field = 1;</code>
     * @return string
     */
    public function getRequestField()
    {
        return $this->readOneof(1);
    }

    public function hasRequestField()
    {
        return $this->hasOneof(1);
    }

    /**
     * Generated from protobuf field <code>string request_field = 1;</code>
     * @param string $var
     * @return $this
     */
    public function setRequestField($var)
    {
        GPBUtil::checkString($var, True);
        $this->writeOneof(1, $var);

        return $this;
    }

    /**
     * Generated from protobuf field <code>string counter_value = 2;</code>
     * @return string
     */
    public function getCounterValue()
    {
        return $this->readOneof(2);
    }

    public function hasCounterValue()
    {
        return $this->hasOneof(2);
    }

    /**
     * Generated from protobuf field <code>string counter_value = 2;</code>
     * @param string $var
     * @return $this
     */
    public function setCounterValue($var)
    {
        GPBUtil::checkString($var, True);
        $this->writeOneof(2, $var);

        return $this;
    }

    /**
     * Generated from protobuf field <code>bytes json_value = 3;</code>
     * @return string
     */
    public function getJsonValue()
    {
        return $this->readOneof(3);
    }

    public function hasJsonValue()
    {
        return $this->hasOneof(3);
    }

    /**
     * Generated from protobuf field <code>bytes json_value = 3;</code>
     * @param string $var
     * @return $this
     */
    public function setJsonValue($var)
    {
        GPBUtil::checkString($var, False);
        $this->writeOneof(3, $var);

        return $this;
    }

    /**
     * @return string
     */
    public function getValue()
    {
        return $this->whichOneof("value");
    }

}

