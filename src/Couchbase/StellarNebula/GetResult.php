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

use DateTimeImmutable;
use DateTimeInterface;

class GetResult extends Result
{
    private Transcoder $transcoder;

    private string $content;

    /** @see DocumentContentType */
    private int $contentType;

    /** @see DocumentCompressionType */
    private int $compressionType;

    private ?int $expiry;

    public function __construct(
        string $bucket,
        string $scope,
        string $collection,
        string $id,
        int|string|null $cas,
        Transcoder $transcoder,
        string $content,
        int $contentType,
        int $compressionType,
        ?int $expiry
    )
    {
        parent::__construct($bucket, $scope, $collection, $id, $cas);
        $this->transcoder = $transcoder;
        $this->content = $content;
        $this->contentType = $contentType;
        $this->compressionType = $compressionType;
        $this->expiry = $expiry;
    }

    public function content()
    {
        return $this->transcoder->decode($this->content, $this->contentType);
    }

    public function contentAs(Transcoder $transcoder, ?int $overrideContentType = null)
    {
        return $transcoder->decode(
            $this->content,
            $overrideContentType == null ? $this->contentType : $overrideContentType
        );
    }

    public function expiryTime(): ?DateTimeInterface
    {
        if ($this->expiry == null || $this->expiry == 0) {
            return null;
        }
        return DateTimeImmutable::createFromFormat("U", sprintf("%d", $this->expiry)) ?: null;
    }
}
