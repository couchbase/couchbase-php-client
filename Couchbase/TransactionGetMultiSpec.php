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

class TransactionGetMultiSpec
{
    private Collection $collection;
    private string $id;
    private ?Transcoder $transcoder = null;

    /**
     * @since 4.3.0
     */
    public function __construct(Collection $collection, string $id)
    {
        $this->collection = $collection;
        $this->id = $id;
    }

    /**
     * Associate custom transcoder with the spec.
     *
     * @param Transcoder $transcoder
     *
     * @return TransactionGetMultiSpec
     * @since 4.3.0
     */
    public function transcoder(Transcoder $transcoder): TransactionGetMultiSpec
    {
        $this->transcoder = $transcoder;
        return $this;
    }

    /**
     * Returns associated transcoder.
     *
     * @param array<TransactionGetMultiSpec>|null $specs
     *
     * @return Transcoder
     * @since 4.3.0
     */
    public static function getTranscoder(array $specs, int $index, Transcoder $default): Transcoder
    {
        if ($specs != null && array_key_exists($index, $specs) && $specs[$index]->transcoder != null) {
            return $specs[$index]->transcoder;
        }
        return $default;
    }

    /**
     * @internal
     *
     * @param TransactionGetMultiSpec|null $spec
     *
     * @return array
     * @since 4.3.0
     */
    public static function export(?TransactionGetMultiSpec $spec): array
    {
        if ($spec == null) {
            return null;
        }
        return [
            $spec->collection->bucketName(),
            $spec->collection->scopeName(),
            $spec->collection->name(),
            $spec->id,
        ];
    }
}
