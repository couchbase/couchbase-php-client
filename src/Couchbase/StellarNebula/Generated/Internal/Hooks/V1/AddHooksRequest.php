<?php
# Generated by the protocol buffer compiler.  DO NOT EDIT!
# source: couchbase/internal/hooks.v1.proto

namespace Couchbase\StellarNebula\Generated\Internal\Hooks\V1;

use Google\Protobuf\Internal\GPBType;
use Google\Protobuf\Internal\RepeatedField;
use Google\Protobuf\Internal\GPBUtil;

/**
 * Generated from protobuf message <code>couchbase.internal.hooks.v1.AddHooksRequest</code>
 */
class AddHooksRequest extends \Google\Protobuf\Internal\Message
{
    /**
     * Generated from protobuf field <code>string hooks_context_id = 1;</code>
     */
    protected $hooks_context_id = '';
    /**
     * Generated from protobuf field <code>repeated .couchbase.internal.hooks.v1.Hook hooks = 2;</code>
     */
    private $hooks;

    /**
     * Constructor.
     *
     * @param array $data {
     *     Optional. Data for populating the Message object.
     *
     *     @type string $hooks_context_id
     *     @type \Couchbase\StellarNebula\Generated\Internal\Hooks\V1\Hook[]|\Google\Protobuf\Internal\RepeatedField $hooks
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
     * Generated from protobuf field <code>repeated .couchbase.internal.hooks.v1.Hook hooks = 2;</code>
     * @return \Google\Protobuf\Internal\RepeatedField
     */
    public function getHooks()
    {
        return $this->hooks;
    }

    /**
     * Generated from protobuf field <code>repeated .couchbase.internal.hooks.v1.Hook hooks = 2;</code>
     * @param \Couchbase\StellarNebula\Generated\Internal\Hooks\V1\Hook[]|\Google\Protobuf\Internal\RepeatedField $var
     * @return $this
     */
    public function setHooks($var)
    {
        $arr = GPBUtil::checkRepeatedField($var, \Google\Protobuf\Internal\GPBType::MESSAGE, \Couchbase\StellarNebula\Generated\Internal\Hooks\V1\Hook::class);
        $this->hooks = $arr;

        return $this;
    }

}

