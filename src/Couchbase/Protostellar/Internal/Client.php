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

use Couchbase\Protostellar\Generated;
use Grpc\Channel;

class Client
{
    private Channel $channel;
    private Generated\KV\V1\KvServiceClient $kv;
    private Generated\Query\V1\QueryServiceClient $query;
    private Generated\Search\V1\SearchServiceClient $search;

    public function __construct(string $host, ClientOptions $options = new ClientOptions())
    {
        $host = substr($host, strpos($host, "/") + 2) . ":18098";
        $this->channel = new Channel($host, $options->channelOptions());
        $this->kv = new Generated\KV\V1\KvServiceClient($host, $options->channelOptions(), $this->channel);
        $this->query = new Generated\Query\V1\QueryServiceClient($host, $options->channelOptions(), $this->channel);
        $this->search = new Generated\Search\V1\SearchServiceClient($host, $options->channelOptions(), $this->channel);
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
}