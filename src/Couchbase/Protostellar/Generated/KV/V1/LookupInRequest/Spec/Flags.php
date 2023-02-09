<?php
# Generated by the protocol buffer compiler.  DO NOT EDIT!
# source: couchbase/kv.v1.proto

namespace Couchbase\Protostellar\Generated\KV\V1\LookupInRequest\Spec;

use Google\Protobuf\Internal\GPBType;
use Google\Protobuf\Internal\RepeatedField;
use Google\Protobuf\Internal\GPBUtil;

/**
 * Generated from protobuf message <code>couchbase.kv.v1.LookupInRequest.Spec.Flags</code>
 */
class Flags extends \Google\Protobuf\Internal\Message
{
    /**
     * Generated from protobuf field <code>optional bool xattr = 1;</code>
     */
    protected $xattr = null;

    /**
     * Constructor.
     *
     * @param array $data {
     *     Optional. Data for populating the Message object.
     *
     *     @type bool $xattr
     * }
     */
    public function __construct($data = NULL) {
        \GPBMetadata\Couchbase\KvV1::initOnce();
        parent::__construct($data);
    }

    /**
     * Generated from protobuf field <code>optional bool xattr = 1;</code>
     * @return bool
     */
    public function getXattr()
    {
        return isset($this->xattr) ? $this->xattr : false;
    }

    public function hasXattr()
    {
        return isset($this->xattr);
    }

    public function clearXattr()
    {
        unset($this->xattr);
    }

    /**
     * Generated from protobuf field <code>optional bool xattr = 1;</code>
     * @param bool $var
     * @return $this
     */
    public function setXattr($var)
    {
        GPBUtil::checkBool($var);
        $this->xattr = $var;

        return $this;
    }

}

// Adding a class alias for backwards compatibility with the previous class name.
class_alias(Flags::class, \Couchbase\Protostellar\Generated\KV\V1\LookupInRequest_Spec_Flags::class);

