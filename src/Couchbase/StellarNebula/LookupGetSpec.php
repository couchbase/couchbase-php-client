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

namespace Couchbase\StellarNebula;

use Couchbase\StellarNebula\Generated\KV\V1\LookupInRequest\Spec\Operation;

class LookupGetSpec implements LookupInSpec
{
    private string $path;
    private bool $isXattr;

    public function __construct(string $path, bool $isXattr = false)
    {
        $this->path = $path;
        $this->isXattr = $isXattr;
    }

    public static function build(string $path, bool $isXattr = false): LookupGetSpec
    {
        return new LookupGetSpec($path, $isXattr);
    }


    public function export(): array
    {
        $flags = [
            'xattr' => $this->isXattr
        ];
        return [
            'operation' => Operation::GET,
            'flags' => $flags,
            'path' => $this->path,
        ];
    }
}