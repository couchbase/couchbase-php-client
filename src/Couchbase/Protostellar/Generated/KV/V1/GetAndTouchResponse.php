<?php
# Generated by the protocol buffer compiler.  DO NOT EDIT!
# source: couchbase/kv/v1/kv.proto

namespace Couchbase\Protostellar\Generated\KV\V1;

use Google\Protobuf\Internal\GPBType;
use Google\Protobuf\Internal\RepeatedField;
use Google\Protobuf\Internal\GPBUtil;

/**
 * Generated from protobuf message <code>couchbase.kv.v1.GetAndTouchResponse</code>
 */
class GetAndTouchResponse extends \Google\Protobuf\Internal\Message
{
    /**
     * Generated from protobuf field <code>bytes content = 1;</code>
     */
    protected $content = '';
    /**
     * Generated from protobuf field <code>.couchbase.kv.v1.DocumentContentType content_type = 2;</code>
     */
    protected $content_type = 0;
    /**
     * Generated from protobuf field <code>.couchbase.kv.v1.DocumentCompressionType compression_type = 5;</code>
     */
    protected $compression_type = 0;
    /**
     * Generated from protobuf field <code>uint64 cas = 3;</code>
     */
    protected $cas = 0;
    /**
     * Generated from protobuf field <code>.google.protobuf.Timestamp expiry = 4;</code>
     */
    protected $expiry = null;

    /**
     * Constructor.
     *
     * @param array $data {
     *     Optional. Data for populating the Message object.
     *
     *     @type string $content
     *     @type int $content_type
     *     @type int $compression_type
     *     @type int|string $cas
     *     @type \Google\Protobuf\Timestamp $expiry
     * }
     */
    public function __construct($data = NULL) {
        \GPBMetadata\Couchbase\Kv\V1\Kv::initOnce();
        parent::__construct($data);
    }

    /**
     * Generated from protobuf field <code>bytes content = 1;</code>
     * @return string
     */
    public function getContent()
    {
        return $this->content;
    }

    /**
     * Generated from protobuf field <code>bytes content = 1;</code>
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
     * Generated from protobuf field <code>.couchbase.kv.v1.DocumentContentType content_type = 2;</code>
     * @return int
     */
    public function getContentType()
    {
        return $this->content_type;
    }

    /**
     * Generated from protobuf field <code>.couchbase.kv.v1.DocumentContentType content_type = 2;</code>
     * @param int $var
     * @return $this
     */
    public function setContentType($var)
    {
        GPBUtil::checkEnum($var, \Couchbase\Protostellar\Generated\KV\V1\DocumentContentType::class);
        $this->content_type = $var;

        return $this;
    }

    /**
     * Generated from protobuf field <code>.couchbase.kv.v1.DocumentCompressionType compression_type = 5;</code>
     * @return int
     */
    public function getCompressionType()
    {
        return $this->compression_type;
    }

    /**
     * Generated from protobuf field <code>.couchbase.kv.v1.DocumentCompressionType compression_type = 5;</code>
     * @param int $var
     * @return $this
     */
    public function setCompressionType($var)
    {
        GPBUtil::checkEnum($var, \Couchbase\Protostellar\Generated\KV\V1\DocumentCompressionType::class);
        $this->compression_type = $var;

        return $this;
    }

    /**
     * Generated from protobuf field <code>uint64 cas = 3;</code>
     * @return int|string
     */
    public function getCas()
    {
        return $this->cas;
    }

    /**
     * Generated from protobuf field <code>uint64 cas = 3;</code>
     * @param int|string $var
     * @return $this
     */
    public function setCas($var)
    {
        GPBUtil::checkUint64($var);
        $this->cas = $var;

        return $this;
    }

    /**
     * Generated from protobuf field <code>.google.protobuf.Timestamp expiry = 4;</code>
     * @return \Google\Protobuf\Timestamp|null
     */
    public function getExpiry()
    {
        return $this->expiry;
    }

    public function hasExpiry()
    {
        return isset($this->expiry);
    }

    public function clearExpiry()
    {
        unset($this->expiry);
    }

    /**
     * Generated from protobuf field <code>.google.protobuf.Timestamp expiry = 4;</code>
     * @param \Google\Protobuf\Timestamp $var
     * @return $this
     */
    public function setExpiry($var)
    {
        GPBUtil::checkMessage($var, \Google\Protobuf\Timestamp::class);
        $this->expiry = $var;

        return $this;
    }

}

