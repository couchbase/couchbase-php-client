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

use Couchbase\DurabilityLevel;
use Couchbase\Exception\DocumentNotFoundException;
use Couchbase\ReplaceOptions;

include_once __DIR__ . "/Helpers/CouchbaseTestCase.php";

class KeyValueReplaceTest extends Helpers\CouchbaseTestCase
{
    public function testReplaceFailsIfDocumentDoesNotExist()
    {
        $collection = $this->defaultCollection();
        $id = $this->uniqueId();

        $this->expectException(DocumentNotFoundException::class);
        $collection->replace($id, ["answer" => "foo"]);
    }

    public function testReplaceCompletesIfDocumentExists()
    {
        $collection = $this->defaultCollection();
        $id = $this->uniqueId();

        $res = $collection->insert($id, ["answer" => 42]);
        $originalCas = $res->cas();

        $this->fixCavesTimeResolutionOnWindows();
        $res = $collection->replace($id, ["answer" => "foo"]);
        $replacedCas = $res->cas();
        $this->assertNotEquals($originalCas, $replacedCas);

        $res = $collection->get($id);
        $this->assertEquals($replacedCas, $res->cas());
        $this->assertEquals(["answer" => "foo"], $res->content());
    }

    public function testReplaceDurabilityMajority()
    {
        $this->skipIfUnsupported($this->version()->supportsEnhancedDurability());

        $key = $this->uniqueId("replace-durability-majority");
        $collection = $this->defaultCollection();
        $collection->upsert($key, ["answer" => 42]);
        $opts = ReplaceOptions::build()->durabilityLevel(DurabilityLevel::MAJORITY);
        $res = $collection->replace($key, ["answer" => 42], $opts);
        $this->assertNotNull($res->cas());
    }

    public function testReplaceDurabilityMajorityAndPersist()
    {
        $this->skipIfUnsupported($this->version()->supportsEnhancedDurability());

        $key = $this->uniqueId("replace-durability-majority-and-persist");
        $collection = $this->defaultCollection();
        $collection->upsert($key, ["answer" => 42]);
        $opts = ReplaceOptions::build()->durabilityLevel(DurabilityLevel::MAJORITY_AND_PERSIST_TO_ACTIVE);
        $res = $collection->replace($key, ["answer" => 45], $opts);
        $this->assertNotNull($res->cas());
    }

    public function testReplaceDurabilityPersistToMajority()
    {
        $this->skipIfUnsupported($this->version()->supportsEnhancedDurability());

        $key = $this->uniqueId("replace-durability-persist-majority");
        $collection = $this->defaultCollection();
        $collection->upsert($key, ["answer" => 42]);
        $opts = ReplaceOptions::build()->durabilityLevel(DurabilityLevel::PERSIST_TO_MAJORITY);
        $res = $collection->replace($key, ["answer" => 42], $opts);
        $this->assertNotNull($res->cas());
    }
}
