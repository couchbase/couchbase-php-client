<?php

/*
 * Copyright 2022-Present Couchbase, Inc.
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

namespace Couchbase\StellarNebula;

class ScanTerm
{
    private string $term;
    private ?bool $exclusive;

    public function __construct(string $term, bool $exclusive = false)
    {
        $this->term = $term;
        $this->exclusive = $exclusive;
    }
    public static function scanTermMinimum(bool $exclusive = false): ScanTerm
    {
        return new ScanTerm("\x00", $exclusive);
    }

    public static function scanTermMaximum(bool $exclusive = false): ScanTerm
    {
        return new ScanTerm("\xFF", $exclusive);
    }

    public function getTerm(): string
    {
        return $this->term;
    }

    public function getExclusive(): bool
    {
        return $this->exclusive;
    }
}
