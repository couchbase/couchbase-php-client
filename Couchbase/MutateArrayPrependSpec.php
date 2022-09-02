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
 * Indicates to prepend a value to an array at a path in a document.
 */
class MutateArrayPrependSpec implements MutateInSpec
{
    private bool $isXattr;
    private bool $createPath;
    private bool $expandMacros;
    private string $path;
    private array $values;

    /**
     * @param string $path
     * @param array $values
     * @param bool $isXattr
     * @param bool $createPath
     * @param bool $expandMacros
     *
     * @since 4.0.0
     */
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

    /**
     * @param string $path
     * @param array $values
     * @param bool $isXattr
     * @param bool $createPath
     * @param bool $expandMacros
     *
     * @return MutateArrayPrependSpec
     * @since 4.0.0
     */
    public static function build(
        string $path,
        array $values,
        bool $isXattr = false,
        bool $createPath = false,
        bool $expandMacros = false
    ): MutateArrayPrependSpec
    {
        return new MutateArrayPrependSpec($path, $values, $isXattr, $createPath, $expandMacros);
    }

    /**
     * @param bool $isXattr
     *
     * @return MutateArrayPrependSpec
     * @since 4.0.0
     */
    public function xattr(bool $isXattr): MutateArrayPrependSpec
    {
        $this->isXattr = $isXattr;
        return $this;
    }

    /**
     * @param bool $createPath
     *
     * @return MutateArrayPrependSpec
     * @since 4.0.0
     */
    public function createPath(bool $createPath): MutateArrayPrependSpec
    {
        $this->createPath = $createPath;
        return $this;
    }

    /**
     * @param bool $expandMacros
     *
     * @return MutateArrayPrependSpec
     * @since 4.0.0
     */
    public function expandMacros(bool $expandMacros): MutateArrayPrependSpec
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
            'opcode' => 'arrayPushFirst',
            'isXattr' => $this->isXattr,
            'createPath' => $this->createPath,
            'expandMacros' => $this->expandMacros,
            'path' => $this->path,
            'value' => join(
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
