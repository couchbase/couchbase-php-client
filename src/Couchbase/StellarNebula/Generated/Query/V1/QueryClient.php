<?php
// GENERATED CODE -- DO NOT EDIT!

namespace Couchbase\StellarNebula\Generated\Query\V1;

/**
 */
class QueryClient extends \Grpc\BaseStub {

    /**
     * @param string $hostname hostname
     * @param array $opts channel options
     * @param \Grpc\Channel $channel (optional) re-use channel object
     */
    public function __construct($hostname, $opts, $channel = null) {
        parent::__construct($hostname, $opts, $channel);
    }

    /**
     * @param \Couchbase\StellarNebula\Generated\Query\V1\QueryRequest $argument input argument
     * @param array $metadata metadata
     * @param array $options call options
     * @return \Grpc\ServerStreamingCall
     */
    public function Query(\Couchbase\StellarNebula\Generated\Query\V1\QueryRequest $argument,
      $metadata = [], $options = []) {
        return $this->_serverStreamRequest('/couchbase.query.v1.Query/Query',
        $argument,
        ['\Couchbase\StellarNebula\Generated\Query\V1\QueryResponse', 'decode'],
        $metadata, $options);
    }

}
