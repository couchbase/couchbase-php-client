<?php
# Generated by the protocol buffer compiler.  DO NOT EDIT!
# source: couchbase/admin/collection/v1/collection.proto

namespace Couchbase\Protostellar\Generated\Admin\Collection\V1;

use Google\Protobuf\Internal\GPBType;
use Google\Protobuf\Internal\RepeatedField;
use Google\Protobuf\Internal\GPBUtil;

/**
 * Generated from protobuf message <code>couchbase.admin.collection.v1.CreateCollectionRequest</code>
 */
class CreateCollectionRequest extends \Google\Protobuf\Internal\Message
{
    /**
     * Generated from protobuf field <code>string bucket_name = 1;</code>
     */
    protected $bucket_name = '';
    /**
     * Generated from protobuf field <code>string scope_name = 2;</code>
     */
    protected $scope_name = '';
    /**
     * Generated from protobuf field <code>string collection_name = 3;</code>
     */
    protected $collection_name = '';
    /**
     * Generated from protobuf field <code>optional uint32 max_expiry_secs = 4;</code>
     */
    protected $max_expiry_secs = null;

    /**
     * Constructor.
     *
     * @param array $data {
     *     Optional. Data for populating the Message object.
     *
     *     @type string $bucket_name
     *     @type string $scope_name
     *     @type string $collection_name
     *     @type int $max_expiry_secs
     * }
     */
    public function __construct($data = NULL) {
        \GPBMetadata\Couchbase\Admin\Collection\V1\Collection::initOnce();
        parent::__construct($data);
    }

    /**
     * Generated from protobuf field <code>string bucket_name = 1;</code>
     * @return string
     */
    public function getBucketName()
    {
        return $this->bucket_name;
    }

    /**
     * Generated from protobuf field <code>string bucket_name = 1;</code>
     * @param string $var
     * @return $this
     */
    public function setBucketName($var)
    {
        GPBUtil::checkString($var, True);
        $this->bucket_name = $var;

        return $this;
    }

    /**
     * Generated from protobuf field <code>string scope_name = 2;</code>
     * @return string
     */
    public function getScopeName()
    {
        return $this->scope_name;
    }

    /**
     * Generated from protobuf field <code>string scope_name = 2;</code>
     * @param string $var
     * @return $this
     */
    public function setScopeName($var)
    {
        GPBUtil::checkString($var, True);
        $this->scope_name = $var;

        return $this;
    }

    /**
     * Generated from protobuf field <code>string collection_name = 3;</code>
     * @return string
     */
    public function getCollectionName()
    {
        return $this->collection_name;
    }

    /**
     * Generated from protobuf field <code>string collection_name = 3;</code>
     * @param string $var
     * @return $this
     */
    public function setCollectionName($var)
    {
        GPBUtil::checkString($var, True);
        $this->collection_name = $var;

        return $this;
    }

    /**
     * Generated from protobuf field <code>optional uint32 max_expiry_secs = 4;</code>
     * @return int
     */
    public function getMaxExpirySecs()
    {
        return isset($this->max_expiry_secs) ? $this->max_expiry_secs : 0;
    }

    public function hasMaxExpirySecs()
    {
        return isset($this->max_expiry_secs);
    }

    public function clearMaxExpirySecs()
    {
        unset($this->max_expiry_secs);
    }

    /**
     * Generated from protobuf field <code>optional uint32 max_expiry_secs = 4;</code>
     * @param int $var
     * @return $this
     */
    public function setMaxExpirySecs($var)
    {
        GPBUtil::checkUint32($var);
        $this->max_expiry_secs = $var;

        return $this;
    }

}

