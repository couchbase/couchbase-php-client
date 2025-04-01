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

class TransactionInsertOptions
{
    private Transcoder $transcoder;

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
     * @return TransactionInsertOptions
     * @since 4.3.0
     */
    public static function build(): TransactionInsertOptions
    {
        return new TransactionInsertOptions();
    }

    /**
     * Associate custom transcoder with the request.
     *
     * @param Transcoder $transcoder
     *
     * @return TransactionInsertOptions
     * @since 4.3.0
     */
    public function transcoder(Transcoder $transcoder): TransactionInsertOptions
    {
        $this->transcoder = $transcoder;
        return $this;
    }

    /**
     * Delegates encoding of the document to associated transcoder
     *
     * @param TransactionInsertOptions|null $options
     * @param                    $document
     *
     * @return array
     * @since 4.3.0
     */
    public static function encodeDocument(?TransactionInsertOptions $options, $document): array
    {
        if ($options == null) {
            return JsonTranscoder::getInstance()->encode($document);
        }
        return $options->transcoder->encode($document);
    }

    /**
     * Returns associated transcoder.
     *
     * @param TransactionInsertOptions|null $options
     *
     * @return Transcoder
     * @since 4.3.0
     */
    public static function getTranscoder(?TransactionInsertOptions $options): Transcoder
    {
        if ($options == null) {
            return JsonTranscoder::getInstance();
        }
        return $options->transcoder;
    }
}
