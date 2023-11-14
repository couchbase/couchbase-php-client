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

namespace Couchbase\Protostellar;

use Couchbase\AnalyticsOptions;
use Couchbase\AnalyticsResult;
use Couchbase\Exception\InvalidArgumentException;
use Couchbase\Protostellar\Internal\Analytics\AnalyticsResponseConverter;
use Couchbase\Protostellar\Internal\Client;
use Couchbase\Protostellar\Internal\Query\QueryResponseConverter;
use Couchbase\Protostellar\Internal\RequestFactory;
use Couchbase\Protostellar\Internal\SharedUtils;
use Couchbase\Protostellar\Internal\TimeoutHandler;
use Couchbase\QueryOptions;
use Couchbase\QueryResult;
use Couchbase\ScopeInterface;

class Scope implements ScopeInterface
{
    public const DEFAULT_NAME = "_default";

    private Client $client;
    private string $bucketName;
    private string $name;

    public function __construct(Client $client, string $bucketName, string $name)
    {
        $this->client = $client;
        $this->bucketName = $bucketName;
        $this->name = $name;
    }

    public function name(): string
    {
        return $this->name;
    }

    public function collection(string $name): Collection
    {
        return new Collection($this->client, $this->bucketName, $this->name, $name);
    }

    /**
     * @throws InvalidArgumentException
     */
    public function query(string $statement, QueryOptions $options = null): QueryResult
    {
        $exportedOptions = QueryOptions::export($options);
        $exportedOptions["bucketName"] = $this->bucketName;
        $exportedOptions["scopeName"] = $this->name;
        $request = RequestFactory::makeRequest(
            ['Couchbase\Protostellar\Internal\Query\QueryRequestConverter', 'getQueryRequest'],
            [$statement, $exportedOptions]
        );
        $timeout = $this->client->timeoutHandler()->getTimeout(TimeoutHandler::QUERY, $exportedOptions);
        $response = ProtostellarOperationRunner::runStreaming(
            SharedUtils::createProtostellarRequest($request, $exportedOptions['readonly'] ?? false, $timeout),
            [$this->client->query(), 'Query']
        );
        $finalArray = QueryResponseConverter::convertQueryResult($response);
        return new QueryResult($finalArray, QueryOptions::getTranscoder($options));
    }

    public function analyticsQuery(string $statement, AnalyticsOptions $options = null): AnalyticsResult
    {
        $exportedOptions = AnalyticsOptions::export($options, $this->name, $this->bucketName);
        $request = RequestFactory::makeRequest(
            ['Couchbase\Protostellar\Internal\Analytics\AnalyticsRequestConverter', 'getAnalyticsRequest'],
            [$statement, $exportedOptions]
        );
        $timeout = $this->client->timeoutHandler()->getTimeout(TimeoutHandler::ANALYTICS, $exportedOptions);
        $response = ProtostellarOperationRunner::runStreaming(
            SharedUtils::createProtostellarRequest($request, false, $timeout),
            [$this->client->analytics(), 'AnalyticsQuery']
        );
        $finalArray = AnalyticsResponseConverter::convertAnalyticsResult($response);
        return new AnalyticsResult($finalArray, AnalyticsOptions::getTranscoder($options));
    }
}
