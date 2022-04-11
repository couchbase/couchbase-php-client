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

interface Transcoder
{
    /**
     * @param mixed $value
     *
     * @return array pair of string and int, that represents encoded value and flags
     * @since 4.0.0
     */
    public function encode($value): array;

    /**
     * @param string $bytes
     * @param int $flags
     *
     * @return mixed decoded value
     * @since 4.0.0
     */
    public function decode(string $bytes, int $flags);
}
