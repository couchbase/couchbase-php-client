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

include_once __DIR__ . "/Helpers/CouchbaseObservabilityTestCase.php";

use Couchbase\Exception\GroupNotFoundException;
use Couchbase\Exception\ScopeNotFoundException;
use Couchbase\Exception\BucketNotFoundException;
use Couchbase\Exception\UnambiguousTimeoutException;
use Couchbase\Management\GetBucketOptions;
use Couchbase\Management\DropBucketOptions;
use Couchbase\Management\CreateCollectionOptions;
use Couchbase\Management\DropCollectionOptions;
use Couchbase\Management\DropGroupOptions;
use Couchbase\Management\DropScopeOptions;
use Couchbase\Management\GetAllQueryIndexesOptions;
use Couchbase\Management\GetAllScopesOptions;
use Couchbase\Management\GetAllSearchIndexesOptions;
use Couchbase\Management\GetAllUsersOptions;
use Couchbase\Management\WatchQueryIndexesOptions;

class ObservabilityHttpMgmtOperationsTest extends Helpers\CouchbaseObservabilityTestCase
{
    public function testGetAllScopes()
    {
        $manager = $this->openBucket(self::env()->bucketName())->collections();
        $manager->getAllScopes(GetAllScopesOptions::build()->parentSpan($this->parentSpan()));

        $getAllScopesSpan = $this->tracer()->getSpans(null, $this->parentSpan())[0];
        $this->assertHttpOperationSpan($getAllScopesSpan, "manager_collections_get_all_scopes", "management", $this->parentSpan(), self::env()->bucketName());
        $this->assertOperationMetrics(1, "manager_collections_get_all_scopes", "management", self::env()->bucketName());
    }

    public function testCreateAndDropCollection()
    {
        $manager = $this->openBucket(self::env()->bucketName())->collections();
        $name = $this->uniqueId("collection");

        $manager->createCollection("_default", $name, null, CreateCollectionOptions::build()->parentSpan($this->parentSpan()));
        $this->consistencyUtil()->waitUntilCollectionPresent(self::env()->bucketName(), "_default", $name);

        $manager->dropCollection("_default", $name, DropCollectionOptions::build()->parentSpan($this->parentSpan()));

        $createCollectionSpan = $this->tracer()->getSpans(null, $this->parentSpan())[0];
        $this->assertHttpOperationSpan($createCollectionSpan, "manager_collections_create_collection", "management", $this->parentSpan(), self::env()->bucketName(), "_default", $name);
        $this->assertOperationMetrics(1, "manager_collections_create_collection", "management", self::env()->bucketName(), "_default", $name);

        $dropCollectionSpan = $this->tracer()->getSpans(null, $this->parentSpan())[1];
        $this->assertHttpOperationSpan($dropCollectionSpan, "manager_collections_drop_collection", "management", $this->parentSpan(), self::env()->bucketName(), "_default", $name);
        $this->assertOperationMetrics(1, "manager_collections_drop_collection", "management", self::env()->bucketName(), "_default", $name);
    }

    public function testDropScope()
    {
        $manager = $this->openBucket(self::env()->bucketName())->collections();
        $name = "does-not-exist";

        $this->wrapException(
            function () use ($manager, $name) {
                $manager->dropScope($name, DropScopeOptions::build()->parentSpan($this->parentSpan()));
            },
            ScopeNotFoundException::class
        );

        $dropScopeSpan = $this->tracer()->getSpans(null, $this->parentSpan())[0];
        $this->assertHttpOperationSpan($dropScopeSpan, "manager_collections_drop_scope", "management", $this->parentSpan(), self::env()->bucketName(), $name);
        $this->assertOperationMetrics(1, "manager_collections_drop_scope", "management", self::env()->bucketName(), $name, null, "ScopeNotFound");
    }

    public function testGetBucket()
    {
        $manager = $this->connectCluster()->buckets();
        $manager->getBucket(self::env()->bucketName(), GetBucketOptions::build()->parentSpan($this->parentSpan()));

        $getBucketSpan = $this->tracer()->getSpans(null, $this->parentSpan())[0];
        $this->assertHttpOperationSpan($getBucketSpan, "manager_buckets_get_bucket", "management", $this->parentSpan(), self::env()->bucketName());
        $this->assertOperationMetrics(1, "manager_buckets_get_bucket", "management", self::env()->bucketName());
    }

    public function testDropBucket()
    {
        $manager = $this->connectCluster()->buckets();
        $name = "does-not-exist";

        $this->wrapException(
            function () use ($manager, $name) {
                $manager->dropBucket($name, DropBucketOptions::build()->parentSpan($this->parentSpan()));
            },
            BucketNotFoundException::class
        );

        $dropBucketSpan = $this->tracer()->getSpans(null, $this->parentSpan())[0];
        $this->assertHttpOperationSpan($dropBucketSpan, "manager_buckets_drop_bucket", "management", $this->parentSpan(), $name);
        $this->assertOperationMetrics(1, "manager_buckets_drop_bucket", "management", $name, null, null, "BucketNotFound");
    }

    public function testGetAllQueryIndexes()
    {
        $this->skipIfCaves();

        $manager = $this->connectCluster()->queryIndexes();
        $manager->getAllIndexes(self::env()->bucketName(), GetAllQueryIndexesOptions::build()->parentSpan($this->parentSpan()));

        $getAllIndexesSpan = $this->tracer()->getSpans(null, $this->parentSpan())[0];
        $this->assertHttpOperationSpan($getAllIndexesSpan, "manager_query_get_all_indexes", "query", $this->parentSpan(), self::env()->bucketName());
        $this->assertOperationMetrics(1, "manager_query_get_all_indexes", "query", self::env()->bucketName());
    }

    public function testGetAllQueryIndexesCollection()
    {
        $this->skipIfCaves();

        $manager = $this->connectCluster()->bucket(self::env()->bucketName())->defaultCollection()->queryIndexes();
        $manager->getAllIndexes(GetAllQueryIndexesOptions::build()->parentSpan($this->parentSpan()));

        $getAllIndexesSpan = $this->tracer()->getSpans(null, $this->parentSpan())[0];
        $this->assertHttpOperationSpan($getAllIndexesSpan, "manager_query_get_all_indexes", "query", $this->parentSpan(), self::env()->bucketName(), "_default", "_default");
        $this->assertOperationMetrics(1, "manager_query_get_all_indexes", "query", self::env()->bucketName(), "_default", "_default");
    }

    public function testWatchIndexes()
    {
        $this->skipIfCaves();

        $manager = $this->connectCluster()->bucket(self::env()->bucketName())->defaultCollection()->queryIndexes();
        $this->wrapException(
            function () use ($manager) {
                $manager->watchIndexes(["does_not_exist"], 200, WatchQueryIndexesOptions::build()->parentSpan($this->parentSpan()));
            },
            UnambiguousTimeoutException::class
        );

        $watchIndexesSpan = $this->tracer()->getSpans(null, $this->parentSpan())[0];
        $this->assertHttpOperationSpan($watchIndexesSpan, "manager_query_watch_indexes", "query", $this->parentSpan(), self::env()->bucketName(), "_default", "_default");
        $this->assertOperationMetrics(1, "manager_query_watch_indexes", "query", self::env()->bucketName(), "_default", "_default", "UnambiguousTimeout");

        $getAllIndexesSpans = $this->tracer()->getSpans(null, $watchIndexesSpan);
        $this->assertNotEmpty($getAllIndexesSpans);
        foreach ($getAllIndexesSpans as $span) {
            $this->assertHttpOperationSpan($span, "manager_query_get_all_indexes", "query", $watchIndexesSpan, self::env()->bucketName(), "_default", "_default");
        }
        $this->assertOperationMetrics(count($getAllIndexesSpans), "manager_query_get_all_indexes", "query", self::env()->bucketName(), "_default", "_default");
    }

    public function testGetAllUsers()
    {
        $manager = $this->connectCluster()->users();
        $manager->getAllUsers(GetAllUsersOptions::build()->parentSpan($this->parentSpan()));

        $getAllUsersSpan = $this->tracer()->getSpans(null, $this->parentSpan())[0];
        $this->assertHttpOperationSpan($getAllUsersSpan, "manager_users_get_all_users", "management", $this->parentSpan());
        $this->assertOperationMetrics(1, "manager_users_get_all_users", "management");
    }

    public function testDropGroup()
    {
        $this->skipIfCaves();

        $manager = $this->connectCluster()->users();
        $name = "does-not-exist";

        $this->wrapException(
            function () use ($manager, $name) {
                $manager->dropGroup($name, DropGroupOptions::build()->parentSpan($this->parentSpan()));
            },
            GroupNotFoundException::class
        );

        $dropGroupSpan = $this->tracer()->getSpans(null, $this->parentSpan())[0];
        $this->assertHttpOperationSpan($dropGroupSpan, "manager_users_drop_group", "management", $this->parentSpan());
        $this->assertOperationMetrics(1, "manager_users_drop_group", "management", null, null, null, "GroupNotFound");
    }

    public function testGetAllSearchIndexes()
    {
        $this->skipIfCaves();

        $manager = $this->connectCluster()->searchIndexes();
        $manager->getAllIndexes(GetAllSearchIndexesOptions::build()->parentSpan($this->parentSpan()));

        $getAllIndexesSpan = $this->tracer()->getSpans(null, $this->parentSpan())[0];
        $this->assertHttpOperationSpan($getAllIndexesSpan, "manager_search_get_all_indexes", "search", $this->parentSpan());
        $this->assertOperationMetrics(1, "manager_search_get_all_indexes", "search");
    }

    public function testGetAllSearchIndexesScope()
    {
        $this->skipIfCaves();
        $this->skipIfUnsupported($this->version()->supportsScopeSearchIndexes());

        $manager = $this->openBucket(self::env()->bucketName())->defaultScope()->searchIndexes();
        $manager->getAllIndexes(GetAllSearchIndexesOptions::build()->parentSpan($this->parentSpan()));

        $getAllIndexesSpan = $this->tracer()->getSpans(null, $this->parentSpan())[0];
        $this->assertHttpOperationSpan($getAllIndexesSpan, "manager_search_get_all_indexes", "search", $this->parentSpan(), self::env()->bucketName(), "_default");
        $this->assertOperationMetrics(1, "manager_search_get_all_indexes", "search", self::env()->bucketName(), "_default");
    }
}
