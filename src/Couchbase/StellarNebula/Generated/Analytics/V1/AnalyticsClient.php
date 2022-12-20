<?php
// GENERATED CODE -- DO NOT EDIT!

namespace Couchbase\StellarNebula\Generated\Analytics\V1;

/**
 */
class AnalyticsClient extends \Grpc\BaseStub {

    /**
     * @param string $hostname hostname
     * @param array $opts channel options
     * @param \Grpc\Channel $channel (optional) re-use channel object
     */
    public function __construct($hostname, $opts, $channel = null) {
        parent::__construct($hostname, $opts, $channel);
    }

    /**
     * @param \Couchbase\StellarNebula\Generated\Analytics\V1\AnalyticsQueryRequest $argument input argument
     * @param array $metadata metadata
     * @param array $options call options
     * @return \Grpc\ServerStreamingCall
     */
    public function AnalyticsQuery(\Couchbase\StellarNebula\Generated\Analytics\V1\AnalyticsQueryRequest $argument,
      $metadata = [], $options = []) {
        return $this->_serverStreamRequest('/couchbase.analytics.v1.Analytics/AnalyticsQuery',
        $argument,
        ['\Couchbase\StellarNebula\Generated\Analytics\V1\AnalyticsQueryResponse', 'decode'],
        $metadata, $options);
    }

}
