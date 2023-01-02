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

namespace Couchbase\StellarNebula\Internal;

use Couchbase\StellarNebula\Generated;
use Grpc\Channel;

class Client
{
    private Channel $channel;
    private Generated\KV\V1\KvClient $kv;

    public function __construct(string $host, ClientOptions $options = new ClientOptions())
    {
        $this->channel = new Channel($host, $options->channelOptions());
        $this->kv = new Generated\KV\V1\KvClient($host, $options->channelOptions(), $this->channel);
    }

    public function close()
    {
        $this->channel->close();
    }

    public function kv(): Generated\KV\V1\KvClient
    {
        return $this->kv;
    }
}
