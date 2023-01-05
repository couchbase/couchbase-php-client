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

class GetOptions
{
    private Transcoder $transcoder;

    public function __construct()
    {
        $this->transcoder = JsonTranscoder::getInstance();
    }

    public static function build(): GetOptions
    {
        return new GetOptions();
    }

    public function transcoder(Transcoder $transcoder): GetOptions
    {
        $this->transcoder = $transcoder;
        return $this;
    }

    public static function getTranscoder(?GetOptions $options): Transcoder
    {
        if ($options == null) {
            return JsonTranscoder::getInstance();
        }
        return $options->transcoder;
    }
}
