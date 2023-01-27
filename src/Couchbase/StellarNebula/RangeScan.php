<?php

namespace Couchbase\StellarNebula;

class RangeScan implements ScanType
{
    private ScanTerm $start;
    private ScanTerm $end;

    public function __construct(ScanTerm $start = null, ScanTerm $end = null)
    {
        $this->start = $start == null ? ScanTerm::scanTermMinimum() : $start;
        $this->end = $end == null ? ScanTerm::scanTermMaximum() : $end;
    }

    public static function rangeScanFromPrefix(string $prefix): RangeScan
    {
        return new RangeScan(
            new ScanTerm($prefix),
            new ScanTerm($prefix . "\xFF", true)
        );
    }

    public function getScanType(): string
    {
        return "range_scan";
    }

    public function getStart(): ScanTerm
    {
        return $this->start;
    }

    public function getEnd(): ScanTerm
    {
        return $this->end;
    }
}
