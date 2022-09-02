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

use Couchbase\Exception\InvalidArgumentException;

/**
 * Indicates to add a value into an array at a path in a document so long as that value does not already exist
 * in the array.
 */
class MutateArrayAddUniqueSpec implements MutateInSpec
{
    private bool $isXattr;
    private bool $createPath;
    private bool $expandMacros;
    private string $path;
    private $value;

    /**
     * @param string $path
     * @param int|float|bool|string|null $value
     * @param bool $isXattr
     * @param bool $createPath
     * @param bool $expandMacros
     *
     * @throws InvalidArgumentException
     * @since 4.0.0
     */
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

    /**
     * @param string $path
     * @param int|float|bool|string|null $value
     * @param bool $isXattr
     * @param bool $createPath
     * @param bool $expandMacros
     *
     * @return MutateArrayAddUniqueSpec
     * @throws InvalidArgumentException
     * @since 4.0.0
     */
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

    /**
     * @param bool $isXattr
     *
     * @return MutateArrayAddUniqueSpec
     * @since 4.0.0
     */
    public function xattr(bool $isXattr): MutateArrayAddUniqueSpec
    {
        $this->isXattr = $isXattr;
        return $this;
    }

    /**
     * @param bool $createPath
     *
     * @return MutateArrayAddUniqueSpec
     * @since 4.0.0
     */
    public function createPath(bool $createPath): MutateArrayAddUniqueSpec
    {
        $this->createPath = $createPath;
        return $this;
    }

    /**
     * @param bool $expandMacros
     *
     * @return MutateArrayAddUniqueSpec
     * @since 4.0.0
     */
    public function expandMacros(bool $expandMacros): MutateArrayAddUniqueSpec
    {
        $this->expandMacros = $expandMacros;
        return $this;
    }

    /**
     * @internal
     *
     * @param MutateInOptions|null $options
     *
     * @return array
     * @since 4.0.0
     */
    public function export(?MutateInOptions $options): array
    {
        return [
            'opcode' => 'arrayAddUnique',
            'isXattr' => $this->isXattr,
            'createPath' => $this->createPath,
            'expandMacros' => $this->expandMacros,
            'path' => $this->path,
            'value' => MutateInOptions::encodeValue($options, $this->value),
        ];
    }
}
