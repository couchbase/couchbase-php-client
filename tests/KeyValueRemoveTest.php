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
use Couchbase\Exception\CasMismatchException;
use Couchbase\Exception\InvalidArgumentException;
use Couchbase\Exception\DocumentNotFoundException;
use Couchbase\RemoveOptions;

include_once __DIR__ . "/Helpers/CouchbaseTestCase.php";

class KeyValueRemoveTest extends Helpers\CouchbaseTestCase
{
    public function testRemoveThrowsCasMismatchForWrongCas()
    {
        $collection = $this->defaultCollection();
        $id = $this->uniqueId();

        $collection->upsert($id, ["answer" => 42]);

        $this->expectException(CasMismatchException::class);
        $collection->remove($id, RemoveOptions::build()->cas("deadbeef"));
    }

    public function testRemoveThrowsCasMismatchForWrongCasGarbageSuffix()
    {
        $collection = $this->defaultCollection();
        $id = $this->uniqueId();

        $res = $collection->upsert($id, ["answer" => 42]);

        $this->expectException(InvalidArgumentException::class);
        $collection->remove($id, RemoveOptions::build()->cas($res->cas() . "-invalid"));
    }

    public function testRemoveThrowsCasMismatchForWrongCasGarbagePrefix()
    {
        $collection = $this->defaultCollection();
        $id = $this->uniqueId();

        $res = $collection->upsert($id, ["answer" => 42]);

        $this->expectException(InvalidArgumentException::class);
        $collection->remove($id, RemoveOptions::build()->cas("invalid-" . $res->cas()));
    }

    public function testRemoveThrowsDocumentNotFoundForUnknownId()
    {
        $collection = $this->defaultCollection();

        $this->expectException(DocumentNotFoundException::class);
        $collection->remove($this->uniqueId());
    }

    public function testRemoveChecksCas()
    {
        $collection = $this->defaultCollection();
        $id = $this->uniqueId();

        $res = $collection->upsert($id, ["answer" => 42]);
        $originalCas = $res->cas();
        $this->assertNotNull($originalCas);

        $this->fixCavesTimeResolutionOnWindows();
        $res = $collection->remove($id, RemoveOptions::build()->cas($originalCas));
        $this->assertNotEquals($originalCas, $res->cas());
    }

    public function testRemoveDurabilityMajority()
    {
        $this->skipIfUnsupported($this->version()->supportsEnhancedDurability());

        $key = $this->uniqueId("remove-durability-majority");
        $collection = $this->defaultCollection();
        $collection->upsert($key, ["answer" => 42]);
        $opts = RemoveOptions::build()->durabilityLevel(DurabilityLevel::MAJORITY);
        $res = $collection->remove($key, $opts);
        $this->assertNotNull($res->cas());
    }

    public function testRemoveDurabilityMajorityAndPersist()
    {
        $this->skipIfUnsupported($this->version()->supportsEnhancedDurability());

        $key = $this->uniqueId("remove-durability-majority-and-persist");
        $collection = $this->defaultCollection();
        $collection->upsert($key, ["answer" => 42]);
        $opts = RemoveOptions::build()->durabilityLevel(DurabilityLevel::MAJORITY_AND_PERSIST_TO_ACTIVE);
        $res = $collection->remove($key, $opts);
        $this->assertNotNull($res->cas());
    }

    public function testRemoveDurabilityPersistToMajority()
    {
        $this->skipIfUnsupported($this->version()->supportsEnhancedDurability());

        $key = $this->uniqueId("remove-durability-persist-majority");
        $collection = $this->defaultCollection();
        $collection->upsert($key, ["answer" => 42]);
        $opts = RemoveOptions::build()->durabilityLevel(DurabilityLevel::PERSIST_TO_MAJORITY);
        $res = $collection->remove($key, $opts);
        $this->assertNotNull($res->cas());
    }
}
