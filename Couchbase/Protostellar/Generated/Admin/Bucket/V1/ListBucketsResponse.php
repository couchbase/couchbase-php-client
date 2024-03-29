<?php
# Generated by the protocol buffer compiler.  DO NOT EDIT!
# source: couchbase/admin/bucket/v1/bucket.proto

namespace Couchbase\Protostellar\Generated\Admin\Bucket\V1;

use Google\Protobuf\Internal\GPBType;
use Google\Protobuf\Internal\RepeatedField;
use Google\Protobuf\Internal\GPBUtil;

/**
 * Generated from protobuf message <code>couchbase.admin.bucket.v1.ListBucketsResponse</code>
 */
class ListBucketsResponse extends \Google\Protobuf\Internal\Message
{
    /**
     * Generated from protobuf field <code>repeated .couchbase.admin.bucket.v1.ListBucketsResponse.Bucket buckets = 1;</code>
     */
    private $buckets;

    /**
     * Constructor.
     *
     * @param array $data {
     *     Optional. Data for populating the Message object.
     *
     *     @type array<\Couchbase\Protostellar\Generated\Admin\Bucket\V1\ListBucketsResponse\Bucket>|\Google\Protobuf\Internal\RepeatedField $buckets
     * }
     */
    public function __construct($data = NULL) {
        \GPBMetadata\Couchbase\Admin\Bucket\V1\Bucket::initOnce();
        parent::__construct($data);
    }

    /**
     * Generated from protobuf field <code>repeated .couchbase.admin.bucket.v1.ListBucketsResponse.Bucket buckets = 1;</code>
     * @return \Google\Protobuf\Internal\RepeatedField
     */
    public function getBuckets()
    {
        return $this->buckets;
    }

    /**
     * Generated from protobuf field <code>repeated .couchbase.admin.bucket.v1.ListBucketsResponse.Bucket buckets = 1;</code>
     * @param array<\Couchbase\Protostellar\Generated\Admin\Bucket\V1\ListBucketsResponse\Bucket>|\Google\Protobuf\Internal\RepeatedField $var
     * @return $this
     */
    public function setBuckets($var)
    {
        $arr = GPBUtil::checkRepeatedField($var, \Google\Protobuf\Internal\GPBType::MESSAGE, \Couchbase\Protostellar\Generated\Admin\Bucket\V1\ListBucketsResponse\Bucket::class);
        $this->buckets = $arr;

        return $this;
    }

}

