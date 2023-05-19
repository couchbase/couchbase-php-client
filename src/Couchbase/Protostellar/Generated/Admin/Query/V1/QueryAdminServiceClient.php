<?php
// GENERATED CODE -- DO NOT EDIT!

namespace Couchbase\Protostellar\Generated\Admin\Query\V1;

/**
 */
class QueryAdminServiceClient extends \Grpc\BaseStub {

    /**
     * @param string $hostname hostname
     * @param array $opts channel options
     * @param \Grpc\Channel $channel (optional) re-use channel object
     */
    public function __construct($hostname, $opts, $channel = null) {
        parent::__construct($hostname, $opts, $channel);
    }

    /**
     * @param \Couchbase\Protostellar\Generated\Admin\Query\V1\GetAllIndexesRequest $argument input argument
     * @param array $metadata metadata
     * @param array $options call options
     * @return \Grpc\UnaryCall
     */
    public function GetAllIndexes(\Couchbase\Protostellar\Generated\Admin\Query\V1\GetAllIndexesRequest $argument,
      $metadata = [], $options = []) {
        return $this->_simpleRequest('/couchbase.admin.query.v1.QueryAdminService/GetAllIndexes',
        $argument,
        ['\Couchbase\Protostellar\Generated\Admin\Query\V1\GetAllIndexesResponse', 'decode'],
        $metadata, $options);
    }

    /**
     * @param \Couchbase\Protostellar\Generated\Admin\Query\V1\CreatePrimaryIndexRequest $argument input argument
     * @param array $metadata metadata
     * @param array $options call options
     * @return \Grpc\UnaryCall
     */
    public function CreatePrimaryIndex(\Couchbase\Protostellar\Generated\Admin\Query\V1\CreatePrimaryIndexRequest $argument,
      $metadata = [], $options = []) {
        return $this->_simpleRequest('/couchbase.admin.query.v1.QueryAdminService/CreatePrimaryIndex',
        $argument,
        ['\Couchbase\Protostellar\Generated\Admin\Query\V1\CreatePrimaryIndexResponse', 'decode'],
        $metadata, $options);
    }

    /**
     * @param \Couchbase\Protostellar\Generated\Admin\Query\V1\CreateIndexRequest $argument input argument
     * @param array $metadata metadata
     * @param array $options call options
     * @return \Grpc\UnaryCall
     */
    public function CreateIndex(\Couchbase\Protostellar\Generated\Admin\Query\V1\CreateIndexRequest $argument,
      $metadata = [], $options = []) {
        return $this->_simpleRequest('/couchbase.admin.query.v1.QueryAdminService/CreateIndex',
        $argument,
        ['\Couchbase\Protostellar\Generated\Admin\Query\V1\CreateIndexResponse', 'decode'],
        $metadata, $options);
    }

    /**
     * @param \Couchbase\Protostellar\Generated\Admin\Query\V1\DropPrimaryIndexRequest $argument input argument
     * @param array $metadata metadata
     * @param array $options call options
     * @return \Grpc\UnaryCall
     */
    public function DropPrimaryIndex(\Couchbase\Protostellar\Generated\Admin\Query\V1\DropPrimaryIndexRequest $argument,
      $metadata = [], $options = []) {
        return $this->_simpleRequest('/couchbase.admin.query.v1.QueryAdminService/DropPrimaryIndex',
        $argument,
        ['\Couchbase\Protostellar\Generated\Admin\Query\V1\DropPrimaryIndexResponse', 'decode'],
        $metadata, $options);
    }

    /**
     * @param \Couchbase\Protostellar\Generated\Admin\Query\V1\DropIndexRequest $argument input argument
     * @param array $metadata metadata
     * @param array $options call options
     * @return \Grpc\UnaryCall
     */
    public function DropIndex(\Couchbase\Protostellar\Generated\Admin\Query\V1\DropIndexRequest $argument,
      $metadata = [], $options = []) {
        return $this->_simpleRequest('/couchbase.admin.query.v1.QueryAdminService/DropIndex',
        $argument,
        ['\Couchbase\Protostellar\Generated\Admin\Query\V1\DropIndexResponse', 'decode'],
        $metadata, $options);
    }

    /**
     * @param \Couchbase\Protostellar\Generated\Admin\Query\V1\BuildDeferredIndexesRequest $argument input argument
     * @param array $metadata metadata
     * @param array $options call options
     * @return \Grpc\UnaryCall
     */
    public function BuildDeferredIndexes(\Couchbase\Protostellar\Generated\Admin\Query\V1\BuildDeferredIndexesRequest $argument,
      $metadata = [], $options = []) {
        return $this->_simpleRequest('/couchbase.admin.query.v1.QueryAdminService/BuildDeferredIndexes',
        $argument,
        ['\Couchbase\Protostellar\Generated\Admin\Query\V1\BuildDeferredIndexesResponse', 'decode'],
        $metadata, $options);
    }

}
