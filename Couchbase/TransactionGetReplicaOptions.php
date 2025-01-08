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

class TransactionGetReplicaOptions
{
    private Transcoder $transcoder;

    /**
     * @since 4.2.6
     */
    public function __construct()
    {
        $this->transcoder = JsonTranscoder::getInstance();
    }

    /**
     * Static helper to keep code more readable
     *
     * @return TransactionGetReplicaOptions
     * @since 4.2.6
     */
    public static function build(): TransactionGetReplicaOptions
    {
        return new TransactionGetReplicaOptions();
    }

    /**
     * Associate custom transcoder with the request.
     *
     * @param Transcoder $transcoder
     *
     * @return TransactionGetReplicaOptions
     * @since 4.2.6
     */
    public function transcoder(Transcoder $transcoder): TransactionGetReplicaOptions
    {
        $this->transcoder = $transcoder;
        return $this;
    }

    /**
     * Returns associated transcoder.
     *
     * @param TransactionGetReplicaOptions|null $options
     *
     * @return Transcoder
     * @since 4.2.6
     */
    public static function getTranscoder(?TransactionGetReplicaOptions $options): Transcoder
    {
        if ($options == null) {
            return JsonTranscoder::getInstance();
        }
        return $options->transcoder;
    }
}
