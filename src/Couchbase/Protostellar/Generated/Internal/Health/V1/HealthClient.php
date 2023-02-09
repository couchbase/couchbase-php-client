<?php
// GENERATED CODE -- DO NOT EDIT!

namespace Couchbase\Protostellar\Generated\Internal\Health\V1;

/**
 */
class HealthClient extends \Grpc\BaseStub {

    /**
     * @param string $hostname hostname
     * @param array $opts channel options
     * @param \Grpc\Channel $channel (optional) re-use channel object
     */
    public function __construct($hostname, $opts, $channel = null) {
        parent::__construct($hostname, $opts, $channel);
    }

    /**
     * @param \Couchbase\Protostellar\Generated\Internal\Health\V1\HealthCheckRequest $argument input argument
     * @param array $metadata metadata
     * @param array $options call options
     * @return \Grpc\UnaryCall
     */
    public function Check(\Couchbase\Protostellar\Generated\Internal\Health\V1\HealthCheckRequest $argument,
      $metadata = [], $options = []) {
        return $this->_simpleRequest('/couchbase.health.v1.Health/Check',
        $argument,
        ['\Couchbase\Protostellar\Generated\Internal\Health\V1\HealthCheckResponse', 'decode'],
        $metadata, $options);
    }

    /**
     * @param \Couchbase\Protostellar\Generated\Internal\Health\V1\HealthCheckRequest $argument input argument
     * @param array $metadata metadata
     * @param array $options call options
     * @return \Grpc\ServerStreamingCall
     */
    public function Watch(\Couchbase\Protostellar\Generated\Internal\Health\V1\HealthCheckRequest $argument,
      $metadata = [], $options = []) {
        return $this->_serverStreamRequest('/couchbase.health.v1.Health/Watch',
        $argument,
        ['\Couchbase\Protostellar\Generated\Internal\Health\V1\HealthCheckResponse', 'decode'],
        $metadata, $options);
    }

}
