<?php
# Generated by the protocol buffer compiler.  DO NOT EDIT!
# source: couchbase/kv.v1.proto

namespace Couchbase\Protostellar\Generated\KV\V1\RangeScanRequest;

use Google\Protobuf\Internal\GPBType;
use Google\Protobuf\Internal\RepeatedField;
use Google\Protobuf\Internal\GPBUtil;

/**
 * Generated from protobuf message <code>couchbase.kv.v1.RangeScanRequest.SnapshotRequirements</code>
 */
class SnapshotRequirements extends \Google\Protobuf\Internal\Message
{
    /**
     * Generated from protobuf field <code>uint64 vb_uuid = 1;</code>
     */
    protected $vb_uuid = 0;
    /**
     * Generated from protobuf field <code>uint64 seqno = 2;</code>
     */
    protected $seqno = 0;
    /**
     * Generated from protobuf field <code>bool check_seqno_exists = 3;</code>
     */
    protected $check_seqno_exists = false;

    /**
     * Constructor.
     *
     * @param array $data {
     *     Optional. Data for populating the Message object.
     *
     *     @type int|string $vb_uuid
     *     @type int|string $seqno
     *     @type bool $check_seqno_exists
     * }
     */
    public function __construct($data = NULL) {
        \GPBMetadata\Couchbase\KvV1::initOnce();
        parent::__construct($data);
    }

    /**
     * Generated from protobuf field <code>uint64 vb_uuid = 1;</code>
     * @return int|string
     */
    public function getVbUuid()
    {
        return $this->vb_uuid;
    }

    /**
     * Generated from protobuf field <code>uint64 vb_uuid = 1;</code>
     * @param int|string $var
     * @return $this
     */
    public function setVbUuid($var)
    {
        GPBUtil::checkUint64($var);
        $this->vb_uuid = $var;

        return $this;
    }

    /**
     * Generated from protobuf field <code>uint64 seqno = 2;</code>
     * @return int|string
     */
    public function getSeqno()
    {
        return $this->seqno;
    }

    /**
     * Generated from protobuf field <code>uint64 seqno = 2;</code>
     * @param int|string $var
     * @return $this
     */
    public function setSeqno($var)
    {
        GPBUtil::checkUint64($var);
        $this->seqno = $var;

        return $this;
    }

    /**
     * Generated from protobuf field <code>bool check_seqno_exists = 3;</code>
     * @return bool
     */
    public function getCheckSeqnoExists()
    {
        return $this->check_seqno_exists;
    }

    /**
     * Generated from protobuf field <code>bool check_seqno_exists = 3;</code>
     * @param bool $var
     * @return $this
     */
    public function setCheckSeqnoExists($var)
    {
        GPBUtil::checkBool($var);
        $this->check_seqno_exists = $var;

        return $this;
    }

}

// Adding a class alias for backwards compatibility with the previous class name.
class_alias(SnapshotRequirements::class, \Couchbase\Protostellar\Generated\KV\V1\RangeScanRequest_SnapshotRequirements::class);

