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

use Couchbase\StellarNebula\Generated\Query\V1\QueryRequest;
use Couchbase\StellarNebula\Internal\Client;
use Google\Protobuf\Duration;

use const Grpc\STATUS_OK;

class Cluster
{
    private Client $client;
    public const DEFAULT_QUERY_TIMEOUT = 2e10;

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
     * @throws ProtocolException
     */
    public function query(string $statement, QueryOptions $options = null): QueryResult
    {
        $exportedOptions = QueryOptions::export($options);
        $request = [
            'statement' => $statement,
        ];
        $timeout = array_key_exists("timeoutMilliseconds", $exportedOptions)
            ? $exportedOptions["timeoutMilliseconds"] * 1000
            : self::DEFAULT_QUERY_TIMEOUT;
        if (isset($exportedOptions["bucketName"])) {
            $request["bucket_name"] = $exportedOptions["bucketName"];
        }
        if (isset($exportedOptions["scopeName"])) {
            $request["scope_name"] = $exportedOptions["scopeName"];
        }
        if (isset($exportedOptions["readonly"])) {
            $request["read_only"] = $exportedOptions["readonly"];
        }
        if (isset($exportedOptions["prepared"])) {
            $request["prepared"] = $exportedOptions["prepared"];
        }
        $tuningOptions = $this->getTuningOptions($exportedOptions);
        if ($tuningOptions) {
            $request["tuning_options"] = $tuningOptions;
        }
        if (isset($exportedOptions["clientContextId"])) {
            $request["client_context_id"] = $exportedOptions["clientContextId"];
        }
        if (isset($exportedOptions["scanConsistency"])) {
            $request["scan_consistency"] = $exportedOptions["scanConsistency"];
        }
        if (isset($exportedOptions["positionalParameters"])) {
            $request["positional_parameters"] = $exportedOptions["positionalParameters"];
        }
        if (isset($exportedOptions["namedParameters"])) {
            $request["named_parameters"] = $exportedOptions["namedParameters"];
        }
        if (isset($exportedOptions["flexIndex"])) {
            $request["flex_index"] = $exportedOptions["flexIndex"];
        }
        if (isset($exportedOptions["preserveExpiry"])) {
            $request["preserve_expiry"] = $exportedOptions["preserveExpiry"];
        }
        if (isset($exportedOptions["consistentWith"])) {
            $request["consistent_with"] = $exportedOptions["consistentWith"];
        }
        if (isset($exportedOptions["profile"])) {
            $request["profile_mode"] = $exportedOptions["profile"];
        }
        print_r($request);
        $pendingCall = $this->client->query()->Query(new QueryRequest($request), [], ['timeout' => $timeout]);
        $res = $pendingCall->responses();
        $arrayOfResults = [];
        foreach ($res as $result) {
            $arrayOfResults[] = new QueryResult($result, QueryOptions::getTranscoder($options));
        }
        print_r("array of results:");
        print_r($arrayOfResults);
        return new QueryResult($res, QueryOptions::getTranscoder($options));
    }

    private function getTuningOptions(array $options): array
    {
        $tuningOptions = [];
        if (isset($options["maxParallelism"])) {
            $tuningOptions["max_parallelism"] = $options["maxParallelism"];
        }
        if (isset($options["pipelineBatch"])) {
            $tuningOptions["pipeline_batch"] = $options["pipelineBatch"];
        }
        if (isset($options["pipelineCap"])) {
            $tuningOptions["pipeline_cap"] = $options["pipelineCap"];
        }
        if (isset($options["scanWait"])) {
            $seconds = floor($options["scanWait"] / 1000);
            $nanos = ($options["scanWait"] % 1000) * 1e6;
            $tuningOptions["scan_wait"] = new Duration(['seconds' => $seconds, 'nanos' => $nanos]);
        }
        if (isset($options["scanCap"])) {
            $tuningOptions["scan_cap"] = $options["scanCap"];
        }
        if (isset($options["metrics"])) {
            $tuningOptions["disable_metrics"] = !$options["metrics"];
        }
        return $tuningOptions;
    }
}
