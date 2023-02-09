<?php
# Generated by the protocol buffer compiler.  DO NOT EDIT!
# source: couchbase/search.v1.proto

namespace Couchbase\Protostellar\Generated\Search\V1;

use Google\Protobuf\Internal\GPBType;
use Google\Protobuf\Internal\RepeatedField;
use Google\Protobuf\Internal\GPBUtil;

/**
 * Generated from protobuf message <code>couchbase.search.v1.GeoDistanceSorting</code>
 */
class GeoDistanceSorting extends \Google\Protobuf\Internal\Message
{
    /**
     * Generated from protobuf field <code>string field = 1;</code>
     */
    protected $field = '';
    /**
     * Generated from protobuf field <code>bool descending = 2;</code>
     */
    protected $descending = false;
    /**
     * Generated from protobuf field <code>.couchbase.search.v1.LatLng center = 3;</code>
     */
    protected $center = null;
    /**
     * Generated from protobuf field <code>string unit = 4;</code>
     */
    protected $unit = '';

    /**
     * Constructor.
     *
     * @param array $data {
     *     Optional. Data for populating the Message object.
     *
     *     @type string $field
     *     @type bool $descending
     *     @type \Couchbase\Protostellar\Generated\Search\V1\LatLng $center
     *     @type string $unit
     * }
     */
    public function __construct($data = NULL) {
        \GPBMetadata\Couchbase\SearchV1::initOnce();
        parent::__construct($data);
    }

    /**
     * Generated from protobuf field <code>string field = 1;</code>
     * @return string
     */
    public function getField()
    {
        return $this->field;
    }

    /**
     * Generated from protobuf field <code>string field = 1;</code>
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
     * Generated from protobuf field <code>bool descending = 2;</code>
     * @return bool
     */
    public function getDescending()
    {
        return $this->descending;
    }

    /**
     * Generated from protobuf field <code>bool descending = 2;</code>
     * @param bool $var
     * @return $this
     */
    public function setDescending($var)
    {
        GPBUtil::checkBool($var);
        $this->descending = $var;

        return $this;
    }

    /**
     * Generated from protobuf field <code>.couchbase.search.v1.LatLng center = 3;</code>
     * @return \Couchbase\Protostellar\Generated\Search\V1\LatLng|null
     */
    public function getCenter()
    {
        return $this->center;
    }

    public function hasCenter()
    {
        return isset($this->center);
    }

    public function clearCenter()
    {
        unset($this->center);
    }

    /**
     * Generated from protobuf field <code>.couchbase.search.v1.LatLng center = 3;</code>
     * @param \Couchbase\Protostellar\Generated\Search\V1\LatLng $var
     * @return $this
     */
    public function setCenter($var)
    {
        GPBUtil::checkMessage($var, \Couchbase\Protostellar\Generated\Search\V1\LatLng::class);
        $this->center = $var;

        return $this;
    }

    /**
     * Generated from protobuf field <code>string unit = 4;</code>
     * @return string
     */
    public function getUnit()
    {
        return $this->unit;
    }

    /**
     * Generated from protobuf field <code>string unit = 4;</code>
     * @param string $var
     * @return $this
     */
    public function setUnit($var)
    {
        GPBUtil::checkString($var, True);
        $this->unit = $var;

        return $this;
    }

}

