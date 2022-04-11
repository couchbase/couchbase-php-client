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

use Couchbase\Exception\DecodingFailureException;

class RawJsonTranscoder implements Transcoder
{
    private static ?RawJsonTranscoder $instance;

    public static function getInstance(): Transcoder
    {
        if (!isset(self::$instance)) {
            self::$instance = new RawJsonTranscoder();
        }
        return self::$instance;
    }

    /**
     * Assumes that input value is a string that already contains JSON-encoded object
     *
     * @param string $value document
     *
     * @return array tuple of encoded value with flags for network layer
     * @since 4.0.0
     */
    public function encode($value): array
    {
        return [
            $value,
            (new TranscoderFlags(TranscoderFlags::DATA_FORMAT_JSON))->encode(),
        ];
    }

    /**
     * Just returns the body, assuming that decoding will be done later by the caller.
     *
     * @param string $bytes encoded data
     * @param int $flags flags from network layer, that describes format of the encoded data
     *
     * @return string decoded document
     * @since 4.0.0
     */
    public function decode(string $bytes, int $flags)
    {
        return $bytes;
    }
}
