<?php
// GENERATED CODE -- DO NOT EDIT!

namespace Couchbase\Protostellar\Generated\Analytics\V1;

/**
 */
class AnalyticsServiceClient extends \Grpc\BaseStub {

    /**
     * @param string $hostname hostname
     * @param array $opts channel options
     * @param \Grpc\Channel $channel (optional) re-use channel object
     */
    public function __construct($hostname, $opts, $channel = null) {
        parent::__construct($hostname, $opts, $channel);
    }

    /**
     * @param \Couchbase\Protostellar\Generated\Analytics\V1\AnalyticsQueryRequest $argument input argument
     * @param array $metadata metadata
     * @param array $options call options
     * @return \Grpc\ServerStreamingCall
     */
    public function AnalyticsQuery(\Couchbase\Protostellar\Generated\Analytics\V1\AnalyticsQueryRequest $argument,
      $metadata = [], $options = []) {
        return $this->_serverStreamRequest('/couchbase.analytics.v1.AnalyticsService/AnalyticsQuery',
        $argument,
        ['\Couchbase\Protostellar\Generated\Analytics\V1\AnalyticsQueryResponse', 'decode'],
        $metadata, $options);
    }

}
