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
 * Indicates to increment or decrement a counter value at a path in a document.
 */
class MutateCounterSpec implements MutateInSpec
{
    private bool $isXattr;
    private bool $createParents;
    private string $path;
    private int $value;

    /**
     * @param string $path
     * @param int $value
     * @param bool $isXattr
     * @param bool $createParents
     * @since 4.0.0
     */
    public function __construct(string $path, int $value, bool $isXattr = false, bool $createParents = false)
    {
        $this->isXattr = $isXattr;
        $this->createParents = $createParents;
        $this->path = $path;
        $this->value = $value;
    }

    /**
     * @param string $path
     * @param mixed $value
     * @param bool $isXattr
     * @param bool $createParents
     * @return MutateCounterSpec
     * @since 4.0.0
     */
    public static function build(string $path, $value, bool $isXattr = false, bool $createParents = false): MutateCounterSpec
    {
        return new MutateCounterSpec($path, $value, $isXattr, $createParents);
    }

    /**
     * @param bool $isXattr
     * @return MutateCounterSpec
     * @since 4.0.0
     */
    public function xattr(bool $isXattr): MutateCounterSpec
    {
        $this->isXattr = $isXattr;
        return $this;
    }

    /**
     * @param bool $createParents
     * @return MutateCounterSpec
     * @since 4.0.0
     */
    public function createParents(bool $createParents): MutateCounterSpec
    {
        $this->createParents = $createParents;
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
            'opcode' => 'counter',
            'isXattr' => $this->isXattr,
            'createParents' => $this->createParents,
            'path' => $this->path,
            'value' => $this->value,
        ];
    }
}
