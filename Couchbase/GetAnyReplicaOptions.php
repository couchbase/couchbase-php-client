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

class GetAnyReplicaOptions
{
    private Transcoder $transcoder;
    private string $readPreference;
    private ?int $timeoutMilliseconds = null;

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
     * @return GetAnyReplicaOptions
     * @since 4.0.1
     */
    public static function build(): GetAnyReplicaOptions
    {
        return new GetAnyReplicaOptions();
    }

    /**
     * Sets the operation timeout in milliseconds.
     *
     * @param int $milliseconds the operation timeout to apply
     *
     * @return GetAnyReplicaOptions
     * @since 4.0.1
     */
    public function timeout(int $milliseconds): GetAnyReplicaOptions
    {
        $this->timeoutMilliseconds = $milliseconds;
        return $this;
    }

    /**
     * Associate custom transcoder with the request.
     *
     * @param Transcoder $transcoder
     *
     * @return GetAnyReplicaOptions
     * @since 4.0.1
     */
    public function transcoder(Transcoder $transcoder): GetAnyReplicaOptions
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
     * @return GetAnyReplicaOptions
     * @since 4.2.6
     */
    public function readPreference(string $readPreference): GetAnyReplicaOptions
    {
        $this->readPreference = $readPreference;
        return $this;
    }

    /**
     * Returns associated transcoder.
     *
     * @param GetAnyReplicaOptions|null $options
     *
     * @return Transcoder
     * @since 4.0.1
     */
    public static function getTranscoder(?GetAnyReplicaOptions $options): Transcoder
    {
        if ($options == null) {
            return JsonTranscoder::getInstance();
        }
        return $options->transcoder;
    }

    /**
     * @param GetAnyReplicaOptions|null $options
     *
     * @return array
     * @internal
     *
     * @since 4.0.1
     */
    public static function export(?GetAnyReplicaOptions $options): array
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
