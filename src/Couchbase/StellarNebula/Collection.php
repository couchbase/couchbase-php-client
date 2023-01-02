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

namespace Couchbase\StellarNebula;

use Couchbase\StellarNebula\Generated\KV\V1\DocumentContentType;
use Couchbase\StellarNebula\Generated\KV\V1\UpsertRequest;
use Couchbase\StellarNebula\Internal\Client;
use RuntimeException;
use const Grpc\STATUS_OK;

class Collection
{
    public const DEFAULT_NAME = "_default";

    private Client $client;
    private string $bucketName;
    private string $scopeName;
    private string $name;

    public function __construct(Client $client, string $bucketName, string $scopeName, string $name)
    {
        $this->client = $client;
        $this->bucketName = $bucketName;
        $this->scopeName = $scopeName;
        $this->name = $name;
    }

    public function name(): string
    {
        return $this->name;
    }

    public function upsert(string $key, $value): UpsertResult
    {
        $req = new UpsertRequest(
            [
                "bucket_name" => $this->bucketName,
                "scope_name" => $this->scopeName,
                "collection_name" => $this->name,
                "key" => $key,
                "content" => json_encode($value),
                "content_type" => DocumentContentType::JSON,
            ]
        );

        /*
         * @type \Google\Protobuf\Timestamp $expiry
         * @type \Couchbase\StellarNebula\Generated\KV\V1\LegacyDurabilitySpec $legacy_durability_spec
         * @type int $durability_level
         */
        try {
            $pendingCall = $this->client->kv()->Upsert($req);
            [$res, $status] = $pendingCall->wait();
            if ($status->code !== STATUS_OK) {
                throw new RuntimeException("unable to upsert the key: " . $status->details, $status->code);
            }
            return new UpsertResult($this->bucketName, $this->scopeName, $this->name, $key, $res->getCas());
        } catch (Grpc\Exception\ConnectException $e) {
            throw new RuntimeException("A connection error occurred", 0, $e);
        } catch (Grpc\Exception\RpcException $e) {
            throw new RuntimeException("A server error occurred", 0, $e);
        } catch (Grpc\Exception\BadHttpStatusException $e) {
            throw new RuntimeException("The server returned a non-200 HTTP status code", 0, $e);
        }
    }
}
