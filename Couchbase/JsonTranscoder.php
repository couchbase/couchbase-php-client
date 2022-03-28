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

class JsonTranscoder implements Transcoder
{
    private static ?JsonTranscoder $instance;

    public static function getInstance(): Transcoder
    {
        if (!isset(self::$instance)) {
            self::$instance = new JsonTranscoder();
        }
        return self::$instance;
    }

    /**
     * Encodes data using json_encode() from json extension
     *
     * @param mixed $value document
     * @return array tuple of encoded value with flags for network layer
     * @since 4.0.0
     */
    public function encode($value): array
    {
        return [json_encode($value), (new TranscoderFlags(TranscoderFlags::DATA_FORMAT_JSON))->encode()];
    }

    /**
     * Decodes data using json_decode() from json extension
     *
     * @param string $bytes encoded data
     * @param int $flags flags from network layer, that describes format of the encoded data
     * @return mixed decoded document
     * @throws DecodingFailureException
     * @since 4.0.0
     */
    public function decode(string $bytes, int $flags)
    {
        if (TranscoderFlags::decode($flags)->isJson()) {
            return json_decode($bytes);
        }
        throw new DecodingFailureException(sprintf("unable to decode bytes with JsonTranscoder: unknown flags %08x", $flags));
    }
}
