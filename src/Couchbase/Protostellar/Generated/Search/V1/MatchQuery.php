<?php
# Generated by the protocol buffer compiler.  DO NOT EDIT!
# source: couchbase/search.v1.proto

namespace Couchbase\Protostellar\Generated\Search\V1;

use Google\Protobuf\Internal\GPBType;
use Google\Protobuf\Internal\RepeatedField;
use Google\Protobuf\Internal\GPBUtil;

/**
 * Generated from protobuf message <code>couchbase.search.v1.MatchQuery</code>
 */
class MatchQuery extends \Google\Protobuf\Internal\Message
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
     * Generated from protobuf field <code>string value = 3;</code>
     */
    protected $value = '';
    /**
     * Generated from protobuf field <code>string analyzer = 4;</code>
     */
    protected $analyzer = '';
    /**
     * Generated from protobuf field <code>uint64 fuzziness = 5;</code>
     */
    protected $fuzziness = 0;
    /**
     * Generated from protobuf field <code>.couchbase.search.v1.MatchQuery.Operator operator = 6;</code>
     */
    protected $operator = 0;
    /**
     * Generated from protobuf field <code>uint64 prefix_length = 7;</code>
     */
    protected $prefix_length = 0;

    /**
     * Constructor.
     *
     * @param array $data {
     *     Optional. Data for populating the Message object.
     *
     *     @type float $boost
     *     @type string $field
     *     @type string $value
     *     @type string $analyzer
     *     @type int|string $fuzziness
     *     @type int $operator
     *     @type int|string $prefix_length
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
     * Generated from protobuf field <code>string value = 3;</code>
     * @return string
     */
    public function getValue()
    {
        return $this->value;
    }

    /**
     * Generated from protobuf field <code>string value = 3;</code>
     * @param string $var
     * @return $this
     */
    public function setValue($var)
    {
        GPBUtil::checkString($var, True);
        $this->value = $var;

        return $this;
    }

    /**
     * Generated from protobuf field <code>string analyzer = 4;</code>
     * @return string
     */
    public function getAnalyzer()
    {
        return $this->analyzer;
    }

    /**
     * Generated from protobuf field <code>string analyzer = 4;</code>
     * @param string $var
     * @return $this
     */
    public function setAnalyzer($var)
    {
        GPBUtil::checkString($var, True);
        $this->analyzer = $var;

        return $this;
    }

    /**
     * Generated from protobuf field <code>uint64 fuzziness = 5;</code>
     * @return int|string
     */
    public function getFuzziness()
    {
        return $this->fuzziness;
    }

    /**
     * Generated from protobuf field <code>uint64 fuzziness = 5;</code>
     * @param int|string $var
     * @return $this
     */
    public function setFuzziness($var)
    {
        GPBUtil::checkUint64($var);
        $this->fuzziness = $var;

        return $this;
    }

    /**
     * Generated from protobuf field <code>.couchbase.search.v1.MatchQuery.Operator operator = 6;</code>
     * @return int
     */
    public function getOperator()
    {
        return $this->operator;
    }

    /**
     * Generated from protobuf field <code>.couchbase.search.v1.MatchQuery.Operator operator = 6;</code>
     * @param int $var
     * @return $this
     */
    public function setOperator($var)
    {
        GPBUtil::checkEnum($var, \Couchbase\Protostellar\Generated\Search\V1\MatchQuery\Operator::class);
        $this->operator = $var;

        return $this;
    }

    /**
     * Generated from protobuf field <code>uint64 prefix_length = 7;</code>
     * @return int|string
     */
    public function getPrefixLength()
    {
        return $this->prefix_length;
    }

    /**
     * Generated from protobuf field <code>uint64 prefix_length = 7;</code>
     * @param int|string $var
     * @return $this
     */
    public function setPrefixLength($var)
    {
        GPBUtil::checkUint64($var);
        $this->prefix_length = $var;

        return $this;
    }

}

