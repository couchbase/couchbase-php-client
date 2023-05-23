<?php
# Generated by the protocol buffer compiler.  DO NOT EDIT!
# source: couchbase/analytics/v1/analytics.proto

namespace Couchbase\Protostellar\Generated\Analytics\V1\AnalyticsQueryResponse;

use Google\Protobuf\Internal\GPBType;
use Google\Protobuf\Internal\RepeatedField;
use Google\Protobuf\Internal\GPBUtil;

/**
 * Generated from protobuf message <code>couchbase.analytics.v1.AnalyticsQueryResponse.MetaData</code>
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
     * Generated from protobuf field <code>.couchbase.analytics.v1.AnalyticsQueryResponse.Metrics metrics = 3;</code>
     */
    protected $metrics = null;
    /**
     * Generated from protobuf field <code>bytes signature = 4;</code>
     */
    protected $signature = '';
    /**
     * Generated from protobuf field <code>repeated .couchbase.analytics.v1.AnalyticsQueryResponse.MetaData.Warning warnings = 5;</code>
     */
    private $warnings;
    /**
     * Generated from protobuf field <code>string status = 6;</code>
     */
    protected $status = '';

    /**
     * Constructor.
     *
     * @param array $data {
     *     Optional. Data for populating the Message object.
     *
     *     @type string $request_id
     *     @type string $client_context_id
     *     @type \Couchbase\Protostellar\Generated\Analytics\V1\AnalyticsQueryResponse\Metrics $metrics
     *     @type string $signature
     *     @type array<\Couchbase\Protostellar\Generated\Analytics\V1\AnalyticsQueryResponse\MetaData\Warning>|\Google\Protobuf\Internal\RepeatedField $warnings
     *     @type string $status
     * }
     */
    public function __construct($data = NULL) {
        \GPBMetadata\Couchbase\Analytics\V1\Analytics::initOnce();
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
     * Generated from protobuf field <code>.couchbase.analytics.v1.AnalyticsQueryResponse.Metrics metrics = 3;</code>
     * @return \Couchbase\Protostellar\Generated\Analytics\V1\AnalyticsQueryResponse\Metrics|null
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
     * Generated from protobuf field <code>.couchbase.analytics.v1.AnalyticsQueryResponse.Metrics metrics = 3;</code>
     * @param \Couchbase\Protostellar\Generated\Analytics\V1\AnalyticsQueryResponse\Metrics $var
     * @return $this
     */
    public function setMetrics($var)
    {
        GPBUtil::checkMessage($var, \Couchbase\Protostellar\Generated\Analytics\V1\AnalyticsQueryResponse\Metrics::class);
        $this->metrics = $var;

        return $this;
    }

    /**
     * Generated from protobuf field <code>bytes signature = 4;</code>
     * @return string
     */
    public function getSignature()
    {
        return $this->signature;
    }

    /**
     * Generated from protobuf field <code>bytes signature = 4;</code>
     * @param string $var
     * @return $this
     */
    public function setSignature($var)
    {
        GPBUtil::checkString($var, False);
        $this->signature = $var;

        return $this;
    }

    /**
     * Generated from protobuf field <code>repeated .couchbase.analytics.v1.AnalyticsQueryResponse.MetaData.Warning warnings = 5;</code>
     * @return \Google\Protobuf\Internal\RepeatedField
     */
    public function getWarnings()
    {
        return $this->warnings;
    }

    /**
     * Generated from protobuf field <code>repeated .couchbase.analytics.v1.AnalyticsQueryResponse.MetaData.Warning warnings = 5;</code>
     * @param array<\Couchbase\Protostellar\Generated\Analytics\V1\AnalyticsQueryResponse\MetaData\Warning>|\Google\Protobuf\Internal\RepeatedField $var
     * @return $this
     */
    public function setWarnings($var)
    {
        $arr = GPBUtil::checkRepeatedField($var, \Google\Protobuf\Internal\GPBType::MESSAGE, \Couchbase\Protostellar\Generated\Analytics\V1\AnalyticsQueryResponse\MetaData\Warning::class);
        $this->warnings = $arr;

        return $this;
    }

    /**
     * Generated from protobuf field <code>string status = 6;</code>
     * @return string
     */
    public function getStatus()
    {
        return $this->status;
    }

    /**
     * Generated from protobuf field <code>string status = 6;</code>
     * @param string $var
     * @return $this
     */
    public function setStatus($var)
    {
        GPBUtil::checkString($var, True);
        $this->status = $var;

        return $this;
    }

}

// Adding a class alias for backwards compatibility with the previous class name.
class_alias(MetaData::class, \Couchbase\Protostellar\Generated\Analytics\V1\AnalyticsQueryResponse_MetaData::class);
