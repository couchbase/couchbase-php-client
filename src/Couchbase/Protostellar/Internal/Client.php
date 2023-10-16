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

namespace Couchbase\Protostellar\Internal;

use Couchbase\ClusterOptions;
use Couchbase\Exception\InvalidArgumentException;
use Couchbase\Protostellar\Generated;
use Grpc\Channel;

class Client
{
    private Channel $channel;
    private Generated\KV\V1\KvServiceClient $kv;
    private Generated\Query\V1\QueryServiceClient $query;
    private Generated\Search\V1\SearchServiceClient $search;
    private Generated\Analytics\V1\AnalyticsServiceClient $analytics;
    private Generated\Admin\Collection\V1\CollectionAdminServiceClient $collectionAdmin;
    private Generated\Admin\Bucket\V1\BucketAdminServiceClient $bucketAdmin;
    private Generated\Admin\Query\V1\QueryAdminServiceClient $queryAdmin;
    private Generated\Admin\Search\V1\SearchAdminServiceClient $searchAdmin;
    private TimeoutHandler $timeoutHandler;

    /**
     * @throws InvalidArgumentException
     */
    public function __construct(string $connectionString, ClusterOptions $clusterOptions, ClientOptions $options = new ClientOptions())
    {
        $parsedConnectionString = parse_url($connectionString);
        $parsedOptions = $options->getConnectionOptions($parsedConnectionString, $clusterOptions->export());
        $clientOpts = $options->channelOptions($parsedOptions);
        $host = $parsedConnectionString['host'] . ":18098";
        $this->channel = new Channel($host, $options->getChannelCredentials($parsedOptions));
        $this->kv = new Generated\KV\V1\KvServiceClient($host, $clientOpts, $this->channel);
        $this->query = new Generated\Query\V1\QueryServiceClient($host, $clientOpts, $this->channel);
        $this->search = new Generated\Search\V1\SearchServiceClient($host, $clientOpts, $this->channel);
        $this->analytics = new Generated\Analytics\V1\AnalyticsServiceClient($host, $clientOpts, $this->channel);
        $this->collectionAdmin = new Generated\Admin\Collection\V1\CollectionAdminServiceClient($host, $clientOpts, $this->channel);
        $this->bucketAdmin = new Generated\Admin\Bucket\V1\BucketAdminServiceClient($host, $clientOpts, $this->channel);
        $this->queryAdmin = new Generated\Admin\Query\V1\QueryAdminServiceClient($host, $clientOpts, $this->channel);
        $this->searchAdmin = new Generated\Admin\Search\V1\SearchAdminServiceClient($host, $clientOpts, $this->channel);
        $this->timeoutHandler = new TimeoutHandler($parsedOptions);
    }

    public function close()
    {
        $this->channel->close();
    }

    public function kv(): Generated\KV\V1\KvServiceClient
    {
        return $this->kv;
    }

    public function query(): Generated\Query\V1\QueryServiceClient
    {
        return $this->query;
    }

    public function search(): Generated\Search\V1\SearchServiceClient
    {
        return $this->search;
    }

    public function analytics(): Generated\Analytics\V1\AnalyticsServiceClient
    {
        return $this->analytics;
    }

    public function collectionAdmin(): Generated\Admin\Collection\V1\CollectionAdminServiceClient
    {
        return $this->collectionAdmin;
    }

    public function bucketAdmin(): Generated\Admin\Bucket\V1\BucketAdminServiceClient
    {
        return $this->bucketAdmin;
    }

    public function queryAdmin(): Generated\Admin\Query\V1\QueryAdminServiceClient
    {
        return $this->queryAdmin;
    }

    public function searchAdmin(): Generated\Admin\Search\V1\SearchAdminServiceClient
    {
        return $this->searchAdmin;
    }

    public function timeoutHandler(): TimeoutHandler
    {
        return $this->timeoutHandler;
    }
}
