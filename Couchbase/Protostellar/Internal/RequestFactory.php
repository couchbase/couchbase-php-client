<?php

namespace Couchbase\Protostellar\Internal;

use Couchbase\Exception\FeatureNotAvailableException;
use Couchbase\Exception\InvalidArgumentException;
use Exception;

class RequestFactory
{
    /**
     * @throws InvalidArgumentException
     * @throws FeatureNotAvailableException
     * @internal
     */
    public static function makeRequest(callable $getRequest, array $params): mixed
    {
        try {
            return $getRequest(...$params);
        } catch (FeatureNotAvailableException $exception) {
            throw $exception;
        } catch (Exception $exception) {
            throw new InvalidArgumentException(sprintf("Error creating GRPC request: %s", $exception->getMessage()));
        }
    }
}
