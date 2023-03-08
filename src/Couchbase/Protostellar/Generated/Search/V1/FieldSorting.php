<?php
# Generated by the protocol buffer compiler.  DO NOT EDIT!
# source: couchbase/search.v1.proto

namespace Couchbase\Protostellar\Generated\Search\V1;

use Google\Protobuf\Internal\GPBType;
use Google\Protobuf\Internal\RepeatedField;
use Google\Protobuf\Internal\GPBUtil;

/**
 * Generated from protobuf message <code>couchbase.search.v1.FieldSorting</code>
 */
class FieldSorting extends \Google\Protobuf\Internal\Message
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
     * Generated from protobuf field <code>string missing = 3;</code>
     */
    protected $missing = '';
    /**
     * Generated from protobuf field <code>string mode = 4;</code>
     */
    protected $mode = '';
    /**
     * Generated from protobuf field <code>string type = 5;</code>
     */
    protected $type = '';

    /**
     * Constructor.
     *
     * @param array $data {
     *     Optional. Data for populating the Message object.
     *
     *     @type string $field
     *     @type bool $descending
     *     @type string $missing
     *     @type string $mode
     *     @type string $type
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
     * Generated from protobuf field <code>string missing = 3;</code>
     * @return string
     */
    public function getMissing()
    {
        return $this->missing;
    }

    /**
     * Generated from protobuf field <code>string missing = 3;</code>
     * @param string $var
     * @return $this
     */
    public function setMissing($var)
    {
        GPBUtil::checkString($var, True);
        $this->missing = $var;

        return $this;
    }

    /**
     * Generated from protobuf field <code>string mode = 4;</code>
     * @return string
     */
    public function getMode()
    {
        return $this->mode;
    }

    /**
     * Generated from protobuf field <code>string mode = 4;</code>
     * @param string $var
     * @return $this
     */
    public function setMode($var)
    {
        GPBUtil::checkString($var, True);
        $this->mode = $var;

        return $this;
    }

    /**
     * Generated from protobuf field <code>string type = 5;</code>
     * @return string
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * Generated from protobuf field <code>string type = 5;</code>
     * @param string $var
     * @return $this
     */
    public function setType($var)
    {
        GPBUtil::checkString($var, True);
        $this->type = $var;

        return $this;
    }

}
