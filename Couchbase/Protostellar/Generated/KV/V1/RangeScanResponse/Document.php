<?php
# Generated by the protocol buffer compiler.  DO NOT EDIT!
# source: couchbase/kv/v1/kv.proto

namespace Couchbase\Protostellar\Generated\KV\V1\RangeScanResponse;

use Google\Protobuf\Internal\GPBType;
use Google\Protobuf\Internal\RepeatedField;
use Google\Protobuf\Internal\GPBUtil;

/**
 * Generated from protobuf message <code>couchbase.kv.v1.RangeScanResponse.Document</code>
 */
class Document extends \Google\Protobuf\Internal\Message
{
    /**
     * Generated from protobuf field <code>string key = 1;</code>
     */
    protected $key = '';
    /**
     * Generated from protobuf field <code>optional bytes content = 2;</code>
     */
    protected $content = null;
    /**
     * Generated from protobuf field <code>optional .couchbase.kv.v1.RangeScanResponse.Document.MetaData meta_data = 3;</code>
     */
    protected $meta_data = null;

    /**
     * Constructor.
     *
     * @param array $data {
     *     Optional. Data for populating the Message object.
     *
     *     @type string $key
     *     @type string $content
     *     @type \Couchbase\Protostellar\Generated\KV\V1\RangeScanResponse\Document\MetaData $meta_data
     * }
     */
    public function __construct($data = NULL) {
        \GPBMetadata\Couchbase\Kv\V1\Kv::initOnce();
        parent::__construct($data);
    }

    /**
     * Generated from protobuf field <code>string key = 1;</code>
     * @return string
     */
    public function getKey()
    {
        return $this->key;
    }

    /**
     * Generated from protobuf field <code>string key = 1;</code>
     * @param string $var
     * @return $this
     */
    public function setKey($var)
    {
        GPBUtil::checkString($var, True);
        $this->key = $var;

        return $this;
    }

    /**
     * Generated from protobuf field <code>optional bytes content = 2;</code>
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
     * Generated from protobuf field <code>optional bytes content = 2;</code>
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
     * Generated from protobuf field <code>optional .couchbase.kv.v1.RangeScanResponse.Document.MetaData meta_data = 3;</code>
     * @return \Couchbase\Protostellar\Generated\KV\V1\RangeScanResponse\Document\MetaData|null
     */
    public function getMetaData()
    {
        return $this->meta_data;
    }

    public function hasMetaData()
    {
        return isset($this->meta_data);
    }

    public function clearMetaData()
    {
        unset($this->meta_data);
    }

    /**
     * Generated from protobuf field <code>optional .couchbase.kv.v1.RangeScanResponse.Document.MetaData meta_data = 3;</code>
     * @param \Couchbase\Protostellar\Generated\KV\V1\RangeScanResponse\Document\MetaData $var
     * @return $this
     */
    public function setMetaData($var)
    {
        GPBUtil::checkMessage($var, \Couchbase\Protostellar\Generated\KV\V1\RangeScanResponse\Document\MetaData::class);
        $this->meta_data = $var;

        return $this;
    }

}

// Adding a class alias for backwards compatibility with the previous class name.
class_alias(Document::class, \Couchbase\Protostellar\Generated\KV\V1\RangeScanResponse_Document::class);

