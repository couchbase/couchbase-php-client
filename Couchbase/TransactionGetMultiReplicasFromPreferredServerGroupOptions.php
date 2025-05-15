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

class TransactionGetMultiReplicasFromPreferredServerGroupOptions
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
     * @return TransactionGetMultiReplicasFromPreferredServerGroupOptions
     * @since 4.3.0
     */
    public static function build(): TransactionGetMultiReplicasFromPreferredServerGroupOptions
    {
        return new TransactionGetMultiReplicasFromPreferredServerGroupOptions();
    }

    /**
     * Associate custom transcoder with the request.
     *
     * @param Transcoder $transcoder
     *
     * @return TransactionGetMultiReplicasFromPreferredServerGroupOptions
     * @since 4.3.0
     */
    public function transcoder(Transcoder $transcoder): TransactionGetMultiReplicasFromPreferredServerGroupOptions
    {
        $this->transcoder = $transcoder;
        return $this;
    }

    /**
     * Specify mode for read skew resolution.
     *
     * @param string $mode
     *
     * @return TransactionGetMultiReplicasFromPreferredServerGroupOptions
     *
     * @see TransactionGetMultiReplicasFromPreferredServerGroupMode
     * @since 4.3.0
     */
    public function mode(string $mode): TransactionGetMultiReplicasFromPreferredServerGroupOptions
    {
        $this->mode = $mode;
        return $this;
    }

    /**
     * Returns associated transcoder.
     *
     * @param TransactionGetMultiReplicasFromPreferredServerGroupOptions|null $options
     *
     * @return Transcoder
     * @since 4.3.0
     */
    public static function getTranscoder(?TransactionGetMultiReplicasFromPreferredServerGroupOptions $options): Transcoder
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
    public static function export(?TransactionGetMultiReplicasFromPreferredServerGroupOptions $options): array
    {
        if ($options == null) {
            return [];
        }
        return [
            "mode" => $options->mode,
        ];
    }
}
