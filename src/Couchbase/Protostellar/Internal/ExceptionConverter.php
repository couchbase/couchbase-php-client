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
            $protoStatus = new Status();
            $protoStatus->mergeFromString($details);
            $anyDetails = $protoStatus->getDetails()[0];
            $typeUrl = $anyDetails->getTypeUrl();
            switch ($typeUrl) {
                case self::TYPE_URL_PRECONDITION_FAILURE:
                    $preconditionFailure = $anyDetails->unpack();
                    $preconditionFailure->discardUnknownFields();
                    if (count($preconditionFailure->getViolations()) > 0) {
                        $violation = $preconditionFailure->getViolations()[0];
                        $type = $violation->getType();
                        if ($type == "CAS") {
                            return RequestBehaviour::fail(new CasMismatchException($protoStatus->getMessage()));
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
                    if ($protoStatus->getCode() == Code::NOT_FOUND) {
                        if ($resourceInfo->getResourceType() == "document") {
                            return RequestBehaviour::fail(new DocumentNotFoundException(message: "Specified document was not found", context: $request->context()));
                        } elseif ($resourceInfo->getResourceType() == "index") {
                            return RequestBehaviour::fail(new IndexNotFoundException(message: "Specified index was not found", context: $request->context()));
                        } elseif ($resourceInfo->getResourceType() == "bucket") {
                            return RequestBehaviour::fail(new BucketNotFoundException(message: "Specified bucket was not found", context: $request->context()));
                        } elseif ($resourceInfo->getResourceType() == "scope") {
                            return RequestBehaviour::fail(new ScopeNotFoundException(message: "Specified scope was not found", context: $request->context()));
                        } elseif ($resourceInfo->getResourceType() == "collection") {
                            return RequestBehaviour::fail(new CollectionNotFoundException(message: "Specified collection was not found", context: $request->context()));
                        }
                    } elseif ($protoStatus->getCode() == Code::ALREADY_EXISTS) {
                        if ($resourceInfo->getResourceType() == "document") {
                            return RequestBehaviour::fail(new DocumentExistsException(message: "Specified document already exists", context: $request->context()));
                        } elseif ($resourceInfo->getResourceType() == "index") {
                            return RequestBehaviour::fail(new IndexExistsException(message: "Specified index already exists", context: $request->context()));
                        } elseif ($resourceInfo->getResourceType() == "bucket") {
                            return RequestBehaviour::fail(new BucketExistsException(message: "Specified bucket already exists", context: $request->context()));
                        } elseif ($resourceInfo->getResourceType() == "scope") {
                            return RequestBehaviour::fail(new ScopeExistsException(message: "Specified scope already exists", context: $request->context()));
                        } elseif ($resourceInfo->getResourceType() == "collection") {
                            return RequestBehaviour::fail(new CollectionExistsException(message: "Specified collection already exists", context: $request->context()));
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
                    return RequestBehaviour::fail(new DecodingFailureException(message: "Failed to decode GRPC response - Unknown typeURL", context: $request->context()));
            }
        } catch (Exception) {
            return RequestBehaviour::fail(new DecodingFailureException(message: "Failed to decode GRPC response", context: $request->context()));
        }
        return self::convertToCouchbaseException($protoStatus, $request);
    }

    private static function convertToCouchbaseException(Status $status, ProtostellarRequest $request): RequestBehaviour
    {
        switch ($status->getCode()) {
            case Code::CANCELLED:
                return RequestBehaviour::fail(new RequestCanceledException(message: "Request cancelled by server", context: $request->context()));
            case Code::INTERNAL:
                return RequestBehaviour::fail(new InternalServerFailureException(context: $request->context()));
            case Code::INVALID_ARGUMENT:
                return RequestBehaviour::fail(new InvalidArgumentException(message: "Invalid argument provided", context: $request->context()));
            case Code::DEADLINE_EXCEEDED:
                return RequestBehaviour::fail(new AmbiguousTimeoutException(message: "The server reported the operation timeout, and the state might have been changed", context: $request->context()));
            case Code::PERMISSION_DENIED:
                return RequestBehaviour::fail(new AuthenticationFailureException(message: "The server reported that permission to the resource was denied", context: $request->context()));
            case Code::UNIMPLEMENTED:
                return RequestBehaviour::fail(new FeatureNotAvailableException(message: $status->getMessage(), context: $request->context()));
            case Code::UNAVAILABLE:
                return RetryOrchestrator::maybeRetry($request, new RetryReason(RetryReason::SOCKET_NOT_AVAILABLE));
            default:
                return RequestBehaviour::fail(new CouchbaseException(message: $status->getMessage(), context: $request->context()));
        }
    }
}
