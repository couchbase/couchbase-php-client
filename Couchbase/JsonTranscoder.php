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
use JsonException;

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

    private int $encodeFlags;
    private int $encodeDepth;
    private bool $decodeAssociative;
    private int $decodeDepth;
    private int $decodeFlags;

    /**
     * @param bool $decodeAssociative passed as $associative to json_decode()
     * @param int $decodeDepth passed as $depth to json_decode()
     * @param int $decodeFlags passed as $flags to json_decode()
     * @param int $encodeFlags passed as $flags to json_encode()
     * @param int $encodeDepth passed as $depth to json_encode()
     */
    public function __construct(
        bool $decodeAssociative = true,
        int $decodeDepth = 512,
        int $decodeFlags = JSON_THROW_ON_ERROR,
        int $encodeFlags = JSON_THROW_ON_ERROR,
        int $encodeDepth = 512
    )
    {
        $this->decodeAssociative = $decodeAssociative;
        $this->decodeDepth = $decodeDepth;
        $this->decodeFlags = $decodeFlags;
        $this->encodeFlags = $encodeFlags;
        $this->encodeDepth = $encodeDepth;
    }

    /**
     * Encodes data using json_encode() from json extension
     *
     * @param mixed $value document
     *
     * @return array tuple of encoded value with flags for network layer
     * @since 4.0.0
     */
    public function encode($value): array
    {
        return [
            json_encode($value, $this->encodeFlags, $this->encodeDepth),
            (new TranscoderFlags(TranscoderFlags::DATA_FORMAT_JSON))->encode(),
        ];
    }

    /**
     * Decodes data using json_decode() from json extension
     *
     * @param string $bytes encoded data
     * @param int $flags flags from network layer, that describes format of the encoded data
     *
     * @return mixed decoded document
     * @throws DecodingFailureException
     * @since 4.0.0
     */
    public function decode(string $bytes, int $flags)
    {
        if (TranscoderFlags::decode($flags)->isJson() || $flags == 0 /* subdoc API cannot set flags */) {
            try {
                return json_decode($bytes, $this->decodeAssociative, $this->decodeDepth, $this->decodeFlags);
            } catch (JsonException $e) {
                throw new DecodingFailureException("unable to decode bytes with JsonTranscoder", 0, $e);
            }
        }
        throw new DecodingFailureException(sprintf("unable to decode bytes with JsonTranscoder: unknown flags 0x%08x", $flags));
    }
}
