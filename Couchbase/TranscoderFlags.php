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

class TranscoderFlags
{
    /**
     * Reserved bit position to avoid zeroing out upper 8 bits
     */
    public const DATA_FORMAT_RESERVED = 0;

    /**
     * Used for SDK specific encodings
     */
    public const DATA_FORMAT_PRIVATE = 1;

    /**
     * Encode as JSON
     */
    public const DATA_FORMAT_JSON = 2;

    /**
     * Store as raw binary format
     */
    public const DATA_FORMAT_BINARY = 3;

    /**
     * Store as a UTF8 string
     */
    public const DATA_FORMAT_STRING = 4;

    /**
     * No compression is being used
     */
    public const COMPRESSION_NONE = 0;

    /**
     * No custom type is being associated
     */
    public const TYPE_CODE_NONE = 0;

    private int $dataFormat;
    private int $compression;
    private int $typeCode;

    /**
     * @param int $dataFormat data format tag, portable across SDKs
     * @param int $compression compression tag, SDK-specific
     * @param int $typeCode type code, SDK-specific
     *
     * @since 4.0.0
     */
    public function __construct(int $dataFormat, int $compression = self::COMPRESSION_NONE, int $typeCode = self::TYPE_CODE_NONE)
    {
        $this->dataFormat = $dataFormat;
        $this->compression = $compression;
        $this->typeCode = $typeCode;
    }

    /**
     * @return bool true if the data format specifies portable JSON encoding
     * @since 4.0.0
     */
    public function isJson(): bool
    {
        return $this->dataFormat == self::DATA_FORMAT_JSON;
    }

    /**
     * @return int
     * @since 4.0.0
     */
    public function dataFormat(): int
    {
        return $this->dataFormat;
    }

    /**
     * @return int
     * @since 4.0.0
     */
    public function typeCode(): int
    {
        return $this->typeCode;
    }

    /**
     * @return int
     * @since 4.0.0
     */
    public function compression(): int
    {
        return $this->compression;
    }

    /**
     * Parses network representation of the flags
     *
     * @param int $flags
     *
     * @return TranscoderFlags
     * @since 4.0.0
     */
    public static function decode(int $flags)
    {
        $highByte = ($flags & 0xff000000) >> 24;
        return new TranscoderFlags($highByte & 0x0f, $highByte & 0xe0, $flags & 0xff);
    }

    /**
     * @return int network representation of the flags
     * @since 4.0.0
     */
    public function encode(): int
    {
        return (($this->dataFormat | $this->compression) << 24) | $this->typeCode;
    }
}
