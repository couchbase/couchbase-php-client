<?php
// GENERATED CODE -- DO NOT EDIT!

namespace Couchbase\Protostellar\Generated\KV\V1;

/**
 */
class KvClient extends \Grpc\BaseStub {

    /**
     * @param string $hostname hostname
     * @param array $opts channel options
     * @param \Grpc\Channel $channel (optional) re-use channel object
     */
    public function __construct($hostname, $opts, $channel = null) {
        parent::__construct($hostname, $opts, $channel);
    }

    /**
     * @param \Couchbase\Protostellar\Generated\KV\V1\GetRequest $argument input argument
     * @param array $metadata metadata
     * @param array $options call options
     * @return \Grpc\UnaryCall
     */
    public function Get(\Couchbase\Protostellar\Generated\KV\V1\GetRequest $argument,
      $metadata = [], $options = []) {
        return $this->_simpleRequest('/couchbase.kv.v1.Kv/Get',
        $argument,
        ['\Couchbase\Protostellar\Generated\KV\V1\GetResponse', 'decode'],
        $metadata, $options);
    }

    /**
     * @param \Couchbase\Protostellar\Generated\KV\V1\GetAndTouchRequest $argument input argument
     * @param array $metadata metadata
     * @param array $options call options
     * @return \Grpc\UnaryCall
     */
    public function GetAndTouch(\Couchbase\Protostellar\Generated\KV\V1\GetAndTouchRequest $argument,
      $metadata = [], $options = []) {
        return $this->_simpleRequest('/couchbase.kv.v1.Kv/GetAndTouch',
        $argument,
        ['\Couchbase\Protostellar\Generated\KV\V1\GetResponse', 'decode'],
        $metadata, $options);
    }

    /**
     * @param \Couchbase\Protostellar\Generated\KV\V1\GetAndLockRequest $argument input argument
     * @param array $metadata metadata
     * @param array $options call options
     * @return \Grpc\UnaryCall
     */
    public function GetAndLock(\Couchbase\Protostellar\Generated\KV\V1\GetAndLockRequest $argument,
      $metadata = [], $options = []) {
        return $this->_simpleRequest('/couchbase.kv.v1.Kv/GetAndLock',
        $argument,
        ['\Couchbase\Protostellar\Generated\KV\V1\GetResponse', 'decode'],
        $metadata, $options);
    }

    /**
     * @param \Couchbase\Protostellar\Generated\KV\V1\UnlockRequest $argument input argument
     * @param array $metadata metadata
     * @param array $options call options
     * @return \Grpc\UnaryCall
     */
    public function Unlock(\Couchbase\Protostellar\Generated\KV\V1\UnlockRequest $argument,
      $metadata = [], $options = []) {
        return $this->_simpleRequest('/couchbase.kv.v1.Kv/Unlock',
        $argument,
        ['\Couchbase\Protostellar\Generated\KV\V1\UnlockResponse', 'decode'],
        $metadata, $options);
    }

    /**
     * @param \Couchbase\Protostellar\Generated\KV\V1\GetReplicaRequest $argument input argument
     * @param array $metadata metadata
     * @param array $options call options
     * @return \Grpc\UnaryCall
     */
    public function GetReplica(\Couchbase\Protostellar\Generated\KV\V1\GetReplicaRequest $argument,
      $metadata = [], $options = []) {
        return $this->_simpleRequest('/couchbase.kv.v1.Kv/GetReplica',
        $argument,
        ['\Couchbase\Protostellar\Generated\KV\V1\GetResponse', 'decode'],
        $metadata, $options);
    }

    /**
     * @param \Couchbase\Protostellar\Generated\KV\V1\TouchRequest $argument input argument
     * @param array $metadata metadata
     * @param array $options call options
     * @return \Grpc\UnaryCall
     */
    public function Touch(\Couchbase\Protostellar\Generated\KV\V1\TouchRequest $argument,
      $metadata = [], $options = []) {
        return $this->_simpleRequest('/couchbase.kv.v1.Kv/Touch',
        $argument,
        ['\Couchbase\Protostellar\Generated\KV\V1\TouchResponse', 'decode'],
        $metadata, $options);
    }

    /**
     * @param \Couchbase\Protostellar\Generated\KV\V1\ExistsRequest $argument input argument
     * @param array $metadata metadata
     * @param array $options call options
     * @return \Grpc\UnaryCall
     */
    public function Exists(\Couchbase\Protostellar\Generated\KV\V1\ExistsRequest $argument,
      $metadata = [], $options = []) {
        return $this->_simpleRequest('/couchbase.kv.v1.Kv/Exists',
        $argument,
        ['\Couchbase\Protostellar\Generated\KV\V1\ExistsResponse', 'decode'],
        $metadata, $options);
    }

    /**
     * @param \Couchbase\Protostellar\Generated\KV\V1\InsertRequest $argument input argument
     * @param array $metadata metadata
     * @param array $options call options
     * @return \Grpc\UnaryCall
     */
    public function Insert(\Couchbase\Protostellar\Generated\KV\V1\InsertRequest $argument,
      $metadata = [], $options = []) {
        return $this->_simpleRequest('/couchbase.kv.v1.Kv/Insert',
        $argument,
        ['\Couchbase\Protostellar\Generated\KV\V1\InsertResponse', 'decode'],
        $metadata, $options);
    }

    /**
     * @param \Couchbase\Protostellar\Generated\KV\V1\UpsertRequest $argument input argument
     * @param array $metadata metadata
     * @param array $options call options
     * @return \Grpc\UnaryCall
     */
    public function Upsert(\Couchbase\Protostellar\Generated\KV\V1\UpsertRequest $argument,
      $metadata = [], $options = []) {
        return $this->_simpleRequest('/couchbase.kv.v1.Kv/Upsert',
        $argument,
        ['\Couchbase\Protostellar\Generated\KV\V1\UpsertResponse', 'decode'],
        $metadata, $options);
    }

    /**
     * @param \Couchbase\Protostellar\Generated\KV\V1\ReplaceRequest $argument input argument
     * @param array $metadata metadata
     * @param array $options call options
     * @return \Grpc\UnaryCall
     */
    public function Replace(\Couchbase\Protostellar\Generated\KV\V1\ReplaceRequest $argument,
      $metadata = [], $options = []) {
        return $this->_simpleRequest('/couchbase.kv.v1.Kv/Replace',
        $argument,
        ['\Couchbase\Protostellar\Generated\KV\V1\ReplaceResponse', 'decode'],
        $metadata, $options);
    }

    /**
     * @param \Couchbase\Protostellar\Generated\KV\V1\RemoveRequest $argument input argument
     * @param array $metadata metadata
     * @param array $options call options
     * @return \Grpc\UnaryCall
     */
    public function Remove(\Couchbase\Protostellar\Generated\KV\V1\RemoveRequest $argument,
      $metadata = [], $options = []) {
        return $this->_simpleRequest('/couchbase.kv.v1.Kv/Remove',
        $argument,
        ['\Couchbase\Protostellar\Generated\KV\V1\RemoveResponse', 'decode'],
        $metadata, $options);
    }

    /**
     * @param \Couchbase\Protostellar\Generated\KV\V1\IncrementRequest $argument input argument
     * @param array $metadata metadata
     * @param array $options call options
     * @return \Grpc\UnaryCall
     */
    public function Increment(\Couchbase\Protostellar\Generated\KV\V1\IncrementRequest $argument,
      $metadata = [], $options = []) {
        return $this->_simpleRequest('/couchbase.kv.v1.Kv/Increment',
        $argument,
        ['\Couchbase\Protostellar\Generated\KV\V1\IncrementResponse', 'decode'],
        $metadata, $options);
    }

    /**
     * @param \Couchbase\Protostellar\Generated\KV\V1\DecrementRequest $argument input argument
     * @param array $metadata metadata
     * @param array $options call options
     * @return \Grpc\UnaryCall
     */
    public function Decrement(\Couchbase\Protostellar\Generated\KV\V1\DecrementRequest $argument,
      $metadata = [], $options = []) {
        return $this->_simpleRequest('/couchbase.kv.v1.Kv/Decrement',
        $argument,
        ['\Couchbase\Protostellar\Generated\KV\V1\DecrementResponse', 'decode'],
        $metadata, $options);
    }

    /**
     * @param \Couchbase\Protostellar\Generated\KV\V1\AppendRequest $argument input argument
     * @param array $metadata metadata
     * @param array $options call options
     * @return \Grpc\UnaryCall
     */
    public function Append(\Couchbase\Protostellar\Generated\KV\V1\AppendRequest $argument,
      $metadata = [], $options = []) {
        return $this->_simpleRequest('/couchbase.kv.v1.Kv/Append',
        $argument,
        ['\Couchbase\Protostellar\Generated\KV\V1\AppendResponse', 'decode'],
        $metadata, $options);
    }

    /**
     * @param \Couchbase\Protostellar\Generated\KV\V1\PrependRequest $argument input argument
     * @param array $metadata metadata
     * @param array $options call options
     * @return \Grpc\UnaryCall
     */
    public function Prepend(\Couchbase\Protostellar\Generated\KV\V1\PrependRequest $argument,
      $metadata = [], $options = []) {
        return $this->_simpleRequest('/couchbase.kv.v1.Kv/Prepend',
        $argument,
        ['\Couchbase\Protostellar\Generated\KV\V1\PrependResponse', 'decode'],
        $metadata, $options);
    }

    /**
     * @param \Couchbase\Protostellar\Generated\KV\V1\LookupInRequest $argument input argument
     * @param array $metadata metadata
     * @param array $options call options
     * @return \Grpc\UnaryCall
     */
    public function LookupIn(\Couchbase\Protostellar\Generated\KV\V1\LookupInRequest $argument,
      $metadata = [], $options = []) {
        return $this->_simpleRequest('/couchbase.kv.v1.Kv/LookupIn',
        $argument,
        ['\Couchbase\Protostellar\Generated\KV\V1\LookupInResponse', 'decode'],
        $metadata, $options);
    }

    /**
     * @param \Couchbase\Protostellar\Generated\KV\V1\MutateInRequest $argument input argument
     * @param array $metadata metadata
     * @param array $options call options
     * @return \Grpc\UnaryCall
     */
    public function MutateIn(\Couchbase\Protostellar\Generated\KV\V1\MutateInRequest $argument,
      $metadata = [], $options = []) {
        return $this->_simpleRequest('/couchbase.kv.v1.Kv/MutateIn',
        $argument,
        ['\Couchbase\Protostellar\Generated\KV\V1\MutateInResponse', 'decode'],
        $metadata, $options);
    }

    /**
     * @param \Couchbase\Protostellar\Generated\KV\V1\RangeScanRequest $argument input argument
     * @param array $metadata metadata
     * @param array $options call options
     * @return \Grpc\UnaryCall
     */
    public function RangeScan(\Couchbase\Protostellar\Generated\KV\V1\RangeScanRequest $argument,
      $metadata = [], $options = []) {
        return $this->_simpleRequest('/couchbase.kv.v1.Kv/RangeScan',
        $argument,
        ['\Couchbase\Protostellar\Generated\KV\V1\RangeScanResponse', 'decode'],
        $metadata, $options);
    }

}
