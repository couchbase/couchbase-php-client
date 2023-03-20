<?php
# Generated by the protocol buffer compiler.  DO NOT EDIT!
# source: couchbase/transactions/v1/transactions.proto

namespace Couchbase\Protostellar\Generated\Transactions\V1;

use Google\Protobuf\Internal\GPBType;
use Google\Protobuf\Internal\RepeatedField;
use Google\Protobuf\Internal\GPBUtil;

/**
 * Generated from protobuf message <code>couchbase.transactions.v1.TransactionGetRequest</code>
 */
class TransactionGetRequest extends \Google\Protobuf\Internal\Message
{
    /**
     * Generated from protobuf field <code>string bucket_name = 1;</code>
     */
    protected $bucket_name = '';
    /**
     * transaction_id and attempt_id are optional to allow read-only transactions.
     * clients are not permitted to use this functionality to optimize
     * non-read-only transactions.
     *
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
     * Constructor.
     *
     * @param array $data {
     *     Optional. Data for populating the Message object.
     *
     *     @type string $bucket_name
     *     @type string $transaction_id
     *           transaction_id and attempt_id are optional to allow read-only transactions.
     *           clients are not permitted to use this functionality to optimize
     *           non-read-only transactions.
     *     @type string $attempt_id
     *     @type string $scope_name
     *     @type string $collection_name
     *     @type string $key
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
     * transaction_id and attempt_id are optional to allow read-only transactions.
     * clients are not permitted to use this functionality to optimize
     * non-read-only transactions.
     *
     * Generated from protobuf field <code>string transaction_id = 2;</code>
     * @return string
     */
    public function getTransactionId()
    {
        return $this->transaction_id;
    }

    /**
     * transaction_id and attempt_id are optional to allow read-only transactions.
     * clients are not permitted to use this functionality to optimize
     * non-read-only transactions.
     *
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

}

