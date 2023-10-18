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

use Couchbase\Protostellar\Internal\ExceptionConverter;
use Exception;

use const Grpc\STATUS_OK;

class ProtostellarOperationRunner
{
    /**
     * @throws Exception
     */
    public static function runUnary(ProtostellarRequest $request, callable $grpcCall): mixed
    {
        while (true) {
            $pendingCall = $grpcCall(
                $request->grpcRequest(),
                [],
                ['timeout' => self::calculateGRPCTimeout($request->absoluteTimeout())]
            );
            [$response, $status] = $pendingCall->wait();
            if ($status->code !== STATUS_OK) {
                $behaviour = ExceptionConverter::convertError($status, $request);
                if (!is_null($behaviour->retryDuration())) {
                    usleep($behaviour->retryDuration());
                    continue;
                } else {
                    throw $behaviour->exception();
                }
            }
            return $response;
        }
    }
    /**
     * @throws Exception
     */
    public static function runStreaming(ProtostellarRequest $request, callable $grpcFunc): mixed
    {
        while (true) {
            $pendingCall = $grpcFunc(
                $request->grpcRequest(),
                [],
                ['timeout' => self::calculateGRPCTimeout($request->absoluteTimeout())]
            );
            $responses = $pendingCall->responses();
            $result = iterator_to_array($responses);
            $status = $pendingCall->getStatus();
            if ($status->code !== STATUS_OK) {
                $behaviour = ExceptionConverter::convertError($status, $request);
                if (!is_null($behaviour->retryDuration())) {
                    usleep($behaviour->retryDuration());
                    continue;
                } else {
                    throw $behaviour->exception();
                }
            }
            return $result;
        }
    }

    private static function calculateGRPCTimeout(float $absoluteTimeout): float
    {
        return $absoluteTimeout - microtime(true) * 1e6;
    }
}
