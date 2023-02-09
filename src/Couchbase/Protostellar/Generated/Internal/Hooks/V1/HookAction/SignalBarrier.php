<?php
# Generated by the protocol buffer compiler.  DO NOT EDIT!
# source: couchbase/internal/hooks.v1.proto

namespace Couchbase\Protostellar\Generated\Internal\Hooks\V1\HookAction;

use Google\Protobuf\Internal\GPBType;
use Google\Protobuf\Internal\RepeatedField;
use Google\Protobuf\Internal\GPBUtil;

/**
 * Generated from protobuf message <code>couchbase.internal.hooks.v1.HookAction.SignalBarrier</code>
 */
class SignalBarrier extends \Google\Protobuf\Internal\Message
{
    /**
     * Generated from protobuf field <code>string barrier_id = 1;</code>
     */
    protected $barrier_id = '';
    /**
     * Generated from protobuf field <code>bool signal_all = 2;</code>
     */
    protected $signal_all = false;

    /**
     * Constructor.
     *
     * @param array $data {
     *     Optional. Data for populating the Message object.
     *
     *     @type string $barrier_id
     *     @type bool $signal_all
     * }
     */
    public function __construct($data = NULL) {
        \GPBMetadata\Couchbase\Internal\HooksV1::initOnce();
        parent::__construct($data);
    }

    /**
     * Generated from protobuf field <code>string barrier_id = 1;</code>
     * @return string
     */
    public function getBarrierId()
    {
        return $this->barrier_id;
    }

    /**
     * Generated from protobuf field <code>string barrier_id = 1;</code>
     * @param string $var
     * @return $this
     */
    public function setBarrierId($var)
    {
        GPBUtil::checkString($var, True);
        $this->barrier_id = $var;

        return $this;
    }

    /**
     * Generated from protobuf field <code>bool signal_all = 2;</code>
     * @return bool
     */
    public function getSignalAll()
    {
        return $this->signal_all;
    }

    /**
     * Generated from protobuf field <code>bool signal_all = 2;</code>
     * @param bool $var
     * @return $this
     */
    public function setSignalAll($var)
    {
        GPBUtil::checkBool($var);
        $this->signal_all = $var;

        return $this;
    }

}

// Adding a class alias for backwards compatibility with the previous class name.
class_alias(SignalBarrier::class, \Couchbase\Protostellar\Generated\Internal\Hooks\V1\HookAction_SignalBarrier::class);

