<?php

namespace Couchbase\Protostellar;

use Couchbase\Exception\InvalidArgumentException;
use Exception;

class RequestFactory
{
    /**
     * @throws InvalidArgumentException
     */
    public static function makeRequest(callable $getRequest, array $params): mixed
    {
        try {
            return $getRequest(...$params);
        } catch (Exception $exception) {
            throw new InvalidArgumentException(sprintf("Error creating GRPC request: %s", $exception->getMessage()));
        }
    }
}