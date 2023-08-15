<?php

namespace Couchbase;

/**
 * Interface for results created by the lookupInReplica operation.
 */
class LookupInReplicaResult extends LookupInResult
{
    private bool $isReplica;

    /**
     * @internal
     *
     * @param array $response
     * @param Transcoder $transcoder
     *
     * @since 4.1.6
     */
    public function __construct(array $response, Transcoder $transcoder)
    {
        parent::__construct($response, $transcoder);
        $this->isReplica = $response["isReplica"];
    }

    /**
     * Returns whether the result came from a replica server
     *
     * @return bool
     * @since 4.1.6
     */
    public function isReplica(): bool
    {
        return $this->isReplica;
    }
}
