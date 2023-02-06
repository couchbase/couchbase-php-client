<?php

# Generated by the protocol buffer compiler.  DO NOT EDIT!
# source: couchbase/admin/collection.v1.proto

namespace Couchbase\StellarNebula\Generated\Admin\Collection\V1\ListCollectionsResponse;

use Google\Protobuf\Internal\GPBType;
use Google\Protobuf\Internal\RepeatedField;
use Google\Protobuf\Internal\GPBUtil;

/**
 * Generated from protobuf message <code>couchbase.admin.collection.v1.ListCollectionsResponse.Scope</code>
 */
class Scope extends \Google\Protobuf\Internal\Message
{
    /**
     * Generated from protobuf field <code>string name = 1;</code>
     */
    protected $name = '';
    /**
     * Generated from protobuf field <code>repeated .couchbase.admin.collection.v1.ListCollectionsResponse.Collection collections = 2;</code>
     */
    private $collections;

    /**
     * Constructor.
     *
     * @param array $data {
     *     Optional. Data for populating the Message object.
     *
     *     @type string $name
     *     @type \Couchbase\StellarNebula\Generated\Admin\Collection\V1\ListCollectionsResponse\Collection[]|\Google\Protobuf\Internal\RepeatedField $collections
     * }
     */
    public function __construct($data = null)
    {
        \GPBMetadata\Couchbase\Admin\CollectionV1::initOnce();
        parent::__construct($data);
    }

    /**
     * Generated from protobuf field <code>string name = 1;</code>
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Generated from protobuf field <code>string name = 1;</code>
     * @param string $var
     * @return $this
     */
    public function setName($var)
    {
        GPBUtil::checkString($var, true);
        $this->name = $var;

        return $this;
    }

    /**
     * Generated from protobuf field <code>repeated .couchbase.admin.collection.v1.ListCollectionsResponse.Collection collections = 2;</code>
     * @return \Google\Protobuf\Internal\RepeatedField
     */
    public function getCollections()
    {
        return $this->collections;
    }

    /**
     * Generated from protobuf field <code>repeated .couchbase.admin.collection.v1.ListCollectionsResponse.Collection collections = 2;</code>
     * @param \Couchbase\StellarNebula\Generated\Admin\Collection\V1\ListCollectionsResponse\Collection[]|\Google\Protobuf\Internal\RepeatedField $var
     * @return $this
     */
    public function setCollections($var)
    {
        $arr = GPBUtil::checkRepeatedField($var, \Google\Protobuf\Internal\GPBType::MESSAGE, \Couchbase\StellarNebula\Generated\Admin\Collection\V1\ListCollectionsResponse\Collection::class);
        $this->collections = $arr;

        return $this;
    }
}

// Adding a class alias for backwards compatibility with the previous class name.
class_alias(Scope::class, \Couchbase\StellarNebula\Generated\Admin\Collection\V1\ListCollectionsResponse_Scope::class);