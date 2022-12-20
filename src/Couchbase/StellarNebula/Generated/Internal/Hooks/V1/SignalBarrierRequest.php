<?php
# Generated by the protocol buffer compiler.  DO NOT EDIT!
# source: couchbase/internal/hooks.v1.proto

namespace Couchbase\StellarNebula\Generated\Internal\Hooks\V1;

use Google\Protobuf\Internal\GPBType;
use Google\Protobuf\Internal\RepeatedField;
use Google\Protobuf\Internal\GPBUtil;

/**
 * Generated from protobuf message <code>couchbase.internal.hooks.v1.SignalBarrierRequest</code>
 */
class SignalBarrierRequest extends \Google\Protobuf\Internal\Message
{
    /**
     * Generated from protobuf field <code>string hooks_context_id = 1;</code>
     */
    protected $hooks_context_id = '';
    /**
     * Generated from protobuf field <code>string barrier_id = 2;</code>
     */
    protected $barrier_id = '';
    /**
     * Generated from protobuf field <code>optional string wait_id = 3;</code>
     */
    protected $wait_id = null;

    /**
     * Constructor.
     *
     * @param array $data {
     *     Optional. Data for populating the Message object.
     *
     *     @type string $hooks_context_id
     *     @type string $barrier_id
     *     @type string $wait_id
     * }
     */
    public function __construct($data = NULL) {
        \GPBMetadata\Couchbase\Internal\HooksV1::initOnce();
        parent::__construct($data);
    }

    /**
     * Generated from protobuf field <code>string hooks_context_id = 1;</code>
     * @return string
     */
    public function getHooksContextId()
    {
        return $this->hooks_context_id;
    }

    /**
     * Generated from protobuf field <code>string hooks_context_id = 1;</code>
     * @param string $var
     * @return $this
     */
    public function setHooksContextId($var)
    {
        GPBUtil::checkString($var, True);
        $this->hooks_context_id = $var;

        return $this;
    }

    /**
     * Generated from protobuf field <code>string barrier_id = 2;</code>
     * @return string
     */
    public function getBarrierId()
    {
        return $this->barrier_id;
    }

    /**
     * Generated from protobuf field <code>string barrier_id = 2;</code>
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
     * Generated from protobuf field <code>optional string wait_id = 3;</code>
     * @return string
     */
    public function getWaitId()
    {
        return isset($this->wait_id) ? $this->wait_id : '';
    }

    public function hasWaitId()
    {
        return isset($this->wait_id);
    }

    public function clearWaitId()
    {
        unset($this->wait_id);
    }

    /**
     * Generated from protobuf field <code>optional string wait_id = 3;</code>
     * @param string $var
     * @return $this
     */
    public function setWaitId($var)
    {
        GPBUtil::checkString($var, True);
        $this->wait_id = $var;

        return $this;
    }

}

