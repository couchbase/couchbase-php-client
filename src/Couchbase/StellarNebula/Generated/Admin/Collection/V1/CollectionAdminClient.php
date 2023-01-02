<?php

// GENERATED CODE -- DO NOT EDIT!

namespace Couchbase\StellarNebula\Generated\Admin\Collection\V1;

/**
 */
class CollectionAdminClient extends \Grpc\BaseStub
{
    /**
     * @param string $hostname hostname
     * @param array $opts channel options
     * @param \Grpc\Channel $channel (optional) re-use channel object
     */
    public function __construct($hostname, $opts, $channel = null)
    {
        parent::__construct($hostname, $opts, $channel);
    }

    /**
     * @param \Couchbase\StellarNebula\Generated\Admin\Collection\V1\ListCollectionsRequest $argument input argument
     * @param array $metadata metadata
     * @param array $options call options
     * @return \Grpc\UnaryCall
     */
    public function ListCollections(
        \Couchbase\StellarNebula\Generated\Admin\Collection\V1\ListCollectionsRequest $argument,
        $metadata = [],
        $options = []
    )
    {
        return $this->_simpleRequest(
            '/couchbase.admin.collection.v1.CollectionAdmin/ListCollections',
            $argument,
            ['\Couchbase\StellarNebula\Generated\Admin\Collection\V1\ListCollectionsResponse', 'decode'],
            $metadata,
            $options
        );
    }

    /**
     * @param \Couchbase\StellarNebula\Generated\Admin\Collection\V1\CreateScopeRequest $argument input argument
     * @param array $metadata metadata
     * @param array $options call options
     * @return \Grpc\UnaryCall
     */
    public function CreateScope(
        \Couchbase\StellarNebula\Generated\Admin\Collection\V1\CreateScopeRequest $argument,
        $metadata = [],
        $options = []
    )
    {
        return $this->_simpleRequest(
            '/couchbase.admin.collection.v1.CollectionAdmin/CreateScope',
            $argument,
            ['\Couchbase\StellarNebula\Generated\Admin\Collection\V1\CreateScopeResponse', 'decode'],
            $metadata,
            $options
        );
    }

    /**
     * @param \Couchbase\StellarNebula\Generated\Admin\Collection\V1\DeleteScopeRequest $argument input argument
     * @param array $metadata metadata
     * @param array $options call options
     * @return \Grpc\UnaryCall
     */
    public function DeleteScope(
        \Couchbase\StellarNebula\Generated\Admin\Collection\V1\DeleteScopeRequest $argument,
        $metadata = [],
        $options = []
    )
    {
        return $this->_simpleRequest(
            '/couchbase.admin.collection.v1.CollectionAdmin/DeleteScope',
            $argument,
            ['\Couchbase\StellarNebula\Generated\Admin\Collection\V1\DeleteScopeResponse', 'decode'],
            $metadata,
            $options
        );
    }

    /**
     * @param \Couchbase\StellarNebula\Generated\Admin\Collection\V1\CreateCollectionRequest $argument input argument
     * @param array $metadata metadata
     * @param array $options call options
     * @return \Grpc\UnaryCall
     */
    public function CreateCollection(
        \Couchbase\StellarNebula\Generated\Admin\Collection\V1\CreateCollectionRequest $argument,
        $metadata = [],
        $options = []
    )
    {
        return $this->_simpleRequest(
            '/couchbase.admin.collection.v1.CollectionAdmin/CreateCollection',
            $argument,
            ['\Couchbase\StellarNebula\Generated\Admin\Collection\V1\CreateCollectionResponse', 'decode'],
            $metadata,
            $options
        );
    }

    /**
     * @param \Couchbase\StellarNebula\Generated\Admin\Collection\V1\DeleteCollectionRequest $argument input argument
     * @param array $metadata metadata
     * @param array $options call options
     * @return \Grpc\UnaryCall
     */
    public function DeleteCollection(
        \Couchbase\StellarNebula\Generated\Admin\Collection\V1\DeleteCollectionRequest $argument,
        $metadata = [],
        $options = []
    )
    {
        return $this->_simpleRequest(
            '/couchbase.admin.collection.v1.CollectionAdmin/DeleteCollection',
            $argument,
            ['\Couchbase\StellarNebula\Generated\Admin\Collection\V1\DeleteCollectionResponse', 'decode'],
            $metadata,
            $options
        );
    }
}
