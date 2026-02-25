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

class GetAllReplicasOptions
{
    private Transcoder $transcoder;
    private string $readPreference;
    private ?int $timeoutMilliseconds = null;
    private ?RequestSpan $parentSpan = null;

    /**
     * @since 4.0.1
     */
    public function __construct()
    {
        $this->transcoder = JsonTranscoder::getInstance();
        $this->readPreference = ReadPreference::NO_PREFERENCE;
    }

    /**
     * Static helper to keep code more readable
     *
     * @return GetAllReplicasOptions
     * @since 4.0.1
     */
    public static function build(): GetAllReplicasOptions
    {
        return new GetAllReplicasOptions();
    }

    /**
     * Sets the operation timeout in milliseconds.
     *
     * @param int $milliseconds the operation timeout to apply
     *
     * @return GetAllReplicasOptions
     * @since 4.0.1
     */
    public function timeout(int $milliseconds): GetAllReplicasOptions
    {
        $this->timeoutMilliseconds = $milliseconds;
        return $this;
    }

    /**
     * Associate custom transcoder with the request.
     *
     * @param Transcoder $transcoder
     *
     * @return GetAllReplicasOptions
     * @since 4.0.1
     */
    public function transcoder(Transcoder $transcoder): GetAllReplicasOptions
    {
        $this->transcoder = $transcoder;
        return $this;
    }

    /**
     * Choose how the replica nodes will be selected. By default, it has no
     * preference and will select any available replica, but it is possible to
     * prioritize or restrict to only nodes in local server group
     *
     * @see ReadPreference
     *
     * @param string $readPreference
     *
     * @return GetAllReplicasOptions
     * @since 4.2.6
     */
    public function readPreference(string $readPreference): GetAllReplicasOptions
    {
        $this->readPreference = $readPreference;
        return $this;
    }

    /**
     * Sets the parent span.
     *
     * @param RequestSpan $parentSpan the parent span
     *
     * @return GetAllReplicasOptions
     * @since 4.5.0
     */
    public function parentSpan(RequestSpan $parentSpan): GetAllReplicasOptions
    {
        $this->parentSpan = $parentSpan;
        return $this;
    }

    /**
     * @internal
     */
    public static function getParentSpan(?GetAllReplicasOptions $options): ?RequestSpan
    {
        return $options?->parentSpan;
    }

    /**
     * Returns associated transcoder.
     *
     * @param GetAllReplicasOptions|null $options
     *
     * @return Transcoder
     * @since 4.0.1
     */
    public static function getTranscoder(?GetAllReplicasOptions $options): Transcoder
    {
        if ($options == null) {
            return JsonTranscoder::getInstance();
        }
        return $options->transcoder;
    }

    /**
     * @param GetAllReplicasOptions|null $options
     *
     * @return array
     * @internal
     *
     * @since 4.0.1
     */
    public static function export(?GetAllReplicasOptions $options): array
    {
        if ($options == null) {
            return [];
        }
        return [
            'timeoutMilliseconds' => $options->timeoutMilliseconds,
            'readPreference' => $options->readPreference,
        ];
    }
}
