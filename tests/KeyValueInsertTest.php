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

use Couchbase\Exception\DocumentExistsException;
use Couchbase\GetOptions;
use Couchbase\InsertOptions;

include_once __DIR__ . "/Helpers/CouchbaseTestCase.php";

class KeyValueInsertTest extends Helpers\CouchbaseTestCase
{
    public function testInsertFailsIfDocumentExistsAlready()
    {
        $collection = $this->defaultCollection();
        $id = $this->uniqueId();

        $collection->insert($id, ["answer" => 42]);

        $this->expectException(DocumentExistsException::class);
        $collection->insert($id, ["answer" => "foo"]);
    }

    public function testCanInsertWithExpiry()
    {
        $expiry = 300;

        $options = new InsertOptions();
        $expiryDate = (new \DateTimeImmutable())->modify('+' . $expiry . ' seconds');
        $options->expiry($expiryDate);

        $collection = $this->defaultCollection();
        $id = $this->uniqueId();

        $collection->insert($id, ["answer" => 42], $options);

        $opts = (GetOptions::build())->withExpiry(true);
        $res = $collection->get($id, $opts);
        $this->assertGreaterThan((new DateTime())->getTimestamp(), $res->expiryTime()->getTimestamp());
    }
}
