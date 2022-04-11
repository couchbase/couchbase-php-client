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

use Couchbase\Exception\DocumentNotFoundException;

include_once __DIR__ . "/Helpers/CouchbaseTestCase.php";

class KeyValueReplaceTest extends Helpers\CouchbaseTestCase
{
    public function testInsertFailsIfDocumentDoesNotExist()
    {
        $collection = $this->defaultCollection();
        $id = $this->uniqueId();

        $this->expectException(DocumentNotFoundException::class);
        $collection->replace($id, ["answer" => "foo"]);
    }

    public function testInsertCompletesIfDocumentExists()
    {
        $collection = $this->defaultCollection();
        $id = $this->uniqueId();

        $res = $collection->insert($id, ["answer" => 42]);
        $originalCas = $res->cas();

        $res = $collection->replace($id, ["answer" => "foo"]);
        $replacedCas = $res->cas();
        $this->assertNotEquals($originalCas, $replacedCas);

        $res = $collection->get($id);
        $this->assertEquals($replacedCas, $res->cas());
        $this->assertEquals(["answer" => "foo"], $res->content());
    }
}
