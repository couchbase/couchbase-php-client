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

class MutateArrayAddUniqueSpec implements MutateInSpec
{
    private bool $isXattr;
    private bool $createPath;
    private bool $expandMacros;
    private string $path;
    private $value;

    public function __construct(
        string $path,
        $value,
        bool $isXattr = false,
        bool $createPath = false,
        bool $expandMacros = false
    )
    {
        if (!is_int($value) && !is_float($value) && !is_bool($value) && !is_string($value) && !is_null($value)) {
            throw new InvalidArgumentException("value for add unique operation must be of primitive type");
        }
        $this->isXattr = $isXattr;
        $this->createPath = $createPath;
        $this->expandMacros = $expandMacros;
        $this->path = $path;
        $this->value = $value;
    }

    public static function build(
        string $path,
        $value,
        bool $isXattr = false,
        bool $createPath = false,
        bool $expandMacros = false
    ): MutateArrayAddUniqueSpec
    {
        return new MutateArrayAddUniqueSpec($path, $value, $isXattr, $createPath, $expandMacros);
    }

    public function xattr(bool $isXattr): MutateArrayAddUniqueSpec
    {
        $this->isXattr = $isXattr;
        return $this;
    }

    public function createPath(bool $createPath): MutateArrayAddUniqueSpec
    {
        $this->createPath = $createPath;
        return $this;
    }

    public function expandMacros(bool $expandMacros): MutateArrayAddUniqueSpec
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
            'operation' => Operation::ARRAY_ADD_UNIQUE,
            'flags' => $flags,
            'path' => $this->path,
            'value' => MutateInOptions::encodeValue($options, $this->value), //TODO: This is not like the other MutateArraySpecs
        ];
    }
}
