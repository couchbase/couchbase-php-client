<?php
# Generated by the protocol buffer compiler.  DO NOT EDIT!
# source: couchbase/kv/v1/kv.proto

namespace Couchbase\Protostellar\Generated\KV\V1\MutateInResponse;

use Google\Protobuf\Internal\GPBType;
use Google\Protobuf\Internal\RepeatedField;
use Google\Protobuf\Internal\GPBUtil;

/**
 * Generated from protobuf message <code>couchbase.kv.v1.MutateInResponse.Spec</code>
 */
class Spec extends \Google\Protobuf\Internal\Message
{
    /**
     * Generated from protobuf field <code>optional bytes content = 1;</code>
     */
    protected $content = null;

    /**
     * Constructor.
     *
     * @param array $data {
     *     Optional. Data for populating the Message object.
     *
     *     @type string $content
     * }
     */
    public function __construct($data = NULL) {
        \GPBMetadata\Couchbase\Kv\V1\Kv::initOnce();
        parent::__construct($data);
    }

    /**
     * Generated from protobuf field <code>optional bytes content = 1;</code>
     * @return string
     */
    public function getContent()
    {
        return isset($this->content) ? $this->content : '';
    }

    public function hasContent()
    {
        return isset($this->content);
    }

    public function clearContent()
    {
        unset($this->content);
    }

    /**
     * Generated from protobuf field <code>optional bytes content = 1;</code>
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
class_alias(Spec::class, \Couchbase\Protostellar\Generated\KV\V1\MutateInResponse_Spec::class);

