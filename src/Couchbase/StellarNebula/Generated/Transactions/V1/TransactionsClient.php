<?php

// GENERATED CODE -- DO NOT EDIT!

namespace Couchbase\StellarNebula\Generated\Transactions\V1;

/**
 */
class TransactionsClient extends \Grpc\BaseStub
{
    /**
     * @param string $hostname hostname
     * @param array $opts channel options
     * @param \Grpc\Channel $channel (optional) re-use channel object
     */
    public function __construct($hostname, $opts, $channel = null)
    {
        parent::__construct($hostname, $opts, $channel);
    }

    /**
     * @param \Couchbase\StellarNebula\Generated\Transactions\V1\TransactionBeginAttemptRequest $argument input argument
     * @param array $metadata metadata
     * @param array $options call options
     * @return \Grpc\UnaryCall
     */
    public function TransactionBeginAttempt(
        \Couchbase\StellarNebula\Generated\Transactions\V1\TransactionBeginAttemptRequest $argument,
        $metadata = [],
        $options = []
    )
    {
        return $this->_simpleRequest(
            '/couchbase.transactions.v1.Transactions/TransactionBeginAttempt',
            $argument,
            ['\Couchbase\StellarNebula\Generated\Transactions\V1\TransactionBeginAttemptResponse', 'decode'],
            $metadata,
            $options
        );
    }

    /**
     * @param \Couchbase\StellarNebula\Generated\Transactions\V1\TransactionCommitRequest $argument input argument
     * @param array $metadata metadata
     * @param array $options call options
     * @return \Grpc\UnaryCall
     */
    public function TransactionCommit(
        \Couchbase\StellarNebula\Generated\Transactions\V1\TransactionCommitRequest $argument,
        $metadata = [],
        $options = []
    )
    {
        return $this->_simpleRequest(
            '/couchbase.transactions.v1.Transactions/TransactionCommit',
            $argument,
            ['\Couchbase\StellarNebula\Generated\Transactions\V1\TransactionCommitResponse', 'decode'],
            $metadata,
            $options
        );
    }

    /**
     * @param \Couchbase\StellarNebula\Generated\Transactions\V1\TransactionRollbackRequest $argument input argument
     * @param array $metadata metadata
     * @param array $options call options
     * @return \Grpc\UnaryCall
     */
    public function TransactionRollback(
        \Couchbase\StellarNebula\Generated\Transactions\V1\TransactionRollbackRequest $argument,
        $metadata = [],
        $options = []
    )
    {
        return $this->_simpleRequest(
            '/couchbase.transactions.v1.Transactions/TransactionRollback',
            $argument,
            ['\Couchbase\StellarNebula\Generated\Transactions\V1\TransactionRollbackResponse', 'decode'],
            $metadata,
            $options
        );
    }

    /**
     * @param \Couchbase\StellarNebula\Generated\Transactions\V1\TransactionGetRequest $argument input argument
     * @param array $metadata metadata
     * @param array $options call options
     * @return \Grpc\UnaryCall
     */
    public function TransactionGet(
        \Couchbase\StellarNebula\Generated\Transactions\V1\TransactionGetRequest $argument,
        $metadata = [],
        $options = []
    )
    {
        return $this->_simpleRequest(
            '/couchbase.transactions.v1.Transactions/TransactionGet',
            $argument,
            ['\Couchbase\StellarNebula\Generated\Transactions\V1\TransactionGetResponse', 'decode'],
            $metadata,
            $options
        );
    }

    /**
     * @param \Couchbase\StellarNebula\Generated\Transactions\V1\TransactionInsertRequest $argument input argument
     * @param array $metadata metadata
     * @param array $options call options
     * @return \Grpc\UnaryCall
     */
    public function TransactionInsert(
        \Couchbase\StellarNebula\Generated\Transactions\V1\TransactionInsertRequest $argument,
        $metadata = [],
        $options = []
    )
    {
        return $this->_simpleRequest(
            '/couchbase.transactions.v1.Transactions/TransactionInsert',
            $argument,
            ['\Couchbase\StellarNebula\Generated\Transactions\V1\TransactionInsertResponse', 'decode'],
            $metadata,
            $options
        );
    }

    /**
     * @param \Couchbase\StellarNebula\Generated\Transactions\V1\TransactionReplaceRequest $argument input argument
     * @param array $metadata metadata
     * @param array $options call options
     * @return \Grpc\UnaryCall
     */
    public function TransactionReplace(
        \Couchbase\StellarNebula\Generated\Transactions\V1\TransactionReplaceRequest $argument,
        $metadata = [],
        $options = []
    )
    {
        return $this->_simpleRequest(
            '/couchbase.transactions.v1.Transactions/TransactionReplace',
            $argument,
            ['\Couchbase\StellarNebula\Generated\Transactions\V1\TransactionReplaceResponse', 'decode'],
            $metadata,
            $options
        );
    }

    /**
     * @param \Couchbase\StellarNebula\Generated\Transactions\V1\TransactionRemoveRequest $argument input argument
     * @param array $metadata metadata
     * @param array $options call options
     * @return \Grpc\UnaryCall
     */
    public function TransactionRemove(
        \Couchbase\StellarNebula\Generated\Transactions\V1\TransactionRemoveRequest $argument,
        $metadata = [],
        $options = []
    )
    {
        return $this->_simpleRequest(
            '/couchbase.transactions.v1.Transactions/TransactionRemove',
            $argument,
            ['\Couchbase\StellarNebula\Generated\Transactions\V1\TransactionRemoveResponse', 'decode'],
            $metadata,
            $options
        );
    }
}
