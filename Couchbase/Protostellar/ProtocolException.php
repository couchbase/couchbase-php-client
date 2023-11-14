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

namespace Couchbase\Protostellar;

use Exception;
use Throwable;

use const Grpc\STATUS_ABORTED;
use const Grpc\STATUS_ALREADY_EXISTS;
use const Grpc\STATUS_CANCELLED;
use const Grpc\STATUS_DATA_LOSS;
use const Grpc\STATUS_DEADLINE_EXCEEDED;
use const Grpc\STATUS_FAILED_PRECONDITION;
use const Grpc\STATUS_INTERNAL;
use const Grpc\STATUS_INVALID_ARGUMENT;
use const Grpc\STATUS_NOT_FOUND;
use const Grpc\STATUS_OK;
use const Grpc\STATUS_OUT_OF_RANGE;
use const Grpc\STATUS_PERMISSION_DENIED;
use const Grpc\STATUS_RESOURCE_EXHAUSTED;
use const Grpc\STATUS_UNAUTHENTICATED;
use const Grpc\STATUS_UNAVAILABLE;
use const Grpc\STATUS_UNIMPLEMENTED;
use const Grpc\STATUS_UNKNOWN;

class ProtocolException extends Exception
{
    private ?object $grpcStatus = null;

    public function __construct($message, $grpcStatus = null, Throwable $previous = null)
    {
        $code = 0;
        if ($grpcStatus) {
            $message = sprintf(
                "%s (%s, %s)",
                $message,
                self::grpcCodeToString($grpcStatus->code),
                $grpcStatus->details
            );
            $code = $grpcStatus->code;
        }
        parent::__construct($message, $code, $previous);
        $this->grpcStatus = $grpcStatus;
    }

    private static function grpcCodeToString(int $code): string
    {
        switch ($code) {
            case STATUS_OK:
                return "OK";
            case STATUS_CANCELLED:
                return "CANCELLED";
            case STATUS_UNKNOWN:
                return "UNKNOWN";
            case STATUS_INVALID_ARGUMENT:
                return "INVALID_ARGUMENT";
            case STATUS_DEADLINE_EXCEEDED:
                return "DEADLINE_EXCEEDED";
            case STATUS_NOT_FOUND:
                return "NOT_FOUND";
            case STATUS_ALREADY_EXISTS:
                return "ALREADY_EXISTS";
            case STATUS_PERMISSION_DENIED:
                return "PERMISSION_DENIED";
            case STATUS_UNAUTHENTICATED:
                return "UNAUTHENTICATED";
            case STATUS_RESOURCE_EXHAUSTED:
                return "RESOURCE_EXHAUSTED";
            case STATUS_FAILED_PRECONDITION:
                return "FAILED_PRECONDITION";
            case STATUS_ABORTED:
                return "ABORTED";
            case STATUS_OUT_OF_RANGE:
                return "OUT_OF_RANGE";
            case STATUS_UNIMPLEMENTED:
                return "UNIMPLEMENTED";
            case STATUS_INTERNAL:
                return "INTERNAL";
            case STATUS_UNAVAILABLE:
                return "UNAVAILABLE";
            case STATUS_DATA_LOSS:
                return "DATA_LOSS";
            default:
                break;
        }
        return sprintf("GRPC_%d", $code);
    }
}
