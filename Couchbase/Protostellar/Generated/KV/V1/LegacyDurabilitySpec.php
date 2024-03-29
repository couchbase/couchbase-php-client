<?php
# Generated by the protocol buffer compiler.  DO NOT EDIT!
# source: couchbase/kv/v1/kv.proto

namespace Couchbase\Protostellar\Generated\KV\V1;

use Google\Protobuf\Internal\GPBType;
use Google\Protobuf\Internal\RepeatedField;
use Google\Protobuf\Internal\GPBUtil;

/**
 * Generated from protobuf message <code>couchbase.kv.v1.LegacyDurabilitySpec</code>
 */
class LegacyDurabilitySpec extends \Google\Protobuf\Internal\Message
{
    /**
     * Generated from protobuf field <code>uint32 num_replicated = 1;</code>
     */
    protected $num_replicated = 0;
    /**
     * Generated from protobuf field <code>uint32 num_persisted = 2;</code>
     */
    protected $num_persisted = 0;

    /**
     * Constructor.
     *
     * @param array $data {
     *     Optional. Data for populating the Message object.
     *
     *     @type int $num_replicated
     *     @type int $num_persisted
     * }
     */
    public function __construct($data = NULL) {
        \GPBMetadata\Couchbase\Kv\V1\Kv::initOnce();
        parent::__construct($data);
    }

    /**
     * Generated from protobuf field <code>uint32 num_replicated = 1;</code>
     * @return int
     */
    public function getNumReplicated()
    {
        return $this->num_replicated;
    }

    /**
     * Generated from protobuf field <code>uint32 num_replicated = 1;</code>
     * @param int $var
     * @return $this
     */
    public function setNumReplicated($var)
    {
        GPBUtil::checkUint32($var);
        $this->num_replicated = $var;

        return $this;
    }

    /**
     * Generated from protobuf field <code>uint32 num_persisted = 2;</code>
     * @return int
     */
    public function getNumPersisted()
    {
        return $this->num_persisted;
    }

    /**
     * Generated from protobuf field <code>uint32 num_persisted = 2;</code>
     * @param int $var
     * @return $this
     */
    public function setNumPersisted($var)
    {
        GPBUtil::checkUint32($var);
        $this->num_persisted = $var;

        return $this;
    }

}

