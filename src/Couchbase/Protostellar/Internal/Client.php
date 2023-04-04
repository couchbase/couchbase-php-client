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
    private Generated\Admin\Collection\V1\CollectionAdminServiceClient $collectionAdmin;
    private Generated\Admin\Bucket\V1\BucketAdminServiceClient $bucketAdmin;

    /**
     * @throws InvalidArgumentException
     */
    public function __construct(string $host, ClusterOptions $clusterOptions, ClientOptions $options = new ClientOptions())
    {
        $opts = $options->channelOptions($clusterOptions);
        $host = substr($host, strpos($host, "/") + 2) . ":18098";
        $this->channel = new Channel($host, []);
        $this->kv = new Generated\KV\V1\KvServiceClient($host, $opts, $this->channel);
        $this->query = new Generated\Query\V1\QueryServiceClient($host, $opts, $this->channel);
        $this->search = new Generated\Search\V1\SearchServiceClient($host, $opts, $this->channel);
        $this->collectionAdmin = new Generated\Admin\Collection\V1\CollectionAdminServiceClient($host, $opts, $this->channel);
        $this->bucketAdmin = new Generated\Admin\Bucket\V1\BucketAdminServiceClient($host, $opts, $this->channel);
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

    public function collectionAdmin(): Generated\Admin\Collection\V1\CollectionAdminServiceClient
    {
        return $this->collectionAdmin;
    }

    public function bucketAdmin(): Generated\Admin\Bucket\V1\BucketAdminServiceClient
    {
        return $this->bucketAdmin;
    }
}
