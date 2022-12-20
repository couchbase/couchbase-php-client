<?php
# Generated by the protocol buffer compiler.  DO NOT EDIT!
# source: couchbase/kv.v1.proto

namespace Couchbase\StellarNebula\Generated\KV\V1\MutateInRequest;

use Google\Protobuf\Internal\GPBType;
use Google\Protobuf\Internal\RepeatedField;
use Google\Protobuf\Internal\GPBUtil;

/**
 * Generated from protobuf message <code>couchbase.kv.v1.MutateInRequest.Spec</code>
 */
class Spec extends \Google\Protobuf\Internal\Message
{
    /**
     * Generated from protobuf field <code>.couchbase.kv.v1.MutateInRequest.Spec.Operation operation = 1;</code>
     */
    protected $operation = 0;
    /**
     * Generated from protobuf field <code>string path = 2;</code>
     */
    protected $path = '';
    /**
     * Generated from protobuf field <code>bytes content = 3;</code>
     */
    protected $content = '';
    /**
     * Generated from protobuf field <code>optional .couchbase.kv.v1.MutateInRequest.Spec.Flags flags = 4;</code>
     */
    protected $flags = null;

    /**
     * Constructor.
     *
     * @param array $data {
     *     Optional. Data for populating the Message object.
     *
     *     @type int $operation
     *     @type string $path
     *     @type string $content
     *     @type \Couchbase\StellarNebula\Generated\KV\V1\MutateInRequest\Spec\Flags $flags
     * }
     */
    public function __construct($data = NULL) {
        \GPBMetadata\Couchbase\KvV1::initOnce();
        parent::__construct($data);
    }

    /**
     * Generated from protobuf field <code>.couchbase.kv.v1.MutateInRequest.Spec.Operation operation = 1;</code>
     * @return int
     */
    public function getOperation()
    {
        return $this->operation;
    }

    /**
     * Generated from protobuf field <code>.couchbase.kv.v1.MutateInRequest.Spec.Operation operation = 1;</code>
     * @param int $var
     * @return $this
     */
    public function setOperation($var)
    {
        GPBUtil::checkEnum($var, \Couchbase\StellarNebula\Generated\KV\V1\MutateInRequest\Spec\Operation::class);
        $this->operation = $var;

        return $this;
    }

    /**
     * Generated from protobuf field <code>string path = 2;</code>
     * @return string
     */
    public function getPath()
    {
        return $this->path;
    }

    /**
     * Generated from protobuf field <code>string path = 2;</code>
     * @param string $var
     * @return $this
     */
    public function setPath($var)
    {
        GPBUtil::checkString($var, True);
        $this->path = $var;

        return $this;
    }

    /**
     * Generated from protobuf field <code>bytes content = 3;</code>
     * @return string
     */
    public function getContent()
    {
        return $this->content;
    }

    /**
     * Generated from protobuf field <code>bytes content = 3;</code>
     * @param string $var
     * @return $this
     */
    public function setContent($var)
    {
        GPBUtil::checkString($var, False);
        $this->content = $var;

        return $this;
    }

    /**
     * Generated from protobuf field <code>optional .couchbase.kv.v1.MutateInRequest.Spec.Flags flags = 4;</code>
     * @return \Couchbase\StellarNebula\Generated\KV\V1\MutateInRequest\Spec\Flags|null
     */
    public function getFlags()
    {
        return $this->flags;
    }

    public function hasFlags()
    {
        return isset($this->flags);
    }

    public function clearFlags()
    {
        unset($this->flags);
    }

    /**
     * Generated from protobuf field <code>optional .couchbase.kv.v1.MutateInRequest.Spec.Flags flags = 4;</code>
     * @param \Couchbase\StellarNebula\Generated\KV\V1\MutateInRequest\Spec\Flags $var
     * @return $this
     */
    public function setFlags($var)
    {
        GPBUtil::checkMessage($var, \Couchbase\StellarNebula\Generated\KV\V1\MutateInRequest\Spec\Flags::class);
        $this->flags = $var;

        return $this;
    }

}

// Adding a class alias for backwards compatibility with the previous class name.
class_alias(Spec::class, \Couchbase\StellarNebula\Generated\KV\V1\MutateInRequest_Spec::class);

