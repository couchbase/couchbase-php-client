<?php
# Generated by the protocol buffer compiler.  DO NOT EDIT!
# source: couchbase/routing/v1/routing.proto

namespace Couchbase\Protostellar\Generated\Routing\V1;

use Google\Protobuf\Internal\GPBType;
use Google\Protobuf\Internal\RepeatedField;
use Google\Protobuf\Internal\GPBUtil;

/**
 * Generated from protobuf message <code>couchbase.routing.v1.WatchRoutingRequest</code>
 */
class WatchRoutingRequest extends \Google\Protobuf\Internal\Message
{
    /**
     * Specifies the specific bucket that will be accessed.
     *
     * Generated from protobuf field <code>optional string bucket_name = 1;</code>
     */
    protected $bucket_name = null;

    /**
     * Constructor.
     *
     * @param array $data {
     *     Optional. Data for populating the Message object.
     *
     *     @type string $bucket_name
     *           Specifies the specific bucket that will be accessed.
     * }
     */
    public function __construct($data = NULL) {
        \GPBMetadata\Couchbase\Routing\V1\Routing::initOnce();
        parent::__construct($data);
    }

    /**
     * Specifies the specific bucket that will be accessed.
     *
     * Generated from protobuf field <code>optional string bucket_name = 1;</code>
     * @return string
     */
    public function getBucketName()
    {
        return isset($this->bucket_name) ? $this->bucket_name : '';
    }

    public function hasBucketName()
    {
        return isset($this->bucket_name);
    }

    public function clearBucketName()
    {
        unset($this->bucket_name);
    }

    /**
     * Specifies the specific bucket that will be accessed.
     *
     * Generated from protobuf field <code>optional string bucket_name = 1;</code>
     * @param string $var
     * @return $this
     */
    public function setBucketName($var)
    {
        GPBUtil::checkString($var, True);
        $this->bucket_name = $var;

        return $this;
    }

}

