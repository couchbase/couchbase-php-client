<?php
# Generated by the protocol buffer compiler.  DO NOT EDIT!
# source: couchbase/search.v1.proto

namespace Couchbase\Protostellar\Generated\Search\V1;

use Google\Protobuf\Internal\GPBType;
use Google\Protobuf\Internal\RepeatedField;
use Google\Protobuf\Internal\GPBUtil;

/**
 * Generated from protobuf message <code>couchbase.search.v1.NumericRangeQuery</code>
 */
class NumericRangeQuery extends \Google\Protobuf\Internal\Message
{
    /**
     * Generated from protobuf field <code>float boost = 1;</code>
     */
    protected $boost = 0.0;
    /**
     * Generated from protobuf field <code>string field = 2;</code>
     */
    protected $field = '';
    /**
     * Generated from protobuf field <code>float min = 3;</code>
     */
    protected $min = 0.0;
    /**
     * Generated from protobuf field <code>float max = 4;</code>
     */
    protected $max = 0.0;
    /**
     * Generated from protobuf field <code>bool inclusive_min = 5;</code>
     */
    protected $inclusive_min = false;
    /**
     * Generated from protobuf field <code>bool inclusive_max = 6;</code>
     */
    protected $inclusive_max = false;

    /**
     * Constructor.
     *
     * @param array $data {
     *     Optional. Data for populating the Message object.
     *
     *     @type float $boost
     *     @type string $field
     *     @type float $min
     *     @type float $max
     *     @type bool $inclusive_min
     *     @type bool $inclusive_max
     * }
     */
    public function __construct($data = NULL) {
        \GPBMetadata\Couchbase\SearchV1::initOnce();
        parent::__construct($data);
    }

    /**
     * Generated from protobuf field <code>float boost = 1;</code>
     * @return float
     */
    public function getBoost()
    {
        return $this->boost;
    }

    /**
     * Generated from protobuf field <code>float boost = 1;</code>
     * @param float $var
     * @return $this
     */
    public function setBoost($var)
    {
        GPBUtil::checkFloat($var);
        $this->boost = $var;

        return $this;
    }

    /**
     * Generated from protobuf field <code>string field = 2;</code>
     * @return string
     */
    public function getField()
    {
        return $this->field;
    }

    /**
     * Generated from protobuf field <code>string field = 2;</code>
     * @param string $var
     * @return $this
     */
    public function setField($var)
    {
        GPBUtil::checkString($var, True);
        $this->field = $var;

        return $this;
    }

    /**
     * Generated from protobuf field <code>float min = 3;</code>
     * @return float
     */
    public function getMin()
    {
        return $this->min;
    }

    /**
     * Generated from protobuf field <code>float min = 3;</code>
     * @param float $var
     * @return $this
     */
    public function setMin($var)
    {
        GPBUtil::checkFloat($var);
        $this->min = $var;

        return $this;
    }

    /**
     * Generated from protobuf field <code>float max = 4;</code>
     * @return float
     */
    public function getMax()
    {
        return $this->max;
    }

    /**
     * Generated from protobuf field <code>float max = 4;</code>
     * @param float $var
     * @return $this
     */
    public function setMax($var)
    {
        GPBUtil::checkFloat($var);
        $this->max = $var;

        return $this;
    }

    /**
     * Generated from protobuf field <code>bool inclusive_min = 5;</code>
     * @return bool
     */
    public function getInclusiveMin()
    {
        return $this->inclusive_min;
    }

    /**
     * Generated from protobuf field <code>bool inclusive_min = 5;</code>
     * @param bool $var
     * @return $this
     */
    public function setInclusiveMin($var)
    {
        GPBUtil::checkBool($var);
        $this->inclusive_min = $var;

        return $this;
    }

    /**
     * Generated from protobuf field <code>bool inclusive_max = 6;</code>
     * @return bool
     */
    public function getInclusiveMax()
    {
        return $this->inclusive_max;
    }

    /**
     * Generated from protobuf field <code>bool inclusive_max = 6;</code>
     * @param bool $var
     * @return $this
     */
    public function setInclusiveMax($var)
    {
        GPBUtil::checkBool($var);
        $this->inclusive_max = $var;

        return $this;
    }

}

