<?php
# Generated by the protocol buffer compiler.  DO NOT EDIT!
# source: couchbase/search/v1/search.proto

namespace Couchbase\Protostellar\Generated\Search\V1;

use Google\Protobuf\Internal\GPBType;
use Google\Protobuf\Internal\RepeatedField;
use Google\Protobuf\Internal\GPBUtil;

/**
 * Generated from protobuf message <code>couchbase.search.v1.PhraseQuery</code>
 */
class PhraseQuery extends \Google\Protobuf\Internal\Message
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
     * Generated from protobuf field <code>repeated string terms = 3;</code>
     */
    private $terms;

    /**
     * Constructor.
     *
     * @param array $data {
     *     Optional. Data for populating the Message object.
     *
     *     @type float $boost
     *     @type string $field
     *     @type array<string>|\Google\Protobuf\Internal\RepeatedField $terms
     * }
     */
    public function __construct($data = NULL) {
        \GPBMetadata\Couchbase\Search\V1\Search::initOnce();
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
     * Generated from protobuf field <code>repeated string terms = 3;</code>
     * @return \Google\Protobuf\Internal\RepeatedField
     */
    public function getTerms()
    {
        return $this->terms;
    }

    /**
     * Generated from protobuf field <code>repeated string terms = 3;</code>
     * @param array<string>|\Google\Protobuf\Internal\RepeatedField $var
     * @return $this
     */
    public function setTerms($var)
    {
        $arr = GPBUtil::checkRepeatedField($var, \Google\Protobuf\Internal\GPBType::STRING);
        $this->terms = $arr;

        return $this;
    }

}
