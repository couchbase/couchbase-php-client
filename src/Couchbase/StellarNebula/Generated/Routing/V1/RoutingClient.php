<?php

// GENERATED CODE -- DO NOT EDIT!

namespace Couchbase\StellarNebula\Generated\Routing\V1;

/**
 */
class RoutingClient extends \Grpc\BaseStub
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
     * @param \Couchbase\StellarNebula\Generated\Routing\V1\WatchRoutingRequest $argument input argument
     * @param array $metadata metadata
     * @param array $options call options
     * @return \Grpc\ServerStreamingCall
     */
    public function WatchRouting(
        \Couchbase\StellarNebula\Generated\Routing\V1\WatchRoutingRequest $argument,
        $metadata = [],
        $options = []
    )
    {
        return $this->_serverStreamRequest(
            '/couchbase.routing.v1.Routing/WatchRouting',
            $argument,
            ['\Couchbase\StellarNebula\Generated\Routing\V1\WatchRoutingResponse', 'decode'],
            $metadata,
            $options
        );
    }
}
