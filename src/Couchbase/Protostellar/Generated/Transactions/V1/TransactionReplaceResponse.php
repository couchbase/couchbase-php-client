<?php
# Generated by the protocol buffer compiler.  DO NOT EDIT!
# source: couchbase/transactions.v1.proto

namespace Couchbase\Protostellar\Generated\Transactions\V1;

use Google\Protobuf\Internal\GPBType;
use Google\Protobuf\Internal\RepeatedField;
use Google\Protobuf\Internal\GPBUtil;

/**
 * Generated from protobuf message <code>couchbase.transactions.v1.TransactionReplaceResponse</code>
 */
class TransactionReplaceResponse extends \Google\Protobuf\Internal\Message
{
    /**
     * Generated from protobuf field <code>uint64 cas = 1;</code>
     */
    protected $cas = 0;

    /**
     * Constructor.
     *
     * @param array $data {
     *     Optional. Data for populating the Message object.
     *
     *     @type int|string $cas
     * }
     */
    public function __construct($data = NULL) {
        \GPBMetadata\Couchbase\TransactionsV1::initOnce();
        parent::__construct($data);
    }

    /**
     * Generated from protobuf field <code>uint64 cas = 1;</code>
     * @return int|string
     */
    public function getCas()
    {
        return $this->cas;
    }

    /**
     * Generated from protobuf field <code>uint64 cas = 1;</code>
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
