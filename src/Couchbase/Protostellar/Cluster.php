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
use Couchbase\ClusterInterface;
use Couchbase\ClusterOptions;
use Couchbase\Exception\InvalidArgumentException;
use Couchbase\Protostellar\Generated\Search\V1\SearchQueryRequest;
use Couchbase\Protostellar\Internal\SearchConverter;
use Couchbase\Protostellar\Internal\SharedUtils;
use Couchbase\QueryOptions;
use Couchbase\QueryResult;
use Couchbase\SearchOptions;
use Couchbase\SearchQuery;
use Couchbase\SearchResult;
use Couchbase\Protostellar\Generated\Query\V1\QueryRequest;
use Couchbase\Protostellar\Internal\Client;
use Couchbase\Protostellar\Internal\QueryConverter;

class Cluster implements ClusterInterface
{
    private Client $client;

    public const DEFAULT_QUERY_TIMEOUT = 7.5e7;

    public function __construct(string $connectionString, ClusterOptions $options = new ClusterOptions())
    {
        $this->client = new Client($connectionString);
    }

    public function close()
    {
        $this->client->close();
    }

    public function bucket(string $name): Bucket
    {
        return new Bucket($this->client, $name);
    }

    /**
     * @throws InvalidArgumentException
     */
    public function query(string $statement, QueryOptions $options = null): QueryResult
    {
        $exportedOptions = QueryOptions::export($options);
        $request = QueryConverter::getQueryRequest($statement, $exportedOptions);
        $timeout = isset($exportedOptions["timeoutMilliseconds"])
            ? $exportedOptions["timeoutMilliseconds"] * 1000
            : self::DEFAULT_QUERY_TIMEOUT;
        $response = ProtostellarOperationRunner::runStreaming(
            SharedUtils::createProtostellarRequest(new QueryRequest($request), $exportedOptions['readonly'] ?? false, $timeout),
            [$this->client->query(), 'Query']
        );
        $res = iterator_to_array($response);
        $finalArray = QueryConverter::convertQueryResult($res);
        return new QueryResult($finalArray, QueryOptions::getTranscoder($options));
    }

    public function analyticsQuery(string $statement, AnalyticsOptions $options = null): AnalyticsResult
    {
        // TODO: Implement analyticsQuery() method.
        return new AnalyticsResult();
    }

    /**
     * @throws InvalidArgumentException
     */
    public function searchQuery(string $indexName, SearchQuery $query, SearchOptions $options = null): SearchResult
    {
        $exportedOptions = SearchOptions::export($options);
        $timeout = isset($exportedOptions["timeoutMilliseconds"])
            ? $exportedOptions["timeoutMilliseconds"] * 1000
            : self::DEFAULT_QUERY_TIMEOUT;
        $request = SearchConverter::getSearchRequest($indexName, $query, $exportedOptions);
        $pendingCall = $this->client->search()->SearchQuery(new SearchQueryRequest($request), [], ['timeout' => $timeout]);
        $res = iterator_to_array($pendingCall->responses());
        $finalArray = SearchConverter::convertSearchResult($res);
        return new SearchResult($finalArray);
    }
}
