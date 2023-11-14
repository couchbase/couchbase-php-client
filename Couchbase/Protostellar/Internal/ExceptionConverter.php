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
use Couchbase\Exception\DocumentExistsException;
use Couchbase\Exception\DocumentNotFoundException;
use Couchbase\Exception\DocumentNotJsonException;
use Couchbase\Exception\FeatureNotAvailableException;
use Couchbase\Exception\IndexExistsException;
use Couchbase\Exception\IndexNotFoundException;
use Couchbase\Exception\InternalServerFailureException;
use Couchbase\Exception\InvalidArgumentException;
use Couchbase\Exception\NumberTooBigException;
use Couchbase\Exception\PathExistsException;
use Couchbase\Exception\PathMismatchException;
use Couchbase\Exception\PathNotFoundException;
use Couchbase\Exception\PathTooDeepException;
use Couchbase\Exception\PermissionDeniedException;
use Couchbase\Exception\RequestCanceledException;
use Couchbase\Exception\ScopeExistsException;
use Couchbase\Exception\ScopeNotFoundException;
use Couchbase\Exception\ValueInvalidException;
use Couchbase\Exception\ValueTooLargeException;
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
    private const TYPE_URL_ERROR_INFO = "type.googleapis.com/google.rpc.ErrorInfo";

    public static function convertError(stdClass $status, ProtostellarRequest $request): RequestBehaviour
    {
        try {
            if ($status->details == "Deadline Exceeded") {
                return $request->cancelDueToTimeout();
            }
            ErrorDetails::initOnce();
            $details = $status->metadata["grpc-status-details-bin"][0] ?? null;
            if (is_null($details)) {
                throw new CouchbaseException($status->details);
            }
            $protoStatus = new Status();
            $protoStatus->mergeFromString($details);
            $anyDetails = $protoStatus->getDetails()[0]; // Only parsing first detail block
            $typeUrl = $anyDetails->getTypeUrl();
            $request->appendContext("server", $protoStatus->getMessage());
            switch ($typeUrl) {
                case self::TYPE_URL_PRECONDITION_FAILURE:
                    $preconditionFailure = $anyDetails->unpack();
                    $preconditionFailure->discardUnknownFields();
                    $violation = $preconditionFailure->getViolations()[0]; // Only parsing first violation
                    $request->appendContext("preconditionViolation", $violation->getType());
                    $type = $violation->getType();
                    if ($type == "LOCKED") {
                        return RetryOrchestrator::maybeRetry($request, new RetryReason(RetryReason::KV_LOCKED));
                    } elseif ($type == "VALUE_TOO_LARGE") {
                        return RequestBehaviour::fail(new ValueTooLargeException($protoStatus->getMessage()));
                    } elseif ($type == "PATH_MISMATCH") {
                        return RequestBehaviour::fail(new PathMismatchException($protoStatus->getMessage()));
                    } elseif ($type == "DOC_TOO_DEEP") {
                        return RequestBehaviour::fail(new PathTooDeepException($protoStatus->getMessage()));
                    } elseif ($type == "DOC_NOT_JSON") {
                        return RequestBehaviour::fail(new DocumentNotJsonException($protoStatus->getMessage()));
                    } elseif ($type == "PATH_VALUE_OUT_OF_RANGE") {
                        return RequestBehaviour::fail(new NumberTooBigException($protoStatus->getMessage()));
                    } elseif ($type == "VALUE_OUT_OF_RANGE") {
                        return RequestBehaviour::fail(new ValueInvalidException($protoStatus->getMessage()));
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
                        } elseif ($resourceInfo->getResourceType() == "searchindex" || $resourceInfo->getResourceType() == "queryindex") {
                            return RequestBehaviour::fail(new IndexNotFoundException(message: "Specified index was not found", context: $request->context()));
                        } elseif ($resourceInfo->getResourceType() == "bucket") {
                            return RequestBehaviour::fail(new BucketNotFoundException(message: "Specified bucket was not found", context: $request->context()));
                        } elseif ($resourceInfo->getResourceType() == "scope") {
                            return RequestBehaviour::fail(new ScopeNotFoundException(message: "Specified scope was not found", context: $request->context()));
                        } elseif ($resourceInfo->getResourceType() == "collection") {
                            return RequestBehaviour::fail(new CollectionNotFoundException(message: "Specified collection was not found", context: $request->context()));
                        } elseif ($resourceInfo->getResourceType() == "path") {
                            return RequestBehaviour::fail(new PathNotFoundException(message: "Specified path was not found", context: $request->context()));
                        }
                    } elseif ($protoStatus->getCode() == Code::ALREADY_EXISTS) {
                        if ($resourceInfo->getResourceType() == "document") {
                            return RequestBehaviour::fail(new DocumentExistsException(message: "Specified document already exists", context: $request->context()));
                        } elseif ($resourceInfo->getResourceType() == "searchindex" || $resourceInfo->getResourceType() == "queryindex") {
                            return RequestBehaviour::fail(new IndexExistsException(message: "Specified index already exists", context: $request->context()));
                        } elseif ($resourceInfo->getResourceType() == "bucket") {
                            return RequestBehaviour::fail(new BucketExistsException(message: "Specified bucket already exists", context: $request->context()));
                        } elseif ($resourceInfo->getResourceType() == "scope") {
                            return RequestBehaviour::fail(new ScopeExistsException(message: "Specified scope already exists", context: $request->context()));
                        } elseif ($resourceInfo->getResourceType() == "collection") {
                            return RequestBehaviour::fail(new CollectionExistsException(message: "Specified collection already exists", context: $request->context()));
                        } elseif ($resourceInfo->getResourceType() == "path") {
                            return RequestBehaviour::fail(new PathExistsException(message: "Specified path already exists", context: $request->context()));
                        }
                    } elseif ($protoStatus->getCode() == Code::PERMISSION_DENIED) {
                        if ($resourceInfo->getResourceType() == "user") {
                            return RequestBehaviour::fail(new AuthenticationFailureException("Your username or password is invalid", context: $request->context()));
                        } else {
                            return RequestBehaviour::fail(new PermissionDeniedException(message: $protoStatus->getMessage(), context: $request->context()));
                        }
                    }
                    break;
                case self::TYPE_URL_ERROR_INFO:
                    $errorInfo = $anyDetails->unpack();
                    $errorInfo->discardUnknownFields();
                    $request->appendContext("reason", $errorInfo->getReason());
                    if ($protoStatus->getCode() == Code::ABORTED) {
                        if ($errorInfo->getReason() == "CAS_MISMATCH") {
                            return RequestBehaviour::fail(new CasMismatchException(message: $protoStatus->getMessage(), context: $request->context()));
                        }
                    }
                    break;
                default:
                    break;
            }
        } catch (Exception) {
            return RequestBehaviour::fail(new CouchbaseException(message: $status->details, context: $request->context()));
        }
        return self::convertToCouchbaseException($protoStatus, $request);
    }

    private static function convertToCouchbaseException(Status $status, ProtostellarRequest $request): RequestBehaviour
    {
        switch ($status->getCode()) {
            case Code::CANCELLED:
                return RequestBehaviour::fail(new RequestCanceledException(message: "Request cancelled by server", context: $request->context()));
            case Code::INTERNAL:
                return RequestBehaviour::fail(new InternalServerFailureException(message: $status->getMessage(), context: $request->context()));
            case Code::INVALID_ARGUMENT:
                return RequestBehaviour::fail(new InvalidArgumentException(message: $status->getMessage(), context: $request->context()));
            case Code::DEADLINE_EXCEEDED:
                return RequestBehaviour::fail(new AmbiguousTimeoutException(message: "The server reported the operation timeout, and the state might have been changed", context: $request->context()));
            case Code::UNIMPLEMENTED:
                return RequestBehaviour::fail(new FeatureNotAvailableException(message: $status->getMessage(), context: $request->context()));
            case Code::UNAUTHENTICATED:
                return RequestBehaviour::fail(new AuthenticationFailureException(message: $status->getMessage(), context: $request->context()));
            case Code::UNAVAILABLE:
                return RetryOrchestrator::maybeRetry($request, new RetryReason(RetryReason::SOCKET_NOT_AVAILABLE));
            default:
                return RequestBehaviour::fail(new CouchbaseException(message: $status->getMessage(), context: $request->context()));
        }
    }
}
