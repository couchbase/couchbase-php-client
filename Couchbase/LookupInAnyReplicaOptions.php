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

class LookupInAnyReplicaOptions
{
    private Transcoder $transcoder;
    private ?int $timeoutMilliseconds = null;
    private ?bool $withExpiry = null;

    /**
     * @since 4.1.6
     */
    public function __construct()
    {
        $this->transcoder = JsonTranscoder::getInstance();
    }

    /**
     * Static helper to keep code more readable
     *
     * @return LookupInAnyReplicaOptions
     * @since 4.1.6
     */
    public static function build(): LookupInAnyReplicaOptions
    {
        return new LookupInAnyReplicaOptions();
    }

    /**
     * Sets the operation timeout in milliseconds.
     *
     * @param int $milliseconds the operation timeout to apply
     *
     * @return LookupInAnyReplicaOptions
     * @since 4.1.6
     */
    public function timeout(int $milliseconds): LookupInAnyReplicaOptions
    {
        $this->timeoutMilliseconds = $milliseconds;
        return $this;
    }

    /**
     * Sets whether to include document expiry with the document content.
     *
     * When used this option will add one extra subdocument path into
     * the LookupIn operation. This can cause the set of subdocument paths
     * to exceed the maximum number (16) of paths allowed in a subdocument
     * operation.
     *
     * @param bool $fetchExpiry whether to include document expiry
     *
     * @return LookupInAnyReplicaOptions
     * @since 4.1.6
     */
    public function withExpiry(bool $fetchExpiry): LookupInAnyReplicaOptions
    {
        $this->withExpiry = $fetchExpiry;
        return $this;
    }

    /**
     * @internal
     * @return bool
     * @since 4.1.6
     */
    public function needToFetchExpiry(): bool
    {
        if ($this->withExpiry == null) {
            return false;
        }
        return $this->withExpiry;
    }
    /**
     * Associate custom transcoder with the request.
     *
     * @param Transcoder $transcoder
     *
     * @return LookupInAnyReplicaOptions
     * @since 4.1.6
     */
    public function transcoder(Transcoder $transcoder): LookupInAnyReplicaOptions
    {
        $this->transcoder = $transcoder;
        return $this;
    }

    /**
     * Returns associated transcoder.
     *
     * @param LookupInAnyReplicaOptions|null $options
     *
     * @return Transcoder
     * @since 4.1.6
     */
    public static function getTranscoder(?LookupInAnyReplicaOptions $options): Transcoder
    {
        if ($options == null) {
            return JsonTranscoder::getInstance();
        }
        return $options->transcoder;
    }

    /**
     * @internal
     *
     * @param LookupInAnyReplicaOptions|null $options
     *
     * @return array
     * @since 4.1.6
     */
    public static function export(?LookupInAnyReplicaOptions $options): array
    {
        if ($options == null) {
            return [];
        }
        return [
            'timeoutMilliseconds' => $options->timeoutMilliseconds,
        ];
    }
}
