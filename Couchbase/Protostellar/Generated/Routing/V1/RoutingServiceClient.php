<?php
// GENERATED CODE -- DO NOT EDIT!

namespace Couchbase\Protostellar\Generated\Routing\V1;

/**
 */
class RoutingServiceClient extends \Grpc\BaseStub {

    /**
     * @param string $hostname hostname
     * @param array $opts channel options
     * @param \Grpc\Channel $channel (optional) re-use channel object
     */
    public function __construct($hostname, $opts, $channel = null) {
        parent::__construct($hostname, $opts, $channel);
    }

    /**
     * @param \Couchbase\Protostellar\Generated\Routing\V1\WatchRoutingRequest $argument input argument
     * @param array $metadata metadata
     * @param array $options call options
     * @return \Grpc\ServerStreamingCall
     */
    public function WatchRouting(\Couchbase\Protostellar\Generated\Routing\V1\WatchRoutingRequest $argument,
      $metadata = [], $options = []) {
        return $this->_serverStreamRequest('/couchbase.routing.v1.RoutingService/WatchRouting',
        $argument,
        ['\Couchbase\Protostellar\Generated\Routing\V1\WatchRoutingResponse', 'decode'],
        $metadata, $options);
    }

}
