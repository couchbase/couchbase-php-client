<?php
# Generated by the protocol buffer compiler.  DO NOT EDIT!
# source: couchbase/internal/hooks/v1/hooks.proto

namespace Couchbase\Protostellar\Generated\Internal\Hooks\V1\HookAction;

use Google\Protobuf\Internal\GPBType;
use Google\Protobuf\Internal\RepeatedField;
use Google\Protobuf\Internal\GPBUtil;

/**
 * Generated from protobuf message <code>couchbase.internal.hooks.v1.HookAction.ReturnResponse</code>
 */
class ReturnResponse extends \Google\Protobuf\Internal\Message
{
    /**
     * Generated from protobuf field <code>.google.protobuf.Any value = 1;</code>
     */
    protected $value = null;

    /**
     * Constructor.
     *
     * @param array $data {
     *     Optional. Data for populating the Message object.
     *
     *     @type \Google\Protobuf\Any $value
     * }
     */
    public function __construct($data = NULL) {
        \GPBMetadata\Couchbase\Internal\Hooks\V1\Hooks::initOnce();
        parent::__construct($data);
    }

    /**
     * Generated from protobuf field <code>.google.protobuf.Any value = 1;</code>
     * @return \Google\Protobuf\Any|null
     */
    public function getValue()
    {
        return $this->value;
    }

    public function hasValue()
    {
        return isset($this->value);
    }

    public function clearValue()
    {
        unset($this->value);
    }

    /**
     * Generated from protobuf field <code>.google.protobuf.Any value = 1;</code>
     * @param \Google\Protobuf\Any $var
     * @return $this
     */
    public function setValue($var)
    {
        GPBUtil::checkMessage($var, \Google\Protobuf\Any::class);
        $this->value = $var;

        return $this;
    }

}

// Adding a class alias for backwards compatibility with the previous class name.
class_alias(ReturnResponse::class, \Couchbase\Protostellar\Generated\Internal\Hooks\V1\HookAction_ReturnResponse::class);

