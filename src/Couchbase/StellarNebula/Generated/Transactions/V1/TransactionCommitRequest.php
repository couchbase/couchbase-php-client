<?php
# Generated by the protocol buffer compiler.  DO NOT EDIT!
# source: couchbase/transactions.v1.proto

namespace Couchbase\StellarNebula\Generated\Transactions\V1;

use Google\Protobuf\Internal\GPBType;
use Google\Protobuf\Internal\RepeatedField;
use Google\Protobuf\Internal\GPBUtil;

/**
 * Generated from protobuf message <code>couchbase.transactions.v1.TransactionCommitRequest</code>
 */
class TransactionCommitRequest extends \Google\Protobuf\Internal\Message
{
    /**
     * Generated from protobuf field <code>string bucket_name = 1;</code>
     */
    protected $bucket_name = '';
    /**
     * Generated from protobuf field <code>string transaction_id = 2;</code>
     */
    protected $transaction_id = '';
    /**
     * Generated from protobuf field <code>string attempt_id = 3;</code>
     */
    protected $attempt_id = '';

    /**
     * Constructor.
     *
     * @param array $data {
     *     Optional. Data for populating the Message object.
     *
     *     @type string $bucket_name
     *     @type string $transaction_id
     *     @type string $attempt_id
     * }
     */
    public function __construct($data = NULL) {
        \GPBMetadata\Couchbase\TransactionsV1::initOnce();
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
     * Generated from protobuf field <code>string transaction_id = 2;</code>
     * @return string
     */
    public function getTransactionId()
    {
        return $this->transaction_id;
    }

    /**
     * Generated from protobuf field <code>string transaction_id = 2;</code>
     * @param string $var
     * @return $this
     */
    public function setTransactionId($var)
    {
        GPBUtil::checkString($var, True);
        $this->transaction_id = $var;

        return $this;
    }

    /**
     * Generated from protobuf field <code>string attempt_id = 3;</code>
     * @return string
     */
    public function getAttemptId()
    {
        return $this->attempt_id;
    }

    /**
     * Generated from protobuf field <code>string attempt_id = 3;</code>
     * @param string $var
     * @return $this
     */
    public function setAttemptId($var)
    {
        GPBUtil::checkString($var, True);
        $this->attempt_id = $var;

        return $this;
    }

}

