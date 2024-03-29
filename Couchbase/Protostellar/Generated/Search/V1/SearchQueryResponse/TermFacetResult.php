<?php
# Generated by the protocol buffer compiler.  DO NOT EDIT!
# source: couchbase/search/v1/search.proto

namespace Couchbase\Protostellar\Generated\Search\V1\SearchQueryResponse;

use Google\Protobuf\Internal\GPBType;
use Google\Protobuf\Internal\RepeatedField;
use Google\Protobuf\Internal\GPBUtil;

/**
 * Generated from protobuf message <code>couchbase.search.v1.SearchQueryResponse.TermFacetResult</code>
 */
class TermFacetResult extends \Google\Protobuf\Internal\Message
{
    /**
     * Generated from protobuf field <code>string field = 2;</code>
     */
    protected $field = '';
    /**
     * Generated from protobuf field <code>int64 total = 3;</code>
     */
    protected $total = 0;
    /**
     * Generated from protobuf field <code>int64 missing = 4;</code>
     */
    protected $missing = 0;
    /**
     * Generated from protobuf field <code>int64 other = 5;</code>
     */
    protected $other = 0;
    /**
     * Generated from protobuf field <code>repeated .couchbase.search.v1.SearchQueryResponse.TermResult terms = 6;</code>
     */
    private $terms;

    /**
     * Constructor.
     *
     * @param array $data {
     *     Optional. Data for populating the Message object.
     *
     *     @type string $field
     *     @type int|string $total
     *     @type int|string $missing
     *     @type int|string $other
     *     @type array<\Couchbase\Protostellar\Generated\Search\V1\SearchQueryResponse\TermResult>|\Google\Protobuf\Internal\RepeatedField $terms
     * }
     */
    public function __construct($data = NULL) {
        \GPBMetadata\Couchbase\Search\V1\Search::initOnce();
        parent::__construct($data);
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
     * Generated from protobuf field <code>int64 total = 3;</code>
     * @return int|string
     */
    public function getTotal()
    {
        return $this->total;
    }

    /**
     * Generated from protobuf field <code>int64 total = 3;</code>
     * @param int|string $var
     * @return $this
     */
    public function setTotal($var)
    {
        GPBUtil::checkInt64($var);
        $this->total = $var;

        return $this;
    }

    /**
     * Generated from protobuf field <code>int64 missing = 4;</code>
     * @return int|string
     */
    public function getMissing()
    {
        return $this->missing;
    }

    /**
     * Generated from protobuf field <code>int64 missing = 4;</code>
     * @param int|string $var
     * @return $this
     */
    public function setMissing($var)
    {
        GPBUtil::checkInt64($var);
        $this->missing = $var;

        return $this;
    }

    /**
     * Generated from protobuf field <code>int64 other = 5;</code>
     * @return int|string
     */
    public function getOther()
    {
        return $this->other;
    }

    /**
     * Generated from protobuf field <code>int64 other = 5;</code>
     * @param int|string $var
     * @return $this
     */
    public function setOther($var)
    {
        GPBUtil::checkInt64($var);
        $this->other = $var;

        return $this;
    }

    /**
     * Generated from protobuf field <code>repeated .couchbase.search.v1.SearchQueryResponse.TermResult terms = 6;</code>
     * @return \Google\Protobuf\Internal\RepeatedField
     */
    public function getTerms()
    {
        return $this->terms;
    }

    /**
     * Generated from protobuf field <code>repeated .couchbase.search.v1.SearchQueryResponse.TermResult terms = 6;</code>
     * @param array<\Couchbase\Protostellar\Generated\Search\V1\SearchQueryResponse\TermResult>|\Google\Protobuf\Internal\RepeatedField $var
     * @return $this
     */
    public function setTerms($var)
    {
        $arr = GPBUtil::checkRepeatedField($var, \Google\Protobuf\Internal\GPBType::MESSAGE, \Couchbase\Protostellar\Generated\Search\V1\SearchQueryResponse\TermResult::class);
        $this->terms = $arr;

        return $this;
    }

}

// Adding a class alias for backwards compatibility with the previous class name.
class_alias(TermFacetResult::class, \Couchbase\Protostellar\Generated\Search\V1\SearchQueryResponse_TermFacetResult::class);

