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

class MutateArrayAppendSpec implements MutateInSpec
{
    private bool $isXattr;
    private bool $createPath;
    private bool $expandMacros;
    private string $path;
    private array $values;

    public function __construct(
        string $path,
        array $values,
        bool $isXattr = false,
        bool $createPath = false,
        bool $expandMacros = false
    )
    {
        $this->isXattr = $isXattr;
        $this->createPath = $createPath;
        $this->expandMacros = $expandMacros;
        $this->path = $path;
        $this->values = $values;
    }

    public static function build(
        string $path,
        array $values,
        bool $isXattr = false,
        bool $createPath = false,
        bool $expandMacros = false
    ): MutateArrayAppendSpec
    {
        return new MutateArrayAppendSpec($path, $values, $isXattr, $createPath, $expandMacros);
    }

    public function xattr(bool $isXattr): MutateArrayAppendSpec
    {
        $this->isXattr = $isXattr;
        return $this;
    }

    public function createPath(bool $createPath): MutateArrayAppendSpec
    {
        $this->createPath = $createPath;
        return $this;
    }

    public function expandMacros(bool $expandMacros): MutateArrayAppendSpec
    {
        $this->expandMacros = $expandMacros;
        return $this;
    }

    public function export(?MutateInOptions $options): array
    {
        $flags = [
            "create_path" => $this->createPath,
            "xattr" => $this->isXattr
        ];
        return [
            'opcode' => Operation::ARRAY_APPEND,
            'flags' => $flags,
            'path' => $this->path,
            'content' => join(
                ",",
                array_map(
                    function ($value) use ($options) {
                        return MutateInOptions::encodeValue($options, $value);
                    },
                    $this->values
                )
            ),
        ];
    }
}