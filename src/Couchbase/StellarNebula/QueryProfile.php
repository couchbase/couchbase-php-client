<?php

declare(strict_types=1);

namespace Couchbase\StellarNebula;

interface QueryProfile
{
    /**
     * Set profiling to off
     */
    public const OFF = "off";

    /**
     * Set profiling to include phase timings
     */
    public const PHASES = "phases";

    /**
     * Set profiling to include execution timings
     */
    public const TIMINGS = "timings";
}