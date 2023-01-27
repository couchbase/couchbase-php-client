<?php

declare(strict_types=1);

namespace Couchbase\StellarNebula;

interface QueryScanConsistency
{
    public const NOT_BOUNDED = "notBounded";

    public const REQUEST_PLUS = "requestPlus";

}