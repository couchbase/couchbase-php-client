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

namespace Couchbase\Management;

class QueryIndex
{
    private string $name;
    private bool $isPrimary;
    private string $type;
    private string $state;
    private array $indexKey;
    private string $bucketName;
    private ?string $scopeName = null;
    private ?string $collectionName = null;
    private ?string $condition = null;
    private ?string $partition = null;

    /**
     * @param array $response
     *
     * @since 4.0.0
     */
    public function __construct(array $response)
    {
        $this->name = $response["name"];
        $this->isPrimary = $response["isPrimary"];
        $this->type = $response["type"];
        $this->state = $response["state"];
        $this->indexKey = $response["indexKey"];
        $this->bucketName = $response["bucketName"];
        if (array_key_exists("condition", $response)) {
            $this->condition = $response["condition"];
        }
        if (array_key_exists("partition", $response)) {
            $this->partition = $response["partition"];
        }
        if (array_key_exists("scopeName", $response)) {
            $this->scopeName = $response["scopeName"];
        }
        if (array_key_exists("collectionName", $response)) {
            $this->collectionName = $response["collectionName"];
        }
    }

    /**
     * @return string
     * @since 4.0.0
     */
    public function name(): string
    {
        return $this->name;
    }

    /**
     * @return string
     * @since 4.0.0
     */
    public function bucketName(): string
    {
        return $this->bucketName;
    }

    /**
     * @return string|null
     * @since 4.0.0
     */
    public function scopeName(): ?string
    {
        return $this->scopeName;
    }

    /**
     * @return string|null
     * @since 4.0.0
     */
    public function collectionName(): ?string
    {
        return $this->collectionName;
    }

    /**
     * @return bool
     * @since 4.0.0
     */
    public function isPrimary(): bool
    {
        return $this->isPrimary;
    }

    /**
     * @return string
     * @since 4.0.0
     */
    public function type(): string
    {
        return $this->type;
    }

    /**
     * @return string
     * @since 4.0.0
     */
    public function state(): string
    {
        return $this->state;
    }

    /**
     * @return array
     * @since 4.0.0
     */
    public function indexKey(): array
    {
        return $this->indexKey;
    }

    /**
     * @return string|null
     * @since 4.0.0
     */
    public function condition(): ?string
    {
        return $this->condition;
    }

    /**
     * @return string|null
     * @since 4.0.0
     */
    public function partition(): ?string
    {
        return $this->partition;
    }
}
