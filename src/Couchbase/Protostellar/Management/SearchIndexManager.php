<?php

/*
 * Copyright 2022-Present Couchbase, Inc.
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *     http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */

declare(strict_types=1);

namespace Couchbase\Protostellar\Management;

use Couchbase\Exception\InvalidArgumentException;
use Couchbase\Management\AllowQueryingSearchIndexOptions;
use Couchbase\Management\AnalyzeDocumentOptions;
use Couchbase\Management\DisallowQueryingSearchIndexOptions;
use Couchbase\Management\DropSearchIndexOptions;
use Couchbase\Management\FreezePlanSearchIndexOptions;
use Couchbase\Management\GetAllSearchIndexesOptions;
use Couchbase\Management\GetIndexedSearchIndexOptions;
use Couchbase\Management\GetSearchIndexOptions;
use Couchbase\Management\PauseIngestSearchIndexOptions;
use Couchbase\Management\ResumeIngestSearchIndexOptions;
use Couchbase\Management\SearchIndex;
use Couchbase\Management\UnfreezePlanSearchIndexOptions;
use Couchbase\Management\UpsertSearchIndexOptions;
use Couchbase\Protostellar\Generated\Admin\Search\V1\AllowIndexQueryingRequest;
use Couchbase\Protostellar\Generated\Admin\Search\V1\AnalyzeDocumentRequest;
use Couchbase\Protostellar\Generated\Admin\Search\V1\CreateIndexRequest;
use Couchbase\Protostellar\Generated\Admin\Search\V1\DeleteIndexRequest;
use Couchbase\Protostellar\Generated\Admin\Search\V1\DisallowIndexQueryingRequest;
use Couchbase\Protostellar\Generated\Admin\Search\V1\FreezeIndexPlanRequest;
use Couchbase\Protostellar\Generated\Admin\Search\V1\GetIndexedDocumentsCountRequest;
use Couchbase\Protostellar\Generated\Admin\Search\V1\GetIndexRequest;
use Couchbase\Protostellar\Generated\Admin\Search\V1\ListIndexesRequest;
use Couchbase\Protostellar\Generated\Admin\Search\V1\PauseIndexIngestRequest;
use Couchbase\Protostellar\Generated\Admin\Search\V1\ResumeIndexIngestRequest;
use Couchbase\Protostellar\Generated\Admin\Search\V1\UnfreezeIndexPlanRequest;
use Couchbase\Protostellar\Generated\Admin\Search\V1\UpdateIndexRequest;
use Couchbase\Protostellar\Internal\Client;
use Couchbase\Protostellar\Internal\SearchIndexManagementRequestConverter;
use Couchbase\Protostellar\Internal\SearchIndexManagementResponseConverter;
use Couchbase\Protostellar\Internal\SharedUtils;
use Couchbase\Protostellar\Internal\TimeoutHandler;
use Couchbase\Protostellar\ProtostellarOperationRunner;

class SearchIndexManager
{
    private Client $client;
    public function __construct(Client $client)
    {
        $this->client = $client;
    }

    /**
     * @throws InvalidArgumentException
     */
    public function getIndex(string $indexName, GetSearchIndexOptions $options = null): SearchIndex
    {
        $exportedOptions = GetSearchIndexOptions::export($options);
        $request = SearchIndexManagementRequestConverter::getGetIndexRequest($indexName);
        $timeout = $this->client->timeoutHandler()->getTimeout(TimeoutHandler::MANAGEMENT, $exportedOptions);
        $response = ProtostellarOperationRunner::runUnary(
            SharedUtils::createProtostellarRequest(new GetIndexRequest($request), true, $timeout),
            [$this->client->searchAdmin(), 'GetIndex']
        );
        return SearchIndexManagementResponseConverter::convertGetIndexResult($response);
    }

    /**
     * @throws InvalidArgumentException
     */
    public function getAllIndexes(GetAllSearchIndexesOptions $options = null): array
    {
        $exportedOptions = GetAllSearchIndexesOptions::export($options);
        $request = SearchIndexManagementRequestConverter::getGetAllIndexesRequest();
        $timeout = $this->client->timeoutHandler()->getTimeout(TimeoutHandler::MANAGEMENT, $exportedOptions);
        $response = ProtostellarOperationRunner::runUnary(
            SharedUtils::createProtostellarRequest(new ListIndexesRequest($request), true, $timeout),
            [$this->client->searchAdmin(), 'ListIndexes']
        );
        return SearchIndexManagementResponseConverter::convertGetAllIndexesResult($response);
    }

    public function upsertIndex(SearchIndex $indexDefinition, UpsertSearchIndexOptions $options = null)
    {
        $exportedOptions = UpsertSearchIndexOptions::export($options);

        $timeout = $this->client->timeoutHandler()->getTimeout(TimeoutHandler::MANAGEMENT, $exportedOptions);
        if (is_null($indexDefinition->uuid())) {
            $request = SearchIndexManagementRequestConverter::getCreateIndexRequest(SearchIndex::export($indexDefinition));
            ProtostellarOperationRunner::runUnary(
                SharedUtils::createProtostellarRequest(new CreateIndexRequest($request), false, $timeout),
                [$this->client->searchAdmin(), 'CreateIndex']
            );
        } else {
            $request = SearchIndexManagementRequestConverter::getUpdateIndexRequest(SearchIndex::export($indexDefinition));
            ProtostellarOperationRunner::runUnary(
                SharedUtils::createProtostellarRequest(new UpdateIndexRequest($request), false, $timeout),
                [$this->client->searchAdmin(), 'UpdateIndex']
            );
        }
    }

    public function dropIndex(string $name, DropSearchIndexOptions $options = null)
    {
        $exportedOptions = DropSearchIndexOptions::export($options);
        $request = SearchIndexManagementRequestConverter::getDropIndexRequest($name);
        $timeout = $this->client->timeoutHandler()->getTimeout(TimeoutHandler::MANAGEMENT, $exportedOptions);
        ProtostellarOperationRunner::runUnary(
            SharedUtils::createProtostellarRequest(new DeleteIndexRequest($request), false, $timeout),
            [$this->client->searchAdmin(), 'DeleteIndex']
        );
    }

    public function getIndexesDocumentsCount(string $indexName, GetIndexedSearchIndexOptions $options = null): int
    {
        $exportedOptions = GetIndexedSearchIndexOptions::export($options);
        $request = SearchIndexManagementRequestConverter::getGetIndexedDocumentCountRequest($indexName);
        $timeout = $this->client->timeoutHandler()->getTimeout(TimeoutHandler::MANAGEMENT, $exportedOptions);
        return ProtostellarOperationRunner::runUnary(
            SharedUtils::createProtostellarRequest(new GetIndexedDocumentsCountRequest($request), true, $timeout),
            [$this->client->searchAdmin(), 'GetIndexedDocumentsCount']
        );
    }

    public function pauseIngest(string $indexName, PauseIngestSearchIndexOptions $options = null)
    {
        $exportedOptions = PauseIngestSearchIndexOptions::export($options);
        $request = SearchIndexManagementRequestConverter::getPauseIngestRequest($indexName);
        $timeout = $this->client->timeoutHandler()->getTimeout(TimeoutHandler::MANAGEMENT, $exportedOptions);
        ProtostellarOperationRunner::runUnary(
            SharedUtils::createProtostellarRequest(new PauseIndexIngestRequest($request), false, $timeout),
            [$this->client->searchAdmin(), 'PauseIndexIngest']
        );
    }

    public function resumeIngest(string $indexName, ResumeIngestSearchIndexOptions $options = null)
    {
        $exportedOptions = ResumeIngestSearchIndexOptions::export($options);
        $request = SearchIndexManagementRequestConverter::getResumeIngestRequest($indexName);
        $timeout = $this->client->timeoutHandler()->getTimeout(TimeoutHandler::MANAGEMENT, $exportedOptions);
        ProtostellarOperationRunner::runUnary(
            SharedUtils::createProtostellarRequest(new ResumeIndexIngestRequest($request), false, $timeout),
            [$this->client->searchAdmin(), 'ResumeIndexIngest']
        );
    }

    public function allowQuerying(string $indexName, AllowQueryingSearchIndexOptions $options = null)
    {
        $exportedOptions = AllowQueryingSearchIndexOptions::export($options);
        $request = SearchIndexManagementRequestConverter::getAllowQueryingRequest($indexName);
        $timeout = $this->client->timeoutHandler()->getTimeout(TimeoutHandler::MANAGEMENT, $exportedOptions);
        ProtostellarOperationRunner::runUnary(
            SharedUtils::createProtostellarRequest(new AllowIndexQueryingRequest($request), false, $timeout),
            [$this->client->searchAdmin(), 'AllowIndexQuerying']
        );
    }

    public function disallowQuerying(string $indexName, DisallowQueryingSearchIndexOptions $options = null)
    {
        $exportedOptions = DisallowQueryingSearchIndexOptions::export($options);
        $request = SearchIndexManagementRequestConverter::getDisallowQueryingRequest($indexName);
        $timeout = $this->client->timeoutHandler()->getTimeout(TimeoutHandler::MANAGEMENT, $exportedOptions);
        ProtostellarOperationRunner::runUnary(
            SharedUtils::createProtostellarRequest(new DisallowIndexQueryingRequest($request), false, $timeout),
            [$this->client->searchAdmin(), 'DisallowIndexQuerying']
        );
    }

    public function freezePlan(string $indexName, FreezePlanSearchIndexOptions $options = null)
    {
        $exportedOptions = FreezePlanSearchIndexOptions::export($options);
        $request = SearchIndexManagementRequestConverter::getFreezePlanRequest($indexName);
        $timeout = $this->client->timeoutHandler()->getTimeout(TimeoutHandler::MANAGEMENT, $exportedOptions);
        ProtostellarOperationRunner::runUnary(
            SharedUtils::createProtostellarRequest(new FreezeIndexPlanRequest($request), false, $timeout),
            [$this->client->searchAdmin(), 'FreezeIndexPlan']
        );
    }

    public function unfreezePlan(string $indexName, UnfreezePlanSearchIndexOptions $options = null)
    {
        $exportedOptions = UnfreezePlanSearchIndexOptions::export($options);
        $request = SearchIndexManagementRequestConverter::getUnfreezePlanRequest($indexName);
        $timeout = $this->client->timeoutHandler()->getTimeout(TimeoutHandler::MANAGEMENT, $exportedOptions);
        ProtostellarOperationRunner::runUnary(
            SharedUtils::createProtostellarRequest(new UnfreezeIndexPlanRequest($request), false, $timeout),
            [$this->client->searchAdmin(), 'UnfreezeIndexPlan']
        );
    }

    public function analyzeDocument(string $indexName, $document, AnalyzeDocumentOptions $options = null): array
    {
        $exportedOptions = AnalyzeDocumentOptions::export($options);
        $request = SearchIndexManagementRequestConverter::getAnalyzeDocumentRequest($indexName, $document);
        $timeout = $this->client->timeoutHandler()->getTimeout(TimeoutHandler::MANAGEMENT, $exportedOptions);
        $response = ProtostellarOperationRunner::runUnary(
            SharedUtils::createProtostellarRequest(new AnalyzeDocumentRequest($request), true, $timeout),
            [$this->client->searchAdmin(), 'AnalyzeDocument']
        );
        return SearchIndexManagementResponseConverter::convertAnalyzeDocumentResult($response);
    }
}
