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

class TransactionGetMultiOptions
{
    private Transcoder $transcoder;
    private ?string $mode = null;

    /**
     * @since 4.3.0
     */
    public function __construct()
    {
        $this->transcoder = JsonTranscoder::getInstance();
    }

    /**
     * Static helper to keep code more readable
     *
     * @return TransactionGetMultiOptions
     * @since 4.3.0
     */
    public static function build(): TransactionGetMultiOptions
    {
        return new TransactionGetMultiOptions();
    }

    /**
     * Associate custom transcoder with the request.
     *
     * @param Transcoder $transcoder
     *
     * @return TransactionGetMultiOptions
     * @since 4.3.0
     */
    public function transcoder(Transcoder $transcoder): TransactionGetMultiOptions
    {
        $this->transcoder = $transcoder;
        return $this;
    }

    /**
     * Specify mode for read skew resolution.
     *
     * @param string $mode
     *
     * @return TransactionGetMultiOptions
     *
     * @see TransactionGetMultiMode
     * @since 4.3.0
     */
    public function mode(string $mode): TransactionGetMultiOptions
    {
        $this->mode = $mode;
        return $this;
    }

    /**
     * Returns associated transcoder.
     *
     * @param TransactionGetMultiOptions|null $options
     *
     * @return Transcoder
     * @since 4.3.0
     */
    public static function getTranscoder(?TransactionGetMultiOptions $options): Transcoder
    {
        if ($options == null) {
            return JsonTranscoder::getInstance();
        }
        return $options->transcoder;
    }

    /**
     * @internal
     * @since 4.3.0
     */
    public static function export(?TransactionGetMultiOptions $options): array
    {
        if ($options == null) {
            return [];
        }
        return [
            "mode" => $options->mode,
        ];
    }
}
