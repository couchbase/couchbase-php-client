<?php
# Generated by the protocol buffer compiler.  DO NOT EDIT!
# source: couchbase/transactions/v1/transactions.proto

namespace Couchbase\Protostellar\Generated\Transactions\V1;

use Google\Protobuf\Internal\GPBType;
use Google\Protobuf\Internal\RepeatedField;
use Google\Protobuf\Internal\GPBUtil;

/**
 * Generated from protobuf message <code>couchbase.transactions.v1.TransactionBeginAttemptRequest</code>
 */
class TransactionBeginAttemptRequest extends \Google\Protobuf\Internal\Message
{
    /**
     * Generated from protobuf field <code>string bucket_name = 1;</code>
     */
    protected $bucket_name = '';
    /**
     * Generated from protobuf field <code>optional string transaction_id = 2;</code>
     */
    protected $transaction_id = null;

    /**
     * Constructor.
     *
     * @param array $data {
     *     Optional. Data for populating the Message object.
     *
     *     @type string $bucket_name
     *     @type string $transaction_id
     * }
     */
    public function __construct($data = NULL) {
        \GPBMetadata\Couchbase\Transactions\V1\Transactions::initOnce();
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
     * Generated from protobuf field <code>optional string transaction_id = 2;</code>
     * @return string
     */
    public function getTransactionId()
    {
        return isset($this->transaction_id) ? $this->transaction_id : '';
    }

    public function hasTransactionId()
    {
        return isset($this->transaction_id);
    }

    public function clearTransactionId()
    {
        unset($this->transaction_id);
    }

    /**
     * Generated from protobuf field <code>optional string transaction_id = 2;</code>
     * @param string $var
     * @return $this
     */
    public function setTransactionId($var)
    {
        GPBUtil::checkString($var, True);
        $this->transaction_id = $var;

        return $this;
    }

}

