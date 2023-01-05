<?php

/*
 * Copyright 2022-Present Couchbase, Inc.
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

    public function encode($value): array
    {
        return [
            json_encode($value, $this->encodeFlags, $this->encodeDepth),
            DocumentContentType::JSON,
        ];
    }

    /**
     * @throws DecodingFailureException
     */
    public function decode(string $bytes, int $contentType)
    {
        if ($contentType != DocumentContentType::JSON) {
            throw new DecodingFailureException(
                sprintf("unable to decode bytes with JsonTranscoder: unknown contentType 0x%08x", $contentType)
            );
        }

        try {
            return json_decode($bytes, $this->decodeAssociative, $this->decodeDepth, $this->decodeFlags);
        } catch (JsonException $e) {
            throw new DecodingFailureException("unable to decode bytes with JsonTranscoder", $e->getCode(), $e);
        }
    }
}
