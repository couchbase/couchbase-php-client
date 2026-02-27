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

namespace Helpers\Metrics;

use Couchbase\ValueRecorder;
use Couchbase\Meter;

class TestMeter implements Meter
{
    private array $recorders = [];

    public function valueRecorder(string $name, array $tags): ValueRecorder
    {
        if (!isset($this->recorders[$name])) {
            $this->recorders[$name] = [];
        }
        $tagKey = $this->tagsToKey($tags);
        if (!isset($this->recorders[$name][$tagKey])) {
            $this->recorders[$name][$tagKey] = new TestValueRecorder($name, $tags);
        }
        return $this->recorders[$name][$tagKey];
    }

    public function close(): void
    {
        // No resources to clean up in this test implementation
    }

    public function reset(): void
    {
        $this->recorders = [];
    }

    public function getValues(string $name, array $tags): array
    {
        $tagKey = $this->tagsToKey($tags);
        if (!isset($this->recorders[$name][$tagKey])) {
            return [];
        }
        return $this->recorders[$name][$tagKey]->getValues();
    }

    private static function tagsToKey(array $tags): string
    {
        ksort($tags);
        $result = "";
        foreach ($tags as $key => $value) {
            $result .= "$key=$value;";
        }
        return $result;
    }
}
