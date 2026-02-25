<?php

/**
 * Copyright 2014-Present Couchbase, Inc.
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

namespace Couchbase\Observability;

use Couchbase\AnalyticsOptions;
use Couchbase\DurabilityLevel;
use Couchbase\Exception\CouchbaseException;
use Couchbase\QueryOptions;
use Couchbase\RequestSpan;
use Couchbase\RequestTracer;
use Couchbase\Meter;
use Throwable;

class ObservabilityHandler
{
    private RequestTracer $tracer;
    private Meter $meter;
    private RequestSpan $opSpan;
    private ?string $clusterName = null;
    private ?string $clusterUuid = null;
    private array $meterAttributes;
    private float $startTime;

    public function __construct(
        $core,
        string $opName,
        ?RequestSpan $parentSpan,
        RequestTracer $tracer,
        Meter $meter
    )
    {
        $this->populateClusterLabels($core);
        $this->tracer = $tracer;
        $this->meter = $meter;
        $this->opSpan = $this->createSpan($opName, $parentSpan);
        $this->meterAttributes = $this->createMeterAttributes();
        $this->startTime = microtime(true);
    }

    private function populateClusterLabels($core): void
    {
        $function = COUCHBASE_EXTENSION_NAMESPACE . '\\clusterLabels';
        $clusterLabels = $function($core);

        if (array_key_exists('clusterName', $clusterLabels)) {
            $this->clusterName = $clusterLabels['clusterName'];
        }
        if (array_key_exists('clusterUuid', $clusterLabels)) {
            $this->clusterUuid = $clusterLabels['clusterUuid'];
        }
    }

    public function getOpSpan()
    {
        return $this->opSpan;
    }

    /**
     * @throws Throwable
     */
    public function withRequestEncodingSpan(callable $callback)
    {
        $span = $this->createSpan(ObservabilityConstants::STEP_REQUEST_ENCODING, $this->opSpan);
        try {
            return $callback();
        } finally {
            $span->end();
        }
    }

    public function addService(string $service): void
    {
        $this->opSpan->addTag(ObservabilityConstants::ATTR_SERVICE, $service);
        $this->meterAttributes[ObservabilityConstants::ATTR_SERVICE] = $service;
    }

    public function addOperationName(string $name): void
    {
        $this->opSpan->addTag(ObservabilityConstants::ATTR_OPERATION_NAME, $name);
        $this->meterAttributes[ObservabilityConstants::ATTR_OPERATION_NAME] = $name;
    }

    public function addBucketName(string $name): void
    {
        $this->opSpan->addTag(ObservabilityConstants::ATTR_BUCKET_NAME, $name);
        $this->meterAttributes[ObservabilityConstants::ATTR_BUCKET_NAME] = $name;
    }

    public function addScopeName(string $name): void
    {
        $this->opSpan->addTag(ObservabilityConstants::ATTR_SCOPE_NAME, $name);
        $this->meterAttributes[ObservabilityConstants::ATTR_SCOPE_NAME] = $name;
    }

    public function addCollectionName(string $name): void
    {
        $this->opSpan->addTag(ObservabilityConstants::ATTR_COLLECTION_NAME, $name);
        $this->meterAttributes[ObservabilityConstants::ATTR_COLLECTION_NAME] = $name;
    }

    public function addDurabilityLevel(?string $level): void
    {
        if (is_null($level)) {
            return;
        }

        switch ($level) {
            case DurabilityLevel::MAJORITY:
                $this->opSpan->addTag(
                    ObservabilityConstants::ATTR_DURABILITY,
                    ObservabilityConstants::ATTR_VALUE_DURABILITY_MAJORITY
                );
                break;
            case DurabilityLevel::MAJORITY_AND_PERSIST_TO_ACTIVE:
                $this->opSpan->addTag(
                    ObservabilityConstants::ATTR_DURABILITY,
                    ObservabilityConstants::ATTR_VALUE_DURABILITY_MAJORITY_AND_PERSIST_TO_ACTIVE
                );
                break;
            case DurabilityLevel::PERSIST_TO_MAJORITY:
                $this->opSpan->addTag(
                    ObservabilityConstants::ATTR_DURABILITY,
                    ObservabilityConstants::ATTR_VALUE_DURABILITY_PERSIST_TO_MAJORITY
                );
                break;
            default:
        }
    }

    public function addRetries(int $retries): void
    {
        $this->opSpan->addTag(ObservabilityConstants::ATTR_RETRIES, $retries);
    }

    public function setSuccess(): void
    {
        $this->opSpan->setStatus(StatusCode::OK);
    }

    public function addError(Throwable $error): void
    {
        $this->opSpan->setStatus(StatusCode::ERROR);

        if ($error instanceof CouchbaseException && get_class($error) !== CouchbaseException::class) {
            $className = get_class($error);
            $parts = explode('\\', $className);
            $shortName = end($parts);
            if (str_ends_with($shortName, 'Exception')) {
                $shortName = substr($shortName, 0, -strlen('Exception'));
            }
            $this->meterAttributes[ObservabilityConstants::ATTR_ERROR_TYPE] = $shortName;
        } else {
            $this->meterAttributes[ObservabilityConstants::ATTR_ERROR_TYPE] = '_OTHER';
        }
    }

    public function addQueryStatement(string $statement, AnalyticsOptions|QueryOptions|null $options): void
    {
        if (
            is_null($options)
            || ($options instanceof QueryOptions && !QueryOptions::usingParameters($options))
            || ($options instanceof AnalyticsOptions && !AnalyticsOptions::usingParameters($options))
        ) {
            return;
        }
        $this->opSpan->addTag(ObservabilityConstants::ATTR_QUERY_STATEMENT, $statement);
    }

    public function end(): void
    {
        $this->opSpan->end();

        // Calculate duration in microseconds
        $durationUs = (int) round((microtime(true) - $this->startTime) * 1_000_000);

        $valueRecorder = $this->meter->valueRecorder(
            ObservabilityConstants::METER_NAME_OPERATION_DURATION,
            $this->meterAttributes
        );
        $valueRecorder->recordValue($durationUs);
    }

    private function createMeterAttributes(): array
    {
        $attrs = [
            ObservabilityConstants::ATTR_SYSTEM_NAME => ObservabilityConstants::ATTR_VALUE_SYSTEM_NAME,
            ObservabilityConstants::ATTR_RESERVED_UNIT => ObservabilityConstants::ATTR_VALUE_RESERVED_UNIT_SECONDS,
        ];
        if (!is_null($this->clusterName)) {
            $attrs[ObservabilityConstants::ATTR_CLUSTER_NAME] = $this->clusterName;
        }
        if (!is_null($this->clusterUuid)) {
            $attrs[ObservabilityConstants::ATTR_CLUSTER_UUID] = $this->clusterUuid;
        }

        return $attrs;
    }

    private function createSpan(string $name, $parent, ?int $startTimestampNanoseconds = null)
    {
        $span = $this->tracer->requestSpan($name, $parent, $startTimestampNanoseconds);

        $span->addTag(ObservabilityConstants::ATTR_SYSTEM_NAME, ObservabilityConstants::ATTR_VALUE_SYSTEM_NAME);
        if (!is_null($this->clusterName)) {
            $span->addTag(ObservabilityConstants::ATTR_CLUSTER_NAME, $this->clusterName);
        }
        if (!is_null($this->clusterUuid)) {
            $span->addTag(ObservabilityConstants::ATTR_CLUSTER_UUID, $this->clusterUuid);
        }

        return $span;
    }
}
