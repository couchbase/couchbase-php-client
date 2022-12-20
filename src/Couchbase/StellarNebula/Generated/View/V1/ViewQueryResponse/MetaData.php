<?php
# Generated by the protocol buffer compiler.  DO NOT EDIT!
# source: couchbase/view.v1.proto

namespace Couchbase\StellarNebula\Generated\View\V1\ViewQueryResponse;

use Google\Protobuf\Internal\GPBType;
use Google\Protobuf\Internal\RepeatedField;
use Google\Protobuf\Internal\GPBUtil;

/**
 * Generated from protobuf message <code>couchbase.view.v1.ViewQueryResponse.MetaData</code>
 */
class MetaData extends \Google\Protobuf\Internal\Message
{
    /**
     * Generated from protobuf field <code>uint64 total_rows = 1;</code>
     */
    protected $total_rows = 0;
    /**
     * Generated from protobuf field <code>bytes debug = 2;</code>
     */
    protected $debug = '';

    /**
     * Constructor.
     *
     * @param array $data {
     *     Optional. Data for populating the Message object.
     *
     *     @type int|string $total_rows
     *     @type string $debug
     * }
     */
    public function __construct($data = NULL) {
        \GPBMetadata\Couchbase\ViewV1::initOnce();
        parent::__construct($data);
    }

    /**
     * Generated from protobuf field <code>uint64 total_rows = 1;</code>
     * @return int|string
     */
    public function getTotalRows()
    {
        return $this->total_rows;
    }

    /**
     * Generated from protobuf field <code>uint64 total_rows = 1;</code>
     * @param int|string $var
     * @return $this
     */
    public function setTotalRows($var)
    {
        GPBUtil::checkUint64($var);
        $this->total_rows = $var;

        return $this;
    }

    /**
     * Generated from protobuf field <code>bytes debug = 2;</code>
     * @return string
     */
    public function getDebug()
    {
        return $this->debug;
    }

    /**
     * Generated from protobuf field <code>bytes debug = 2;</code>
     * @param string $var
     * @return $this
     */
    public function setDebug($var)
    {
        GPBUtil::checkString($var, False);
        $this->debug = $var;

        return $this;
    }

}

// Adding a class alias for backwards compatibility with the previous class name.
class_alias(MetaData::class, \Couchbase\StellarNebula\Generated\View\V1\ViewQueryResponse_MetaData::class);

