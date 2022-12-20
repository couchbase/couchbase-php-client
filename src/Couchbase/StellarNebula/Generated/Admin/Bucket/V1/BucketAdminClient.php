<?php
// GENERATED CODE -- DO NOT EDIT!

namespace Couchbase\StellarNebula\Generated\Admin\Bucket\V1;

/**
 */
class BucketAdminClient extends \Grpc\BaseStub {

    /**
     * @param string $hostname hostname
     * @param array $opts channel options
     * @param \Grpc\Channel $channel (optional) re-use channel object
     */
    public function __construct($hostname, $opts, $channel = null) {
        parent::__construct($hostname, $opts, $channel);
    }

    /**
     * @param \Couchbase\StellarNebula\Generated\Admin\Bucket\V1\ListBucketsRequest $argument input argument
     * @param array $metadata metadata
     * @param array $options call options
     * @return \Grpc\UnaryCall
     */
    public function ListBuckets(\Couchbase\StellarNebula\Generated\Admin\Bucket\V1\ListBucketsRequest $argument,
      $metadata = [], $options = []) {
        return $this->_simpleRequest('/couchbase.admin.bucket.v1.BucketAdmin/ListBuckets',
        $argument,
        ['\Couchbase\StellarNebula\Generated\Admin\Bucket\V1\ListBucketsResponse', 'decode'],
        $metadata, $options);
    }

    /**
     * @param \Couchbase\StellarNebula\Generated\Admin\Bucket\V1\CreateBucketRequest $argument input argument
     * @param array $metadata metadata
     * @param array $options call options
     * @return \Grpc\UnaryCall
     */
    public function CreateBucket(\Couchbase\StellarNebula\Generated\Admin\Bucket\V1\CreateBucketRequest $argument,
      $metadata = [], $options = []) {
        return $this->_simpleRequest('/couchbase.admin.bucket.v1.BucketAdmin/CreateBucket',
        $argument,
        ['\Couchbase\StellarNebula\Generated\Admin\Bucket\V1\CreateBucketResponse', 'decode'],
        $metadata, $options);
    }

    /**
     * @param \Couchbase\StellarNebula\Generated\Admin\Bucket\V1\UpdateBucketRequest $argument input argument
     * @param array $metadata metadata
     * @param array $options call options
     * @return \Grpc\UnaryCall
     */
    public function UpdateBucket(\Couchbase\StellarNebula\Generated\Admin\Bucket\V1\UpdateBucketRequest $argument,
      $metadata = [], $options = []) {
        return $this->_simpleRequest('/couchbase.admin.bucket.v1.BucketAdmin/UpdateBucket',
        $argument,
        ['\Couchbase\StellarNebula\Generated\Admin\Bucket\V1\UpdateBucketResponse', 'decode'],
        $metadata, $options);
    }

    /**
     * @param \Couchbase\StellarNebula\Generated\Admin\Bucket\V1\DeleteBucketRequest $argument input argument
     * @param array $metadata metadata
     * @param array $options call options
     * @return \Grpc\UnaryCall
     */
    public function DeleteBucket(\Couchbase\StellarNebula\Generated\Admin\Bucket\V1\DeleteBucketRequest $argument,
      $metadata = [], $options = []) {
        return $this->_simpleRequest('/couchbase.admin.bucket.v1.BucketAdmin/DeleteBucket',
        $argument,
        ['\Couchbase\StellarNebula\Generated\Admin\Bucket\V1\DeleteBucketResponse', 'decode'],
        $metadata, $options);
    }

}
