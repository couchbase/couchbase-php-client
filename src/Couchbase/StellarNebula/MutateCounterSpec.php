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

use Couchbase\StellarNebula\Generated\KV\V1\MutateInRequest\Spec\Operation;

class MutateCounterSpec
{
    private bool $isXattr;
    private bool $createPath;
    private string $path;
    private int $value;

    public function __construct(string $path, int $value, bool $isXattr = false, bool $createPath = false)
    {
        $this->isXattr = $isXattr;
        $this->createPath = $createPath;
        $this->path = $path;
        $this->value = $value;
    }

    public static function build(string $path, $value, bool $isXattr = false, bool $createPath = false): MutateCounterSpec
    {
        return new MutateCounterSpec($path, $value, $isXattr, $createPath);
    }

    public function xattr(bool $isXattr): MutateCounterSpec
    {
        $this->isXattr = $isXattr;
        return $this;
    }

    public function createPath(bool $createPath): MutateCounterSpec
    {
        $this->createPath = $createPath;
        return $this;
    }

    public function export(?MutateInOptions $options): array
    {
        $flags = [
            "create_path" => $this->createPath,
            "xattr" => $this->isXattr
        ];
        return [
            'operation' => Operation::COUNTER,
            'flags' => $flags,
            'path' => $this->path,
            'value' => $this->value,
        ];
    }
}
