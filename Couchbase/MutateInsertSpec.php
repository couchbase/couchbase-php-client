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
 * Indicates to insert a value at a path in a document.
 */
class MutateInsertSpec implements MutateInSpec
{
    private bool $isXattr;
    private bool $createParents;
    private bool $expandMacros;
    private string $path;
    private $value;

    /**
     * @param string $path
     * @param mixed $value
     * @param bool $isXattr
     * @param bool $createParents
     * @param bool $expandMacros
     * @since 4.0.0
     */
    public function __construct(string $path, $value, bool $isXattr = false, bool $createParents = false, bool $expandMacros = false)
    {
        $this->isXattr = $isXattr;
        $this->createParents = $createParents;
        $this->expandMacros = $expandMacros;
        $this->path = $path;
        $this->value = $value;
    }

    /**
     * @param string $path
     * @param mixed $value
     * @param bool $isXattr
     * @param bool $createParents
     * @param bool $expandMacros
     * @return MutateInsertSpec
     * @since 4.0.0
     */
    public static function build(string $path, $value, bool $isXattr = false, bool $createParents = false, bool $expandMacros = false): MutateInsertSpec
    {
        return new MutateInsertSpec($path, $value, $isXattr, $createParents, $expandMacros);
    }

    /**
     * @param bool $isXattr
     * @return MutateInsertSpec
     * @since 4.0.0
     */
    public function xattr(bool $isXattr): MutateInsertSpec
    {
        $this->isXattr = $isXattr;
        return $this;
    }

    /**
     * @param bool $createParents
     * @return MutateInsertSpec
     * @since 4.0.0
     */
    public function createParents(bool $createParents): MutateInsertSpec
    {
        $this->createParents = $createParents;
        return $this;
    }

    /**
     * @param bool $expandMacros
     * @return MutateInsertSpec
     * @since 4.0.0
     */
    public function expandMacros(bool $expandMacros): MutateInsertSpec
    {
        $this->expandMacros = $expandMacros;
        return $this;
    }

    /**
     * @private
     * @param MutateInOptions|null $options
     * @return array
     * @since 4.0.0
     */
    public function export(?MutateInOptions $options): array
    {
        return [
            'opcode' => 'dictionaryAdd',
            'isXattr' => $this->isXattr,
            'createParents' => $this->createParents,
            'expandMacros' => $this->expandMacros,
            'path' => $this->path,
            'value' => MutateInOptions::encodeValue($options, $this->value),
        ];
    }
}
