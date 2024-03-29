<?php
# Generated by the protocol buffer compiler.  DO NOT EDIT!
# source: couchbase/analytics/v1/analytics.proto

namespace Couchbase\Protostellar\Generated\Analytics\V1\AnalyticsQueryResponse;

use Google\Protobuf\Internal\GPBType;
use Google\Protobuf\Internal\RepeatedField;
use Google\Protobuf\Internal\GPBUtil;

/**
 * Generated from protobuf message <code>couchbase.analytics.v1.AnalyticsQueryResponse.Metrics</code>
 */
class Metrics extends \Google\Protobuf\Internal\Message
{
    /**
     * Generated from protobuf field <code>.google.protobuf.Duration elapsed_time = 1;</code>
     */
    protected $elapsed_time = null;
    /**
     * Generated from protobuf field <code>.google.protobuf.Duration execution_time = 2;</code>
     */
    protected $execution_time = null;
    /**
     * Generated from protobuf field <code>uint64 result_count = 3;</code>
     */
    protected $result_count = 0;
    /**
     * Generated from protobuf field <code>uint64 result_size = 4;</code>
     */
    protected $result_size = 0;
    /**
     * Generated from protobuf field <code>uint64 mutation_count = 5;</code>
     */
    protected $mutation_count = 0;
    /**
     * Generated from protobuf field <code>uint64 sort_count = 6;</code>
     */
    protected $sort_count = 0;
    /**
     * Generated from protobuf field <code>uint64 error_count = 7;</code>
     */
    protected $error_count = 0;
    /**
     * Generated from protobuf field <code>uint64 warning_count = 8;</code>
     */
    protected $warning_count = 0;
    /**
     * Generated from protobuf field <code>uint64 processed_objects = 9;</code>
     */
    protected $processed_objects = 0;

    /**
     * Constructor.
     *
     * @param array $data {
     *     Optional. Data for populating the Message object.
     *
     *     @type \Google\Protobuf\Duration $elapsed_time
     *     @type \Google\Protobuf\Duration $execution_time
     *     @type int|string $result_count
     *     @type int|string $result_size
     *     @type int|string $mutation_count
     *     @type int|string $sort_count
     *     @type int|string $error_count
     *     @type int|string $warning_count
     *     @type int|string $processed_objects
     * }
     */
    public function __construct($data = NULL) {
        \GPBMetadata\Couchbase\Analytics\V1\Analytics::initOnce();
        parent::__construct($data);
    }

    /**
     * Generated from protobuf field <code>.google.protobuf.Duration elapsed_time = 1;</code>
     * @return \Google\Protobuf\Duration|null
     */
    public function getElapsedTime()
    {
        return $this->elapsed_time;
    }

    public function hasElapsedTime()
    {
        return isset($this->elapsed_time);
    }

    public function clearElapsedTime()
    {
        unset($this->elapsed_time);
    }

    /**
     * Generated from protobuf field <code>.google.protobuf.Duration elapsed_time = 1;</code>
     * @param \Google\Protobuf\Duration $var
     * @return $this
     */
    public function setElapsedTime($var)
    {
        GPBUtil::checkMessage($var, \Google\Protobuf\Duration::class);
        $this->elapsed_time = $var;

        return $this;
    }

    /**
     * Generated from protobuf field <code>.google.protobuf.Duration execution_time = 2;</code>
     * @return \Google\Protobuf\Duration|null
     */
    public function getExecutionTime()
    {
        return $this->execution_time;
    }

    public function hasExecutionTime()
    {
        return isset($this->execution_time);
    }

    public function clearExecutionTime()
    {
        unset($this->execution_time);
    }

    /**
     * Generated from protobuf field <code>.google.protobuf.Duration execution_time = 2;</code>
     * @param \Google\Protobuf\Duration $var
     * @return $this
     */
    public function setExecutionTime($var)
    {
        GPBUtil::checkMessage($var, \Google\Protobuf\Duration::class);
        $this->execution_time = $var;

        return $this;
    }

    /**
     * Generated from protobuf field <code>uint64 result_count = 3;</code>
     * @return int|string
     */
    public function getResultCount()
    {
        return $this->result_count;
    }

    /**
     * Generated from protobuf field <code>uint64 result_count = 3;</code>
     * @param int|string $var
     * @return $this
     */
    public function setResultCount($var)
    {
        GPBUtil::checkUint64($var);
        $this->result_count = $var;

        return $this;
    }

    /**
     * Generated from protobuf field <code>uint64 result_size = 4;</code>
     * @return int|string
     */
    public function getResultSize()
    {
        return $this->result_size;
    }

    /**
     * Generated from protobuf field <code>uint64 result_size = 4;</code>
     * @param int|string $var
     * @return $this
     */
    public function setResultSize($var)
    {
        GPBUtil::checkUint64($var);
        $this->result_size = $var;

        return $this;
    }

    /**
     * Generated from protobuf field <code>uint64 mutation_count = 5;</code>
     * @return int|string
     */
    public function getMutationCount()
    {
        return $this->mutation_count;
    }

    /**
     * Generated from protobuf field <code>uint64 mutation_count = 5;</code>
     * @param int|string $var
     * @return $this
     */
    public function setMutationCount($var)
    {
        GPBUtil::checkUint64($var);
        $this->mutation_count = $var;

        return $this;
    }

    /**
     * Generated from protobuf field <code>uint64 sort_count = 6;</code>
     * @return int|string
     */
    public function getSortCount()
    {
        return $this->sort_count;
    }

    /**
     * Generated from protobuf field <code>uint64 sort_count = 6;</code>
     * @param int|string $var
     * @return $this
     */
    public function setSortCount($var)
    {
        GPBUtil::checkUint64($var);
        $this->sort_count = $var;

        return $this;
    }

    /**
     * Generated from protobuf field <code>uint64 error_count = 7;</code>
     * @return int|string
     */
    public function getErrorCount()
    {
        return $this->error_count;
    }

    /**
     * Generated from protobuf field <code>uint64 error_count = 7;</code>
     * @param int|string $var
     * @return $this
     */
    public function setErrorCount($var)
    {
        GPBUtil::checkUint64($var);
        $this->error_count = $var;

        return $this;
    }

    /**
     * Generated from protobuf field <code>uint64 warning_count = 8;</code>
     * @return int|string
     */
    public function getWarningCount()
    {
        return $this->warning_count;
    }

    /**
     * Generated from protobuf field <code>uint64 warning_count = 8;</code>
     * @param int|string $var
     * @return $this
     */
    public function setWarningCount($var)
    {
        GPBUtil::checkUint64($var);
        $this->warning_count = $var;

        return $this;
    }

    /**
     * Generated from protobuf field <code>uint64 processed_objects = 9;</code>
     * @return int|string
     */
    public function getProcessedObjects()
    {
        return $this->processed_objects;
    }

    /**
     * Generated from protobuf field <code>uint64 processed_objects = 9;</code>
     * @param int|string $var
     * @return $this
     */
    public function setProcessedObjects($var)
    {
        GPBUtil::checkUint64($var);
        $this->processed_objects = $var;

        return $this;
    }

}

// Adding a class alias for backwards compatibility with the previous class name.
class_alias(Metrics::class, \Couchbase\Protostellar\Generated\Analytics\V1\AnalyticsQueryResponse_Metrics::class);

