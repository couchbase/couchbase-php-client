<?php
// GENERATED CODE -- DO NOT EDIT!

namespace Couchbase\StellarNebula\Generated\View\V1;

/**
 */
class ViewClient extends \Grpc\BaseStub {

    /**
     * @param string $hostname hostname
     * @param array $opts channel options
     * @param \Grpc\Channel $channel (optional) re-use channel object
     */
    public function __construct($hostname, $opts, $channel = null) {
        parent::__construct($hostname, $opts, $channel);
    }

    /**
     * @param \Couchbase\StellarNebula\Generated\View\V1\ViewQueryRequest $argument input argument
     * @param array $metadata metadata
     * @param array $options call options
     * @return \Grpc\ServerStreamingCall
     */
    public function ViewQuery(\Couchbase\StellarNebula\Generated\View\V1\ViewQueryRequest $argument,
      $metadata = [], $options = []) {
        return $this->_serverStreamRequest('/couchbase.view.v1.View/ViewQuery',
        $argument,
        ['\Couchbase\StellarNebula\Generated\View\V1\ViewQueryResponse', 'decode'],
        $metadata, $options);
    }

}
