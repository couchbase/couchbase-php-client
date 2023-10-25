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

use Couchbase\Extension;

class BucketManager implements BucketManagerInterface
{
    /**
     * @var resource
     */
    private $core;

    /**
     * @internal
     * @param $core
     * @since 4.0.0
     */
    public function __construct($core)
    {
        $this->core = $core;
    }

    /**
     * Create a new bucket.
     *
     * @param BucketSettings $settings the settings for the bucket.
     * @param CreateBucketOptions|null $options the options to use when creating the bucket.
     * @since 4.0.0
     */
    public function createBucket(BucketSettings $settings, CreateBucketOptions $options = null)
    {
        Extension\bucketCreate($this->core, BucketSettings::export($settings), CreateBucketOptions::export($options));
    }

    /**
     * Update an existing bucket.
     *
     * @param BucketSettings $settings the settings for the bucket.
     * @param UpdateBucketOptions|null $options the options to use when updating the bucket.
     * @since 4.0.0
     */
    public function updateBucket(BucketSettings $settings, UpdateBucketOptions $options = null)
    {
        Extension\bucketUpdate($this->core, BucketSettings::export($settings), UpdateBucketOptions::export($options));
    }

    /**
     * Remove an existing bucket.
     *
     * @param string $name the name of the bucket.
     * @deprecated see dropBucket
     * @since 4.0.0
     */
    public function removeBucket(string $name)
    {
        $this->dropBucket($name);
    }

    /**
     * Drop an existing bucket.
     *
     * @param string $name the name of the bucket.
     * @param DropBucketOptions|null $options the options to use when dropping the bucket.
     * @since 4.0.0
     */
    public function dropBucket(string $name, DropBucketOptions $options = null)
    {
        Extension\bucketDrop($this->core, $name, DropBucketOptions::export($options));
    }

    /**
     * Get an existing bucket.
     *
     * @param string $name the name of the bucket.
     * @param GetBucketOptions|null $options the options to use when getting the bucket.
     * @since 4.0.0
     */
    public function getBucket(string $name, GetBucketOptions $options = null): BucketSettings
    {
        $result = Extension\bucketGet($this->core, $name, GetBucketOptions::export($options));
        return BucketSettings::import($result);
    }

    /**
     * Get all buckets.
     *
     * @param GetAllQueryIndexesOptions|null $options the options to use when getting the buckets.
     *
     * @since 4.0.0
     */
    public function getAllBuckets(GetAllBucketsOptions $options = null): array
    {
        $result = Extension\bucketGetAll($this->core, GetAllBucketsOptions::export($options));
        $buckets = [];
        foreach ($result as $bucket) {
            $buckets[] = BucketSettings::import($bucket);
        }
        return $buckets;
    }

    /**
     * Flush an existing bucket.
     *
     * @param string $name the name of the bucket.
     * @param FlushBucketOptions|null $options the options to use when flushing the bucket.
     * @since 4.0.0
     */
    public function flush(string $name, FlushBucketOptions $options = null)
    {
        Extension\bucketFlush($this->core, $name, FlushBucketOptions::export($options));
    }
}
