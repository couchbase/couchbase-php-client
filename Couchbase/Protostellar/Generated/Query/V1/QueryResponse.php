<?php
# Generated by the protocol buffer compiler.  DO NOT EDIT!
# source: couchbase/query/v1/query.proto

namespace Couchbase\Protostellar\Generated\Query\V1;

use Google\Protobuf\Internal\GPBType;
use Google\Protobuf\Internal\RepeatedField;
use Google\Protobuf\Internal\GPBUtil;

/**
 * Generated from protobuf message <code>couchbase.query.v1.QueryResponse</code>
 */
class QueryResponse extends \Google\Protobuf\Internal\Message
{
    /**
     * Generated from protobuf field <code>repeated bytes rows = 1;</code>
     */
    private $rows;
    /**
     * Generated from protobuf field <code>optional .couchbase.query.v1.QueryResponse.MetaData meta_data = 2;</code>
     */
    protected $meta_data = null;

    /**
     * Constructor.
     *
     * @param array $data {
     *     Optional. Data for populating the Message object.
     *
     *     @type array<string>|\Google\Protobuf\Internal\RepeatedField $rows
     *     @type \Couchbase\Protostellar\Generated\Query\V1\QueryResponse\MetaData $meta_data
     * }
     */
    public function __construct($data = NULL) {
        \GPBMetadata\Couchbase\Query\V1\Query::initOnce();
        parent::__construct($data);
    }

    /**
     * Generated from protobuf field <code>repeated bytes rows = 1;</code>
     * @return \Google\Protobuf\Internal\RepeatedField
     */
    public function getRows()
    {
        return $this->rows;
    }

    /**
     * Generated from protobuf field <code>repeated bytes rows = 1;</code>
     * @param array<string>|\Google\Protobuf\Internal\RepeatedField $var
     * @return $this
     */
    public function setRows($var)
    {
        $arr = GPBUtil::checkRepeatedField($var, \Google\Protobuf\Internal\GPBType::BYTES);
        $this->rows = $arr;

        return $this;
    }

    /**
     * Generated from protobuf field <code>optional .couchbase.query.v1.QueryResponse.MetaData meta_data = 2;</code>
     * @return \Couchbase\Protostellar\Generated\Query\V1\QueryResponse\MetaData|null
     */
    public function getMetaData()
    {
        return $this->meta_data;
    }

    public function hasMetaData()
    {
        return isset($this->meta_data);
    }

    public function clearMetaData()
    {
        unset($this->meta_data);
    }

    /**
     * Generated from protobuf field <code>optional .couchbase.query.v1.QueryResponse.MetaData meta_data = 2;</code>
     * @param \Couchbase\Protostellar\Generated\Query\V1\QueryResponse\MetaData $var
     * @return $this
     */
    public function setMetaData($var)
    {
        GPBUtil::checkMessage($var, \Couchbase\Protostellar\Generated\Query\V1\QueryResponse\MetaData::class);
        $this->meta_data = $var;

        return $this;
    }

}

