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

namespace Couchbase\StellarNebula;

use OutOfBoundsException;

//TODO: Match this with grpc response: spec->getContent()?
class MutateInResult extends MutationResult
{
    private bool $deleted;
    private array $fields;

    public function __construct(
        string $bucket,
        string $scope,
        string $collection,
        string $id,
        int|string|null $cas,
        MutationToken $token,
        array $response
    )
    {
        parent::__construct($bucket, $scope, $collection, $id, $cas, $token);
        $this->fields = $response;
    }

    public function content(int $index)
    {
        if (array_key_exists($index, $this->fields)) {
            $field = $this->fields[$index];
            return JsonTranscoder::getInstance()->decode($field['value'], 0);
        }
        throw new OutOfBoundsException(sprintf("MutateIn result index is out of bounds: %d", $index));
    }

    public function contentByPath(string $path)
    {
        foreach ($this->fields as $field) {
            if ($field['path'] == $path) {
                return JsonTranscoder::getInstance()->decode($field['value'], 0);
            }
        }
        throw new OutOfBoundsException(sprintf("MutateIn result does not have entry for path: %s", $path));
    }

    public function path(int $index): ?string
    {
        if (array_key_exists($index, $this->fields)) {
            return $this->fields[$index]['path'];
        }
        throw new OutOfBoundsException(sprintf("MutateIn result index is out of bounds: %d", $index));
    }
}
