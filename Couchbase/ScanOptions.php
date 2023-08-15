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

use Couchbase\Exception\InvalidArgumentException;

class ScanOptions
{
    private Transcoder $transcoder;
    private ?MutationState $consistentWith = null;
    private ?int $timeoutMilliseconds = null;
    private ?bool $idsOnly = null;
    private ?int $batchByteLimit = null;
    private ?int $batchItemLimit = null;
    private ?int $concurrency = null;

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
     * @return ScanOptions
     * @since 4.1.6
     */
    public static function build(): ScanOptions
    {
        return new ScanOptions();
    }

    /**
     * Associate custom transcoder with the request.
     *
     * @param Transcoder $transcoder
     *
     * @return ScanOptions
     * @since 4.1.6
     */
    public function transcoder(Transcoder $transcoder): ScanOptions
    {
        $this->transcoder = $transcoder;
        return $this;
    }

    /**
     * Sets the mutation state to achieve consistency with for read your own writes (RYOW).
     *
     * @param MutationState $state the mutation state to achieve consistency with
     *
     * @return ScanOptions
     * @since 4.1.6
     */
    public function consistentWith(MutationState $state): ScanOptions
    {
        $this->consistentWith = $state;
        return $this;
    }

    /**
     * Sets the operation timeout in milliseconds.
     *
     * @param int $milliseconds the operation timeout to apply
     *
     * @return ScanOptions
     * @since 4.1.6
     */
    public function timeout(int $milliseconds): ScanOptions
    {
        $this->timeoutMilliseconds = $milliseconds;
        return $this;
    }

    /**
     * Sets if the scan should only return document ids.
     *
     * @param bool $idsOnly
     *
     * @return ScanOptions
     * @since 4.1.6
     */
    public function idsOnly(bool $idsOnly): ScanOptions
    {
        $this->idsOnly = $idsOnly;
        return $this;
    }

    /**
     * Sets the limit applied to the number of bytes returned from the server
     * for each partition batch
     *
     * @param int $batchByteLimit
     *
     * @return ScanOptions
     * @since 4.1.6
     */
    public function batchByteLimit(int $batchByteLimit): ScanOptions
    {
        $this->batchByteLimit = $batchByteLimit;
        return $this;
    }

    /**
     * Sets the limit applied to the number of items returned from the server
     * for each partition batch
     *
     * @param int $batchItemLimit
     *
     * @return ScanOptions
     * @since 4.1.6
     */
    public function batchItemLimit(int $batchItemLimit): ScanOptions
    {
        $this->batchItemLimit = $batchItemLimit;
        return $this;
    }

    /**
     * Specifies the number of vBuckets which the client should scan in parallel
     *
     * @param int $concurrency
     *
     * @return ScanOptions
     * @throws InvalidArgumentException
     * @Since 4.1.6
     */
    public function concurrency(int $concurrency): ScanOptions
    {
        if ($concurrency < 1) {
            throw new InvalidArgumentException("Concurrency must be positive");
        }
        $this->concurrency = $concurrency;
        return $this;
    }

    /**
     * Returns associated transcoder.
     *
     * @param ScanOptions|null $options
     *
     * @return Transcoder
     * @since 4.1.6
     */
    public static function getTranscoder(?ScanOptions $options): Transcoder
    {
        if ($options == null) {
            return JsonTranscoder::getInstance();
        }
        return $options->transcoder;
    }

    /**
     * @internal
     *
     * @param ScanOptions|null $options
     *
     * @return array
     * @since 4.1.6
     */
    public static function export(?ScanOptions $options): array
    {
        if ($options == null) {
            return [];
        }
        return [
            'consistentWith' => $options->consistentWith == null ? null : $options->consistentWith->export(),
            'timeoutMilliseconds' => $options->timeoutMilliseconds,
            'idsOnly' => $options->idsOnly,
            'batchByteLimit' => $options->batchByteLimit,
            'batchItemLimit' => $options->batchItemLimit,
            'concurrency' => $options->concurrency,
        ];
    }
}
