<?php
# Generated by the protocol buffer compiler.  DO NOT EDIT!
# source: couchbase/search.v1.proto

namespace Couchbase\StellarNebula\Generated\Search\V1;

use Google\Protobuf\Internal\GPBType;
use Google\Protobuf\Internal\RepeatedField;
use Google\Protobuf\Internal\GPBUtil;

/**
 * Generated from protobuf message <code>couchbase.search.v1.RegexpQuery</code>
 */
class RegexpQuery extends \Google\Protobuf\Internal\Message
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
     * Generated from protobuf field <code>string regexp = 3;</code>
     */
    protected $regexp = '';

    /**
     * Constructor.
     *
     * @param array $data {
     *     Optional. Data for populating the Message object.
     *
     *     @type float $boost
     *     @type string $field
     *     @type string $regexp
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
     * Generated from protobuf field <code>string regexp = 3;</code>
     * @return string
     */
    public function getRegexp()
    {
        return $this->regexp;
    }

    /**
     * Generated from protobuf field <code>string regexp = 3;</code>
     * @param string $var
     * @return $this
     */
    public function setRegexp($var)
    {
        GPBUtil::checkString($var, True);
        $this->regexp = $var;

        return $this;
    }

}

