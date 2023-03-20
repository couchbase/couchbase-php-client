<?php
# Generated by the protocol buffer compiler.  DO NOT EDIT!
# source: couchbase/query/v1/query.proto

namespace Couchbase\Protostellar\Generated\Query\V1\QueryResponse;

use Google\Protobuf\Internal\GPBType;
use Google\Protobuf\Internal\RepeatedField;
use Google\Protobuf\Internal\GPBUtil;

/**
 * Generated from protobuf message <code>couchbase.query.v1.QueryResponse.MetaData</code>
 */
class MetaData extends \Google\Protobuf\Internal\Message
{
    /**
     * Generated from protobuf field <code>string request_id = 1;</code>
     */
    protected $request_id = '';
    /**
     * Generated from protobuf field <code>string client_context_id = 2;</code>
     */
    protected $client_context_id = '';
    /**
     * Generated from protobuf field <code>optional .couchbase.query.v1.QueryResponse.MetaData.Metrics metrics = 3;</code>
     */
    protected $metrics = null;
    /**
     * Generated from protobuf field <code>.couchbase.query.v1.QueryResponse.MetaData.Status status = 4;</code>
     */
    protected $status = 0;
    /**
     * Generated from protobuf field <code>repeated .couchbase.query.v1.QueryResponse.MetaData.Warning warnings = 5;</code>
     */
    private $warnings;
    /**
     * Generated from protobuf field <code>optional bytes profile = 6;</code>
     */
    protected $profile = null;
    /**
     * Generated from protobuf field <code>bytes signature = 7;</code>
     */
    protected $signature = '';

    /**
     * Constructor.
     *
     * @param array $data {
     *     Optional. Data for populating the Message object.
     *
     *     @type string $request_id
     *     @type string $client_context_id
     *     @type \Couchbase\Protostellar\Generated\Query\V1\QueryResponse\MetaData\Metrics $metrics
     *     @type int $status
     *     @type array<\Couchbase\Protostellar\Generated\Query\V1\QueryResponse\MetaData\Warning>|\Google\Protobuf\Internal\RepeatedField $warnings
     *     @type string $profile
     *     @type string $signature
     * }
     */
    public function __construct($data = NULL) {
        \GPBMetadata\Couchbase\Query\V1\Query::initOnce();
        parent::__construct($data);
    }

    /**
     * Generated from protobuf field <code>string request_id = 1;</code>
     * @return string
     */
    public function getRequestId()
    {
        return $this->request_id;
    }

    /**
     * Generated from protobuf field <code>string request_id = 1;</code>
     * @param string $var
     * @return $this
     */
    public function setRequestId($var)
    {
        GPBUtil::checkString($var, True);
        $this->request_id = $var;

        return $this;
    }

    /**
     * Generated from protobuf field <code>string client_context_id = 2;</code>
     * @return string
     */
    public function getClientContextId()
    {
        return $this->client_context_id;
    }

    /**
     * Generated from protobuf field <code>string client_context_id = 2;</code>
     * @param string $var
     * @return $this
     */
    public function setClientContextId($var)
    {
        GPBUtil::checkString($var, True);
        $this->client_context_id = $var;

        return $this;
    }

    /**
     * Generated from protobuf field <code>optional .couchbase.query.v1.QueryResponse.MetaData.Metrics metrics = 3;</code>
     * @return \Couchbase\Protostellar\Generated\Query\V1\QueryResponse\MetaData\Metrics|null
     */
    public function getMetrics()
    {
        return $this->metrics;
    }

    public function hasMetrics()
    {
        return isset($this->metrics);
    }

    public function clearMetrics()
    {
        unset($this->metrics);
    }

    /**
     * Generated from protobuf field <code>optional .couchbase.query.v1.QueryResponse.MetaData.Metrics metrics = 3;</code>
     * @param \Couchbase\Protostellar\Generated\Query\V1\QueryResponse\MetaData\Metrics $var
     * @return $this
     */
    public function setMetrics($var)
    {
        GPBUtil::checkMessage($var, \Couchbase\Protostellar\Generated\Query\V1\QueryResponse\MetaData\Metrics::class);
        $this->metrics = $var;

        return $this;
    }

    /**
     * Generated from protobuf field <code>.couchbase.query.v1.QueryResponse.MetaData.Status status = 4;</code>
     * @return int
     */
    public function getStatus()
    {
        return $this->status;
    }

    /**
     * Generated from protobuf field <code>.couchbase.query.v1.QueryResponse.MetaData.Status status = 4;</code>
     * @param int $var
     * @return $this
     */
    public function setStatus($var)
    {
        GPBUtil::checkEnum($var, \Couchbase\Protostellar\Generated\Query\V1\QueryResponse\MetaData\Status::class);
        $this->status = $var;

        return $this;
    }

    /**
     * Generated from protobuf field <code>repeated .couchbase.query.v1.QueryResponse.MetaData.Warning warnings = 5;</code>
     * @return \Google\Protobuf\Internal\RepeatedField
     */
    public function getWarnings()
    {
        return $this->warnings;
    }

    /**
     * Generated from protobuf field <code>repeated .couchbase.query.v1.QueryResponse.MetaData.Warning warnings = 5;</code>
     * @param array<\Couchbase\Protostellar\Generated\Query\V1\QueryResponse\MetaData\Warning>|\Google\Protobuf\Internal\RepeatedField $var
     * @return $this
     */
    public function setWarnings($var)
    {
        $arr = GPBUtil::checkRepeatedField($var, \Google\Protobuf\Internal\GPBType::MESSAGE, \Couchbase\Protostellar\Generated\Query\V1\QueryResponse\MetaData\Warning::class);
        $this->warnings = $arr;

        return $this;
    }

    /**
     * Generated from protobuf field <code>optional bytes profile = 6;</code>
     * @return string
     */
    public function getProfile()
    {
        return isset($this->profile) ? $this->profile : '';
    }

    public function hasProfile()
    {
        return isset($this->profile);
    }

    public function clearProfile()
    {
        unset($this->profile);
    }

    /**
     * Generated from protobuf field <code>optional bytes profile = 6;</code>
     * @param string $var
     * @return $this
     */
    public function setProfile($var)
    {
        GPBUtil::checkString($var, False);
        $this->profile = $var;

        return $this;
    }

    /**
     * Generated from protobuf field <code>bytes signature = 7;</code>
     * @return string
     */
    public function getSignature()
    {
        return $this->signature;
    }

    /**
     * Generated from protobuf field <code>bytes signature = 7;</code>
     * @param string $var
     * @return $this
     */
    public function setSignature($var)
    {
        GPBUtil::checkString($var, False);
        $this->signature = $var;

        return $this;
    }

}

// Adding a class alias for backwards compatibility with the previous class name.
class_alias(MetaData::class, \Couchbase\Protostellar\Generated\Query\V1\QueryResponse_MetaData::class);

