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

use Couchbase\StellarNebula\Generated\KV\V1\GetRequest;
use Couchbase\StellarNebula\Generated\KV\V1\InsertRequest;
use Couchbase\StellarNebula\Generated\KV\V1\LegacyDurabilitySpec;
use Couchbase\StellarNebula\Generated\KV\V1\UpsertRequest;
use Couchbase\StellarNebula\Internal\Client;
use Google\Protobuf\Timestamp;

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

    /**
     * @throws ProtocolException
     */
    public function upsert(string $key, $document, UpsertOptions $options): MutationResult
    {
        [$encodedDocument, $contentType] = UpsertOptions::encodeDocument($options, $document);
        $exportedOptions = InsertOptions::export($options);
        $request = [
            "bucket_name" => $this->bucketName,
            "scope_name" => $this->scopeName,
            "collection_name" => $this->name,
            "key" => $key,
            "content" => $encodedDocument,
            "content_type" => $contentType,
        ];
        if (array_key_exists("expirySeconds", $exportedOptions)) {
            $request["expiry"] = new Timestamp(["seconds" => $exportedOptions["expirySeconds"]]);
        }
        if (array_key_exists("durabilityLevel", $exportedOptions)) {
            $request["durability_level"] = $this->convertDurabilityLevel($exportedOptions["durabilityLevel"]);
        }
        if (array_key_exists("legacyDurability", $exportedOptions)) {
            $request["legacy_durability_spec"] = $this->convertLegacyDurability($exportedOptions["legacyDurability"]);
        }

        $pendingCall = $this->client->kv()->Upsert(new UpsertRequest($request));
        [$res, $status] = $pendingCall->wait();
        if ($status->code !== STATUS_OK) {
            throw new ProtocolException("unable to upsert the key", $status);
        }
        return new MutationResult(
            $this->bucketName,
            $this->scopeName,
            $this->name,
            $key,
            $res->getCas(),
            new MutationToken(
                $res->getMutationToken()->getBucketName(),
                $res->getMutationToken()->getVbucketId(),
                $res->getMutationToken()->getVbucketUuid(),
                $res->getMutationToken()->getSeqNo()
            )
        );
    }

    /**
     * @throws ProtocolException
     */
    public function insert(string $key, $document, InsertOptions $options = null): MutationResult
    {
        [$encodedDocument, $contentType] = InsertOptions::encodeDocument($options, $document);
        $exportedOptions = InsertOptions::export($options);
        $request = [
            "bucket_name" => $this->bucketName,
            "scope_name" => $this->scopeName,
            "collection_name" => $this->name,
            "key" => $key,
            "content" => $encodedDocument,
            "content_type" => $contentType,
        ];
        if (array_key_exists("expirySeconds", $exportedOptions)) {
            $request["expiry"] = new Timestamp(["seconds" => $exportedOptions["expirySeconds"]]);
        }
        if (array_key_exists("durabilityLevel", $exportedOptions)) {
            $request["durability_level"] = $this->convertDurabilityLevel($exportedOptions["durabilityLevel"]);
        }
        if (array_key_exists("legacyDurability", $exportedOptions)) {
            $request["legacy_durability_spec"] = $this->convertLegacyDurability($exportedOptions["legacyDurability"]);
        }
        $pendingCall = $this->client->kv()->Insert(new InsertRequest($request));
        [$res, $status] = $pendingCall->wait();
        if ($status->code !== STATUS_OK) {
            throw new ProtocolException("unable to insert the key", $status);
        }
        return new MutationResult(
            $this->bucketName,
            $this->scopeName,
            $this->name,
            $key,
            $res->getCas(),
            new MutationToken(
                $res->getMutationToken()->getBucketName(),
                $res->getMutationToken()->getVbucketId(),
                $res->getMutationToken()->getVbucketUuid(),
                $res->getMutationToken()->getSeqNo()
            )
        );
    }

    /**
     * @throws ProtocolException
     */
    public function get(string $key, GetOptions $options = null): GetResult
    {
        $request = [
            "bucket_name" => $this->bucketName,
            "scope_name" => $this->scopeName,
            "collection_name" => $this->name,
            "key" => $key,
        ];
        $pendingCall = $this->client->kv()->Get(new GetRequest($request));
        [$res, $status] = $pendingCall->wait();
        if ($status->code !== STATUS_OK) {
            throw new ProtocolException("unable to insert the key", $status);
        }
        return new GetResult(
            $this->bucketName,
            $this->scopeName,
            $this->name,
            $key,
            $res->getCas(),
            GetOptions::getTranscoder($options),
            $res->getContent(),
            $res->getContentType(),
            $res->getCompressionType(),
            $res->getExpiry()?->getSeconds(),
        );
    }

    private function convertDurabilityLevel(string $durabilityLevel): ?int
    {
        if ($durabilityLevel === DurabilityLevel::MAJORITY) {
            return \Couchbase\StellarNebula\Generated\KV\V1\DurabilityLevel::MAJORITY;
        }
        if ($durabilityLevel === DurabilityLevel::MAJORITY_AND_PERSIST_TO_ACTIVE) {
            return \Couchbase\StellarNebula\Generated\KV\V1\DurabilityLevel::MAJORITY_AND_PERSIST_TO_ACTIVE;
        }
        if ($durabilityLevel === DurabilityLevel::PERSIST_TO_MAJORITY) {
            return \Couchbase\StellarNebula\Generated\KV\V1\DurabilityLevel::PERSIST_TO_MAJORITY;
        }
        return null;
    }

    private function convertLegacyDurability(?array $legacyDurability): ?LegacyDurabilitySpec
    {
        if ($legacyDurability == null) {
            return null;
        }
        return new LegacyDurabilitySpec(
            [
                "num_replicated" => $legacyDurability["replicateTo"],
                "num_persisted" => $legacyDurability["persistTo"],
            ]
        );
    }
}
