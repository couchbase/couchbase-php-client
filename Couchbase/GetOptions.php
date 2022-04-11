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

class GetOptions
{
    private Transcoder $transcoder;
    private ?int $timeoutMilliseconds = null;
    private bool $withExpiry = false;
    private ?array $projections = null;

    /**
     * @since 4.0.0
     */
    public function __construct()
    {
        $this->transcoder = JsonTranscoder::getInstance();
    }

    /**
     * Static helper to keep code more readable
     *
     * @return GetOptions
     * @since 4.0.0
     */
    public static function build(): GetOptions
    {
        return new GetOptions();
    }

    /**
     * Sets the operation timeout in milliseconds.
     *
     * @param int $milliseconds the operation timeout to apply
     *
     * @return GetOptions
     * @since 4.0.0
     */
    public function timeout(int $milliseconds): GetOptions
    {
        $this->timeoutMilliseconds = $milliseconds;
        return $this;
    }

    /**
     * Sets whether to include document expiry with the document content.
     *
     * When used this option will transparently transform the Get
     * operation into a subdocument operation performing a full document
     * fetch as well as the expiry.
     *
     * @param bool $fetchExpiry whether to include document expiry
     *
     * @return GetOptions
     * @since 4.0.0
     */
    public function withExpiry(bool $fetchExpiry): GetOptions
    {
        $this->withExpiry = $fetchExpiry;
        return $this;
    }

    /**
     * Sets whether to cause the Get operation to only fetch the fields
     * from the document indicated by the paths provided.
     *
     * When used this option will transparently transform the Get
     * operation into a subdocument operation fetching only the required
     * fields.
     *
     * @param array $projections the array of field names (array of strings)
     *
     * @return GetOptions
     * @since 4.0.0
     */
    public function project(array $projections): GetOptions
    {
        $this->projections = $projections;
        return $this;
    }

    /**
     * Associate custom transcoder with the request.
     *
     * @param Transcoder $transcoder
     *
     * @return GetOptions
     * @since 4.0.0
     */
    public function transcoder(Transcoder $transcoder): GetOptions
    {
        $this->transcoder = $transcoder;
        return $this;
    }

    /**
     * Returns associated transcoder.
     *
     * @param GetOptions|null $options
     *
     * @return Transcoder
     * @since 4.0.0
     */
    public static function getTranscoder(?GetOptions $options): Transcoder
    {
        if ($options == null) {
            return JsonTranscoder::getInstance();
        }
        return $options->transcoder;
    }

    /**
     * @internal
     *
     * @param GetOptions|null $options
     *
     * @return array
     * @since 4.0.0
     */
    public static function export(?GetOptions $options): array
    {
        if ($options == null) {
            return [];
        }
        return [
            'timeoutMilliseconds' => $options->timeoutMilliseconds,
            'withExpiry' => $options->withExpiry,
            'projections' => $options->projections,
        ];
    }
}
