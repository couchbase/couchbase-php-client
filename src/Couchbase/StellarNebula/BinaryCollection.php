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

use Couchbase\StellarNebula\Generated\KV\V1\AppendRequest;
use Couchbase\StellarNebula\Generated\KV\V1\DecrementRequest;
use Couchbase\StellarNebula\Generated\KV\V1\IncrementRequest;
use Couchbase\StellarNebula\Generated\KV\V1\PrependRequest;
use Couchbase\StellarNebula\Internal\Client;

use Google\Protobuf\Timestamp;
use const Grpc\STATUS_OK;

class BinaryCollection
{
    private Client $client;
    private string $bucketName;
    private string $scopeName;
    private string $name;

    /**
     * @param string $name
     * @param string $scopeName
     * @param string $bucketName
     * @param resource $core
     *
     * @internal
     */
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
    public function append(string $key, string $value, AppendOptions $options = null): MutationResult
    {
        $exportedOptions = AppendOptions::export($options);
        $request = [
            "bucket_name" => $this->bucketName,
            "scope_name" => $this->scopeName,
            "collection_name" => $this->name,
            "key" => $key,
            "content" => $value,
        ];
        $timeout = array_key_exists("timeoutMilliseconds", $exportedOptions)
            ? $exportedOptions["timeoutMilliseconds"] * 1000
            : Collection::DEFAULT_KV_TIMEOUT;
        if (array_key_exists("durabilityLevel", $exportedOptions)) {
            $request["durability_level"] = Collection::convertDurabilityLevel($exportedOptions["durabilityLevel"]);
        }
        if (array_key_exists("legacyDurability", $exportedOptions)) {
            $request["legacy_durability_spec"] = Collection::convertLegacyDurability($exportedOptions["legacyDurability"]);
        }
        if (array_key_exists("cas", $exportedOptions)) {
            $request["cas"] = $exportedOptions["cas"];
        }
        $pendingCall = $this->client->kv()->Append(new AppendRequest($request), [], ['timeout' => $timeout]);
        [$res, $status] = $pendingCall->wait();
        if ($status->code !== STATUS_OK) {
            throw new ProtocolException("unable to remove the key", $status);
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
    public function prepend(string $key, string $value, PrependOptions $options = null): MutationResult
    {
        $exportedOptions = PrependOptions::export($options);
        $request = [
            "bucket_name" => $this->bucketName,
            "scope_name" => $this->scopeName,
            "collection_name" => $this->name,
            "key" => $key,
            "content" => $value,
        ];
        $timeout = array_key_exists("timeoutMilliseconds", $exportedOptions)
            ? $exportedOptions["timeoutMilliseconds"] * 1000
            : Collection::DEFAULT_KV_TIMEOUT;
        if (array_key_exists("durabilityLevel", $exportedOptions)) {
            $request["durability_level"] = Collection::convertDurabilityLevel($exportedOptions["durabilityLevel"]);
        }
        if (array_key_exists("legacyDurability", $exportedOptions)) {
            $request["legacy_durability_spec"] = Collection::convertLegacyDurability($exportedOptions["legacyDurability"]);
        }
        if (array_key_exists("cas", $exportedOptions)) {
            $request["cas"] = $exportedOptions["cas"];
        }
        $pendingCall = $this->client->kv()->Prepend(new PrependRequest($request), [], ['timeout' => $timeout]);
        [$res, $status] = $pendingCall->wait();
        if ($status->code !== STATUS_OK) {
            throw new ProtocolException("unable to remove the key", $status);
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
    public function increment(string $key, IncrementOptions $options = null): CounterResult
    {
        $exportedOptions = IncrementOptions::export($options);
        $request = [
            "bucket_name" => $this->bucketName,
            "scope_name" => $this->scopeName,
            "collection_name" => $this->name,
            "key" => $key,
        ];
        $timeout = array_key_exists("timeoutMilliseconds", $exportedOptions)
            ? $exportedOptions["timeoutMilliseconds"] * 1000
            : Collection::DEFAULT_KV_TIMEOUT;
        $request["delta"] = array_key_exists("delta", $exportedOptions)
            ? $exportedOptions["delta"]
            : 1;
        if (array_key_exists("initialValue", $exportedOptions)) {
            $request["initial"] = $exportedOptions["initialValue"];
        }
        if (array_key_exists("expirySeconds", $exportedOptions)) {
            $request["expiry"] = new Timestamp(["seconds" => $exportedOptions["expirySeconds"]]);
        }
        if (array_key_exists("durabilityLevel", $exportedOptions)) {
            $request["durability_level"] = Collection::convertDurabilityLevel($exportedOptions["durabilityLevel"]);
        }
        if (array_key_exists("legacyDurability", $exportedOptions)) {
            $request["legacy_durability_spec"] = Collection::convertLegacyDurability($exportedOptions["legacyDurability"]);
        }
        $pendingCall = $this->client->kv()->Increment(new IncrementRequest($request), [], ['timeout' => $timeout]);
        [$res, $status] = $pendingCall->wait();
        if ($status->code !== STATUS_OK) {
            throw new ProtocolException("unable to remove the key", $status);
        }
        return new CounterResult(
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
            ),
            $res->getContent()
        );
    }

    /**
     * @throws ProtocolException
     */
    public function decrement(string $key, DecrementOptions $options = null): CounterResult
    {
        $exportedOptions = DecrementOptions::export($options);
        // TODO: Old API also has durabilityTimeoutSeconds, not in proto,
        $request = [
            "bucket_name" => $this->bucketName,
            "scope_name" => $this->scopeName,
            "collection_name" => $this->name,
            "key" => $key,
        ];
        $timeout = array_key_exists("timeoutMilliseconds", $exportedOptions)
            ? $exportedOptions["timeoutMilliseconds"] * 1000
            : Collection::DEFAULT_KV_TIMEOUT;
        $request["delta"] = array_key_exists("delta", $exportedOptions)
            ? $exportedOptions["delta"]
            : 1;
        if (array_key_exists("initialValue", $exportedOptions)) {
            $request["initial"] = $exportedOptions["initialValue"];
        }
        if (array_key_exists("expirySeconds", $exportedOptions)) {
            $request["expiry"] = new Timestamp(["seconds" => $exportedOptions["expirySeconds"]]);
        }
        if (array_key_exists("durabilityLevel", $exportedOptions)) {
            $request["durability_level"] = Collection::convertDurabilityLevel($exportedOptions["durabilityLevel"]);
        }
        if (array_key_exists("legacyDurability", $exportedOptions)) {
            $request["legacy_durability_spec"] = Collection::convertLegacyDurability($exportedOptions["legacyDurability"]);
        }
        $pendingCall = $this->client->kv()->Decrement(new DecrementRequest($request), [], ['timeout' => $timeout]);
        [$res, $status] = $pendingCall->wait();
        if ($status->code !== STATUS_OK) {
            throw new ProtocolException("unable to remove the key", $status);
        }
        return new CounterResult(
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
            ),
            $res->getContent()
        );
    }
}
