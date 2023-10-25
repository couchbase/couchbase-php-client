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
use Couchbase\Management\SearchIndexManagerInterface;
use Couchbase\Management\UnfreezePlanSearchIndexOptions;
use Couchbase\Management\UpsertSearchIndexOptions;
use Couchbase\Protostellar\Internal\Client;
use Couchbase\Protostellar\Internal\Management\SearchIndexManagementResponseConverter;
use Couchbase\Protostellar\Internal\RequestFactory;
use Couchbase\Protostellar\Internal\SharedUtils;
use Couchbase\Protostellar\Internal\TimeoutHandler;
use Couchbase\Protostellar\ProtostellarOperationRunner;

class SearchIndexManager implements SearchIndexManagerInterface
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
        $request = RequestFactory::makeRequest(
            ['Couchbase\Protostellar\Internal\Management\SearchIndexManagementRequestConverter', 'getGetIndexRequest'],
            [$indexName]
        );
        $timeout = $this->client->timeoutHandler()->getTimeout(TimeoutHandler::MANAGEMENT, $exportedOptions);
        $response = ProtostellarOperationRunner::runUnary(
            SharedUtils::createProtostellarRequest($request, true, $timeout),
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
        $request = RequestFactory::makeRequest(
            ['Couchbase\Protostellar\Internal\Management\SearchIndexManagementRequestConverter', 'getGetAllIndexesRequest'],
            []
        );
        $timeout = $this->client->timeoutHandler()->getTimeout(TimeoutHandler::MANAGEMENT, $exportedOptions);
        $response = ProtostellarOperationRunner::runUnary(
            SharedUtils::createProtostellarRequest($request, true, $timeout),
            [$this->client->searchAdmin(), 'ListIndexes']
        );
        return SearchIndexManagementResponseConverter::convertGetAllIndexesResult($response);
    }

    public function upsertIndex(SearchIndex $indexDefinition, UpsertSearchIndexOptions $options = null)
    {
        $exportedOptions = UpsertSearchIndexOptions::export($options);
        $exportedIndex = SearchIndex::export($indexDefinition);

        $timeout = $this->client->timeoutHandler()->getTimeout(TimeoutHandler::MANAGEMENT, $exportedOptions);
        if (is_null($indexDefinition->uuid())) {
            $request = RequestFactory::makeRequest(
                ['Couchbase\Protostellar\Internal\Management\SearchIndexManagementRequestConverter', 'getCreateIndexRequest'],
                [$exportedIndex]
            );
            ProtostellarOperationRunner::runUnary(
                SharedUtils::createProtostellarRequest($request, false, $timeout),
                [$this->client->searchAdmin(), 'CreateIndex']
            );
        } else {
            $request = RequestFactory::makeRequest(
                ['Couchbase\Protostellar\Internal\Management\SearchIndexManagementRequestConverter', 'getUpdateIndexRequest'],
                [$exportedIndex]
            );
            ProtostellarOperationRunner::runUnary(
                SharedUtils::createProtostellarRequest($request, false, $timeout),
                [$this->client->searchAdmin(), 'UpdateIndex']
            );
        }
    }

    public function dropIndex(string $name, DropSearchIndexOptions $options = null)
    {
        $exportedOptions = DropSearchIndexOptions::export($options);
        $request = RequestFactory::makeRequest(
            ['Couchbase\Protostellar\Internal\Management\SearchIndexManagementRequestConverter', 'getDropIndexRequest'],
            [$name]
        );
        $timeout = $this->client->timeoutHandler()->getTimeout(TimeoutHandler::MANAGEMENT, $exportedOptions);
        ProtostellarOperationRunner::runUnary(
            SharedUtils::createProtostellarRequest($request, false, $timeout),
            [$this->client->searchAdmin(), 'DeleteIndex']
        );
    }

    public function getIndexedDocumentsCount(string $indexName, GetIndexedSearchIndexOptions $options = null): int
    {
        $exportedOptions = GetIndexedSearchIndexOptions::export($options);
        $request = RequestFactory::makeRequest(
            ['Couchbase\Protostellar\Internal\Management\SearchIndexManagementRequestConverter', 'getGetIndexedDocumentCountRequest'],
            [$indexName]
        );
        $timeout = $this->client->timeoutHandler()->getTimeout(TimeoutHandler::MANAGEMENT, $exportedOptions);
        return ProtostellarOperationRunner::runUnary(
            SharedUtils::createProtostellarRequest($request, true, $timeout),
            [$this->client->searchAdmin(), 'GetIndexedDocumentsCount']
        );
    }

    public function pauseIngest(string $indexName, PauseIngestSearchIndexOptions $options = null)
    {
        $exportedOptions = PauseIngestSearchIndexOptions::export($options);
        $request = RequestFactory::makeRequest(
            ['Couchbase\Protostellar\Internal\Management\SearchIndexManagementRequestConverter', 'getPauseIngestRequest'],
            [$indexName]
        );
        $timeout = $this->client->timeoutHandler()->getTimeout(TimeoutHandler::MANAGEMENT, $exportedOptions);
        ProtostellarOperationRunner::runUnary(
            SharedUtils::createProtostellarRequest($request, false, $timeout),
            [$this->client->searchAdmin(), 'PauseIndexIngest']
        );
    }

    public function resumeIngest(string $indexName, ResumeIngestSearchIndexOptions $options = null)
    {
        $exportedOptions = ResumeIngestSearchIndexOptions::export($options);
        $request = RequestFactory::makeRequest(
            ['Couchbase\Protostellar\Internal\Management\SearchIndexManagementRequestConverter', 'getResumeIngestRequest'],
            [$indexName]
        );
        $timeout = $this->client->timeoutHandler()->getTimeout(TimeoutHandler::MANAGEMENT, $exportedOptions);
        ProtostellarOperationRunner::runUnary(
            SharedUtils::createProtostellarRequest($request, false, $timeout),
            [$this->client->searchAdmin(), 'ResumeIndexIngest']
        );
    }

    public function allowQuerying(string $indexName, AllowQueryingSearchIndexOptions $options = null)
    {
        $exportedOptions = AllowQueryingSearchIndexOptions::export($options);
        $request = RequestFactory::makeRequest(
            ['Couchbase\Protostellar\Internal\Management\SearchIndexManagementRequestConverter', 'getAllowQueryingRequest'],
            [$indexName]
        );
        $timeout = $this->client->timeoutHandler()->getTimeout(TimeoutHandler::MANAGEMENT, $exportedOptions);
        ProtostellarOperationRunner::runUnary(
            SharedUtils::createProtostellarRequest($request, false, $timeout),
            [$this->client->searchAdmin(), 'AllowIndexQuerying']
        );
    }

    public function disallowQuerying(string $indexName, DisallowQueryingSearchIndexOptions $options = null)
    {
        $exportedOptions = DisallowQueryingSearchIndexOptions::export($options);
        $request = RequestFactory::makeRequest(
            ['Couchbase\Protostellar\Internal\Management\SearchIndexManagementRequestConverter', 'getDisallowQueryingRequest'],
            [$indexName]
        );
        $timeout = $this->client->timeoutHandler()->getTimeout(TimeoutHandler::MANAGEMENT, $exportedOptions);
        ProtostellarOperationRunner::runUnary(
            SharedUtils::createProtostellarRequest($request, false, $timeout),
            [$this->client->searchAdmin(), 'DisallowIndexQuerying']
        );
    }

    public function freezePlan(string $indexName, FreezePlanSearchIndexOptions $options = null)
    {
        $exportedOptions = FreezePlanSearchIndexOptions::export($options);
        $request = RequestFactory::makeRequest(
            ['Couchbase\Protostellar\Internal\Management\SearchIndexManagementRequestConverter', 'getFreezePlanRequest'],
            [$indexName]
        );
        $timeout = $this->client->timeoutHandler()->getTimeout(TimeoutHandler::MANAGEMENT, $exportedOptions);
        ProtostellarOperationRunner::runUnary(
            SharedUtils::createProtostellarRequest($request, false, $timeout),
            [$this->client->searchAdmin(), 'FreezeIndexPlan']
        );
    }

    public function unfreezePlan(string $indexName, UnfreezePlanSearchIndexOptions $options = null)
    {
        $exportedOptions = UnfreezePlanSearchIndexOptions::export($options);
        $request = RequestFactory::makeRequest(
            ['Couchbase\Protostellar\Internal\Management\SearchIndexManagementRequestConverter', 'getUnfreezePlanRequest'],
            [$indexName]
        );
        $timeout = $this->client->timeoutHandler()->getTimeout(TimeoutHandler::MANAGEMENT, $exportedOptions);
        ProtostellarOperationRunner::runUnary(
            SharedUtils::createProtostellarRequest($request, false, $timeout),
            [$this->client->searchAdmin(), 'UnfreezeIndexPlan']
        );
    }

    public function analyzeDocument(string $indexName, $document, AnalyzeDocumentOptions $options = null): array
    {
        $exportedOptions = AnalyzeDocumentOptions::export($options);
        $request = RequestFactory::makeRequest(
            ['Couchbase\Protostellar\Internal\Management\SearchIndexManagementRequestConverter', 'getAnalyzeDocumentRequest'],
            [$indexName, $document]
        );
        $timeout = $this->client->timeoutHandler()->getTimeout(TimeoutHandler::MANAGEMENT, $exportedOptions);
        $response = ProtostellarOperationRunner::runUnary(
            SharedUtils::createProtostellarRequest($request, true, $timeout),
            [$this->client->searchAdmin(), 'AnalyzeDocument']
        );
        return SearchIndexManagementResponseConverter::convertAnalyzeDocumentResult($response);
    }
}
