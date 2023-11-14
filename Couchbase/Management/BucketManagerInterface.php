<?php

namespace Couchbase\Management;

interface BucketManagerInterface
{
    public function createBucket(BucketSettings $settings, CreateBucketOptions $options = null);

    public function updateBucket(BucketSettings $settings, UpdateBucketOptions $options = null);

    public function dropBucket(string $name, DropBucketOptions $options = null);

    public function getBucket(string $name, GetBucketOptions $options = null): BucketSettings;

    public function getAllBuckets(GetAllBucketsOptions $options = null): array;

    public function flush(string $name, FlushBucketOptions $options = null);
}
