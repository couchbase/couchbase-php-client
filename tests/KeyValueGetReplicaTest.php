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

use Couchbase\Exception\DocumentIrretrievableException;
use Couchbase\Exception\DocumentNotFoundException;
use Couchbase\Exception\DocumentNotFoundOnReplicaException;
use Couchbase\Exception\InvalidArgumentException;
use Couchbase\Exception\ReplicaIndexOutOfBoundsException;
use Couchbase\GetAllReplicasOptions;
use Couchbase\GetAnyReplicaOptions;
use Couchbase\GetReplicaStrategy;
use Couchbase\GetReplicaStrategyFromIndexOptions;
use Couchbase\ReadPreference;
use Couchbase\ReplicaIndex;
use Couchbase\UpsertOptions;
use Couchbase\DurabilityLevel;

include_once __DIR__ . "/Helpers/CouchbaseTestCase.php";

class KeyValueGetReplicaTest extends Helpers\CouchbaseTestCase
{
    public function testGetAnyReplicaReturnsCorrectValue()
    {
        $this->skipIfReplicasAreNotConfigured();

        $id = $this->uniqueId();
        $collection = $this->defaultCollection();
        $res = $collection->upsert($id, ["answer" => 42]);
        $cas = $res->cas();
        $this->assertNotNull($cas);
        $res = $collection->getAnyReplica($id);
        $this->assertEquals(["answer" => 42], $res->content());
    }

    public function testGetAllReplicasReturnCorrectValue()
    {
        $this->skipIfReplicasAreNotConfigured();

        $id = $this->uniqueId();
        $collection = $this->defaultCollection();
        $opts = UpsertOptions::build()->durabilityLevel(DurabilityLevel::MAJORITY_AND_PERSIST_TO_ACTIVE);
        $res = $collection->upsert($id, ["answer" => 42], $opts);
        $cas = $res->cas();
        $this->assertNotNull($cas);
        sleep(1);
        $results = $collection->getAllReplicas($id);
        $this->assertGreaterThanOrEqual(1, count($results));
        $seenActiveVersion = false;
        foreach ($results as $res) {
            $this->assertEquals(["answer" => 42], $res->content());
            if (!$res->isReplica()) {
                $seenActiveVersion = true;
            }
        }
        $this->assertTrue($seenActiveVersion);
    }

    public function testGetReplicaReturnsCorrectValue()
    {
        $this->skipIfProtostellar();
        $this->skipIfReplicasAreNotConfigured();

        $id = $this->uniqueId();
        $collection = $this->defaultCollection();
        $opts = UpsertOptions::build()->durabilityLevel(DurabilityLevel::MAJORITY_AND_PERSIST_TO_ACTIVE);
        $res = $collection->upsert($id, ["answer" => 42], $opts);
        $cas = $res->cas();
        $this->assertNotNull($cas);
        sleep(1);
        $res = $collection->getReplica($id, GetReplicaStrategy::fromIndex(ReplicaIndex::FIRST));
        $this->assertEquals(["answer" => 42], $res->content());
        $this->assertTrue($res->isReplica());
    }

    public function testGetReplicaWithWrapReturnsCorrectValue()
    {
        $this->skipIfProtostellar();
        $this->skipIfReplicasAreNotConfigured();

        $id = $this->uniqueId();
        $collection = $this->defaultCollection();
        $opts = UpsertOptions::build()->durabilityLevel(DurabilityLevel::MAJORITY_AND_PERSIST_TO_ACTIVE);
        $res = $collection->upsert($id, ["answer" => 42], $opts);
        $cas = $res->cas();
        $this->assertNotNull($cas);
        sleep(1);
        // On a bucket with fewer than 3 replicas, wrap resolves THIRD to a replica the bucket has.
        $strategy = GetReplicaStrategy::fromIndex(
            ReplicaIndex::THIRD,
            GetReplicaStrategyFromIndexOptions::build()->wrap(true)
        );
        $res = $collection->getReplica($id, $strategy);
        $this->assertEquals(["answer" => 42], $res->content());
        $this->assertTrue($res->isReplica());
    }

    public function testGetReplicaThrowsDocumentNotFoundOnReplicaExceptionForMissingId()
    {
        $this->skipIfProtostellar();
        $this->skipIfReplicasAreNotConfigured();

        $id = $this->uniqueId();
        $collection = $this->defaultCollection();
        $this->expectException(DocumentNotFoundOnReplicaException::class);
        $collection->getReplica($id, GetReplicaStrategy::fromIndex(ReplicaIndex::FIRST));
    }

    public function testGetReplicaThrowsReplicaIndexOutOfBoundsExceptionForIndexBeyondReplicaCount()
    {
        $this->skipIfProtostellar();
        $this->skipIfCaves();

        $bucketName = $this->env()->bucketName();
        $numReplicas = $this->connectCluster()->buckets()->getBucket($bucketName)->numReplicas() ?? 0;
        if ($numReplicas > ReplicaIndex::THIRD) {
            $this->markTestSkipped("bucket $bucketName has $numReplicas replicas, so ReplicaIndex::THIRD is in bounds");
        }

        $id = $this->uniqueId();
        $collection = $this->defaultCollection();
        $collection->upsert($id, ["answer" => 42]);
        $this->expectException(ReplicaIndexOutOfBoundsException::class);
        $collection->getReplica($id, GetReplicaStrategy::fromIndex(ReplicaIndex::THIRD));
    }

    public function testGetReplicaStrategyRejectsIndexBelowFirst()
    {
        $this->expectException(InvalidArgumentException::class);
        GetReplicaStrategy::fromIndex(ReplicaIndex::FIRST - 1);
    }

    public function testGetReplicaStrategyRejectsIndexBeyondThird()
    {
        $this->expectException(InvalidArgumentException::class);
        GetReplicaStrategy::fromIndex(ReplicaIndex::THIRD + 1);
    }

    public function testGetAllReplicasThrowsDocumentNotFoundExceptionForMissingId()
    {
        $this->skipIfReplicasAreNotConfigured();

        $id = $this->uniqueId();
        $collection = $this->defaultCollection();
        $this->expectException(DocumentNotFoundException::class);
        $results = $collection->getAllReplicas($id);
    }

    public function testGetAnyReplicaThrowsIrretrievableExceptionForMissingId()
    {
        $this->skipIfReplicasAreNotConfigured();

        $id = $this->uniqueId();
        $collection = $this->defaultCollection();
        $this->expectException(DocumentIrretrievableException::class);
        $collection->getAnyReplica($id);
    }

    public function testGetAnyReplicaWithPreferredServerGroup()
    {
        $this->skipIfReplicasAreNotConfigured();
        $this->skipIfUnsupported($this->version()->supportsServerGroupReplicaReads());

        $id = $this->uniqueId();
        $collection = $this->defaultCollection();
        $collection->upsert($id, ["answer" => 42]);
        // The cluster here does not have the selected server group configured
        $this->expectException(DocumentIrretrievableException::class);
        $opts = GetAnyReplicaOptions::build()->readPreference(ReadPreference::SELECTED_SERVER_GROUP);
        $collection->getAnyReplica($id, $opts);
    }

    public function testGetAllReplicasWithPreferredServerGroup()
    {
        $this->skipIfReplicasAreNotConfigured();
        $this->skipIfUnsupported($this->version()->supportsServerGroupReplicaReads());

        $id = $this->uniqueId();
        $collection = $this->defaultCollection();
        $collection->upsert($id, ["answer" => 42]);
        // The cluster here does not have the selected server group configured
        $this->expectException(DocumentIrretrievableException::class);
        $opts = GetAllReplicasOptions::build()->readPreference(ReadPreference::SELECTED_SERVER_GROUP);
        $collection->getAllReplicas($id, $opts);
    }
}
