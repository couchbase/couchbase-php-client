<?php
# Generated by the protocol buffer compiler.  DO NOT EDIT!
# source: couchbase/kv/v1/kv.proto

namespace Couchbase\Protostellar\Generated\KV\V1;

use Google\Protobuf\Internal\GPBType;
use Google\Protobuf\Internal\RepeatedField;
use Google\Protobuf\Internal\GPBUtil;

/**
 * Generated from protobuf message <code>couchbase.kv.v1.LookupInResponse</code>
 */
class LookupInResponse extends \Google\Protobuf\Internal\Message
{
    /**
     * Generated from protobuf field <code>repeated .couchbase.kv.v1.LookupInResponse.Spec specs = 1;</code>
     */
    private $specs;
    /**
     * Generated from protobuf field <code>uint64 cas = 2;</code>
     */
    protected $cas = 0;

    /**
     * Constructor.
     *
     * @param array $data {
     *     Optional. Data for populating the Message object.
     *
     *     @type array<\Couchbase\Protostellar\Generated\KV\V1\LookupInResponse\Spec>|\Google\Protobuf\Internal\RepeatedField $specs
     *     @type int|string $cas
     * }
     */
    public function __construct($data = NULL) {
        \GPBMetadata\Couchbase\Kv\V1\Kv::initOnce();
        parent::__construct($data);
    }

    /**
     * Generated from protobuf field <code>repeated .couchbase.kv.v1.LookupInResponse.Spec specs = 1;</code>
     * @return \Google\Protobuf\Internal\RepeatedField
     */
    public function getSpecs()
    {
        return $this->specs;
    }

    /**
     * Generated from protobuf field <code>repeated .couchbase.kv.v1.LookupInResponse.Spec specs = 1;</code>
     * @param array<\Couchbase\Protostellar\Generated\KV\V1\LookupInResponse\Spec>|\Google\Protobuf\Internal\RepeatedField $var
     * @return $this
     */
    public function setSpecs($var)
    {
        $arr = GPBUtil::checkRepeatedField($var, \Google\Protobuf\Internal\GPBType::MESSAGE, \Couchbase\Protostellar\Generated\KV\V1\LookupInResponse\Spec::class);
        $this->specs = $arr;

        return $this;
    }

    /**
     * Generated from protobuf field <code>uint64 cas = 2;</code>
     * @return int|string
     */
    public function getCas()
    {
        return $this->cas;
    }

    /**
     * Generated from protobuf field <code>uint64 cas = 2;</code>
     * @param int|string $var
     * @return $this
     */
    public function setCas($var)
    {
        GPBUtil::checkUint64($var);
        $this->cas = $var;

        return $this;
    }

}

