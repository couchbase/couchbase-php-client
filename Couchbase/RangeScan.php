<?php

/**
 * Copyright 2014-Present Couchbase, Inc.
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *     http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */

declare(strict_types=1);

namespace Couchbase;

/**
 * A RangeScan performs a scan on a range of keys with the range specified through a start and end ScanTerm
 */
class RangeScan implements ScanType
{
    private ?ScanTerm $from;
    private ?ScanTerm $to;

    /**
     * @param ScanTerm|null $from RangeScan start term. Defaults to minimum value
     * @param ScanTerm|null $to RangeScan from term. Defaults to maximum value
     *
     * @since 4.1.6
     */
    public function __construct(ScanTerm $from = null, ScanTerm $to = null)
    {
        $this->from = $from;
        $this->to = $to;
    }

    /**
     * Static helper to keep code more readable
     *
     * @param ScanTerm|null $from RangeScan start term. Defaults to minimum value
     * @param ScanTerm|null $to RangeScan from term. Defaults to maximum value
     *
     * @return RangeScan
     * @since 4.1.6
     */
    public static function build(ScanTerm $from = null, ScanTerm $to = null): RangeScan
    {
        return new RangeScan($from, $to);
    }

    /**
     * @param ScanTerm $from
     *
     * @return RangeScan
     * @since 4.1.6
     */
    public function from(ScanTerm $from): RangeScan
    {
        $this->from = $from;
        return $this;
    }

    /**
     * @param ScanTerm $to
     *
     * @return RangeScan
     * @since 4.1.6
     */
    public function to(ScanTerm $to): RangeScan
    {
        $this->to = $to;
        return $this;
    }

    /**
     * @internal
     *
     * @param RangeScan $rangeScan
     *
     * @return array
     * @since 4.1.6
     */
    public static function export(RangeScan $rangeScan): array
    {
        return [
            'type' => 'range_scan',
            'from' => $rangeScan->from == null ? null : $rangeScan->from->export($rangeScan->from),
            'to' => $rangeScan->to == null ? null : $rangeScan->to->export($rangeScan->to)
        ];
    }
}
