<?php
// GENERATED CODE -- DO NOT EDIT!

namespace Couchbase\StellarNebula\Generated\Internal\Hooks\V1;

/**
 */
class HooksClient extends \Grpc\BaseStub {

    /**
     * @param string $hostname hostname
     * @param array $opts channel options
     * @param \Grpc\Channel $channel (optional) re-use channel object
     */
    public function __construct($hostname, $opts, $channel = null) {
        parent::__construct($hostname, $opts, $channel);
    }

    /**
     * @param \Couchbase\StellarNebula\Generated\Internal\Hooks\V1\CreateHooksContextRequest $argument input argument
     * @param array $metadata metadata
     * @param array $options call options
     * @return \Grpc\UnaryCall
     */
    public function CreateHooksContext(\Couchbase\StellarNebula\Generated\Internal\Hooks\V1\CreateHooksContextRequest $argument,
      $metadata = [], $options = []) {
        return $this->_simpleRequest('/couchbase.internal.hooks.v1.Hooks/CreateHooksContext',
        $argument,
        ['\Couchbase\StellarNebula\Generated\Internal\Hooks\V1\CreateHooksContextResponse', 'decode'],
        $metadata, $options);
    }

    /**
     * @param \Couchbase\StellarNebula\Generated\Internal\Hooks\V1\DestroyHooksContextRequest $argument input argument
     * @param array $metadata metadata
     * @param array $options call options
     * @return \Grpc\UnaryCall
     */
    public function DestroyHooksContext(\Couchbase\StellarNebula\Generated\Internal\Hooks\V1\DestroyHooksContextRequest $argument,
      $metadata = [], $options = []) {
        return $this->_simpleRequest('/couchbase.internal.hooks.v1.Hooks/DestroyHooksContext',
        $argument,
        ['\Couchbase\StellarNebula\Generated\Internal\Hooks\V1\DestroyHooksContextResponse', 'decode'],
        $metadata, $options);
    }

    /**
     * @param \Couchbase\StellarNebula\Generated\Internal\Hooks\V1\AddHooksRequest $argument input argument
     * @param array $metadata metadata
     * @param array $options call options
     * @return \Grpc\UnaryCall
     */
    public function AddHooks(\Couchbase\StellarNebula\Generated\Internal\Hooks\V1\AddHooksRequest $argument,
      $metadata = [], $options = []) {
        return $this->_simpleRequest('/couchbase.internal.hooks.v1.Hooks/AddHooks',
        $argument,
        ['\Couchbase\StellarNebula\Generated\Internal\Hooks\V1\AddHooksResponse', 'decode'],
        $metadata, $options);
    }

    /**
     * @param \Couchbase\StellarNebula\Generated\Internal\Hooks\V1\WatchBarrierRequest $argument input argument
     * @param array $metadata metadata
     * @param array $options call options
     * @return \Grpc\ServerStreamingCall
     */
    public function WatchBarrier(\Couchbase\StellarNebula\Generated\Internal\Hooks\V1\WatchBarrierRequest $argument,
      $metadata = [], $options = []) {
        return $this->_serverStreamRequest('/couchbase.internal.hooks.v1.Hooks/WatchBarrier',
        $argument,
        ['\Couchbase\StellarNebula\Generated\Internal\Hooks\V1\WatchBarrierResponse', 'decode'],
        $metadata, $options);
    }

    /**
     * @param \Couchbase\StellarNebula\Generated\Internal\Hooks\V1\SignalBarrierRequest $argument input argument
     * @param array $metadata metadata
     * @param array $options call options
     * @return \Grpc\UnaryCall
     */
    public function SignalBarrier(\Couchbase\StellarNebula\Generated\Internal\Hooks\V1\SignalBarrierRequest $argument,
      $metadata = [], $options = []) {
        return $this->_simpleRequest('/couchbase.internal.hooks.v1.Hooks/SignalBarrier',
        $argument,
        ['\Couchbase\StellarNebula\Generated\Internal\Hooks\V1\SignalBarrierResponse', 'decode'],
        $metadata, $options);
    }

}
