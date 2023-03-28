<?php
// GENERATED CODE -- DO NOT EDIT!

namespace Couchbase\Protostellar\Generated\Admin\Collection\V1;

/**
 */
class CollectionAdminServiceClient extends \Grpc\BaseStub {

    /**
     * @param string $hostname hostname
     * @param array $opts channel options
     * @param \Grpc\Channel $channel (optional) re-use channel object
     */
    public function __construct($hostname, $opts, $channel = null) {
        parent::__construct($hostname, $opts, $channel);
    }

    /**
     * @param \Couchbase\Protostellar\Generated\Admin\Collection\V1\ListCollectionsRequest $argument input argument
     * @param array $metadata metadata
     * @param array $options call options
     * @return \Grpc\UnaryCall
     */
    public function ListCollections(\Couchbase\Protostellar\Generated\Admin\Collection\V1\ListCollectionsRequest $argument,
      $metadata = [], $options = []) {
        return $this->_simpleRequest('/couchbase.admin.collection.v1.CollectionAdminService/ListCollections',
        $argument,
        ['\Couchbase\Protostellar\Generated\Admin\Collection\V1\ListCollectionsResponse', 'decode'],
        $metadata, $options);
    }

    /**
     * @param \Couchbase\Protostellar\Generated\Admin\Collection\V1\CreateScopeRequest $argument input argument
     * @param array $metadata metadata
     * @param array $options call options
     * @return \Grpc\UnaryCall
     */
    public function CreateScope(\Couchbase\Protostellar\Generated\Admin\Collection\V1\CreateScopeRequest $argument,
      $metadata = [], $options = []) {
        return $this->_simpleRequest('/couchbase.admin.collection.v1.CollectionAdminService/CreateScope',
        $argument,
        ['\Couchbase\Protostellar\Generated\Admin\Collection\V1\CreateScopeResponse', 'decode'],
        $metadata, $options);
    }

    /**
     * @param \Couchbase\Protostellar\Generated\Admin\Collection\V1\DeleteScopeRequest $argument input argument
     * @param array $metadata metadata
     * @param array $options call options
     * @return \Grpc\UnaryCall
     */
    public function DeleteScope(\Couchbase\Protostellar\Generated\Admin\Collection\V1\DeleteScopeRequest $argument,
      $metadata = [], $options = []) {
        return $this->_simpleRequest('/couchbase.admin.collection.v1.CollectionAdminService/DeleteScope',
        $argument,
        ['\Couchbase\Protostellar\Generated\Admin\Collection\V1\DeleteScopeResponse', 'decode'],
        $metadata, $options);
    }

    /**
     * @param \Couchbase\Protostellar\Generated\Admin\Collection\V1\CreateCollectionRequest $argument input argument
     * @param array $metadata metadata
     * @param array $options call options
     * @return \Grpc\UnaryCall
     */
    public function CreateCollection(\Couchbase\Protostellar\Generated\Admin\Collection\V1\CreateCollectionRequest $argument,
      $metadata = [], $options = []) {
        return $this->_simpleRequest('/couchbase.admin.collection.v1.CollectionAdminService/CreateCollection',
        $argument,
        ['\Couchbase\Protostellar\Generated\Admin\Collection\V1\CreateCollectionResponse', 'decode'],
        $metadata, $options);
    }

    /**
     * @param \Couchbase\Protostellar\Generated\Admin\Collection\V1\DeleteCollectionRequest $argument input argument
     * @param array $metadata metadata
     * @param array $options call options
     * @return \Grpc\UnaryCall
     */
    public function DeleteCollection(\Couchbase\Protostellar\Generated\Admin\Collection\V1\DeleteCollectionRequest $argument,
      $metadata = [], $options = []) {
        return $this->_simpleRequest('/couchbase.admin.collection.v1.CollectionAdminService/DeleteCollection',
        $argument,
        ['\Couchbase\Protostellar\Generated\Admin\Collection\V1\DeleteCollectionResponse', 'decode'],
        $metadata, $options);
    }

}
