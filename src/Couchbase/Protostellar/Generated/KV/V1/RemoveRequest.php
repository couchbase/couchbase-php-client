<?php
# Generated by the protocol buffer compiler.  DO NOT EDIT!
# source: couchbase/kv.v1.proto

namespace Couchbase\Protostellar\Generated\KV\V1;

use Google\Protobuf\Internal\GPBType;
use Google\Protobuf\Internal\RepeatedField;
use Google\Protobuf\Internal\GPBUtil;

/**
 * Generated from protobuf message <code>couchbase.kv.v1.RemoveRequest</code>
 */
class RemoveRequest extends \Google\Protobuf\Internal\Message
{
    /**
     * Generated from protobuf field <code>string bucket_name = 1;</code>
     */
    protected $bucket_name = '';
    /**
     * Generated from protobuf field <code>string scope_name = 2;</code>
     */
    protected $scope_name = '';
    /**
     * Generated from protobuf field <code>string collection_name = 3;</code>
     */
    protected $collection_name = '';
    /**
     * Generated from protobuf field <code>string key = 4;</code>
     */
    protected $key = '';
    /**
     * Generated from protobuf field <code>optional uint64 cas = 5;</code>
     */
    protected $cas = null;
    protected $durability_spec;

    /**
     * Constructor.
     *
     * @param array $data {
     *     Optional. Data for populating the Message object.
     *
     *     @type string $bucket_name
     *     @type string $scope_name
     *     @type string $collection_name
     *     @type string $key
     *     @type int|string $cas
     *     @type \Couchbase\Protostellar\Generated\KV\V1\LegacyDurabilitySpec $legacy_durability_spec
     *     @type int $durability_level
     * }
     */
    public function __construct($data = NULL) {
        \GPBMetadata\Couchbase\KvV1::initOnce();
        parent::__construct($data);
    }

    /**
     * Generated from protobuf field <code>string bucket_name = 1;</code>
     * @return string
     */
    public function getBucketName()
    {
        return $this->bucket_name;
    }

    /**
     * Generated from protobuf field <code>string bucket_name = 1;</code>
     * @param string $var
     * @return $this
     */
    public function setBucketName($var)
    {
        GPBUtil::checkString($var, True);
        $this->bucket_name = $var;

        return $this;
    }

    /**
     * Generated from protobuf field <code>string scope_name = 2;</code>
     * @return string
     */
    public function getScopeName()
    {
        return $this->scope_name;
    }

    /**
     * Generated from protobuf field <code>string scope_name = 2;</code>
     * @param string $var
     * @return $this
     */
    public function setScopeName($var)
    {
        GPBUtil::checkString($var, True);
        $this->scope_name = $var;

        return $this;
    }

    /**
     * Generated from protobuf field <code>string collection_name = 3;</code>
     * @return string
     */
    public function getCollectionName()
    {
        return $this->collection_name;
    }

    /**
     * Generated from protobuf field <code>string collection_name = 3;</code>
     * @param string $var
     * @return $this
     */
    public function setCollectionName($var)
    {
        GPBUtil::checkString($var, True);
        $this->collection_name = $var;

        return $this;
    }

    /**
     * Generated from protobuf field <code>string key = 4;</code>
     * @return string
     */
    public function getKey()
    {
        return $this->key;
    }

    /**
     * Generated from protobuf field <code>string key = 4;</code>
     * @param string $var
     * @return $this
     */
    public function setKey($var)
    {
        GPBUtil::checkString($var, True);
        $this->key = $var;

        return $this;
    }

    /**
     * Generated from protobuf field <code>optional uint64 cas = 5;</code>
     * @return int|string
     */
    public function getCas()
    {
        return isset($this->cas) ? $this->cas : 0;
    }

    public function hasCas()
    {
        return isset($this->cas);
    }

    public function clearCas()
    {
        unset($this->cas);
    }

    /**
     * Generated from protobuf field <code>optional uint64 cas = 5;</code>
     * @param int|string $var
     * @return $this
     */
    public function setCas($var)
    {
        GPBUtil::checkUint64($var);
        $this->cas = $var;

        return $this;
    }

    /**
     * Generated from protobuf field <code>.couchbase.kv.v1.LegacyDurabilitySpec legacy_durability_spec = 6;</code>
     * @return \Couchbase\Protostellar\Generated\KV\V1\LegacyDurabilitySpec|null
     */
    public function getLegacyDurabilitySpec()
    {
        return $this->readOneof(6);
    }

    public function hasLegacyDurabilitySpec()
    {
        return $this->hasOneof(6);
    }

    /**
     * Generated from protobuf field <code>.couchbase.kv.v1.LegacyDurabilitySpec legacy_durability_spec = 6;</code>
     * @param \Couchbase\Protostellar\Generated\KV\V1\LegacyDurabilitySpec $var
     * @return $this
     */
    public function setLegacyDurabilitySpec($var)
    {
        GPBUtil::checkMessage($var, \Couchbase\Protostellar\Generated\KV\V1\LegacyDurabilitySpec::class);
        $this->writeOneof(6, $var);

        return $this;
    }

    /**
     * Generated from protobuf field <code>.couchbase.kv.v1.DurabilityLevel durability_level = 7;</code>
     * @return int
     */
    public function getDurabilityLevel()
    {
        return $this->readOneof(7);
    }

    public function hasDurabilityLevel()
    {
        return $this->hasOneof(7);
    }

    /**
     * Generated from protobuf field <code>.couchbase.kv.v1.DurabilityLevel durability_level = 7;</code>
     * @param int $var
     * @return $this
     */
    public function setDurabilityLevel($var)
    {
        GPBUtil::checkEnum($var, \Couchbase\Protostellar\Generated\KV\V1\DurabilityLevel::class);
        $this->writeOneof(7, $var);

        return $this;
    }

    /**
     * @return string
     */
    public function getDurabilitySpec()
    {
        return $this->whichOneof("durability_spec");
    }

}

