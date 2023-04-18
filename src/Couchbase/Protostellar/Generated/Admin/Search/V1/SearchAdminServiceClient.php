<?php
// GENERATED CODE -- DO NOT EDIT!

namespace Couchbase\Protostellar\Generated\Admin\Search\V1;

/**
 */
class SearchAdminServiceClient extends \Grpc\BaseStub {

    /**
     * @param string $hostname hostname
     * @param array $opts channel options
     * @param \Grpc\Channel $channel (optional) re-use channel object
     */
    public function __construct($hostname, $opts, $channel = null) {
        parent::__construct($hostname, $opts, $channel);
    }

    /**
     * @param \Couchbase\Protostellar\Generated\Admin\Search\V1\GetIndexRequest $argument input argument
     * @param array $metadata metadata
     * @param array $options call options
     * @return \Grpc\UnaryCall
     */
    public function GetIndex(\Couchbase\Protostellar\Generated\Admin\Search\V1\GetIndexRequest $argument,
      $metadata = [], $options = []) {
        return $this->_simpleRequest('/couchbase.admin.search.v1.SearchAdminService/GetIndex',
        $argument,
        ['\Couchbase\Protostellar\Generated\Admin\Search\V1\GetIndexResponse', 'decode'],
        $metadata, $options);
    }

    /**
     * @param \Couchbase\Protostellar\Generated\Admin\Search\V1\ListIndexesRequest $argument input argument
     * @param array $metadata metadata
     * @param array $options call options
     * @return \Grpc\UnaryCall
     */
    public function ListIndexes(\Couchbase\Protostellar\Generated\Admin\Search\V1\ListIndexesRequest $argument,
      $metadata = [], $options = []) {
        return $this->_simpleRequest('/couchbase.admin.search.v1.SearchAdminService/ListIndexes',
        $argument,
        ['\Couchbase\Protostellar\Generated\Admin\Search\V1\ListIndexesResponse', 'decode'],
        $metadata, $options);
    }

    /**
     * @param \Couchbase\Protostellar\Generated\Admin\Search\V1\UpsertIndexRequest $argument input argument
     * @param array $metadata metadata
     * @param array $options call options
     * @return \Grpc\UnaryCall
     */
    public function UpsertIndex(\Couchbase\Protostellar\Generated\Admin\Search\V1\UpsertIndexRequest $argument,
      $metadata = [], $options = []) {
        return $this->_simpleRequest('/couchbase.admin.search.v1.SearchAdminService/UpsertIndex',
        $argument,
        ['\Couchbase\Protostellar\Generated\Admin\Search\V1\UpsertIndexResponse', 'decode'],
        $metadata, $options);
    }

    /**
     * @param \Couchbase\Protostellar\Generated\Admin\Search\V1\DeleteIndexRequest $argument input argument
     * @param array $metadata metadata
     * @param array $options call options
     * @return \Grpc\UnaryCall
     */
    public function DeleteIndex(\Couchbase\Protostellar\Generated\Admin\Search\V1\DeleteIndexRequest $argument,
      $metadata = [], $options = []) {
        return $this->_simpleRequest('/couchbase.admin.search.v1.SearchAdminService/DeleteIndex',
        $argument,
        ['\Couchbase\Protostellar\Generated\Admin\Search\V1\DeleteIndexResponse', 'decode'],
        $metadata, $options);
    }

}
