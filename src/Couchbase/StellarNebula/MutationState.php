<?php

declare(strict_types=1);

namespace Couchbase\StellarNebula;

class MutationState
{
    private array $tokens;

    public function __construct()
    {
    }

    public function add(MutationResult $source): MutationState
    {
        $token = $source->mutationToken();
        if ($token != null) {
            $this->tokens[] = $token;
        }
        return $this;
    }

    public function tokens(): array
    {
        return $this->tokens;
    }

    public function export(): array
    {
        $state = [];
        /** @var MutationToken $token */
        foreach ($this->tokens as $token) {
            $state[] = [
                "vbucket_id" => $token->vbucketId(),
                "vbucket_uuid" => hexdec($token->vbucketUuid()),
                "seq_no" => hexdec($token->sequenceNumber()),
                "bucket_name" => $token->bucket(),
            ];
        }

        return $state;
    }
}
