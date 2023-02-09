<?php
# Generated by the protocol buffer compiler.  DO NOT EDIT!
# source: couchbase/transactions.v1.proto

namespace Couchbase\Protostellar\Generated\Transactions\V1;

use Google\Protobuf\Internal\GPBType;
use Google\Protobuf\Internal\RepeatedField;
use Google\Protobuf\Internal\GPBUtil;

/**
 * Generated from protobuf message <code>couchbase.transactions.v1.TransactionInsertRequest</code>
 */
class TransactionInsertRequest extends \Google\Protobuf\Internal\Message
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
     * Generated from protobuf field <code>string scope_name = 4;</code>
     */
    protected $scope_name = '';
    /**
     * Generated from protobuf field <code>string collection_name = 5;</code>
     */
    protected $collection_name = '';
    /**
     * Generated from protobuf field <code>string key = 6;</code>
     */
    protected $key = '';
    /**
     * Generated from protobuf field <code>bytes value = 7;</code>
     */
    protected $value = '';

    /**
     * Constructor.
     *
     * @param array $data {
     *     Optional. Data for populating the Message object.
     *
     *     @type string $bucket_name
     *     @type string $transaction_id
     *     @type string $attempt_id
     *     @type string $scope_name
     *     @type string $collection_name
     *     @type string $key
     *     @type string $value
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

    /**
     * Generated from protobuf field <code>string scope_name = 4;</code>
     * @return string
     */
    public function getScopeName()
    {
        return $this->scope_name;
    }

    /**
     * Generated from protobuf field <code>string scope_name = 4;</code>
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
     * Generated from protobuf field <code>string collection_name = 5;</code>
     * @return string
     */
    public function getCollectionName()
    {
        return $this->collection_name;
    }

    /**
     * Generated from protobuf field <code>string collection_name = 5;</code>
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
     * Generated from protobuf field <code>string key = 6;</code>
     * @return string
     */
    public function getKey()
    {
        return $this->key;
    }

    /**
     * Generated from protobuf field <code>string key = 6;</code>
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
     * Generated from protobuf field <code>bytes value = 7;</code>
     * @return string
     */
    public function getValue()
    {
        return $this->value;
    }

    /**
     * Generated from protobuf field <code>bytes value = 7;</code>
     * @param string $var
     * @return $this
     */
    public function setValue($var)
    {
        GPBUtil::checkString($var, False);
        $this->value = $var;

        return $this;
    }

}

