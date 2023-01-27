<?php

namespace Couchbase\StellarNebula;

class CounterResult extends MutationResult
{
    private int $value;

    public function __construct(
        string $bucket,
        string $scope,
        string $collection,
        string $id,
        int|string|null $cas,
        MutationToken $token,
        int|string $value
    )
    {
        parent::__construct($bucket, $scope, $collection, $id, $cas, $token);
        $this->value = $value;
    }

    public function content(): int
    {
        return $this->value;
    }
}
