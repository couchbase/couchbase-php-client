<?php
# Generated by the protocol buffer compiler.  DO NOT EDIT!
# source: couchbase/routing/v1/routing.proto

namespace Couchbase\Protostellar\Generated\Routing\V1;

use Google\Protobuf\Internal\GPBType;
use Google\Protobuf\Internal\RepeatedField;
use Google\Protobuf\Internal\GPBUtil;

/**
 * Generated from protobuf message <code>couchbase.routing.v1.QueryRouting</code>
 */
class QueryRouting extends \Google\Protobuf\Internal\Message
{
    /**
     * Generated from protobuf field <code>repeated .couchbase.routing.v1.QueryRoutingEndpoint endpoints = 1;</code>
     */
    private $endpoints;

    /**
     * Constructor.
     *
     * @param array $data {
     *     Optional. Data for populating the Message object.
     *
     *     @type array<\Couchbase\Protostellar\Generated\Routing\V1\QueryRoutingEndpoint>|\Google\Protobuf\Internal\RepeatedField $endpoints
     * }
     */
    public function __construct($data = NULL) {
        \GPBMetadata\Couchbase\Routing\V1\Routing::initOnce();
        parent::__construct($data);
    }

    /**
     * Generated from protobuf field <code>repeated .couchbase.routing.v1.QueryRoutingEndpoint endpoints = 1;</code>
     * @return \Google\Protobuf\Internal\RepeatedField
     */
    public function getEndpoints()
    {
        return $this->endpoints;
    }

    /**
     * Generated from protobuf field <code>repeated .couchbase.routing.v1.QueryRoutingEndpoint endpoints = 1;</code>
     * @param array<\Couchbase\Protostellar\Generated\Routing\V1\QueryRoutingEndpoint>|\Google\Protobuf\Internal\RepeatedField $var
     * @return $this
     */
    public function setEndpoints($var)
    {
        $arr = GPBUtil::checkRepeatedField($var, \Google\Protobuf\Internal\GPBType::MESSAGE, \Couchbase\Protostellar\Generated\Routing\V1\QueryRoutingEndpoint::class);
        $this->endpoints = $arr;

        return $this;
    }

}

