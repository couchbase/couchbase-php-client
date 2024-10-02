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

interface BucketManagerInterface
{
    public function createBucket(BucketSettings $settings, ?CreateBucketOptions $options = null);

    public function updateBucket(BucketSettings $settings, ?UpdateBucketOptions $options = null);

    public function dropBucket(string $name, ?DropBucketOptions $options = null);

    public function getBucket(string $name, ?GetBucketOptions $options = null): BucketSettings;

    public function getAllBuckets(?GetAllBucketsOptions $options = null): array;

    public function flush(string $name, ?FlushBucketOptions $options = null);
}
