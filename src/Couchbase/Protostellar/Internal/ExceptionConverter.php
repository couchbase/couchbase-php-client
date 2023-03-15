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

use Couchbase\Exception\AmbiguousTimeoutException;
use Couchbase\Exception\AuthenticationFailureException;
use Couchbase\Exception\BucketExistsException;
use Couchbase\Exception\BucketNotFoundException;
use Couchbase\Exception\CasMismatchException;
use Couchbase\Exception\CollectionExistsException;
use Couchbase\Exception\CollectionNotFoundException;
use Couchbase\Exception\CouchbaseException;
use Couchbase\Exception\DecodingFailureException;
use Couchbase\Exception\DocumentExistsException;
use Couchbase\Exception\DocumentNotFoundException;
use Couchbase\Exception\FeatureNotAvailableException;
use Couchbase\Exception\IndexExistsException;
use Couchbase\Exception\IndexNotFoundException;
use Couchbase\Exception\InternalServerFailureException;
use Couchbase\Exception\InvalidArgumentException;
use Couchbase\Exception\RequestCanceledException;
use Couchbase\Exception\ScopeExistsException;
use Couchbase\Exception\ScopeNotFoundException;
use Couchbase\HttpException;
use Couchbase\Protostellar\ProtostellarRequest;
use Couchbase\Protostellar\RequestBehaviour;
use Couchbase\Protostellar\Retries\RetryOrchestrator;
use Couchbase\Protostellar\Retries\RetryReason;
use Exception;
use Google\Rpc\Code;
use Google\Rpc\Status;
use GPBMetadata\Google\Rpc\ErrorDetails;
use stdClass;

class ExceptionConverter
{
    private const TYPE_URL_PRECONDITION_FAILURE = "type.googleapis.com/google.rpc.PreconditionFailure";
    private const TYPE_URL_RESOURCE_INFO = "type.googleapis.com/google.rpc.ResourceInfo";
    private const TYPE_URL_ERROR_INFO = "type.googleapis.com/google.rpc/ErrorInfo";
    private const TYPE_URL_BAD_REQUEST = "type.googleapis.com/google.rpc.BadRequest";


    /**
     * @throws HttpException
     */
    public static function convertError(stdClass $status, ProtostellarRequest $request): RequestBehaviour
    {
        try {
            ErrorDetails::initOnce();
            $details = $status->metadata["grpc-status-details-bin"][0];
            $s = new Status();
            $s->mergeFromString($details);
            $anyDetails = $s->getDetails()[0];
            $typeUrl = $anyDetails->getTypeUrl();
            switch ($typeUrl) {
                case self::TYPE_URL_PRECONDITION_FAILURE:
                    $preconditionFailure = $anyDetails->unpack();
                    $preconditionFailure->discardUnknownFields();
                    if (count($preconditionFailure->getViolations()) > 0) {
                        $violation = $preconditionFailure->getViolations()[0];
                        $type = $violation->getType();
                        if ($type == "CAS") {
                            return RequestBehaviour::fail(new CasMismatchException($s->getMessage()));
                        } elseif ($type == "LOCKED") {
                            return RetryOrchestrator::maybeRetry($request, new RetryReason(RetryReason::KV_LOCKED));
                        }
                    }
                    break;
                case self::TYPE_URL_RESOURCE_INFO:
                    $resourceInfo = $anyDetails->unpack();
                    $resourceInfo->discardUnknownFields();
                    $request->appendContext("resourceName", $resourceInfo->getResourceName());
                    $request->appendContext("resourceType", $resourceInfo->getResourceType());
                    if ($s->getCode() == Code::NOT_FOUND) {
                        if ($resourceInfo->getResourceType() == "document") {
                            return RequestBehaviour::fail(new DocumentNotFoundException("Specified document was not found"));
                        } elseif ($resourceInfo->getResourceType() == "index") {
                            return RequestBehaviour::fail(new IndexNotFoundException("Specified index was not found"));
                        } elseif ($resourceInfo->getResourceType() == "bucket") {
                            return RequestBehaviour::fail(new BucketNotFoundException("Specified bucket was not found"));
                        } elseif ($resourceInfo->getResourceType() == "scope") {
                            return RequestBehaviour::fail(new ScopeNotFoundException("Specified scope was not found"));
                        } elseif ($resourceInfo->getResourceType() == "collection") {
                            return RequestBehaviour::fail(new CollectionNotFoundException("Specified collection was not found"));
                        }
                    } elseif ($s->getCode() == Code::ALREADY_EXISTS) {
                        if ($resourceInfo->getResourceType() == "document") {
                            return RequestBehaviour::fail(new DocumentExistsException("Specified document already exists"));
                        } elseif ($resourceInfo->getResourceType() == "index") {
                            return RequestBehaviour::fail(new IndexExistsException("Specified index already exists"));
                        } elseif ($resourceInfo->getResourceType() == "bucket") {
                            return RequestBehaviour::fail(new BucketExistsException("Specified bucket already exists"));
                        } elseif ($resourceInfo->getResourceType() == "scope") {
                            return RequestBehaviour::fail(new ScopeExistsException("Specified scope already exists"));
                        } elseif ($resourceInfo->getResourceType() == "collection") {
                            return RequestBehaviour::fail(new CollectionExistsException("Specified collection already exists"));
                        }
                    }
                    break;
                case self::TYPE_URL_ERROR_INFO:
                    $errorInfo = $anyDetails->unpack();
                    $errorInfo->discardUnknownFields();
                    $request->appendContext("errorReason", $errorInfo->getReason());
                    $request->appendContext("errorDomain", $errorInfo->getDomain());
                    $request->appendContext("errorMetadata", $errorInfo->getMetadata());
                    break;
                case self::TYPE_URL_BAD_REQUEST:
                    $badRequest = $anyDetails->unpack();
                    $badRequest->discardUnknownFields();
                    if (count($badRequest->getFieldViolations()) > 0) {
                        $fieldViolation = $badRequest->getFieldViolations()[0];
                        $request->appendContext("field", $fieldViolation->getField());
                        $request->appendContext("description", $fieldViolation->getDescription());
                    }
                    break;
                default:
                    return RequestBehaviour::fail(new DecodingFailureException("Failed to decode GRPC response - Unknown typeURL"));
            }
        } catch (Exception $exception) {
            return RequestBehaviour::fail(new DecodingFailureException("Failed to decode GRPC response"));
        }
        return self::convertToCouchbaseException($s, $request);
    }

    private static function convertToCouchbaseException(Status $status, ProtostellarRequest $request): RequestBehaviour
    {
        switch ($status->getCode()) {
            case Code::CANCELLED:
                return RequestBehaviour::fail(new RequestCanceledException("Request cancelled by server"));
            case Code::INTERNAL:
                return RequestBehaviour::fail(new InternalServerFailureException());
            case Code::INVALID_ARGUMENT:
                return RequestBehaviour::fail(new InvalidArgumentException("Invalid argument provided"));
            case Code::DEADLINE_EXCEEDED:
                return RequestBehaviour::fail(new AmbiguousTimeoutException("The server reported the operation timeout, and the state might have been changed"));
            case Code::PERMISSION_DENIED:
                return RequestBehaviour::fail(new AuthenticationFailureException("The server reported that permission to the resource was denied"));
            case Code::UNIMPLEMENTED:
                return RequestBehaviour::fail(new FeatureNotAvailableException($status->getMessage()));
            case Code::UNAVAILABLE:
                return RetryOrchestrator::maybeRetry($request, new RetryReason(RetryReason::SOCKET_NOT_AVAILABLE));
            default:
                return RequestBehaviour::fail(new CouchbaseException($status->getMessage()));
        }
    }
}
