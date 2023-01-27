<?php

declare(strict_types=1);

namespace Couchbase\StellarNebula;

class QueryWarning
{
    private int $code;
    private string $message;

    public function __construct(array $warning)
    {
        $this->code = $warning["code"];
        $this->message = $warning["message"];
    }

    public function code(): int
    {
        return $this->code;
    }

    public function message(): int
    {
        return $this->message;
    }
}
