<?php

namespace Couchbase;

class LookupInReplicaResult extends LookupInResult
{
    private bool $isReplica;

    public function __construct(array $response, Transcoder $transcoder)
    {
        parent::__construct($response, $transcoder);
        $this->isReplica = $response["isReplica"];
    }

    public function isReplica(): bool
    {
        return $this->isReplica;
    }
}
