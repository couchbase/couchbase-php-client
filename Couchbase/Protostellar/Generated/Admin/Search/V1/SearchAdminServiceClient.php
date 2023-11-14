<?php
// GENERATED CODE -- DO NOT EDIT!

namespace Couchbase\Protostellar\Generated\Admin\Search\V1;

/**
 */
class SearchAdminServiceClient extends \Grpc\BaseStub {

    /**
     * @param string $hostname hostname
     * @param array $opts channel options
     * @param \Grpc\Channel $channel (optional) re-use channel object
     */
    public function __construct($hostname, $opts, $channel = null) {
        parent::__construct($hostname, $opts, $channel);
    }

    /**
     * @param \Couchbase\Protostellar\Generated\Admin\Search\V1\GetIndexRequest $argument input argument
     * @param array $metadata metadata
     * @param array $options call options
     * @return \Grpc\UnaryCall
     */
    public function GetIndex(\Couchbase\Protostellar\Generated\Admin\Search\V1\GetIndexRequest $argument,
      $metadata = [], $options = []) {
        return $this->_simpleRequest('/couchbase.admin.search.v1.SearchAdminService/GetIndex',
        $argument,
        ['\Couchbase\Protostellar\Generated\Admin\Search\V1\GetIndexResponse', 'decode'],
        $metadata, $options);
    }

    /**
     * @param \Couchbase\Protostellar\Generated\Admin\Search\V1\ListIndexesRequest $argument input argument
     * @param array $metadata metadata
     * @param array $options call options
     * @return \Grpc\UnaryCall
     */
    public function ListIndexes(\Couchbase\Protostellar\Generated\Admin\Search\V1\ListIndexesRequest $argument,
      $metadata = [], $options = []) {
        return $this->_simpleRequest('/couchbase.admin.search.v1.SearchAdminService/ListIndexes',
        $argument,
        ['\Couchbase\Protostellar\Generated\Admin\Search\V1\ListIndexesResponse', 'decode'],
        $metadata, $options);
    }

    /**
     * @param \Couchbase\Protostellar\Generated\Admin\Search\V1\CreateIndexRequest $argument input argument
     * @param array $metadata metadata
     * @param array $options call options
     * @return \Grpc\UnaryCall
     */
    public function CreateIndex(\Couchbase\Protostellar\Generated\Admin\Search\V1\CreateIndexRequest $argument,
      $metadata = [], $options = []) {
        return $this->_simpleRequest('/couchbase.admin.search.v1.SearchAdminService/CreateIndex',
        $argument,
        ['\Couchbase\Protostellar\Generated\Admin\Search\V1\CreateIndexResponse', 'decode'],
        $metadata, $options);
    }

    /**
     * @param \Couchbase\Protostellar\Generated\Admin\Search\V1\UpdateIndexRequest $argument input argument
     * @param array $metadata metadata
     * @param array $options call options
     * @return \Grpc\UnaryCall
     */
    public function UpdateIndex(\Couchbase\Protostellar\Generated\Admin\Search\V1\UpdateIndexRequest $argument,
      $metadata = [], $options = []) {
        return $this->_simpleRequest('/couchbase.admin.search.v1.SearchAdminService/UpdateIndex',
        $argument,
        ['\Couchbase\Protostellar\Generated\Admin\Search\V1\UpdateIndexResponse', 'decode'],
        $metadata, $options);
    }

    /**
     * @param \Couchbase\Protostellar\Generated\Admin\Search\V1\DeleteIndexRequest $argument input argument
     * @param array $metadata metadata
     * @param array $options call options
     * @return \Grpc\UnaryCall
     */
    public function DeleteIndex(\Couchbase\Protostellar\Generated\Admin\Search\V1\DeleteIndexRequest $argument,
      $metadata = [], $options = []) {
        return $this->_simpleRequest('/couchbase.admin.search.v1.SearchAdminService/DeleteIndex',
        $argument,
        ['\Couchbase\Protostellar\Generated\Admin\Search\V1\DeleteIndexResponse', 'decode'],
        $metadata, $options);
    }

    /**
     * @param \Couchbase\Protostellar\Generated\Admin\Search\V1\AnalyzeDocumentRequest $argument input argument
     * @param array $metadata metadata
     * @param array $options call options
     * @return \Grpc\UnaryCall
     */
    public function AnalyzeDocument(\Couchbase\Protostellar\Generated\Admin\Search\V1\AnalyzeDocumentRequest $argument,
      $metadata = [], $options = []) {
        return $this->_simpleRequest('/couchbase.admin.search.v1.SearchAdminService/AnalyzeDocument',
        $argument,
        ['\Couchbase\Protostellar\Generated\Admin\Search\V1\AnalyzeDocumentResponse', 'decode'],
        $metadata, $options);
    }

    /**
     * @param \Couchbase\Protostellar\Generated\Admin\Search\V1\GetIndexedDocumentsCountRequest $argument input argument
     * @param array $metadata metadata
     * @param array $options call options
     * @return \Grpc\UnaryCall
     */
    public function GetIndexedDocumentsCount(\Couchbase\Protostellar\Generated\Admin\Search\V1\GetIndexedDocumentsCountRequest $argument,
      $metadata = [], $options = []) {
        return $this->_simpleRequest('/couchbase.admin.search.v1.SearchAdminService/GetIndexedDocumentsCount',
        $argument,
        ['\Couchbase\Protostellar\Generated\Admin\Search\V1\GetIndexedDocumentsCountResponse', 'decode'],
        $metadata, $options);
    }

    /**
     * @param \Couchbase\Protostellar\Generated\Admin\Search\V1\PauseIndexIngestRequest $argument input argument
     * @param array $metadata metadata
     * @param array $options call options
     * @return \Grpc\UnaryCall
     */
    public function PauseIndexIngest(\Couchbase\Protostellar\Generated\Admin\Search\V1\PauseIndexIngestRequest $argument,
      $metadata = [], $options = []) {
        return $this->_simpleRequest('/couchbase.admin.search.v1.SearchAdminService/PauseIndexIngest',
        $argument,
        ['\Couchbase\Protostellar\Generated\Admin\Search\V1\PauseIndexIngestResponse', 'decode'],
        $metadata, $options);
    }

    /**
     * @param \Couchbase\Protostellar\Generated\Admin\Search\V1\ResumeIndexIngestRequest $argument input argument
     * @param array $metadata metadata
     * @param array $options call options
     * @return \Grpc\UnaryCall
     */
    public function ResumeIndexIngest(\Couchbase\Protostellar\Generated\Admin\Search\V1\ResumeIndexIngestRequest $argument,
      $metadata = [], $options = []) {
        return $this->_simpleRequest('/couchbase.admin.search.v1.SearchAdminService/ResumeIndexIngest',
        $argument,
        ['\Couchbase\Protostellar\Generated\Admin\Search\V1\ResumeIndexIngestResponse', 'decode'],
        $metadata, $options);
    }

    /**
     * @param \Couchbase\Protostellar\Generated\Admin\Search\V1\AllowIndexQueryingRequest $argument input argument
     * @param array $metadata metadata
     * @param array $options call options
     * @return \Grpc\UnaryCall
     */
    public function AllowIndexQuerying(\Couchbase\Protostellar\Generated\Admin\Search\V1\AllowIndexQueryingRequest $argument,
      $metadata = [], $options = []) {
        return $this->_simpleRequest('/couchbase.admin.search.v1.SearchAdminService/AllowIndexQuerying',
        $argument,
        ['\Couchbase\Protostellar\Generated\Admin\Search\V1\AllowIndexQueryingResponse', 'decode'],
        $metadata, $options);
    }

    /**
     * @param \Couchbase\Protostellar\Generated\Admin\Search\V1\DisallowIndexQueryingRequest $argument input argument
     * @param array $metadata metadata
     * @param array $options call options
     * @return \Grpc\UnaryCall
     */
    public function DisallowIndexQuerying(\Couchbase\Protostellar\Generated\Admin\Search\V1\DisallowIndexQueryingRequest $argument,
      $metadata = [], $options = []) {
        return $this->_simpleRequest('/couchbase.admin.search.v1.SearchAdminService/DisallowIndexQuerying',
        $argument,
        ['\Couchbase\Protostellar\Generated\Admin\Search\V1\DisallowIndexQueryingResponse', 'decode'],
        $metadata, $options);
    }

    /**
     * @param \Couchbase\Protostellar\Generated\Admin\Search\V1\FreezeIndexPlanRequest $argument input argument
     * @param array $metadata metadata
     * @param array $options call options
     * @return \Grpc\UnaryCall
     */
    public function FreezeIndexPlan(\Couchbase\Protostellar\Generated\Admin\Search\V1\FreezeIndexPlanRequest $argument,
      $metadata = [], $options = []) {
        return $this->_simpleRequest('/couchbase.admin.search.v1.SearchAdminService/FreezeIndexPlan',
        $argument,
        ['\Couchbase\Protostellar\Generated\Admin\Search\V1\FreezeIndexPlanResponse', 'decode'],
        $metadata, $options);
    }

    /**
     * @param \Couchbase\Protostellar\Generated\Admin\Search\V1\UnfreezeIndexPlanRequest $argument input argument
     * @param array $metadata metadata
     * @param array $options call options
     * @return \Grpc\UnaryCall
     */
    public function UnfreezeIndexPlan(\Couchbase\Protostellar\Generated\Admin\Search\V1\UnfreezeIndexPlanRequest $argument,
      $metadata = [], $options = []) {
        return $this->_simpleRequest('/couchbase.admin.search.v1.SearchAdminService/UnfreezeIndexPlan',
        $argument,
        ['\Couchbase\Protostellar\Generated\Admin\Search\V1\UnfreezeIndexPlanResponse', 'decode'],
        $metadata, $options);
    }

}
