<?php

declare(strict_types=1);

namespace Couchbase\StellarNebula;

class QueryResult
{
    private ?QueryMetaData $meta = null;
    private array $rows;

    public function __construct(array $result, Transcoder $transcoder)
    {
        if (array_key_exists("meta_data", $result)) {
            $this->meta = new QueryMetaData($result["meta_data"]);
        }
        $this->rows = [];
        foreach ($result["rows"] as $row) {
            $this->rows[] = $transcoder->decode($row, 0);
        }
    }

    public function metaData(): ?QueryMetaData
    {
        return $this->meta;
    }

    public function rows(): ?array
    {
        return $this->rows;
    }
}
