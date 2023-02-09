<?php
# Generated by the protocol buffer compiler.  DO NOT EDIT!
# source: couchbase/kv.v1.proto

namespace Couchbase\Protostellar\Generated\KV\V1\LookupInResponse;

use Google\Protobuf\Internal\GPBType;
use Google\Protobuf\Internal\RepeatedField;
use Google\Protobuf\Internal\GPBUtil;

/**
 * Generated from protobuf message <code>couchbase.kv.v1.LookupInResponse.Spec</code>
 */
class Spec extends \Google\Protobuf\Internal\Message
{
    /**
     * Generated from protobuf field <code>.google.rpc.Status status = 1;</code>
     */
    protected $status = null;
    /**
     * Generated from protobuf field <code>bytes content = 2;</code>
     */
    protected $content = '';

    /**
     * Constructor.
     *
     * @param array $data {
     *     Optional. Data for populating the Message object.
     *
     *     @type \Google\Rpc\Status $status
     *     @type string $content
     * }
     */
    public function __construct($data = NULL) {
        \GPBMetadata\Couchbase\KvV1::initOnce();
        parent::__construct($data);
    }

    /**
     * Generated from protobuf field <code>.google.rpc.Status status = 1;</code>
     * @return \Google\Rpc\Status|null
     */
    public function getStatus()
    {
        return $this->status;
    }

    public function hasStatus()
    {
        return isset($this->status);
    }

    public function clearStatus()
    {
        unset($this->status);
    }

    /**
     * Generated from protobuf field <code>.google.rpc.Status status = 1;</code>
     * @param \Google\Rpc\Status $var
     * @return $this
     */
    public function setStatus($var)
    {
        GPBUtil::checkMessage($var, \Google\Rpc\Status::class);
        $this->status = $var;

        return $this;
    }

    /**
     * Generated from protobuf field <code>bytes content = 2;</code>
     * @return string
     */
    public function getContent()
    {
        return $this->content;
    }

    /**
     * Generated from protobuf field <code>bytes content = 2;</code>
     * @param string $var
     * @return $this
     */
    public function setContent($var)
    {
        GPBUtil::checkString($var, False);
        $this->content = $var;

        return $this;
    }

}

// Adding a class alias for backwards compatibility with the previous class name.
class_alias(Spec::class, \Couchbase\Protostellar\Generated\KV\V1\LookupInResponse_Spec::class);

