<?php
// GENERATED CODE -- DO NOT EDIT!

namespace Couchbase\Protostellar\Generated\Internal\Hooks\V1;

/**
 */
class HooksServiceClient extends \Grpc\BaseStub {

    /**
     * @param string $hostname hostname
     * @param array $opts channel options
     * @param \Grpc\Channel $channel (optional) re-use channel object
     */
    public function __construct($hostname, $opts, $channel = null) {
        parent::__construct($hostname, $opts, $channel);
    }

    /**
     * @param \Couchbase\Protostellar\Generated\Internal\Hooks\V1\CreateHooksContextRequest $argument input argument
     * @param array $metadata metadata
     * @param array $options call options
     * @return \Grpc\UnaryCall
     */
    public function CreateHooksContext(\Couchbase\Protostellar\Generated\Internal\Hooks\V1\CreateHooksContextRequest $argument,
      $metadata = [], $options = []) {
        return $this->_simpleRequest('/couchbase.internal.hooks.v1.HooksService/CreateHooksContext',
        $argument,
        ['\Couchbase\Protostellar\Generated\Internal\Hooks\V1\CreateHooksContextResponse', 'decode'],
        $metadata, $options);
    }

    /**
     * @param \Couchbase\Protostellar\Generated\Internal\Hooks\V1\DestroyHooksContextRequest $argument input argument
     * @param array $metadata metadata
     * @param array $options call options
     * @return \Grpc\UnaryCall
     */
    public function DestroyHooksContext(\Couchbase\Protostellar\Generated\Internal\Hooks\V1\DestroyHooksContextRequest $argument,
      $metadata = [], $options = []) {
        return $this->_simpleRequest('/couchbase.internal.hooks.v1.HooksService/DestroyHooksContext',
        $argument,
        ['\Couchbase\Protostellar\Generated\Internal\Hooks\V1\DestroyHooksContextResponse', 'decode'],
        $metadata, $options);
    }

    /**
     * @param \Couchbase\Protostellar\Generated\Internal\Hooks\V1\AddHooksRequest $argument input argument
     * @param array $metadata metadata
     * @param array $options call options
     * @return \Grpc\UnaryCall
     */
    public function AddHooks(\Couchbase\Protostellar\Generated\Internal\Hooks\V1\AddHooksRequest $argument,
      $metadata = [], $options = []) {
        return $this->_simpleRequest('/couchbase.internal.hooks.v1.HooksService/AddHooks',
        $argument,
        ['\Couchbase\Protostellar\Generated\Internal\Hooks\V1\AddHooksResponse', 'decode'],
        $metadata, $options);
    }

    /**
     * @param \Couchbase\Protostellar\Generated\Internal\Hooks\V1\WatchBarrierRequest $argument input argument
     * @param array $metadata metadata
     * @param array $options call options
     * @return \Grpc\ServerStreamingCall
     */
    public function WatchBarrier(\Couchbase\Protostellar\Generated\Internal\Hooks\V1\WatchBarrierRequest $argument,
      $metadata = [], $options = []) {
        return $this->_serverStreamRequest('/couchbase.internal.hooks.v1.HooksService/WatchBarrier',
        $argument,
        ['\Couchbase\Protostellar\Generated\Internal\Hooks\V1\WatchBarrierResponse', 'decode'],
        $metadata, $options);
    }

    /**
     * @param \Couchbase\Protostellar\Generated\Internal\Hooks\V1\SignalBarrierRequest $argument input argument
     * @param array $metadata metadata
     * @param array $options call options
     * @return \Grpc\UnaryCall
     */
    public function SignalBarrier(\Couchbase\Protostellar\Generated\Internal\Hooks\V1\SignalBarrierRequest $argument,
      $metadata = [], $options = []) {
        return $this->_simpleRequest('/couchbase.internal.hooks.v1.HooksService/SignalBarrier',
        $argument,
        ['\Couchbase\Protostellar\Generated\Internal\Hooks\V1\SignalBarrierResponse', 'decode'],
        $metadata, $options);
    }

}
