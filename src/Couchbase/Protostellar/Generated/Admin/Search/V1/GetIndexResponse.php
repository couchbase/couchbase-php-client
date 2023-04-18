<?php
# Generated by the protocol buffer compiler.  DO NOT EDIT!
# source: couchbase/admin/searchindex/v1/searchindex.proto

namespace Couchbase\Protostellar\Generated\Admin\Search\V1;

use Google\Protobuf\Internal\GPBType;
use Google\Protobuf\Internal\RepeatedField;
use Google\Protobuf\Internal\GPBUtil;

/**
 * Generated from protobuf message <code>couchbase.admin.search.v1.GetIndexResponse</code>
 */
class GetIndexResponse extends \Google\Protobuf\Internal\Message
{
    /**
     * Generated from protobuf field <code>string name = 1;</code>
     */
    protected $name = '';
    /**
     * Generated from protobuf field <code>map<string, bytes> params = 2;</code>
     */
    private $params;
    /**
     * Generated from protobuf field <code>map<string, bytes> plan_params = 3;</code>
     */
    private $plan_params;
    /**
     * Generated from protobuf field <code>optional string source_name = 4;</code>
     */
    protected $source_name = null;
    /**
     * Generated from protobuf field <code>map<string, bytes> source_params = 5;</code>
     */
    private $source_params;
    /**
     * Generated from protobuf field <code>optional string source_type = 6;</code>
     */
    protected $source_type = null;
    /**
     * Generated from protobuf field <code>optional string source_uuid = 7;</code>
     */
    protected $source_uuid = null;
    /**
     * Generated from protobuf field <code>string type = 8;</code>
     */
    protected $type = '';
    /**
     * Generated from protobuf field <code>optional string uuid = 9;</code>
     */
    protected $uuid = null;

    /**
     * Constructor.
     *
     * @param array $data {
     *     Optional. Data for populating the Message object.
     *
     *     @type string $name
     *     @type array|\Google\Protobuf\Internal\MapField $params
     *     @type array|\Google\Protobuf\Internal\MapField $plan_params
     *     @type string $source_name
     *     @type array|\Google\Protobuf\Internal\MapField $source_params
     *     @type string $source_type
     *     @type string $source_uuid
     *     @type string $type
     *     @type string $uuid
     * }
     */
    public function __construct($data = NULL) {
        \GPBMetadata\Couchbase\Admin\Searchindex\V1\Searchindex::initOnce();
        parent::__construct($data);
    }

    /**
     * Generated from protobuf field <code>string name = 1;</code>
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Generated from protobuf field <code>string name = 1;</code>
     * @param string $var
     * @return $this
     */
    public function setName($var)
    {
        GPBUtil::checkString($var, True);
        $this->name = $var;

        return $this;
    }

    /**
     * Generated from protobuf field <code>map<string, bytes> params = 2;</code>
     * @return \Google\Protobuf\Internal\MapField
     */
    public function getParams()
    {
        return $this->params;
    }

    /**
     * Generated from protobuf field <code>map<string, bytes> params = 2;</code>
     * @param array|\Google\Protobuf\Internal\MapField $var
     * @return $this
     */
    public function setParams($var)
    {
        $arr = GPBUtil::checkMapField($var, \Google\Protobuf\Internal\GPBType::STRING, \Google\Protobuf\Internal\GPBType::BYTES);
        $this->params = $arr;

        return $this;
    }

    /**
     * Generated from protobuf field <code>map<string, bytes> plan_params = 3;</code>
     * @return \Google\Protobuf\Internal\MapField
     */
    public function getPlanParams()
    {
        return $this->plan_params;
    }

    /**
     * Generated from protobuf field <code>map<string, bytes> plan_params = 3;</code>
     * @param array|\Google\Protobuf\Internal\MapField $var
     * @return $this
     */
    public function setPlanParams($var)
    {
        $arr = GPBUtil::checkMapField($var, \Google\Protobuf\Internal\GPBType::STRING, \Google\Protobuf\Internal\GPBType::BYTES);
        $this->plan_params = $arr;

        return $this;
    }

    /**
     * Generated from protobuf field <code>optional string source_name = 4;</code>
     * @return string
     */
    public function getSourceName()
    {
        return isset($this->source_name) ? $this->source_name : '';
    }

    public function hasSourceName()
    {
        return isset($this->source_name);
    }

    public function clearSourceName()
    {
        unset($this->source_name);
    }

    /**
     * Generated from protobuf field <code>optional string source_name = 4;</code>
     * @param string $var
     * @return $this
     */
    public function setSourceName($var)
    {
        GPBUtil::checkString($var, True);
        $this->source_name = $var;

        return $this;
    }

    /**
     * Generated from protobuf field <code>map<string, bytes> source_params = 5;</code>
     * @return \Google\Protobuf\Internal\MapField
     */
    public function getSourceParams()
    {
        return $this->source_params;
    }

    /**
     * Generated from protobuf field <code>map<string, bytes> source_params = 5;</code>
     * @param array|\Google\Protobuf\Internal\MapField $var
     * @return $this
     */
    public function setSourceParams($var)
    {
        $arr = GPBUtil::checkMapField($var, \Google\Protobuf\Internal\GPBType::STRING, \Google\Protobuf\Internal\GPBType::BYTES);
        $this->source_params = $arr;

        return $this;
    }

    /**
     * Generated from protobuf field <code>optional string source_type = 6;</code>
     * @return string
     */
    public function getSourceType()
    {
        return isset($this->source_type) ? $this->source_type : '';
    }

    public function hasSourceType()
    {
        return isset($this->source_type);
    }

    public function clearSourceType()
    {
        unset($this->source_type);
    }

    /**
     * Generated from protobuf field <code>optional string source_type = 6;</code>
     * @param string $var
     * @return $this
     */
    public function setSourceType($var)
    {
        GPBUtil::checkString($var, True);
        $this->source_type = $var;

        return $this;
    }

    /**
     * Generated from protobuf field <code>optional string source_uuid = 7;</code>
     * @return string
     */
    public function getSourceUuid()
    {
        return isset($this->source_uuid) ? $this->source_uuid : '';
    }

    public function hasSourceUuid()
    {
        return isset($this->source_uuid);
    }

    public function clearSourceUuid()
    {
        unset($this->source_uuid);
    }

    /**
     * Generated from protobuf field <code>optional string source_uuid = 7;</code>
     * @param string $var
     * @return $this
     */
    public function setSourceUuid($var)
    {
        GPBUtil::checkString($var, True);
        $this->source_uuid = $var;

        return $this;
    }

    /**
     * Generated from protobuf field <code>string type = 8;</code>
     * @return string
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * Generated from protobuf field <code>string type = 8;</code>
     * @param string $var
     * @return $this
     */
    public function setType($var)
    {
        GPBUtil::checkString($var, True);
        $this->type = $var;

        return $this;
    }

    /**
     * Generated from protobuf field <code>optional string uuid = 9;</code>
     * @return string
     */
    public function getUuid()
    {
        return isset($this->uuid) ? $this->uuid : '';
    }

    public function hasUuid()
    {
        return isset($this->uuid);
    }

    public function clearUuid()
    {
        unset($this->uuid);
    }

    /**
     * Generated from protobuf field <code>optional string uuid = 9;</code>
     * @param string $var
     * @return $this
     */
    public function setUuid($var)
    {
        GPBUtil::checkString($var, True);
        $this->uuid = $var;

        return $this;
    }

}

