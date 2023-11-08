<?php
// GENERATED CODE -- DO NOT EDIT!

namespace Couchbase\Protostellar\Generated\KV\V1;

/**
 */
class KvServiceClient extends \Grpc\BaseStub {

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
        return $this->_simpleRequest('/couchbase.kv.v1.KvService/Get',
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
        return $this->_simpleRequest('/couchbase.kv.v1.KvService/GetAndTouch',
        $argument,
        ['\Couchbase\Protostellar\Generated\KV\V1\GetAndTouchResponse', 'decode'],
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
        return $this->_simpleRequest('/couchbase.kv.v1.KvService/GetAndLock',
        $argument,
        ['\Couchbase\Protostellar\Generated\KV\V1\GetAndLockResponse', 'decode'],
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
        return $this->_simpleRequest('/couchbase.kv.v1.KvService/Unlock',
        $argument,
        ['\Couchbase\Protostellar\Generated\KV\V1\UnlockResponse', 'decode'],
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
        return $this->_simpleRequest('/couchbase.kv.v1.KvService/Touch',
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
        return $this->_simpleRequest('/couchbase.kv.v1.KvService/Exists',
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
        return $this->_simpleRequest('/couchbase.kv.v1.KvService/Insert',
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
        return $this->_simpleRequest('/couchbase.kv.v1.KvService/Upsert',
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
        return $this->_simpleRequest('/couchbase.kv.v1.KvService/Replace',
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
        return $this->_simpleRequest('/couchbase.kv.v1.KvService/Remove',
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
        return $this->_simpleRequest('/couchbase.kv.v1.KvService/Increment',
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
        return $this->_simpleRequest('/couchbase.kv.v1.KvService/Decrement',
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
        return $this->_simpleRequest('/couchbase.kv.v1.KvService/Append',
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
        return $this->_simpleRequest('/couchbase.kv.v1.KvService/Prepend',
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
        return $this->_simpleRequest('/couchbase.kv.v1.KvService/LookupIn',
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
        return $this->_simpleRequest('/couchbase.kv.v1.KvService/MutateIn',
        $argument,
        ['\Couchbase\Protostellar\Generated\KV\V1\MutateInResponse', 'decode'],
        $metadata, $options);
    }

    /**
     * @param \Couchbase\Protostellar\Generated\KV\V1\GetAllReplicasRequest $argument input argument
     * @param array $metadata metadata
     * @param array $options call options
     * @return \Grpc\ServerStreamingCall
     */
    public function GetAllReplicas(\Couchbase\Protostellar\Generated\KV\V1\GetAllReplicasRequest $argument,
      $metadata = [], $options = []) {
        return $this->_serverStreamRequest('/couchbase.kv.v1.KvService/GetAllReplicas',
        $argument,
        ['\Couchbase\Protostellar\Generated\KV\V1\GetAllReplicasResponse', 'decode'],
        $metadata, $options);
    }

}
