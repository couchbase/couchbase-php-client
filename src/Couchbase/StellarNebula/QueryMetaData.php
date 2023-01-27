<?php

declare(strict_types=1);

namespace Couchbase\StellarNebula;

use Couchbase\StellarNebula\Generated\Query\V1\QueryResponse\MetaData\MetaDataStatus;

class QueryMetaData
{
    private string $status;
    private string $requestId;
    private string $clientContextId;
    private string $signature;
    private ?string $profile = null;
    private array $warnings;
    private ?array $metrics;

    public function __construct(array $meta)
    {
        $this->status = MetaDataStatus::name($meta["status"]);
        $this->requestId = $meta["request_id"];
        $this->clientContextId = $meta["client_context_id"];
        $this->signature = $meta["signature"];
        if (array_key_exists("profile", $meta)) {
            $this->profile = $meta["profile"];
        }
        $this->warnings = [];
        if (array_key_exists("warnings", $meta)) {
            foreach ($meta["warnings"] as $warning) {
                $this->warnings[] = new QueryWarning($warning);
            }
        }
        if (array_key_exists("metrics", $meta)) {
            $this->metrics = $meta["metrics"];
        } else {
            $this->metrics = [
                "error_count" => 0,
                "mutation_count" => 0,
                "result_count" => 0,
                "result_size" => 0,
                "sort_count" => 0,
                "warning_count" => 0,
                "elapsed_time" => 0,
                "execution_time" => 0,
            ];
        }
    }

    public function status(): ?string
    {
        return $this->status;
    }

    public function requestId(): ?string
    {
        return $this->requestId;
    }

    public function clientContextId(): ?string
    {
        return $this->clientContextId;
    }

    public function signature(): ?array
    {
        if ($this->signature == null) {
            return null;
        }
        return json_decode($this->signature, true);
    }

    public function warnings(): ?array
    {
        return $this->warnings;
    }

    /**
     * @throws \Exception
     */
    public function errors(): ?array
    {
        throw new \Exception("errors are no longer supported");
    }

    public function metrics(): ?array
    {
        return $this->metrics;
    }

    public function profile(): ?array
    {
        if ($this->profile == null) {
            return null;
        }
        return json_decode($this->profile, true);
    }
}
