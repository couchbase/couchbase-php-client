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

namespace Couchbase\Observability;

use Couchbase\RequestTracer;
use Couchbase\RequestSpan;
use Couchbase\Meter;
use Throwable;

class ObservabilityContext
{
    private RequestTracer $tracer;
    private Meter $meter;

    /**
     * @var resource
     */
    private $core;

    private ?string $bucketName = null;
    private ?string $scopeName = null;
    private ?string $collectionName = null;
    private ?string $service = null;

    public function __construct(
        $core,
        RequestTracer $tracer,
        Meter $meter,
        ?string $bucketName = null,
        ?string $scopeName = null,
        ?string $collectionName = null,
        ?string $service = null
    )
    {
        $this->core = $core;
        $this->tracer = $tracer;
        $this->meter = $meter;
        $this->bucketName = $bucketName;
        $this->scopeName = $scopeName;
        $this->collectionName = $collectionName;
        $this->service = $service;
    }

    public static function from(
        ObservabilityContext $context,
        ?string $bucketName = null,
        ?string $scopeName = null,
        ?string $collectionName = null,
        ?string $service = null
    ): ObservabilityContext
    {
        return new ObservabilityContext(
            $context->core,
            $context->tracer,
            $context->meter,
            $bucketName ?? $context->bucketName,
            $scopeName ?? $context->scopeName,
            $collectionName ?? $context->collectionName,
            $service ?? $context->service
        );
    }

    public function recordOperation(
        string $opName,
        ?RequestSpan $parentSpan,
        callable $operation
    )
    {
        $handler = new ObservabilityHandler(
            $this->core,
            $opName,
            $parentSpan,
            $this->tracer,
            $this->meter
        );

        $handler->addOperationName($opName);
        if (!is_null($this->bucketName)) {
            $handler->addBucketName($this->bucketName);
        }
        if (!is_null($this->scopeName)) {
            $handler->addScopeName($this->scopeName);
        }
        if (!is_null($this->collectionName)) {
            $handler->addCollectionName($this->collectionName);
        }
        if (!is_null($this->service)) {
            $handler->addService($this->service);
        }

        try {
            $result = $operation($handler);
            $handler->setSuccess();
            return $result;
        } catch (Throwable $e) {
            $handler->addError($e);
            throw $e;
        } finally {
            $handler->end();
        }
    }

    public function close(): void
    {
        $this->tracer->close();
        $this->meter->close();
    }
}
